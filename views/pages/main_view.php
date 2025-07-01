<?php
/**
 * Main View 
 */

// Check if user is authenticated
$is_authenticated = is_user_authenticated();

// If user is authenticated, show navigation and dashboard
if ($is_authenticated): ?>
    <div class="dashboard-content" style="margin-bottom: 2rem;">
        <h1>Welcome back to CloudBank!</h1>
        <p style="background-color: white;
    color: #4a90e2;
    border-left: 4px solid #4a90e2;
    padding: 10px;
    font-weight: bold;">Use the navigation buttons above to access your Cloudbank features.</p>
    </div>
<?php else: ?>
    <div>
        <div class="content-wrapper">
            <h1 class="cloudbank-title">CloudBank</h1>
            <form id="loginForm">
                <div class="message info" style="display: none;"></div>
                <input id="phone" type="tel" name="phone" required />
                <button type="submit" class="btn">
                    <span class="button-text">Verify Phone Number</span>
                    <span class="spinner"></span>
                </button>
            </form>
        </div>
    </div>

    <style>
        .btn {
           width: 100%;
           max-width: 400px;
           height: 50px;
           margin-top: 2rem;
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
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .spinner {
            display: none;
            margin-left: 8px;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    
    <script>
        class PhoneVerification {
            constructor() {
                this.form = document.getElementById('loginForm');
                this.phoneInputField = document.querySelector("#phone");
                this.messageDiv = document.querySelector(".message");
                this.submitButton = this.form.querySelector('button[type="submit"]');
                this.buttonText = this.submitButton.querySelector('.button-text');
                this.spinner = this.submitButton.querySelector('.spinner');
                this.isSubmitting = false;

                // Initialize phone input
                this.phoneInput = window.intlTelInput(this.phoneInputField, {
                    preferredCountries: ["us", "ru", "in", "de"],
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                });

                // Bind event listeners
                this.form.addEventListener('submit', this.handleSubmit.bind(this));
            } // end  constructor

            showMessage(message, type = 'info') {
                this.messageDiv.textContent = message;
                this.messageDiv.style.display = 'block';
                this.messageDiv.classList.remove('info', 'error', 'success', 'fade-out');
                this.messageDiv.classList.add(type);

                setTimeout(() => {
                    this.messageDiv.classList.add('fade-out');
                    setTimeout(() => {
                        this.messageDiv.style.display = 'none';
                    }, 300);
                }, 3000);
            }

            setLoading(isLoading) {
                this.isSubmitting = isLoading;
                this.submitButton.disabled = isLoading;
                this.buttonText.textContent = isLoading ? 'Verifying...' : 'Verify Phone Number';
                this.spinner.style.display = isLoading ? 'inline-block' : 'none';
            }

            async handleSubmit(event) {
                event.preventDefault();

                // Prevent double submission
                if (this.isSubmitting) {
                    return;
                }

                // Validate phone number
                if (!this.phoneInput.isValidNumber()) {
                    this.showMessage('Please enter a valid phone number', 'error');
                    return;
                }

                // Get phone details
                const countryCode = this.phoneInput.getSelectedCountryData().dialCode;
                const nationalNumber = this.phoneInput.getNumber()
                    .replace(`+${countryCode}`, '')
                    .replace(/\D/g, '');

                // Construct URL with query parameters
                const params = new URLSearchParams({
                    country_code: countryCode,
                    phone_number: nationalNumber
                });

                const url = `verify_phone.php?${params.toString()}`;

                // Log the full URL for troubleshooting
                console.log('Full GET request URL:', url);

                this.setLoading(true);

                try {
                    const response = await fetch(url, {
                        method: 'GET'
                    });

                    const data = await response.json();

                    if (data.success) {
                        window.location.href = 'enter_code.php';
                    } else {
                        this.showMessage(data.message || 'Verification failed. Please try again.', 'error');
                        this.setLoading(false);
                    }

                } catch (error) {
                    console.error('Error:', error);
                    this.showMessage('An error occurred. Please try again.', 'error');
                    this.setLoading(false);
                } finally {
                    this.setLoading(false);
                }
            } // End ASYNC HANDLE SUB
        } // end class

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new PhoneVerification();
        });
    </script>
<?php endif; ?>