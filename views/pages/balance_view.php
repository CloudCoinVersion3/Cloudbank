<?php
/**
 * Balance View Page
 * Displays user's account balance with loading animation
 */

// Include required files
require_once __DIR__ . '/../../utils/csrf_functions.php';

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>

<style>
    .balance-content {
        background-color: rgba(255, 255, 255, 0.3);
        border: 1px solid white;
        padding: 2em;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        text-align: center;
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
        box-sizing: border-box;
        margin-bottom: 2rem;
    }
    .balance-content .balance-container {
        background-color: rgba(255, 255, 255, 0.6);
        border: 2px solid #4a90e2;
        color: #4a90e2;
        padding: 30px 10px;
        border-radius: 5px;
        font-size: 24px;
        text-align: center;
    }
    .loader {
        border: 5px solid rgba(255, 255, 255, 0.2);
        border-top: 5px solid white;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 20px auto;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .loading-text {
        color: white;
        font-size: 18px;
        text-align: center;
        margin-top: 20px;
    }
    #balanceContent, 
    #errorContent {
        display: none;
    }
    .error-message {
        font-size: 20px;
        text-align: center;
        margin-top: 20px;
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border: 1px solid #dc3545;
        padding: 10px 20px;
        border-radius: 5px;
    }
</style>

<div class="balance-content">
    <h1>Balance</h1>
    <div id="loader">
        <div class="loader"></div>
        <p class="loading-text">Checking balance...</p>
    </div>
    <div id="balanceContent">
        <p class="balance-container">Your balance is: <span id="txtBalance" style="font-weight:bold;">0.00</span> CloudCoins</p>
    </div>
    <div id="errorContent">
        <p class="error-message">Error: Unable to fetch balance. Please try again later.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Target your existing HTML elements
    const loaderDiv = document.getElementById('loader');
    const balanceContentDiv = document.getElementById('balanceContent');
    const errorContentDiv = document.getElementById('errorContent');
    const balanceSpan = document.getElementById('txtBalance');
    const errorMessageP = errorContentDiv.querySelector('.error-message');
    const csrfToken = '<?php echo addslashes($csrfToken); ?>';

    // This function now correctly hides/shows your specific divs
    function showContent(isError, message = 'Error: Unable to fetch balance. Please try again later.') {
        loaderDiv.style.display = 'none';
        if (isError) {
            errorMessageP.textContent = message;
            errorContentDiv.style.display = 'block';
            balanceContentDiv.style.display = 'none';
        } else {
            errorContentDiv.style.display = 'none';
            balanceContentDiv.style.display = 'block';
        }
    }

    // This function is rewritten to handle JSON responses
    function refreshBalance() {
        // Show the loader immediately
        loaderDiv.style.display = 'block';
        balanceContentDiv.style.display = 'none';
        errorContentDiv.style.display = 'none';

        if (!csrfToken) {
            console.error("CSRF Token is missing.");
            showContent(true, 'Error: Session is invalid. Please refresh.');
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'controllers/fetch_balance.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    // Parse the response as JSON
                    const response = JSON.parse(this.responseText);
                    // Check for the 'success' flag from the controller
                    if (response.success === true && typeof response.balance !== 'undefined') {
                        balanceSpan.textContent = response.balance;
                        showContent(false); // Show balance
                    } else {
                        // Show the specific error message from the backend
                        showContent(true, response.message || 'An unknown error occurred.');
                    }
                } catch (e) {
                    console.error("Error parsing JSON:", e, this.responseText);
                    showContent(true, 'Error: Invalid response from server.');
                }
            } else {
                console.error("Request failed with status:", this.status);
                showContent(true, 'Error: Could not connect to the server.');
            }
        };

        xhr.onerror = function() {
            console.error("Network error occurred.");
            showContent(true, 'A network error occurred. Please try again.');
        };
        
        // A short delay helps UX by ensuring the loader is visible
        setTimeout(() => {
             xhr.send('csrf_token=' + encodeURIComponent(csrfToken));
        }, 500);
    }

    // Fetch the balance as soon as the page is ready
    refreshBalance();
});
</script>
