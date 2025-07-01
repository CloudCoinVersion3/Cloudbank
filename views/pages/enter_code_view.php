<?php
// ===== 2. views/pages/enter_code_view.php (Content only - Like your main_view.php) =====
/**
 * Enter Code View 
 */

// Redirect to index if phone number is not in session
if (!isset($_SESSION['phone_number'])) {
    header('Location: ' . base_url('index.php?error=session_expired'));
    exit;
}
?>

<div class="main-container">
    <div class="form-container">
        <h1>Enter Verification Code</h1>
        <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 1.5rem;">
            A verification code was sent to <?php echo htmlspecialchars($_SESSION['phone_number']); ?>.
        </p>
        
        <div id="message" class="message hidden"></div>
        
        <form id="verificationForm">
            <input type="text" 
                   name="verification_code" 
                   placeholder="Enter verification code" 
                   required 
                   maxlength="6" 
                   pattern="\d{6}"
                   autocomplete="one-time-code"
                   inputmode="numeric">
            
            <button type="submit" id="verifyBtn">
                <span id="btnText">Verify</span>
                <span id="btnLoader" class="loader hidden"></span>
            </button>
        </form>
        
        <p style="margin-top: 1.5rem;">
            <a href="<?php echo base_url('index.php'); ?>" class="change-number-link">Change phone number</a>
        </p>
    </div>
</div>

<style>
.hidden { 
    display: none !important; 
}

.loader {
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid #4a90e2;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    animation: spin 1s linear infinite;
    display: inline-block;
    vertical-align: middle;
    margin-left: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.form-container input[type="text"] {
    width: 90% !important;
    height: 50px !important;
    padding: 10px 20px !important;
    margin-bottom: 30px !important;
    font-size: 16px !important; 
}

.form-container input[type="text"]:focus {
    outline: none !important;
    border-color: #4a90e2 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
    transform: translateY(-1px) !important;
}

.form-container button {
    width: 90% !important;
    height: 50px !important;
    padding: 0 1rem !important;
    border-radius: 25px !important;
    border: 1px solid #4a90e2 !important;
    background-color: white !important;
    color: #4a90e2 !important;
}

.form-container button:hover {
   background-color: rgba(255, 255, 255, 0.3) !important;
}

.form-container button:disabled {
    opacity: 0.7 !important;
    cursor: not-allowed !important;
}

.change-number-link {
    color: rgba(255, 255, 255, 0.8) !important;
    text-decoration: underline !important;
    font-size: 0.9rem !important;
    transition: color 0.3s ease !important;
}

.change-number-link:hover {
    color: white !important;
}

</style>

<script>
document.getElementById('verificationForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = document.getElementById('verifyBtn');
    const btnText = document.getElementById('btnText');
    const btnLoader = document.getElementById('btnLoader');
    const messageDiv = document.getElementById('message');
    
    // Show loading state
    btnText.classList.add('hidden');
    btnLoader.classList.remove('hidden');
    btn.disabled = true;

    const formData = new FormData(e.target);

    try {
        const response = await fetch('<?php echo base_url('controllers/verify_code_controller.php'); ?>', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();

        // Show message
        messageDiv.textContent = data.message;
        messageDiv.classList.remove('hidden', 'error', 'success');
        messageDiv.classList.add(data.success ? 'success' : 'error');

        if (data.success) {
            // Redirect to balance page after successful verification
            setTimeout(() => {
                window.location.href = '<?php echo base_url('index.php?page=balance'); ?>';
            }, 1500);
        } else {
            // Reset button state on error
            btnText.classList.remove('hidden');
            btnLoader.classList.add('hidden');
            btn.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        messageDiv.textContent = 'An unexpected error occurred. Please try again.';
        messageDiv.classList.remove('hidden', 'success');
        messageDiv.classList.add('error');
        
        // Reset button state
        btnText.classList.remove('hidden');
        btnLoader.classList.add('hidden');
        btn.disabled = false;
    }
});

// Auto-focus on the input field
document.addEventListener('DOMContentLoaded', () => {
    const codeInput = document.querySelector('input[name="verification_code"]');
    if (codeInput) {
        codeInput.focus();
    }
});
</script>