<?php
/**
 * Withdraw Controller
 * Handles withdrawal requests and coordinates with the withdraw model
 */

// Set headers for a streaming JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); 

// Ensure all errors are displayed for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if it hasn't been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/withdraw.php';
require_once __DIR__ . '/../utils/csrf_functions.php';

class WithdrawController {
    
    private $withdrawModel;
    
    public function __construct() {
        $this->withdrawModel = new WithdrawModel();
    }
    
    /**
     * Handles the entire withdrawal request from validation to execution.
     */
    public function handleWithdrawal() {
        try {
            // 1. Validate request method and session state
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['withdraw'])) {
                throw new Exception('Invalid request method');
            }
            if (!isset($_SESSION['phone_verified']) || $_SESSION['phone_verified'] !== true) {
                throw new Exception('User not verified');
            }
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid request token');
            }
            
            // 2. Validate the withdrawal amount
            $amount = filter_input(INPUT_POST, 'withdraw_amount', FILTER_VALIDATE_FLOAT);
            if ($amount === false || $amount <= 0) {
                throw new Exception('Invalid amount. Please enter a positive number.');
            }
            $amount = round($amount, 2);
            
            // 3. Get phone number and execute withdrawal
            // The model will now handle the entire streaming response.
            $phoneNumber = $_SESSION['phone_number'];
            $this->withdrawModel->processWithdrawal($phoneNumber, $amount);

        } catch (Exception $e) {
            // If any validation fails, send a single JSON error response and stop.
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

// --- Entry point ---
$controller = new WithdrawController();
$controller->handleWithdrawal();