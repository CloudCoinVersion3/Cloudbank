<?php
/**
 * Phone Verification Endpoint - Standalone
 */

// Simply redirect to the new controller with the same parameters
$query_string = $_SERVER['QUERY_STRING'];
header('Location: controllers/verify_phone_controller.php?' . $query_string);
exit;
