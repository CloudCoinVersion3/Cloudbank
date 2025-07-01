<?php
/**
 * Phone Verification Controller
 * 
 * Handles phone verification requests 
 */

// Clean output buffer and set headers first
ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Disable error display for clean JSON output
ini_set('display_errors', 0);
error_reporting(0);

// Load Twilio SDK first (before any use statements) - use correct path
$vendor_path = __DIR__ . '/../twilio/vendor/autoload.php';
if (file_exists($vendor_path)) {
    require_once $vendor_path;
}

// Import Twilio classes at the top level
use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;

// Function to send JSON response and exit
function sendResponse($success, $message = '') {
    ob_clean(); // Clean any previous output
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse(false, 'Invalid request method');
    }
    
    // Get and validate input parameters
    $country_code = $_GET['country_code'] ?? '';
    $phone_number = $_GET['phone_number'] ?? '';
    
    if (empty($country_code) || empty($phone_number)) {
        sendResponse(false, 'Missing phone number parameters');
    }
    
    // Clean input (remove non-numeric characters)
    $country_code = preg_replace('/[^0-9]/', '', $country_code);
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    
    if (empty($country_code) || empty($phone_number)) {
        sendResponse(false, 'Invalid phone number format');
    }
    
    // Include configuration using correct path
    require_once __DIR__ . '/../config/config.php';
    
    // Check if Twilio credentials are loaded 
    if (empty($account_sid) || empty($auth_token) || empty($verify_service_sid)) {
        sendResponse(false, 'Twilio configuration not properly loaded');
    }
    
    // Check if Twilio SDK was loaded
    if (!file_exists($vendor_path)) {
        sendResponse(false, 'Twilio SDK not found at: ' . $vendor_path);
    }
    
    // Check if Twilio classes are available
    if (!class_exists('Twilio\Rest\Client')) {
        sendResponse(false, 'Twilio SDK not properly installed');
    }
    
    // Create full phone number
    $full_phone_number = '+' . $country_code . $phone_number;
    
    // Initialize Twilio client
    $client = new Client($account_sid, $auth_token);
    
    // Send verification
    $verification = $client->verify->v2->services($verify_service_sid)
        ->verifications
        ->create($full_phone_number, 'sms');
    
    // Check verification status
    if ($verification->status === 'pending') {
        // Start session and store phone number
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['phone_number'] = $full_phone_number;
        $_SESSION['verification_sid'] = $verification->sid;
        $_SESSION['verification_started'] = time();
        
        sendResponse(true, 'Verification code sent successfully');
    } else {
        sendResponse(false, 'Failed to send verification code. Status: ' . $verification->status);
    }
    
} catch (RestException $e) {
    // Handle Twilio-specific errors
    $errorCode = $e->getCode();
    
    $errorMessages = [
        60200 => 'Invalid phone number format',
        60203 => 'Phone number not valid for the selected country',
        60212 => 'Too many verification attempts. Please try again later',
        60202 => 'This phone number cannot receive SMS',
        60205 => 'Maximum send attempts reached',
        60210 => 'Invalid phone number',
        60220 => 'Invalid verification service configuration'
    ];
    
    $message = $errorMessages[$errorCode] ?? 'Verification failed: ' . $e->getMessage();
    sendResponse(false, $message);
    
} catch (Exception $e) {
    // Handle general errors
    error_log('Phone verification controller error: ' . $e->getMessage());
    sendResponse(false, 'An error occurred: ' . $e->getMessage());
    
} catch (Error $e) {
    // Handle fatal errors
    error_log('Fatal error in phone verification: ' . $e->getMessage());
    sendResponse(false, 'System error occurred');
}