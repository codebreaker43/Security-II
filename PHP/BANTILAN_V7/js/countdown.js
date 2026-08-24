document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form');
    var countdownTimer = document.getElementById('countdown-timer');
    var errorMessage = document.getElementById('error-message');
    var submitButton = document.getElementById('mySubmit');
    var usernameField = document.getElementById('username');
    var passwordField = document.getElementById('password');
    var showPasswordCheckbox = document.getElementById('togglePassword');
    var forgotPasswordLink = document.getElementById('forgot-password-link');
    var registerHereLink = document.querySelector('.signip');
    var homeLink = document.querySelector('nav ul li a');
    var registerButton = document.querySelector('button a[href="registration.php"]');

    // If remaining time exists, start the countdown
    if (remainingTime > 0) {
        // Hide the error message
        errorMessage.innerHTML = '';
        countdownTimer.style.display = 'block';
        countdownTimer.innerHTML = 'You are locked out. Please try again in ' + remainingTime + ' seconds.';
        disableElements();

        var countdown = setInterval(function () {
            remainingTime -= 1;
            if (remainingTime <= 0) {
                clearInterval(countdown);
                countdownTimer.style.display = 'none';
                enableElements();
            } else {
                countdownTimer.innerHTML = 'You are locked out. Please try again in ' + remainingTime + ' seconds.';
            }
        }, 1000);
    }

    function disableElements() {
        submitButton.disabled = true;
        usernameField.disabled = true;
        passwordField.disabled = true;
        showPasswordCheckbox.disabled = true;
    
        // Add the 'disabled' class to elements
        submitButton.classList.add('disabled');
        usernameField.classList.add('disabled');
        passwordField.classList.add('disabled');
        showPasswordCheckbox.classList.add('disabled');
    
        if (forgotPasswordLink) {
            forgotPasswordLink.style.pointerEvents = 'none';
            forgotPasswordLink.style.opacity = '0.5';
            forgotPasswordLink.classList.add('disabled');
        }
        if (registerHereLink) {
            registerHereLink.style.pointerEvents = 'none';
            registerHereLink.style.opacity = '0.5';
            registerHereLink.classList.add('disabled');
        }
        if (homeLink) {
            homeLink.style.pointerEvents = 'none';
            homeLink.style.opacity = '0.5';
            homeLink.classList.add('disabled');
        }
        if (registerButton) {
            registerButton.style.pointerEvents = 'none';
            registerButton.style.opacity = '0.5';
            registerButton.classList.add('disabled');
        }
    }
    
    function enableElements() {
        submitButton.disabled = false;
        usernameField.disabled = false;
        passwordField.disabled = false;
        showPasswordCheckbox.disabled = false;
    
        // Remove the 'disabled' class from elements
        submitButton.classList.remove('disabled');
        usernameField.classList.remove('disabled');
        passwordField.classList.remove('disabled');
        showPasswordCheckbox.classList.remove('disabled');
    
        if (forgotPasswordLink) {
            forgotPasswordLink.style.pointerEvents = 'auto';
            forgotPasswordLink.style.opacity = '1';
            forgotPasswordLink.classList.remove('disabled');
        }
        if (registerHereLink) {
            registerHereLink.style.pointerEvents = 'auto';
            registerHereLink.style.opacity = '1';
            registerHereLink.classList.remove('disabled');
        }
        if (homeLink) {
            homeLink.style.pointerEvents = 'auto';
            homeLink.style.opacity = '1';
            homeLink.classList.remove('disabled');
        }
        if (registerButton) {
            registerButton.style.pointerEvents = 'auto';
            registerButton.style.opacity = '1';
            registerButton.classList.remove('disabled');
        }
    }
    
});
