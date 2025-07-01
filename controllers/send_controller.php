<?php
// File: controllers/send_controller.php

header('Content-Type: application/json');
// For production, set display_errors to 0
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/send.php';
require_once __DIR__ . '/../utils/csrf_functions.php';

/**
 * Sends a standardized JSON response and terminates the script.
 */
function sendJsonResponse($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// --- Main Execution ---

// 1. Basic security and session validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Invalid request method.');
}
if (!isset($_SESSION['phone_verified']) || $_SESSION['phone_verified'] !== true) {
    sendJsonResponse('error', 'User not authenticated.');
}
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    sendJsonResponse('error', 'Invalid session token. Please refresh the page.');
}

// 2. Input validation
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);
$recipientNumber = $_POST['recipient_number'] ?? '';
$tag = ""; // The tag/memo is not required, so we pass an empty string.

if (!$amount || $amount <= 0) {
    sendJsonResponse('error', 'Please enter a valid amount greater than zero.');
}
if (empty($recipientNumber)) {
    sendJsonResponse('error', 'Please enter a valid recipient phone number.');
}

// 3. Process the transaction
try {
    $sendModel = new SendModel();
    $senderPhone = $_SESSION['phone_number'];

    $result = $sendModel->sendCoins($senderPhone, $amount, $recipientNumber, $tag);

    if ($result['success']) {
        sendJsonResponse('success', $result['message']);
    } else {
        sendJsonResponse('error', $result['message']);
    }

} catch (Exception $e) {
    error_log('Send Controller Error: ' . $e->getMessage());
    sendJsonResponse('error', 'A server error occurred. Please try again later.');
}
