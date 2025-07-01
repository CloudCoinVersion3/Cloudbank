<?php
// File: controllers/fetch_balance.php

// Set headers for a clean JSON response and prevent error display in output
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// All required files for this operation
require_once __DIR__ . '/../models/WalletService.php';
require_once __DIR__ . '/../utils/csrf_functions.php';

/**
 * Sends a JSON response and exits the script.
 * @param bool $success - Whether the operation was successful.
 * @param string|null $message - An optional message.
 * @param mixed|null $data - Optional data payload.
 */
function send_json_response($success, $message = null, $data = null) {
    $response = ['success' => $success];
    if ($message !== null) {
        $response['message'] = $message;
    }
    if ($data !== null) {
        // If data is an array, merge it into the response
        if (is_array($data)) {
            $response = array_merge($response, $data);
        } else {
             $response['data'] = $data;
        }
    }
    echo json_encode($response);
    exit;
}


// --- Main Execution ---

// 1. Check for valid request and session state
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(false, 'Invalid request method.');
}

if (!isset($_SESSION['phone_verified']) || $_SESSION['phone_verified'] !== true) {
    send_json_response(false, 'User not authenticated.');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    send_json_response(false, 'Invalid session token.');
}

// 2. Try to fetch the balance
try {
    $walletService = new WalletService();
    $phoneNumber = $_SESSION['phone_number'];
    
    $balance = $walletService->getBalance($phoneNumber);

    if ($balance !== false) {
        // Success: send a valid JSON response with the balance.
        send_json_response(true, 'Balance retrieved successfully', ['balance' => number_format((float)$balance, 2, '.', '')]);
    } else {
        send_json_response(false, 'Could not retrieve balance from the service.');
    }

} catch (Exception $e) {
    // Log the actual error for debugging, but send a generic message to the client
    error_log('Fetch Balance Error: ' . $e->getMessage());
    send_json_response(false, 'A server error occurred while fetching the balance.');
}

?>
