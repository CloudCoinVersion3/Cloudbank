<?php
// File: views/pages/send_view.php
session_start();
require_once __DIR__ . '/../../utils/csrf_functions.php';

// Check if the user is logged in (phone verified)
if (!isset($_SESSION['phone_verified']) || $_SESSION['phone_verified'] !== true) {
    header("Location: index.php");
    exit();
}
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send CloudCoins - CloudBank</title>
    
    <!-- Stylesheets for intl-tel-input and your existing styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    
    <style>
        #phone {
            border-radius: 5px;
            height: 50px;
            padding-left: 50px !important;
            margin-bottom: 30px !important;
            font-size: 16px !important;
        }

        .iti {
            max-width: 390px !important;
            width: 90% !important;
        }

        .iti__flag-container {
            top: -28px !important
        }

        .iti__country-list {
            max-width: 390px !important;
            margin-top: -25px !important
        }

        .iti--allow-dropdown .iti__flag-container:hover .iti__selected-flag {
            background-color: transparent !important;
        }


        @media screen and (max-width: 520px) {
            .iti__country-list {
                width: calc(100vw - 104px) !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
            }

            .iti__country {
                padding: 5px !important;
            }

            .iti__country-name,
            .iti__dial-code {
                font-size: 14px !important
            }
        }
    </style>
</head>
<body>
     <div class="form-container">
        <h1>Send CloudCoins</h1>
        <form id="sendForm" method="post" action="controllers/send_controller.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <input type="number" name="amount" placeholder="Amount" required min="1">
            
            <input id="phone" type="tel" name="recipient_number" placeholder="Recipient's Phone" required />
            
            <button type="submit" class="button">Send</button>
        </form>
        <div id="responseMessage" class="message" style="display:none;"></div>
    </div>

    <!-- Script for intl-tel-input and form handling -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script>
        // Initialize international phone input
        const phoneInputField = document.querySelector("#phone");
        const phoneInput = window.intlTelInput(phoneInputField, {
            preferredCountries: ["us", "ru", "in", "de"],
            initialCountry: "auto",
            geoIpLookup: function(callback) {
                fetch("https://ipapi.co/json")
                    .then(res => res.json())
                    .then(data => callback(data.country_code))
                    .catch(() => callback("us"));
            },
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        });

        document.addEventListener('DOMContentLoaded', function() {
            const sendForm = document.getElementById('sendForm');
            const responseMessageDiv = document.getElementById('responseMessage');

            sendForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                const submitButton = form.querySelector('button[type="submit"]');

                if (!phoneInput.isValidNumber()) {
                    responseMessageDiv.textContent = 'Please enter a valid phone number.';
                    responseMessageDiv.className = 'message error';
                    responseMessageDiv.style.display = 'block';
                    return;
                }

                const formData = new FormData(form);
                // This line now correctly overwrites the 'recipient_number' field with the full international format
                formData.set('recipient_number', phoneInput.getNumber());

                submitButton.disabled = true;
                submitButton.textContent = 'Sending...';
                responseMessageDiv.style.display = 'none';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.onload = function() {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Send';
                    responseMessageDiv.style.display = 'block';

                    try {
                        const response = JSON.parse(this.responseText);
                        responseMessageDiv.textContent = response.message;
                        if (response.status === 'success') {
                            responseMessageDiv.className = 'message success';
                            form.reset();
                            phoneInput.setCountry('us'); 
                        } else {
                            responseMessageDiv.className = 'message error';
                        }
                    } catch (err) {
                        console.error('Error parsing response:', err, this.responseText);
                        responseMessageDiv.textContent = 'A critical error occurred. Please try again.';
                        responseMessageDiv.className = 'message error';
                    }
                };

                xhr.onerror = function() {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Send';
                    responseMessageDiv.textContent = 'A network error occurred. Please check your connection.';
                    responseMessageDiv.className = 'message error';
                };

                xhr.send(formData);
            });
        });
    </script>
</body>
</html>
