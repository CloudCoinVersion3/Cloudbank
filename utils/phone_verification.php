<?php
/**
 * Phone Verification Utilities
 * 
 * Contains functions for Twilio integration and phone verification
 */

// Load Twilio SDK
$vendor_path = __DIR__ . '/../twilio/vendor/autoload.php';
if (file_exists($vendor_path)) {
    require_once $vendor_path;
}

use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;

/**
 * Send verification code to a phone number
 * 
 * @param string $phone_number Full phone number with country code (e.g., +1234567890)
 * @return array Result with success status and message
 */
function send_verification_code($phone_number) {
    try {
        // Get Twilio configuration
        $account_sid = get_twilio_config('account_sid');
        $auth_token = get_twilio_config('auth_token');
        $verify_service_sid = get_twilio_config('verify_service_sid');
        
        // Validate Twilio configuration
        if (empty($account_sid) || empty($auth_token) || empty($verify_service_sid)) {
            return [
                'success' => false,
                'message' => 'Twilio configuration not properly loaded'
            ];
        }
        
        // Check if Twilio SDK is available
        if (!class_exists('Twilio\Rest\Client')) {
            return [
                'success' => false,
                'message' => 'Twilio SDK not properly installed'
            ];
        }
        
        // Initialize Twilio client
        $client = new Client($account_sid, $auth_token);
        
        // Send verification
        $verification = $client->verify->v2->services($verify_service_sid)
            ->verifications
            ->create($phone_number, 'sms');
        
        // Check verification status
        if ($verification->status === 'pending') {
            return [
                'success' => true,
                'message' => 'Verification code sent successfully',
                'verification_sid' => $verification->sid
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send verification code. Status: ' . $verification->status
            ];
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
        
        return [
            'success' => false,
            'message' => $message
        ];
        
    } catch (Exception $e) {
        error_log('Phone verification error: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An error occurred while sending verification code'
        ];
    }
}

/**
 * Verify a code against a phone number
 * 
 * @param string $phone_number Full phone number with country code
 * @param string $code 6-digit verification code
 * @return array Result with success status and message
 */
function verify_code($phone_number, $code) {
    try {
        // Get Twilio configuration
        $account_sid = get_twilio_config('account_sid');
        $auth_token = get_twilio_config('auth_token');
        $verify_service_sid = get_twilio_config('verify_service_sid');
        
        // Validate Twilio configuration
        if (empty($account_sid) || empty($auth_token) || empty($verify_service_sid)) {
            return [
                'success' => false,
                'message' => 'Service configuration error'
            ];
        }
        
        // Validate code format
        if (!preg_match('/^\d{6}$/', $code)) {
            return [
                'success' => false,
                'message' => 'Please enter a valid 6-digit verification code'
            ];
        }
        
        // Initialize Twilio client
        $client = new Client($account_sid, $auth_token);
        
        // Verify code
        $verification_check = $client->verify->v2->services($verify_service_sid)
            ->verificationChecks
            ->create([
                'to' => $phone_number,
                'code' => $code
            ]);
        
        if ($verification_check->status === 'approved') {
            return [
                'success' => true,
                'message' => 'Phone number verified successfully!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid verification code. Please try again.'
            ];
        }
        
    } catch (RestException $e) {
        // Handle Twilio-specific errors
        $errorCode = $e->getCode();
        
        $errorMessages = [
            60601 => 'Invalid verification code',
            60603 => 'Maximum verification attempts reached'
        ];
        
        $message = $errorMessages[$errorCode] ?? 'Error verifying code. Please try again.';
        
        return [
            'success' => false,
            'message' => $message
        ];
        
    } catch (Exception $e) {
        error_log('Code verification error: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An unexpected error occurred while verifying code'
        ];
    }
}

/**
 * Format phone number for Twilio (ensure it starts with +)
 * 
 * @param string $country_code Country code (e.g., "1")
 * @param string $phone_number Phone number without country code
 * @return string Formatted phone number (e.g., "+1234567890")
 */
function format_phone_number($country_code, $phone_number) {
    // Remove any non-numeric characters
    $country_code = preg_replace('/[^0-9]/', '', $country_code);
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    
    // Ensure phone number starts with +
    return '+' . $country_code . $phone_number;
}

/**
 * Validate phone number format
 * 
 * @param string $phone_number Full phone number with country code
 * @return bool True if valid format
 */
function validate_phone_format($phone_number) {
    // Should start with + followed by 7-15 digits
    return preg_match('/^\+[1-9]\d{6,14}$/', $phone_number);
}