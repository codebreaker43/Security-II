document.addEventListener('DOMContentLoaded', function () {
    // Check and display forgot password link
    if (showForgotPassword === 'true') {
        document.getElementById('forgot-password-link').innerHTML = 
            '<p>Forgot Password? <a href="forgot_password.php" class="reset-link">Reset Here</a></p>';
    }

    // Display error message if available
    if (errorMessage) {
        document.getElementById('error-message').innerHTML = errorMessage;
    }
});
