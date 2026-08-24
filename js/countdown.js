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

        disableAllInteractions();

        var countdown = setInterval(function () {
            remainingTime -= 1;
            if (remainingTime <= 0) {
                clearInterval(countdown);
                countdownTimer.style.display = 'none';
                enableAllInteractions();
            } else {
                countdownTimer.innerHTML = 'You are locked out. Please try again in ' + remainingTime + ' seconds.';
            }
        }, 1000);
    }

    function disableAllInteractions() {
        // Add class to body to handle cursor and pointer-events via CSS
        document.body.classList.add('countdown-active');

        // Explicitly disable form inputs to prevent focus/tab navigation
        var inputs = document.querySelectorAll('input, button, select, textarea, a');
        inputs.forEach(function (el) {
            el.classList.add('disabled');
            if (el.tagName !== 'A') {
                el.disabled = true;
            }
        });

        // Add capture phase listener to stop all clicks
        window.addEventListener('click', stopEvent, true);
    }

    function stopEvent(e) {
        e.stopPropagation();
        e.preventDefault();
    }

    function enableAllInteractions() {
        // Remove class from body
        document.body.classList.remove('countdown-active');

        // Re-enable interactions
        var inputs = document.querySelectorAll('input, button, select, textarea, a');
        inputs.forEach(function (el) {
            el.classList.remove('disabled');
            if (el.tagName !== 'A') {
                el.disabled = false;
            }
        });

        // Remove capture phase listener
        window.removeEventListener('click', stopEvent, true);
    }

});
