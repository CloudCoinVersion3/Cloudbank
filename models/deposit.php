<?php
// File: models/deposit.php

require_once __DIR__ . '/CloudBankAPI.php';
// It's good practice to also include the WalletService here as it's related to deposits
require_once __DIR__ . '/WalletService.php';

/**
 * Deposit Model
 *
 * Handles the business logic for all types of deposits.
 */
class Deposit {
    private $api;
    private $walletService;

    public function __construct() {
        $this->api = new CloudBankAPI();
        // Instantiate WalletService to handle code-based deposits
        $this->walletService = new WalletService();
    }

    /**
     * Processes a deposit made with a deposit code by delegating to the WalletService.
     *
     * @param string $phoneNumber The user's phone number.
     * @param string $depositCode The deposit code from the user.
     * @return mixed The result from the WalletService.
     */
    public function processCodeDeposit($phoneNumber, $depositCode) {
        $error = ''; // WalletService expects an error variable by reference
        // The WalletService::deposit method is specialized for this and handles the full async flow.
        return $this->walletService->deposit($phoneNumber, $depositCode, $error);
    }

    /**
     * Initiates a file deposit task. It validates the file and starts the API task.
     * Note: This method does not wait for the task to complete.
     *
     * @param string $phoneNumber The user's phone number.
     * @param array  $uploadedFile The $_FILES['...'] array.
     * @param string $filePath The destination path where the controller moved the file.
     * @param string $tag A tag for the transaction.
     * @return string The Task ID for the initiated import process.
     * @throws Exception If validation or the API call fails.
     */
    public function startFileDepositTask($phoneNumber, $uploadedFile, $filePath, $tag = 'file-deposit') {
        // 1. Check for duplicate file using your existing logic
        $fileHash = hash_file('sha256', $filePath);
        $fileName = $uploadedFile['name'];
        $duplicateCheck = $this->checkDuplicateFile($fileHash, $fileName);
        if ($duplicateCheck['isDuplicate']) {
            throw new Exception($duplicateCheck['message']);
        }

        // 2. Prepare data for the API
        $walletName = CloudBankAPI::normalizePhoneNumber($phoneNumber);
        $this->api->logData("Initiating file import for wallet: $walletName, file: $filePath");
        $postData = [
            'name' => $walletName,
            'items' => [['type' => 'file', 'data' => $filePath]],
            'tag' => $tag
        ];

        // 3. Make the initial API call to start the task
        $initialResponse = $this->api->callMethod("import", "POST", $postData, true);

        // 4. Error handling
        if ($this->api->isApiError($initialResponse) || !isset($initialResponse->payload->id)) {
            $errorMessage = $initialResponse->payload->message ?? 'Failed to start file import task. The API did not return a task ID.';
            $this->api->logError("API Error on starting file deposit: " . $errorMessage);
            throw new Exception($errorMessage);
        }

        $taskId = $initialResponse->payload->id;
        $this->api->logData("File import task started successfully. Task ID: $taskId");
        return $taskId;
    }

    /**
     * Monitors the progress of an asynchronous task by its ID.
     * This method will stream progress to the client via the controller.
     *
     * @param string $taskId The task ID to monitor.
     * @return stdClass The final data from the completed task.
     * @throws Exception If the task fails, times out, or if status cannot be retrieved.
     */
    public function monitorTask($taskId) {
        // This directly uses the powerful monitoring method from your CloudBankAPI
        return $this->api->monitorTaskWithProgress($taskId);
    }
    
    /**
     * Checks if a file has been uploaded recently to prevent duplicates.
     * (This is your original, effective function).
     */
    private function checkDuplicateFile($fileHash, $fileName) {
        $duplicateCheckFile = __DIR__ . '/../logs/file_uploads.json';
        $recentUploads = [];

        if (file_exists($duplicateCheckFile)) {
            $recentUploads = json_decode(file_get_contents($duplicateCheckFile), true) ?? [];
        }

        // Clean up old entries (older than 1 hour)
        $recentUploads = array_filter($recentUploads, function($data) {
            return (time() - ($data['timestamp'] ?? 0)) < 3600;
        });

        // Check for duplicate
        if (isset($recentUploads[$fileHash])) {
            $timeDiff = time() - $recentUploads[$fileHash]['timestamp'];
            $minutesAgo = round($timeDiff / 60);
            return [
                'isDuplicate' => true,
                'message' => "This file was already uploaded $minutesAgo minutes ago. Please wait at least an hour before uploading the same file again."
            ];
        }

        // Add current upload to recent uploads
        $recentUploads[$fileHash] = [
            'name' => $fileName,
            'timestamp' => time()
        ];
        
        $logDir = dirname($duplicateCheckFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        file_put_contents($duplicateCheckFile, json_encode($recentUploads));

        return ['isDuplicate' => false];
    }
}
?>
