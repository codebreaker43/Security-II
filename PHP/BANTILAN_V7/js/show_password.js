
// Get references to the password input and the checkbox
const passwordInput = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');

// Add an event listener to the checkbox
togglePassword.addEventListener('change', function() {
    // Toggle the type attribute of the password input
    if (this.checked) {
        passwordInput.setAttribute('type', 'text'); // Show password
    } else {
        passwordInput.setAttribute('type', 'password'); // Hide password
    }
});


