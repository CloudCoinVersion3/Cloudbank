<?php
/**
 * Main Form Navigation
 * Reusable action buttons for all authenticated pages
 */

// Ensure config is loaded if not already
if (!function_exists('is_user_authenticated')) {
    require_once __DIR__ . '/../../config/config.php';
}

// Get current page parameter and type
$page_param = $_GET['page'] ?? 'main';
$type_param = $_GET['type'] ?? '';

// Only show navigation if user is authenticated
if (!is_user_authenticated()) {
    return;
}
?>

<!-- Main Form -->
<div class="action-buttons">
    <!-- Balance Button -->
    <form action="index.php" method="get">
        <input type="hidden" name="page" value="balance">
        <button type="submit" class="button <?php echo ($page_param === 'balance') ? 'active-button' : ''; ?>">BALANCE</button>
    </form>
    
    <?php 
    // Handle deposit button logic based on current page and type
    if ($page_param === 'deposit'): 
        if ($type_param === 'code'): ?>
            <!-- On code deposit page, show file deposit option -->
            <form action="index.php" method="get">
                <input type="hidden" name="page" value="deposit">
                <input type="hidden" name="type" value="file">
                <button type="submit" class="button">DEPOSIT BY FILE</button>
            </form>
        <?php elseif ($type_param === 'file'): ?>
            <!-- On file deposit page, show code deposit option -->
            <form action="index.php" method="get">
                <input type="hidden" name="page" value="deposit">
                <input type="hidden" name="type" value="code">
                <button type="submit" class="button">DEPOSIT BY CODE</button>
            </form>
        <?php else: ?>
            <!-- On main deposit page, show active state -->
            <form action="index.php" method="get">
                <input type="hidden" name="page" value="deposit">
                <button type="submit" class="button active-button">DEPOSIT</button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <!-- Show main deposit button on other pages -->
        <form action="index.php" method="get">
            <input type="hidden" name="page" value="deposit">
            <button type="submit" class="button">DEPOSIT</button>
        </form>
    <?php endif; ?>
    
    <!-- Withdraw Button -->
    <form action="index.php" method="get">
        <input type="hidden" name="page" value="withdraw">
        <button type="submit" class="button <?php echo ($page_param === 'withdraw') ? 'active-button' : ''; ?>">WITHDRAW</button>
    </form>
    
    <!-- Send Button -->
    <form action="index.php" method="get">
        <input type="hidden" name="page" value="send">
        <button type="submit" class="button <?php echo ($page_param === 'send') ? 'active-button' : ''; ?>">SEND</button>
    </form>
    
    <!-- Transactions/Statement Button -->
    <form action="index.php" method="get">
        <input type="hidden" name="page" value="transactions">
        <button type="submit" class="button <?php echo ($page_param === 'transactions') ? 'active-button' : ''; ?>">STATEMENT</button>
    </form>
</div>

<style>
    .active-button {
        color: #4a90e2 !important;
        border: 2px solid #4a90e2 !important;
        background-color: #ffffff !important;
    }
</style>