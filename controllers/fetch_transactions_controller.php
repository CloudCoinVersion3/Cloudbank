<?php
/**
 * Fetch Transactions API Controller
 * Handles asynchronous requests for transaction history.
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Errors are logged and returned as JSON

require_once __DIR__ . '/../models/transactions.php';
require_once __DIR__ . '/../utils/csrf_functions.php';

header('Content-Type: application/json');

/**
 * Sends a JSON error response and terminates the script.
 * @param string $message The error message.
 * @param int $code The HTTP response code.
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'status' => 'error',
        'error' => $message
    ]);
    exit;
}

try {
    // Security: Check for session and CSRF token
    if (!isset($_SESSION['phone_verified']) || $_SESSION['phone_verified'] !== true) {
        sendError('Unauthorized access.', 403);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        sendError('Invalid request or CSRF token.', 403);
    }

    if (empty($_SESSION['phone_number'])) {
        sendError('User session is invalid.', 400);
    }

    $phoneNumber = $_SESSION['phone_number'];

    // Use the Transactions model to get the data
    $transactionModel = new Transactions();
    $processedTransactions = $transactionModel->getProcessedTransactions($phoneNumber);

    if ($processedTransactions === null) {
        sendError('Could not retrieve transaction history.', 500);
    }

    // Send successful response
    echo json_encode([
        'status' => 'success',
        'data' => $processedTransactions
    ]);

} catch (Exception $e) {
    error_log("Error in fetch_transactions_controller.php: " . $e->getMessage());
    sendError('An internal server error occurred. Please try again later.', 500);
}