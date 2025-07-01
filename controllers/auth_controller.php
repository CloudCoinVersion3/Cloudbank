<?php
/**
 * Authentication Controller
 * 
 * Handles phone verification, code verification, and logout functionality
 */

// Load required dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/csrf_functions.php';
require_once __DIR__ . '/../utils/phone_verification.php';

/**
 * Handle phone verification request
 */
function handle_phone_verification() {
    $response = [
        'success' => false,
        'message' => ''
    ];
    
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $response['message'] = 'Invalid security token';
        return $response;
    }
    
    // Validate phone number
    $phone = $_POST['phone'] ?? '';
    if (empty($phone)) {
        $response['message'] = 'Phone number is required';
        return $response;
    }
    
    // Sanitize and validate phone number
    $phone = htmlspecialchars(trim($phone), ENT_QUOTES, 'UTF-8');
    
    // Validate phone format (should include country code)
    if (!validate_phone_format($phone)) {
        $response['message'] = 'Please enter a valid phone number with country code';
        return $response;
    }
    
    try {
        // Send verification code
        $verification_result = send_verification_code($phone);
        
        if ($verification_result['success']) {
            // Store phone in session for code verification
            $_SESSION['phone_number'] = $phone;
            $_SESSION['verification_started'] = time();
            
            // Store verification SID if provided
            if (isset($verification_result['verification_sid'])) {
                $_SESSION['verification_sid'] = $verification_result['verification_sid'];
            }
            
            $response['success'] = true;
            $response['message'] = $verification_result['message'];
            $response['next_step'] = 'code_verification';
        } else {
            $response['message'] = $verification_result['message'];
        }
    } catch (Exception $e) {
        $response['message'] = 'An error occurred while sending verification code';
        error_log('Phone verification error: ' . $e->getMessage());
    }
    
    return $response;
}

/**
 * Handle code verification request
 */
function handle_code_verification() {
    $response = [
        'success' => false,
        'message' => ''
    ];
    
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $response['message'] = 'Invalid security token';
        return $response;
    }
    
    // Check if verification was started
    if (!isset($_SESSION['phone_number']) || !isset($_SESSION['verification_started'])) {
        $response['message'] = 'No verification in progress. Please start over.';
        return $response;
    }
    
    // Check if verification has expired (15 minutes)
    if ((time() - $_SESSION['verification_started']) > 900) {
        unset($_SESSION['phone_number'], $_SESSION['verification_started']);
        $response['message'] = 'Verification code expired. Please start over.';
        return $response;
    }
    
    $phone = $_SESSION['phone_number'];
    $code = $_POST['verification_code'] ?? '';
    
    if (empty($code)) {
        $response['message'] = 'Verification code is required';
        return $response;
    }
    
    // Validate code format (6 digits)
    if (!preg_match('/^\d{6}$/', $code)) {
        $response['message'] = 'Please enter a valid 6-digit code';
        return $response;
    }
    
    try {
        // Verify code using phone verification utility
        $verification_result = verify_code($phone, $code);
        
        if ($verification_result['success']) {
            // Set user as authenticated
            $_SESSION['phone_verified'] = true;
            $_SESSION['verified_phone'] = $phone;
            $_SESSION['login_time'] = time();
            
            // Clean up verification session data
            unset($_SESSION['verification_started'], $_SESSION['verification_sid']);
            
            $response['success'] = true;
            $response['message'] = $verification_result['message'];
            $response['redirect'] = base_url('index.php?page=balance');
        } else {
            $response['message'] = $verification_result['message'];
        }
    } catch (Exception $e) {
        $response['message'] = 'An error occurred while verifying code';
        error_log('Code verification error: ' . $e->getMessage());
    }
    
    return $response;
}

/**
 * Handle logout request
 */
function handle_logout() {
    // Clear all session data
    session_destroy();
    
    // Start a new session for the redirect message
    session_start();
    $_SESSION['message'] = 'You have been logged out successfully.';
    
    // Redirect to home page
    header('Location: ' . base_url('index.php'));
    exit;
}

/**
 * Resend verification code
 */
function handle_resend_code() {
    $response = [
        'success' => false,
        'message' => ''
    ];
    
    // Check if verification was started
    if (!isset($_SESSION['phone_number'])) {
        $response['message'] = 'No verification in progress. Please start over.';
        return $response;
    }
    
    $phone = $_SESSION['phone_number'];
    
    try {
        // Send new verification code
        $verification_result = send_verification_code($phone);
        
        if ($verification_result['success']) {
            // Update verification timestamp
            $_SESSION['verification_started'] = time();
            
            // Update verification SID if provided
            if (isset($verification_result['verification_sid'])) {
                $_SESSION['verification_sid'] = $verification_result['verification_sid'];
            }
            
            $response['success'] = true;
            $response['message'] = $verification_result['message'];
        } else {
            $response['message'] = $verification_result['message'];
        }
    } catch (Exception $e) {
        $response['message'] = 'An error occurred while resending code';
        error_log('Resend code error: ' . $e->getMessage());
    }
    
    return $response;
}

// =====================================
// REQUEST HANDLING LOGIC
// =====================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle different actions based on request
$action = $_REQUEST['action'] ?? '';

// Set content type for AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
}

switch ($action) {
    case 'verify_phone':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo json_encode(handle_phone_verification());
            exit;
        }
        break;
        
    case 'verify_code':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo json_encode(handle_code_verification());
            exit;
        }
        break;
        
    case 'resend_code':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo json_encode(handle_resend_code());
            exit;
        }
        break;
        
    case 'logout':
        handle_logout(); // This function handles the redirect
        break;
        
    default:
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Unknown action: ' . $action
            ]);
            exit;
        } else {
            // Redirect to home page for invalid GET requests
            header('Location: ' . base_url('index.php'));
            exit;
        }
}