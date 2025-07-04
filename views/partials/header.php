<?php
/**
 * Header Partial - Fixed Authentication
 * 
 * Contains the main header with navigation and user menu
 */

// Ensure required functions are available
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/csrf_functions.php';

// Get current page and authentication status
$current_page = get_current_page();
$is_phone_verified = is_user_authenticated();

?>

<header>
    <div class="header-container">
        <div class="logo">
            <a href="<?php echo base_url('index.php'); ?>">
                <img src="assets/imgs/cloudbank-logo.webp" alt="CloudBank Logo" loading="lazy" width="150" height="40"> 
            </a>
        </div>
        
        <div class="menu-items">
            <nav class="desktop-nav">
                <ul class="nav-links">
                    <li class="<?php echo ($current_page == 'about_us.php') ? 'active-page' : ''; ?>">
                        <a href="<?php echo base_url('about_us.php'); ?>">About</a>
                    </li>
                    <li>
                        <a href="https://cloudcoin.com/" target="_blank">Buy CloudCoin</a>
                    </li>
                    <li>
                        <a href="https://youtu.be/3KAwILniN2s" target="_blank">How it works</a>
                    </li>
                    <li>
                        <a href="https://cloudcoin.com/support" target="_blank">Support</a>
                    </li>
                </ul>
            </nav>
            
            <div class="user-menu">
                <?php if ($is_phone_verified): ?>
                    <!-- <div class="user-actions">
                        <form action="<?php echo base_url('controllers/message_controller.php'); ?>" method="post" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="send_form">
                            <button type="submit" class="menu-btn">
                                SEND MESSAGE
                            </button>
                        </form>
                        
                        <form action="<?php echo base_url('controllers/message_controller.php'); ?>" method="post" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="check_messages">
                            <button type="submit" class="menu-btn">
                                CHECK MESSAGES
                            </button>
                        </form>
                    </div> -->
                    
                    <a href="<?php echo base_url('controllers/auth_controller.php?action=logout'); ?>" class="login-btn">
                        LOG OUT
                    </a>
                <?php endif; ?>
                <!-- Don't show LOG IN button when not authenticated - let the main page handle verification -->
            </div>
            
            <div class="hamburger">
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
            </div>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu">
    <div class="close-icon">
        <div class="line"></div>
        <div class="line"></div>
    </div>
    
    <ul class="nav-links">
        <li class="<?php echo ($current_page == 'about_us.php') ? 'active-page' : ''; ?>">
            <a href="<?php echo base_url('about_us.php'); ?>">
                <span>About</span>
                <svg class="arrow-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7l7 7-7 7" />
                </svg>
            </a>
        </li>
        <li>
            <a href="https://cloudcoin.com/" target="_blank">
                <span>Buy CloudCoin</span>
                <svg class="arrow-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7l7 7-7 7" />
                </svg>
            </a>
        </li>
        <li>
            <a href="https://youtu.be/3KAwILniN2s" target="_blank">
                <span>How it works</span>
                <svg class="arrow-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7l7 7-7 7" />
                </svg>
            </a>
        </li>
        <li>
            <a href="https://cloudcoin.com/support" target="_blank">
                <span>Support</span>
                <svg class="arrow-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7l7 7-7 7" />
                </svg>
            </a>
        </li>
        
        <?php if ($is_phone_verified): ?>
            <!-- <li>
                <a href="<?php echo base_url('controllers/message_controller.php?action=send_form'); ?>">
                    <span>SEND MESSAGE</span>
                    <svg class="arrow-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7l7 7-7 7" />
                    </svg>
                </a>
            </li>
            <li>
                <a href="<?php echo base_url('controllers/message_controller.php?action=check_messages'); ?>">
                    <span>CHECK MESSAGES</span>
                    <svg class="arrow-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7-7 7" />
                    </svg>
                </a>
            </li> -->
            <li>
                <a href="<?php echo base_url('controllers/auth_controller.php?action=logout'); ?>">
                    <span>LOG OUT</span>
                    <svg class="arrow-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7l7 7-7 7" />
                    </svg>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>

<!-- Header JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.querySelector('header');
        const hamburger = document.querySelector('.hamburger');
        const mobileMenu = document.querySelector('.mobile-menu');
        const scrollThreshold = 50;

        // Header scroll effect
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > scrollThreshold) {
                header.classList.add('header-scrolled');
            } else {
                header.classList.remove('header-scrolled');
            }
        });

        // Mobile menu toggle
        if (hamburger && mobileMenu) {
            hamburger.addEventListener('click', function() {
                hamburger.classList.toggle('active');
                mobileMenu.classList.toggle('active');
                
                // Add show class to list items with delay
                const listItems = mobileMenu.querySelectorAll('.nav-links li');
                listItems.forEach((item, index) => {
                    setTimeout(() => {
                        item.classList.toggle('show');
                    }, 100 * index);
                });
            });
        }
    });
</script>