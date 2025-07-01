<?php
// File: views/pages/deposit_file.php

require_once 'utils/csrf_functions.php';
?>

<style>
    .file-input-wrapper {
        position: relative;
        border: 2px dashed rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        background-color: rgba(255, 255, 255, 0.05);
        transition: all 0.3s ease;
        margin-bottom: 20px;
        width: 80%;
        cursor: pointer;
    }

    .file-input-wrapper:hover {
        border-color: rgba(255, 255, 255, 0.4);
        background-color: rgba(255, 255, 255, 0.1);
    }

    .file-input-wrapper input[type="file"] {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .upload-icon {
        display: block;
        margin: 0 auto 10px;
        width: 40px;
        height: 40px;
        stroke: #4a90e2; 
        opacity: 1; 
    }

    .drop-text {
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 5px;
    }

    .supported-formats {
        font-size: 0.85em;
        color: rgba(255, 255, 255, 0.5);
    }

    .selected-file {
        margin-top: 10px;
        font-size: 0.9em;
        color: rgba(255, 255, 255, 0.7);
    }
    
    .btn {
      width: 100%;
      margin-top: 0;
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

    .message {
        max-width: 400px;
        margin: 10px auto;
        word-wrap: break-word;
        overflow-wrap: break-word;
        opacity: 1;
        transition: opacity 0.3s ease-in-out;
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

</style>

<div class="form-container">
    <h1 id="main-heading">Deposit by File</h1>

    <div id="deposit-by-file-form">
        <form id="fileDepositForm" action="controllers/deposit_api_controller.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="file-input-wrapper">
                <svg class="upload-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#4a90e2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <div class="drop-text">Drop your file here or browse</div>
                <div class="supported-formats">Supported formats: .stack, .zip, .png, .bin</div>
                <input type="file" name="deposit_file" id="deposit_file" accept=".stack,.zip,.png,.bin" required>
                <div class="selected-file"></div>
            </div>

            <button type="submit" class="submit-btn">Upload and Deposit</button>
        </form>
    </div>

    <div id="progressBar" style="display: none;">
        <div class="bar"></div>
    </div>
    <div id="message" class="message" style="display:none;"></div>
</div>
    
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileDepositForm = document.getElementById('fileDepositForm');
        const fileInput = document.getElementById('deposit_file');
        const selectedFileDiv = document.querySelector('.selected-file');
        const progressBar = document.getElementById('progressBar');
        const progressBarInner = progressBar.querySelector('.bar');
        const messageDiv = document.getElementById('message');
        const submitButton = document.querySelector('button[type="submit"]');
        const fileInputWrapper = document.querySelector('.file-input-wrapper');

        function showMessage(text, type, duration = 30000) {
            messageDiv.textContent = text;
            messageDiv.className = `message ${type}`;
            messageDiv.style.display = 'block';

            if (duration) {
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                        messageDiv.style.opacity = '1';
                    }, 300);
                }, duration);
            }
        }

        function resetForm() {
            fileInput.value = '';
            selectedFileDiv.textContent = '';
            submitButton.disabled = false;
            fileInput.disabled = false;
            fileInputWrapper.style.pointerEvents = 'auto';
            fileInputWrapper.style.opacity = '1';
        }

        function disableForm() {
            submitButton.disabled = true;
            fileInput.disabled = true;
            fileInputWrapper.style.pointerEvents = 'none';
            fileInputWrapper.style.opacity = '0.6';
        }

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const maxSize = 100 * 1024 * 1024; // 100MB

                if (file.size > maxSize) {
                    showMessage('File size exceeds maximum limit of 100MB', 'error');
                    this.value = '';
                    selectedFileDiv.textContent = '';
                    return;
                }

                selectedFileDiv.textContent = `Selected: ${file.name}`;
            } else {
                selectedFileDiv.textContent = '';
            }
        });

        fileDepositForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!fileInput.files.length) {
                showMessage('Please select a file to deposit', 'error');
                return;
            }

            const formData = new FormData(this);

            disableForm();
            progressBar.style.display = 'block';
            messageDiv.style.display = 'none';
            progressBarInner.style.width = '0%';

            let buffer = "";
            let lastProgress = 0;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.action, true);

            xhr.onprogress = function() {
                const newData = xhr.responseText.substr(buffer.length);
                buffer = xhr.responseText;

                newData.split("\n").forEach(line => {
                    if (line.trim()) {
                        try {
                            const response = JSON.parse(line);
                            if (response.status === 'running' || response.status === 'completed') {
                                const progress = Math.max(lastProgress, response.progress || 0);
                                progressBarInner.style.width = progress + '%';
                                lastProgress = progress;
                            }
                        } catch (err) {}
                    }
                });
            };

            xhr.onload = function() {
                try {
                    const lines = xhr.responseText.trim().split("\n");
                    let lastResponse = null;

                    for (let i = lines.length - 1; i >= 0; i--) {
                        if (lines[i].trim()) {
                            try {
                                lastResponse = JSON.parse(lines[i]);
                                break;
                            } catch (e) {
                                continue;
                            }
                        }
                    }

                    if (!lastResponse) {
                        throw new Error('No valid response received');
                    }

                    if (lastResponse.status === 'error') {
                        // Check for the duplicate file error. If it occurs,
                        // hide the progress bar immediately.
                        if (lastResponse.message && lastResponse.message.includes('This file was already uploaded')) {
                             progressBar.style.display = 'none';
                        }
                        showMessage(lastResponse.message || 'Deposit failed', 'error');
                        return; // Exit here, finally will still run
                    }

                    if (lastResponse.status === 'completed' && lastResponse.data) {
                        progressBarInner.style.width = '100%';
                        
                        const totalAmount = lastResponse.data.total || lastResponse.data.total_sum || lastResponse.data.total_value;
                        
                        if (typeof totalAmount !== 'undefined') {
                            showMessage(`Successfully deposited ${totalAmount} CloudCoins`, 'success');
                        } else {
                            showMessage('Deposit completed successfully!', 'success');
                        }
                    }
                } catch (err) {
                    showMessage(err.message || 'An error occurred processing the deposit', 'error');
                } finally {
                    // This block now correctly handles all cases.
                    // For success or other errors, it hides the progress bar after a delay.
                    // For the duplicate error, the bar is already hidden, so this has no visible effect.
                    setTimeout(() => {
                        progressBar.style.display = 'none';
                        progressBarInner.style.width = '0%';
                    }, 1000);
                    resetForm();
                }
            };

            xhr.onerror = function() {
                showMessage('Network error occurred. Please try again.', 'error');
                setTimeout(() => {
                    progressBar.style.display = 'none';
                    progressBarInner.style.width = '0%';
                }, 1000);
                resetForm();
            };

            xhr.send(formData);
        });
    });
</script>

