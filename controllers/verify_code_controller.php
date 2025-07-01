<?php
/**
 * Code Verification Endpoint 
 */

// Load Twilio SDK first
$vendor_path = __DIR__ . '/../twilio/vendor/autoload.php';
if (file_exists($vendor_path)) {
    require_once $vendor_path;
}

use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Configure PHP settings
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to send JSON response
function sendResponse(bool $success, string $message = ''): void {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    // Validate session
    if (!isset($_SESSION['phone_number'])) {
        sendResponse(false, 'Session expired. Please start over.');
    }
    
    // Check if verification has expired (15 minutes)
    if (isset($_SESSION['verification_started']) && 
        (time() - $_SESSION['verification_started']) > 900) {
        // Clear expired session data
        unset($_SESSION['phone_number'], $_SESSION['verification_started'], $_SESSION['verification_sid']);
        sendResponse(false, 'Verification code expired. Please start over.');
    }
    
    // Validate input
    $verification_code = trim($_POST['verification_code'] ?? '');
    if (!$verification_code || !preg_match('/^\d{6}$/', $verification_code)) {
        sendResponse(false, 'Please enter a valid 6-digit verification code.');
    }
    
    // Get phone number from session
    $phone_number = $_SESSION['phone_number'];
    
    // Validate Twilio configuration 
    if (empty($account_sid) || empty($auth_token) || empty($verify_service_sid)) {
        sendResponse(false, 'Service configuration error.');
    }
    
    // Check if Twilio SDK is available
    if (!class_exists('Twilio\Rest\Client')) {
        sendResponse(false, 'Twilio SDK not properly installed.');
    }
    
    // Verify the code using Twilio directly
    $client = new Client($account_sid, $auth_token);
    $verification_check = $client->verify->v2->services($verify_service_sid)
        ->verificationChecks
        ->create([
            'to' => $phone_number,
            'code' => $verification_code
        ]);

    if ($verification_check->status === 'approved') {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set user as verified
        $_SESSION['phone_verified'] = true;
        $_SESSION['verified_phone'] = $phone_number;
        $_SESSION['verification_code'] = $verification_code;
        $_SESSION['login_time'] = time();
        
        // Clean up verification session data
        unset($_SESSION['verification_started'], $_SESSION['verification_sid']);
        
        sendResponse(true, 'Phone number verified successfully!');
    } else {
        sendResponse(false, 'Invalid verification code. Please try again.');
    }
    
} catch (RestException $e) {
    error_log('Twilio error: ' . $e->getMessage());
    $errorMessages = [
        60601 => 'Invalid verification code.',
        60603 => 'Maximum verification attempts reached.',
    ];
    $message = $errorMessages[$e->getCode()] ?? 'Error verifying code. Please try again.';
    sendResponse(false, $message);
} catch (Exception $e) {
    error_log('Code verification error: ' . $e->getMessage());
    sendResponse(false, 'An unexpected error occurred.');
}
?>