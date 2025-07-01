<?php
/**
 * CSRF Protection Functions
 * 
 * Simple CSRF token generation and validation
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a CSRF token
 * 
 * @return string Generated CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if token is valid
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Alternative function name for compatibility
 * 
 * @param string $token Token to validate
 * @return bool True if token is valid
 */
function validate_csrf_token($token) {
    return validateCSRFToken($token);
}