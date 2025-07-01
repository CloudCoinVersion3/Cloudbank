<?php
// File: views/pages/withdraw.php

require_once __DIR__ . '/../../utils/csrf_functions.php';
$csrf_token = generateCSRFToken();
?>

<style>
.code-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-top: 1.5rem;
    background-color: rgba(255, 255, 255, 0.2);
    border: 1px solid #4a90e2;
    padding: 1rem;
    border-radius: 10px;
}
.copy-icon {
    cursor: pointer;
    color: #4a90e2;
    transition: all 0.3s ease;
    font-size: 1.2rem;
    padding: 8px;
    background-color: rgba(74, 144, 226, 0.1);
    border-radius: 50%;
}
.copy-icon:hover {
    background-color: rgba(74, 144, 226, 0.2);
    transform: scale(1.05);
}
#transmitCode {
    color: #4a90e2;
    font-size: 1.1rem;
    font-weight: 500;
}
.tooltip {
    position: relative;
}
.tooltip .tooltiptext {
    visibility: hidden;
    width: 70px;
    color: #ffffff;
    background-color: #4a90e2;
    border-radius: 6px;
    padding: 6px 10px;
    position: absolute;
    bottom: 125%;
    left: 50%;
    margin-left: -35px;
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 0.875rem;
    text-align: center;
}
.tooltip:hover .tooltiptext {
    visibility: visible;
    opacity: 1;
}
#balanceSection {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.balance-display {
    background-color: rgba(255, 255, 255, 0.3);
    border: 2px solid #4a90e2;
    color: #4a90e2;
    padding: 12px 20px;
    border-radius: 4px;
    font-size: 20px;
    text-align: center;
    margin: 0 0 30px;
    width: 90%;
    box-sizing: border-box;
}
.balance-loader {
    border: 3px solid rgba(74, 144, 226, 0.2);
    border-top: 3px solid #4a90e2;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    animation: spin 1s linear infinite;
    margin: 10px auto;
    display: inline-block;
}
.message.error {
    background-color: rgba(220, 53, 69, 0.1);
    border: 1px solid #dc3545;
    color: #721c24;
    padding: 12px 20px;
    border-radius: 4px;
    margin: 0 0 30px;
    text-align: center;
    width: 90%;
    box-sizing: border-box;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="form-container">
    <h1>Withdraw CloudCoins</h1>
    
    <div id="balanceSection">
        <div class="balance-display" id="balanceDisplay">
            <div id="balanceLoader" class="balance-loader"></div>
            <span id="balanceText" style="display: none;">Your balance: <strong id="currentBalance">0</strong> CloudCoins</span>
        </div>
        <div id="balanceError" class="message error" style="display: none;"></div>
    </div>
    
    <form id="withdrawForm" action="controllers/withdraw_controller.php" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="number" name="withdraw_amount" step="0.01" placeholder="Enter amount to withdraw" required min="0.01">
        <button type="submit" name="withdraw" class="button">Withdraw</button>
    </form>
    
    <div id="progressBar" style="display: none;">
        <div class="bar"></div>
    </div>
    
    <div id="message" class="message" style="display:none;"></div>
    
    <div id="codeContainer" class="code-container" style="display:none;">
        <span id="transmitCode"></span>
        <div class="tooltip">
            <i class="fas fa-copy copy-icon" onclick="copyTransmitCode()"></i>
            <span class="tooltiptext" id="copyTooltip">Copy</span>
        </div>
    </div>
</div>

<script>
function showTemporaryMessage(messageDiv, text, type) {
    messageDiv.textContent = text;
    messageDiv.className = `message ${type}`;
    messageDiv.style.display = 'block';

    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 30000);
}

function handleCompletedResponse(response) {
    var messageDiv = document.getElementById('message');
    var codeContainer = document.getElementById('codeContainer');
    var transmitCodeSpan = document.getElementById('transmitCode');

    if (response.status === 'completed' && response.data && response.data.amount && response.data.transmit_code) {
        showTemporaryMessage(messageDiv, "Successfully withdrew " + response.data.amount + " CloudCoins.", 'success');
        transmitCodeSpan.textContent = "Your withdrawal code is: " + response.data.transmit_code;
        codeContainer.style.display = 'flex';
    } else if (response.status === 'error') {
        showTemporaryMessage(messageDiv, response.message || 'An error occurred. Please try again.', 'error');
        codeContainer.style.display = 'none';
    } else {
        showTemporaryMessage(messageDiv, 'Unexpected response. Please try again or contact support.', 'error');
        codeContainer.style.display = 'none';
    }
}

function updateBalance() {
    const balanceLoader = document.getElementById('balanceLoader');
    const balanceText = document.getElementById('balanceText');
    const currentBalanceSpan = document.getElementById('currentBalance');
    const balanceErrorDiv = document.getElementById('balanceError');
    const balanceDisplayDiv = document.getElementById('balanceDisplay');
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    balanceLoader.style.display = 'inline-block';
    balanceText.style.display = 'none';
    balanceErrorDiv.style.display = 'none';
    balanceDisplayDiv.style.display = 'block';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'controllers/fetch_balance.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onload = function() {
        balanceLoader.style.display = 'none';
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success === true && typeof response.balance !== 'undefined') {
                    currentBalanceSpan.textContent = response.balance;
                    balanceText.style.display = 'inline';
                } else {
                    balanceDisplayDiv.style.display = 'none';
                    balanceErrorDiv.textContent = response.message || 'Error: Unable to fetch balance.';
                    balanceErrorDiv.style.display = 'block';
                }
            } catch (e) {
                console.error("Error parsing balance response:", e);
                balanceDisplayDiv.style.display = 'none';
                balanceErrorDiv.textContent = 'Error: Invalid response from server.';
                balanceErrorDiv.style.display = 'block';
            }
        } else {
            console.error("Balance fetch failed with status:", this.status);
            balanceDisplayDiv.style.display = 'none';
            balanceErrorDiv.textContent = 'Error: Could not connect to the server.';
            balanceErrorDiv.style.display = 'block';
        }
    };
    
    xhr.onerror = function() {
        balanceLoader.style.display = 'none';
        balanceDisplayDiv.style.display = 'none';
        balanceErrorDiv.textContent = 'A network error occurred. Please try again.';
        balanceErrorDiv.style.display = 'block';
    };

    xhr.send('csrf_token=' + encodeURIComponent(csrfToken));
}


function copyTransmitCode() {
    var transmitCodeText = document.getElementById('transmitCode').textContent;
    var code = transmitCodeText.split(': ')[1];
    if (code) {
        navigator.clipboard.writeText(code).then(function() {
            var tooltip = document.getElementById('copyTooltip');
            tooltip.innerHTML = "Copied!";
            setTimeout(function() {
                tooltip.innerHTML = "Copy";
            }, 1500);
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('withdrawForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        var withdrawAmount = parseFloat(form.querySelector('input[name="withdraw_amount"]').value);

        if (isNaN(withdrawAmount) || withdrawAmount <= 0) {
            showTemporaryMessage(document.getElementById('message'), 'Please enter a valid positive number for the withdrawal amount.', 'error');
            return;
        }

        withdrawAmount = Math.round(withdrawAmount * 100) / 100;
        formData.set('withdraw_amount', withdrawAmount.toFixed(2));
        formData.append('withdraw', '1');

        var progressBar = document.getElementById('progressBar');
        var progressBarInner = progressBar.querySelector('.bar');
        var messageDiv = document.getElementById('message');
        var codeContainer = document.getElementById('codeContainer');

        progressBar.style.display = 'block';
        messageDiv.style.display = 'none';
        codeContainer.style.display = 'none';
        progressBarInner.style.width = '2%';

        var buffer = "";
        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onprogress = function() {
            var newData = xhr.responseText.substr(buffer.length);
            buffer = xhr.responseText;
            var lines = newData.split("\n");
            lines.forEach(function(line) {
                if (line.trim()) {
                    try {
                        var response = JSON.parse(line);
                        if (response.status === 'running' || response.status === 'completed') {
                            var progress = Math.max(2, response.progress || 0);
                            progressBarInner.style.width = progress + '%';
                        }
                    } catch (err) {}
                }
            });
        };

        xhr.onload = function() {
            progressBar.style.display = 'none';
            try {
                var lines = xhr.responseText.split("\n").filter(line => line.trim());
                var lastLine = lines[lines.length - 1];
                var response = JSON.parse(lastLine);
                handleCompletedResponse(response);
                if (response.status === 'completed') {
                    // Refresh balance after a successful withdrawal
                    setTimeout(updateBalance, 1000);
                }
            } catch (err) {
                showTemporaryMessage(messageDiv, 'An error occurred. Please try again.', 'error');
                console.error('Error parsing response:', err, xhr.responseText);
            }
            form.querySelector('input[name="withdraw_amount"]').value = '';
        };

        xhr.onerror = function() {
            progressBar.style.display = 'none';
            showTemporaryMessage(messageDiv, 'An error occurred. Please try again.', 'error');
            form.querySelector('input[name="withdraw_amount"]').value = '';
        };

        xhr.send(formData);
    });

    // Fetch the initial balance when the page loads
    updateBalance();
});
</script>
