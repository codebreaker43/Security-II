(function () {
    function initCountdown() {
        var countdownTimer = document.getElementById('countdown-timer');
        var errorMessage   = document.getElementById('error-message');
        var attemptBadge   = document.getElementById('attempt-counter');
        var usernameField  = document.getElementById('username');
        var submitButton   = document.getElementById('mySubmit');

        function formatWaitText(seconds) {
            if (seconds === 1) {
                return 'Please wait 1 second.';
            }
            return 'Please wait ' + seconds + ' seconds.';
        }

        // Block all mouse, touch, and keyboard interactions during lockout countdown
        function stopEvent(e) {
            e.stopPropagation();
            e.preventDefault();
            return false;
        }

        // Render the countdown banner directly inside the form card
        function renderCountdown(seconds) {
            if (!countdownTimer) return;
            var waitText = formatWaitText(seconds);
            countdownTimer.className = 'lockout-box';
            countdownTimer.style.display = 'flex';
            countdownTimer.innerHTML =
                '<div class="lock-icon-wrap">' +
                    '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">' +
                        '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>' +
                        '<path d="M7 11V7a5 5 0 0 1 10 0v4"/>' +
                    '</svg>' +
                '</div>' +
                '<div class="lock-content">' +
                    '<div class="lock-header">' +
                        '<span id="countdown-status-text" style="color:#ff944d; font-weight:800; font-size:0.95rem;">' + waitText + '</span>' +
                    '</div>' +
                    '<div class="lock-desc">Too many failed attempts. All buttons and fields are disabled.</div>' +
                '</div>';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('disabled');
            }
        }

        function updateSeconds(seconds) {
            var waitText = formatWaitText(seconds);
            var statusText = document.getElementById('countdown-status-text');

            if (statusText) {
                statusText.textContent = waitText;
            }
        }

        // Disable all fields, buttons, and links across the page
        function disableAllInteractions() {
            document.body.classList.add('countdown-active');

            // Disable all inputs, buttons, and links
            var interactives = document.querySelectorAll('input, button, select, textarea, a');
            interactives.forEach(function (el) {
                el.classList.add('disabled');
                if (el.tagName !== 'A') {
                    el.disabled = true;
                }
                el.setAttribute('tabindex', '-1');
            });

            // Intercept all clicks and keyboard events
            window.addEventListener('click',     stopEvent, true);
            window.addEventListener('mousedown', stopEvent, true);
            window.addEventListener('keydown',   stopEvent, true);
            window.addEventListener('keyup',     stopEvent, true);
            window.addEventListener('keypress',  stopEvent, true);
            window.addEventListener('submit',    stopEvent, true);
        }

        // Re-enable all fields, buttons, and links when countdown ends
        function enableAllInteractions() {
            document.body.classList.remove('countdown-active');

            var interactives = document.querySelectorAll('input, button, select, textarea, a');
            interactives.forEach(function (el) {
                el.classList.remove('disabled');
                if (el.tagName !== 'A') {
                    el.disabled = false;
                }
                el.removeAttribute('tabindex');
            });

            window.removeEventListener('click',     stopEvent, true);
            window.removeEventListener('mousedown', stopEvent, true);
            window.removeEventListener('keydown',   stopEvent, true);
            window.removeEventListener('keyup',     stopEvent, true);
            window.removeEventListener('keypress',  stopEvent, true);
            window.removeEventListener('submit',    stopEvent, true);

            // Reset submit button text
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('disabled');
                submitButton.innerHTML =
                    '<span>Sign In</span>' +
                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
                        '<line x1="5" y1="12" x2="19" y2="12"></line>' +
                        '<polyline points="12 5 19 12 12 19"></polyline>' +
                    '</svg>';
            }

            // Show unlock notification banner: You may try again.
            if (countdownTimer) {
                countdownTimer.className = 'unlock-box';
                countdownTimer.style.display = 'flex';
                countdownTimer.innerHTML =
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">' +
                        '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>' +
                        '<polyline points="22 4 12 14.01 9 11.01"/>' +
                    '</svg>' +
                    '<span style="font-weight:700; font-size:0.95rem;">You may try again.</span>';
            }

            if (usernameField) {
                usernameField.focus();
            }
        }

        // Start countdown if currently locked out
        var activeRemainingTime = (typeof remainingTime !== 'undefined') ? parseInt(remainingTime, 10) : 0;
        if (activeRemainingTime > 0) {
            if (errorMessage) {
                errorMessage.style.display = 'none';
                errorMessage.innerHTML = '';
            }
            if (attemptBadge) {
                attemptBadge.style.display = 'none';
            }

            renderCountdown(activeRemainingTime);
            disableAllInteractions();

            var timerCount = activeRemainingTime;
            var countdownInterval = setInterval(function () {
                timerCount -= 1;

                if (timerCount <= 0) {
                    clearInterval(countdownInterval);
                    enableAllInteractions();
                } else {
                    updateSeconds(timerCount);
                }
            }, 1000);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCountdown);
    } else {
        initCountdown();
    }
})();


