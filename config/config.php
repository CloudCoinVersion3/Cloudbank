<?php
/**
 * CloudBank Configuration File
 * 
 * Contains all application configuration settings
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting settings
ini_set('display_errors', 0);
error_reporting(0);

// Application settings
define('APP_NAME', 'CloudBank');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/cloudbank/');

// Security settings
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour

// Initialize variables 
$account_sid = "";
$auth_token = "";
$test_auth_token = "";
$verify_service_sid = "";
$twilio_phone = "";
$my_phone = "";

try {
    // $filename = __DIR__ . '/api_keys.toml';
     $filename = '/root/api_keys.toml';
    
    if (file_exists($filename)) {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Remove comment lines and empty lines
        $clean_lines = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && $line[0] !== '#') {
                $clean_lines[] = $line;
            }
        }
        
        // Assign values 
        if (count($clean_lines) >= 6) {
            $account_sid = isset($clean_lines[0]) ? trim($clean_lines[0]) : '';
            $auth_token = isset($clean_lines[1]) ? trim($clean_lines[1]) : '';
            $test_auth_token = isset($clean_lines[2]) ? trim($clean_lines[2]) : '';
            $verify_service_sid = isset($clean_lines[3]) ? trim($clean_lines[3]) : '';
            $twilio_phone = isset($clean_lines[4]) ? trim($clean_lines[4]) : '';
            $my_phone = isset($clean_lines[5]) ? trim($clean_lines[5]) : '';
        }
    }
} catch (Exception $e) {
    error_log("Config error: " . $e->getMessage());
}

$twilio_config = [
    'account_sid' => $account_sid,
    'auth_token' => $auth_token,
    'test_auth_token' => $test_auth_token,
    'verify_service_sid' => $verify_service_sid,
    'twilio_phone' => $twilio_phone,
    'my_phone' => $my_phone
];

// Make Twilio configuration globally accessible
$GLOBALS['twilio_config'] = $twilio_config;

/**
 * Get Twilio configuration value
 * 
 * @param string $key Configuration key
 * @return string Configuration value or empty string
 */
function get_twilio_config($key) {
    global $twilio_config;
    return $twilio_config[$key] ?? '';
}

/**
 * Check if user is authenticated (phone verified)
 * 
 * @return bool True if user is authenticated
 */
function is_user_authenticated() {
    return isset($_SESSION['phone_verified']) && $_SESSION['phone_verified'] === true;
}

/**
 * Get current page name
 * 
 * @return string Current page filename
 */
function get_current_page() {
    return basename($_SERVER['PHP_SELF']);
}

/**
 * Generate base URL for the application
 * 
 * @param string $path Optional path to append
 * @return string Complete URL
 */
function base_url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . BASE_URL . ltrim($path, '/');
}

// Define constants for compatibility
define('TWILIO_ACCOUNT_SID', $account_sid);
define('TWILIO_AUTH_TOKEN', $auth_token);
define('TWILIO_VERIFY_SERVICE_SID', $verify_service_sid);
define('TWILIO_PHONE', $twilio_phone);

// Include the service factory for API functions
require_once __DIR__ . '/../utils/ServiceFactory.php';
