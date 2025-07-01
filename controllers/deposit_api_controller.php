<?php
// File: controllers/deposit_api_controller.php

// Ensure all errors are reported for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers for streaming JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // Important for streaming progress

require_once __DIR__ . '/../models/deposit.php';
require_once __DIR__ . '/../utils/csrf_functions.php';


/**
 * Deposit Controller
 *
 * Handles both code and file-based deposit requests.
 */
class DepositController {
    private $depositModel;

    public function __construct() {
        $this->depositModel = new Deposit();
    }

    /**
     * Process a deposit from a user-provided code.
     */
    public function processCodeDeposit() {
        try {
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception("Invalid CSRF token");
            }

            if (!isset($_POST['deposit_code'])) {
                throw new Exception("Deposit code is required");
            }
            
            $phoneNumber = $_SESSION['phone_number'] ?? '';
            if (!$phoneNumber) {
                throw new Exception("User not authenticated");
            }

            $depositCode = trim($_POST['deposit_code']);
            $result = $this->depositModel->processCodeDeposit($phoneNumber, $depositCode);
            
            if (!$result) {
                 throw new Exception("Deposit failed. Please check the code and try again.");
            }

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]) . "\n";
            ob_flush();
            flush();
        }
    }

    /**
     * Process a deposit from an uploaded file.
     * This method handles file validation, initiation, and progress streaming.
     */
    public function processFileDeposit() {
        $filePath = null; // To ensure we can clean up the file in case of error
        try {
            // 1. Initial Validations (CSRF, Session, File Existence)
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid CSRF token.');
            }

            if (!isset($_SESSION['phone_number'])) {
                throw new Exception('User not authenticated. Please log in again.');
            }

            if (!isset($_FILES['deposit_file']) || $_FILES['deposit_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception($this->getFileUploadError($_FILES['deposit_file']['error'] ?? UPLOAD_ERR_NO_FILE));
            }

            $uploadedFile = $_FILES['deposit_file'];
            $phoneNumber = $_SESSION['phone_number'];
            
            // 2. File Property Validations (Size, Type)
            $this->validateFileProperties($uploadedFile);

            // 3. Move file to a secure, temporary location
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = uniqid('upload_', true) . '_' . basename($uploadedFile['name']);
            $filePath = $uploadDir . $newFileName;

            if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
                throw new Exception('Failed to process uploaded file. Please try again.');
            }
            chmod($filePath, 0644);


            // 4. Start the Deposit Task via the Model
            echo json_encode(['status' => 'running', 'progress' => 5, 'message' => 'File uploaded, starting deposit...']) . "\n";
            ob_flush();
            flush();
            
            $taskId = $this->depositModel->startFileDepositTask($phoneNumber, $uploadedFile, $filePath);

            // 5. Monitor the Task Progress
            $finalData = $this->depositModel->monitorTask($taskId);

            echo json_encode(['status' => 'completed', 'progress' => 100, 'data' => $finalData]) . "\n";
            ob_flush();
            flush();

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]) . "\n";
            ob_flush();
            flush();
        } finally {
            // 6. Cleanup: always delete the uploaded file
            if ($filePath && file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Validates the properties of the uploaded file.
     */
    private function validateFileProperties($file) {
        $maxSize = 100 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds maximum limit of 100MB.');
        }

        $allowedExtensions = ['stack', 'zip', 'png', 'bin'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions));
        }
    }

    /**
     * Converts a file upload error code into a human-readable string.
     */
    private function getFileUploadError($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE: return 'File size exceeds server limit.';
            case UPLOAD_ERR_FORM_SIZE: return 'File size exceeds form limit.';
            case UPLOAD_ERR_PARTIAL: return 'File was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE: return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR: return 'Server is missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE: return 'Server failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION: return 'A server extension stopped the file upload.';
            default: return 'An unknown error occurred during file upload.';
        }
    }
}

// --- Request Router ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$controller = new DepositController();

if (isset($_FILES['deposit_file'])) {
    $controller->processFileDeposit();
} elseif (isset($_POST['deposit_code'])) {
    $controller->processCodeDeposit();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid deposit request. No file or code provided.']);
}

?>
