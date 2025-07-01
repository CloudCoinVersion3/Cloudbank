<?php
/**
 * CloudBank Main Entry Point 
 */

// Load core configuration and dependencies
require_once 'config/config.php';
require_once 'utils/csrf_functions.php';

// Get current page from URL parameter
$page = $_GET['page'] ?? 'main';
$type = $_GET['type'] ?? '';

// Validate page parameter to prevent directory traversal
$allowed_pages = ['main', 'deposit', 'withdraw', 'send', 'transactions', 'balance'];
if (!in_array($page, $allowed_pages)) {
    $page = 'main';
}

// For pages that require authentication, redirect to phone verification if not logged in
$protected_pages = ['deposit', 'withdraw', 'send', 'transactions', 'balance'];
if (in_array($page, $protected_pages) && !is_user_authenticated()) {
    $page = 'main'; // Show login/verification form
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/common_head_content.php'; ?>
    <title><?php echo APP_NAME . ($page !== 'main' ? ' - ' . ucfirst($page) : ''); ?></title>
    
    <!-- Add intl-tel-input CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
</head>
<body>
    <?php include 'views/partials/header.php'; ?>
    
    <div class="main-container">
        
       
            <?php 
                // Router logic to determine which view to display
                switch($page) {
                    case 'balance':
                        if (file_exists('views/pages/balance_view.php')) {
                            include 'views/pages/balance_view.php';
                        } else {
                            echo "<h2>Balance</h2><p>This page is under construction.</p>";
                        }
                        break;
                    case 'deposit':
                        // Handle deposit with sub-routing based on type parameter
                        switch($type) {
                            case 'code':
                                // Show code deposit form
                                if (file_exists('views/pages/deposit_code.php')) {
                                    include 'views/pages/deposit_code.php';
                                } else {
                                    echo "<h2>Deposit by Code</h2><p>This page is under construction.</p>";
                                }
                                break;
                            case 'file':
                                // Show file deposit form
                                if (file_exists('views/pages/deposit_file.php')) {
                                    include 'views/pages/deposit_file.php';
                                } else {
                                    echo "<h2>Deposit by File</h2><p>This page is under construction.</p>";
                                }
                                break;
                            default:
                                // Show deposit type selection
                                if (file_exists('views/pages/deposit_type_choose.php')) {
                                    include 'views/pages/deposit_type_choose.php';
                                } elseif (file_exists('views/pages/deposit_view.php')) {
                                    include 'views/pages/deposit_view.php';
                                } else {
                                    echo "<h2>Deposit CloudCoins</h2><p>This page is under construction.</p>";
                                }
                                break;
                        }
                        break;
                    case 'withdraw':
                        if (file_exists('views/pages/withdraw_view.php')) {
                            include 'views/pages/withdraw_view.php';
                        } else {
                            echo "<h2>Withdraw</h2><p>This page is under construction.</p>";
                        }
                        break;
                    case 'send':
                        if (file_exists('views/pages/send_view.php')) {
                            include 'views/pages/send_view.php';
                        } else {
                            echo "<h2>Send Money</h2><p>This page is under construction.</p>";
                        }
                        break;
                    case 'transactions':
                        if (file_exists('views/pages/transactions_view.php')) {
                            include 'views/pages/transactions_view.php';
                        } else {
                            echo "<h2>Transactions</h2><p>This page is under construction.</p>";
                        }
                        break;
                    default:
                        include 'views/pages/main_view.php';
                        break;
                }
            ?>

            <?php 
                // Show navigation only for authenticated users
                if (is_user_authenticated()): 
                    include './views/partials/nav.php'; 
                endif; 
            ?> 

        
        
    </div>

    <!-- Footer - only show for authenticated users -->
    <?php if (is_user_authenticated() && file_exists('views/partials/footer.php')): ?>
        <?php include 'views/partials/footer.php'; ?>
    <?php endif; ?>
</body>
</html>