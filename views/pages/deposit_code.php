<?php
// File: views/pages/deposit_code.php

require_once 'utils/csrf_functions.php';
?>

<style>
    .deposit-options {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 30px;
        width: 100%;
    }

    .btn {
        width: 100%;
    }

    .submit-btn {
        margin-bottom: 15px;
    }

    #main-heading {
        padding-top: 30px;
    }

    .message {
        max-width: 400px;
        margin: 10px auto;
        word-wrap: break-word;
        overflow-wrap: break-word;
        opacity: 1;
        transition: opacity 0.3s ease-in-out;
    }

    .message.fade-out {
        opacity: 0;
    }

    .message.success {
        background-color: rgba(40, 167, 69, 0.1);
        border: 1px solid #28a745;
        color: #155724;
        padding: 12px 20px;
        border-radius: 4px;
        margin: 10px 0;
        text-align: center;
    }

    .message.error {
        background-color: rgba(220, 53, 69, 0.1);
        border: 1px solid #dc3545;
        color: #721c24;
        padding: 12px 20px;
        border-radius: 4px;
        margin: 10px 0;
        text-align: center;
    }


    #progressBar {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 5px;
        padding: 3px;
        margin: 1rem 0;
    }

    #progressBar .bar {
        height: 20px;
        border-radius: 3px;
        background-color: #4a90e2;
        transition: width 0.3s ease;
        width: 0%;
    }
</style>

<div class="form-container">
    <h1 id="main-heading">Deposit by Code</h1>
    
    <div id="deposit-by-code-form">
        <form id="depositForm" action="controllers/deposit_api_controller.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="text" 
                   name="deposit_code"  
                   placeholder="Enter deposit code" 
                   required>
            <button type="submit" name="deposit" class="submit-btn">Deposit</button>
        </form>
    </div>

    <div id="progressBar" style="display: none;">
        <div class="bar"></div>
    </div>
    <div id="message" class="message" style="display:none;"></div>
</div>

<script>
    function showMessage(messageDiv, text, type, duration = 30000) {
        messageDiv.textContent = text;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';

        if (duration) {
            setTimeout(() => {
                messageDiv.classList.add('fade-out');
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                    messageDiv.classList.remove('fade-out');
                }, 300);
            }, duration);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const depositForm = document.getElementById('depositForm');
        const progressBar = document.getElementById('progressBar');
        const progressBarInner = progressBar.querySelector('.bar');
        const messageDiv = document.getElementById('message');

        depositForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('deposit', '1');

            const depositCodeInput = this.querySelector('input[name="deposit_code"]');
            depositCodeInput.disabled = true;
            this.querySelector('button[type="submit"]').disabled = true;

            progressBar.style.display = 'block';
            messageDiv.style.display = 'none';
            progressBarInner.style.width = '2%';

            let buffer = "";

            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onprogress = function() {
                const newData = xhr.responseText.substr(buffer.length);
                buffer = xhr.responseText;
                newData.split("\n").forEach(line => {
                    if (line.trim()) {
                        try {
                            const response = JSON.parse(line);
                            if (response.status === 'running' || response.status === 'completed') {
                                const progress = Math.max(2, response.progress || 0);
                                progressBarInner.style.width = progress + '%';
                            }
                        } catch (err) {
                            console.error('Progress update parsing error:', err);
                        }
                    }
                });
            };

            xhr.onload = function() {
                depositCodeInput.disabled = false;
                depositForm.querySelector('button[type="submit"]').disabled = false;
                progressBar.style.display = 'none';
                
                try {
                    const lines = xhr.responseText.split("\n").filter(line => line.trim());
                    const lastLine = lines[lines.length - 1];
                    if (!lastLine) {
                        showMessage(messageDiv, 'No response from server. Please try again.', 'error');
                        return;
                    }
                    const response = JSON.parse(lastLine);

                    if (response.status === 'completed') {
                        showMessage(messageDiv, `Successfully deposited ${response.data.total} CloudCoins.`, 'success');
                    } else if (response.status === 'error') {
                        showMessage(messageDiv, response.message || 'Deposit failed. Please try again.', 'error');
                    }
                    depositCodeInput.value = '';
                } catch (err) {
                    showMessage(messageDiv, 'An error occurred processing the deposit. Please try again.', 'error');
                    console.error('Response parsing error:', err);
                }
            };

            xhr.onerror = function() {
                depositCodeInput.disabled = false;
                depositForm.querySelector('button[type="submit"]').disabled = false;
                progressBar.style.display = 'none';
                showMessage(messageDiv, 
                          'Network error occurred. Please try again.', 
                          'error');
            };

            xhr.send(formData);
        });
    });
</script>
