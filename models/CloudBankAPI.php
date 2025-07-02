<?php
/**
 * CloudBank API Core Class
 * Handles all API communications with the CloudCoin services
 */
class CloudBankAPI {
    
    private $baseUrl;
    private $logDir;
    
    /**
     * Constructor for the CloudBankAPI class.
     * Initializes the base URL for the API and ensures the log directory exists.
     */
    public function __construct() {
        $this->baseUrl = "http://localhost:8006/api/v1/";
        $this->logDir = __DIR__ . "/../logs";
        
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }
    
    /**
     * Logs a general message to the API log file.
     *
     * @param string $msg The message to log.
     */
    public function logData($msg) {
        $d = date("Y-m-d H:i:s");
        $logFile = $this->logDir . "/api.log";
        file_put_contents($logFile, "$d: $msg\n", FILE_APPEND);
    }
    
    /**
     * Logs an error message to the API log file, prefixed with "Error:".
     *
     * @param string $msg The error message to log.
     */
    public function logError($msg) {
        $this->logData("Error: $msg");
    }
    
    /**
     * Makes a call to a specific API endpoint using cURL.
     *
     * @param string $path The API endpoint path (e.g., "wallets/123").
     * @param string $method The HTTP method to use ('GET', 'POST', 'PUT').
     * @param array|null $data The data to send with the request.
     * @param bool $raw If true, returns the raw response object even on HTTP error codes.
     * @return stdClass|null The decoded JSON response object, or null on failure.
     */
    public function callMethod($path, $method = 'POST', $data = null, $raw = false) {
        $curl = curl_init();
        
        $this->logData("Calling API: $method $path " . json_encode($data));
        $url = $this->baseUrl . $path;
        
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } else if ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } else {
            if ($data) {
                $url .= "?" . http_build_query($data);
            }
        }
        
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($result === false) {
            $this->logError("cURL Error: " . curl_error($curl));
            curl_close($curl);
            return null;
        }
        
        curl_close($curl);
        
        $data = json_decode($result);
        if (!$data) {
            $this->logError("Failed to decode JSON response: " . $result);
            return null;
        }
        
        $this->logData("API Response: " . print_r($data, true));
        
        if ($httpCode >= 400 && !$raw) {
            $this->logError("API Error. HTTP Code: $httpCode, Response: " . print_r($data, true));
            return null;
        }
        
        return $data;
    }
    
    /**
     * Checks if the API response data indicates an error.
     *
     * @param stdClass|null $data The API response object.
     * @return bool True if the response is an error, false otherwise.
     */
    public function isApiError($data) {
        if (!$data || (isset($data->status) && $data->status != "success")) {
            return true;
        }
        return false;
    }
    
    /**
     * Monitors the progress of an asynchronous task and streams progress to the client.
     *
     * @param string $taskId The ID of the task to monitor.
     * @param string &$error A reference to a variable to store error messages.
     * @return stdClass|null The final data from the completed task, or null on failure/timeout.
     */
    public function monitorAsyncTask($taskId, &$error) {
        $timeout = 300;
        $startTime = time();
        
        while (true) {
            $taskResponse = $this->callMethod("tasks/" . $taskId, 'GET', [], true);
            if (!$taskResponse || !isset($taskResponse->payload)) {
                $error = "Failed to get task response";
                return null;
            }
            
            $status = $taskResponse->payload->status;
            $progress = $taskResponse->payload->progress ?? 0;
            
            echo json_encode(['status' => $status, 'progress' => $progress]) . "\n";
            ob_flush();
            flush();
            
            if ($status === "completed") {
                return $taskResponse->payload->data;
            }
            
           if ($status === "error") {
                $error = $taskResponse->payload->message ?? 'Task failed with an unknown error.';
                return null;
            }
            if (time() - $startTime > $timeout) {
                $error = "Operation timed out after " . $timeout . " seconds";
                return null;
            }
            sleep(1);
        }
    }
    
    /**
     * Calls an asynchronous API method and monitors its progress.
     * This method is designed to stream progress updates back to the client.
     *
     * @param string $path The API endpoint path for the async task.
     * @param string &$error A reference to a variable to store error messages.
     * @param string $method The HTTP method ('POST', 'PUT', etc.).
     * @param array|null $data The data to send with the request.
     * @return stdClass|null The result of the completed task, or null on failure.
     */
    public function callAsyncMethod($path, &$error, $method = 'POST', $data = null) {
        $response = $this->callMethod($path, $method, $data, true);
        if (!$response || !isset($response->payload->id)) {
            $error = "Failed to initiate async task. Response: " . json_encode($response);
            $this->logError($error);
            return null;
        }
        $taskID = $response->payload->id;
        return $this->monitorAsyncTask($taskID, $error);
    }
    
    /**
     * Calls an asynchronous API method and waits for it to complete without streaming progress.
     *
     * @param string $path The API endpoint path for the async task.
     * @param string &$error A reference to a variable to store error messages.
     * @param string $method The HTTP method ('POST', 'PUT', etc.).
     * @param array|null $data The data to send with the request.
     * @return stdClass|null The result of the completed task, or null on failure.
     */
    public function callAsyncMethodAndWait($path, &$error, $method = 'POST', $data = null) {
        $response = $this->callMethod($path, $method, $data, true);
        if (!$response || !isset($response->payload->id)) {
            $error = "Failed to initiate async task. Response: " . json_encode($response);
            $this->logError($error);
            return null;
        }
            
        $taskID = $response->payload->id;
        $timeout = 300;
        $startTime = time();
        while (true) {
            if (time() - $startTime > $timeout) {
                $error = "Operation timed out";
                return null;
            }

            $taskResponse = $this->callMethod("tasks/" . $taskID, 'GET', [], true);
            if (!$taskResponse || !isset($taskResponse->payload)) {
                $error = "Failed to get task response";
                return null;
            }

            $status = $taskResponse->payload->status;
            if ($status === "completed") {
                return $taskResponse->payload->data;
            }
            if ($status === "error") {
                $error = $taskResponse->payload->message ?? 'Task failed with an unknown error.';
                return null;
            }
            sleep(1);
        }
    }

    /**
     * Monitors a task and streams JSON progress updates to the client.
     * This method is designed for real-time feedback on the front end.
     *
     * @param string $taskId The ID of the task to monitor.
     * @return stdClass The final data from the completed task.
     * @throws Exception If the task fails or times out.
     */
    public function monitorTaskWithProgress($taskId) {
        $timeout = 300; // 5-minute timeout
        $startTime = time();

        while (true) {
            if (time() - $startTime > $timeout) {
                throw new Exception("Operation timed out after " . $timeout . " seconds");
            }

            $taskResponse = $this->callMethod("tasks/" . $taskId, 'GET', [], true);

            if (!$taskResponse || !isset($taskResponse->payload)) {
                // Wait and retry once in case of a transient network issue
                sleep(2);
                $taskResponse = $this->callMethod("tasks/" . $taskId, 'GET', [], true);
                if (!$taskResponse || !isset($taskResponse->payload)) {
                    throw new Exception("Failed to get task status. The API response was invalid.");
                }
            }
            
            $status = $taskResponse->payload->status;
            $progress = $taskResponse->payload->progress ?? 0;
            $message = $taskResponse->payload->message ?? 'Processing...';

            // Stream the current status to the client
            echo json_encode(['status' => $status, 'progress' => $progress, 'message' => $message]) . "\n";
            ob_flush();
            flush();

            if ($status === "completed") {
                // Return the final data payload on success
                return $taskResponse->payload->data;
            }
            
            if ($status === "error") {
                // Throw an exception on failure, which the controller will catch
                $errorMessage = $taskResponse->payload->message ?? 'Task failed with an unknown error.';
                throw new Exception($errorMessage);
            }

            // Wait before checking the status again
            sleep(1);
        }
    }

    /**
     * Normalizes a phone number by removing all non-numeric characters.
     *
     * @param string $phone The phone number to normalize.
     * @return string The normalized phone number.
     */
    public static function normalizePhoneNumber($phone) {
        return preg_replace('/[^0-9]/', '', $phone);
    }
    
    /**
     * Inserts a hyphen into a 7-character string (for deposit codes).
     *
     * @param string $string The input string.
     * @return string The string with a hyphen inserted, or the original string if not applicable.
     */
    public static function insertHyphen($string) {
        if (strlen($string) === 7 && strpos($string, '-') === false) {
            return substr($string, 0, 3) . '-' . substr($string, 3);
        }
        return $string;
    }
}
?>
