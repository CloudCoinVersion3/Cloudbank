<?php
/**
 * Common Head Content
 * 
 * Contains meta tags and common stylesheets used across all pages
 */

// Ensure config is loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}
?>

<!-- Meta tags -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="CloudBank - Secure digital banking with CloudCoin technology">
<meta name="keywords" content="cloudbank, cloudcoin, digital banking, secure banking">
<meta name="author" content="CloudBank">

<!-- Favicon -->
<link rel="icon" type="image/png" href="assets/images/favicon.png">

<!-- External stylesheets -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">

<!-- Application stylesheets -->
<link rel="stylesheet" href="assets/css/styles.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/verification-styles.css">

<!-- External scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>

<!-- CSRF Token for JavaScript -->
<script>
    window.CSRF_TOKEN = '<?php echo isset($_SESSION) ? (require_once __DIR__ . '/../utils/csrf_functions.php') && generateCSRFToken() : ''; ?>';
    window.BASE_URL = '<?php echo base_url(); ?>';
</script>