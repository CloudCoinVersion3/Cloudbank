<?php
/**
 * CloudBank API Core Class
 * Handles all API communications with the CloudCoin services
 */
class CloudBankAPI {
    
    private $baseUrl;
    private $logDir;
    
    public function __construct() {
        $this->baseUrl = "http://localhost:8006/api/v1/";
        $this->logDir = __DIR__ . "/../logs";
        
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }
    
    public function logData($msg) {
        $d = date("Y-m-d H:i:s");
        $logFile = $this->logDir . "/api.log";
        file_put_contents($logFile, "$d: $msg\n", FILE_APPEND);
    }
    
    public function logError($msg) {
        $this->logData("Error: $msg");
    }
    
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
    
    public function isApiError($data) {
        if (!$data || (isset($data->status) && $data->status != "success")) {
            return true;
        }
        return false;
    }
    
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
     * This method waits for completion and returns the final result without echoing progress.
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

    public static function normalizePhoneNumber($phone) {
        return preg_replace('/[^0-9]/', '', $phone);
    }
    
    public static function insertHyphen($string) {
        if (strlen($string) === 7 && strpos($string, '-') === false) {
            return substr($string, 0, 3) . '-' . substr($string, 3);
        }
        return $string;
    }
}

?>