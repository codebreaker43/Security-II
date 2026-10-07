// Show/Hide Password Functionality
function initShowPassword() {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword') || document.getElementById('showPassword');

    if (togglePassword && passwordInput && !togglePassword.dataset.showPasswordBound) {
        togglePassword.dataset.showPasswordBound = 'true';
        togglePassword.addEventListener('change', function () {
            passwordInput.type = this.checked ? 'text' : 'password';
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initShowPassword);
} else {
    initShowPassword();
}
