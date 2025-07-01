<?php
/**
 * Enter Code Page - Standalone
 */

// Load configuration
require_once 'config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to index if phone number is not in session
if (!isset($_SESSION['phone_number'])) {
    header('Location: index.php?error=session_expired');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "includes/common_head_content.php"; ?>
    <title>Enter Verification Code - CloudBank</title>
</head>
<body>
    <?php include "views/partials/header.php"; ?>
    
    <div class="container">
        <main class="main-content">
            <?php include 'views/pages/enter_code_view.php'; ?>
        </main>
    </div>
</body>
</html>
