// Required Asterisk
function initAsterisks() {
    const requiredFields = ['username', 'password'];

    requiredFields.forEach(fieldId => {
        const inputElement = document.getElementById(fieldId);
        if (!inputElement) return;

        const prevLabel = inputElement.previousElementSibling;
        const requiredSpan = prevLabel ? prevLabel.querySelector('.required-asterisk') : null;
        if (!requiredSpan) return;

        inputElement.addEventListener('input', function() {
            if (inputElement.value.trim() !== '') {
                requiredSpan.style.display = 'none';
            } else {
                requiredSpan.style.display = 'inline';
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener("DOMContentLoaded", initAsterisks);
} else {
    initAsterisks();
}

// Username Input Validation
// Add an event listener to the username input for validation
document.getElementById('username').addEventListener('input', function () {
    const username = this.value; // Get current username value
    const usernameError = document.getElementById('username_error'); // Get the error message element
    usernameError.textContent = ''; // Clear any previous error messages

    // Define validation rules
    const minLength = 6; // Minimum length for username
    const maxLength = 15; // Maximum length for username
    const usernamePattern = /^[a-zA-Z0-9.]+$/; // Regex pattern for allowed characters
    const startsOrEndsWithPeriod = /^\./.test(username) || /\.$/.test(username); // Check for period placement

    // If username is empty, exit the function
    if (username.length === 0) {
        return;
    }

    // Validate username length
    if (username.length < minLength) {
        usernameError.textContent = `Username must be at least ${minLength} characters.`; // Error message for short username
        usernameError.style.color = 'red'; // Set error message color to red
        return; // Exit the function
    }

    if (username.length > maxLength) {
        usernameError.textContent = `Username must not exceed ${maxLength} characters.`; // Error message for long username
        usernameError.style.color = 'red'; 
        return;
    }

    // Validate username characters
    if (!usernamePattern.test(username)) {
        usernameError.textContent = 'Username can only contain letters, numbers, or periods.'; // Error for invalid characters
        usernameError.style.color = 'red'; 
        return;
    }

    // Validate username start and end characters
    if (startsOrEndsWithPeriod) {
        usernameError.textContent = 'Username cannot start or end with a period.'; // Error for period placement
        usernameError.style.color = 'red'; 
        return;
    }
});

// Password Input Validation with additional rules
// Add an event listener to the password input for validation
document.getElementById('password').addEventListener('input', function () {
    const password = this.value; // Get current password value
    const passwordError = document.getElementById('password_error'); // Get the error message element
    passwordError.textContent = ''; // Clear any previous error messages

    // Define validation rules
    const minLength = 8; // Minimum length for password
    const maxLength = 15; // Maximum length for password
    const hasUpperCase = /[A-Z]/; // Check for uppercase letters
    const hasNumber = /[0-9]/; // Check for numbers
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/; // Check for special characters

    // If password is empty, exit the function
    if (password.length === 0) {
        return;
    }

    // Validate password length
    if (password.length < minLength) {
        passwordError.textContent = `Password must be at least ${minLength} characters.`; // Error message for short password
        passwordError.style.color = 'red'; 
        return;
    }

    if (password.length > maxLength) {
        passwordError.textContent = `Password must not exceed ${maxLength} characters.`; // Error message for long password
        passwordError.style.color = 'red'; 
        return;
    }

    // Validate password content
    if (!hasUpperCase.test(password)) {
        passwordError.textContent = 'Password must contain at least one uppercase letter.'; // Error for missing uppercase
        passwordError.style.color = 'red'; 
        return;
    }

    if (!hasNumber.test(password)) {
        passwordError.textContent = 'Password must contain at least one number.'; // Error for missing number
        passwordError.style.color = 'red'; 
        return;
    }

    if (!hasSpecialChar.test(password)) {
        passwordError.textContent = 'Password must contain at least one special character (e.g., !@#$%^&*).'; // Error for missing special character
        passwordError.style.color = 'red'; 
        return;
    }
});



// Form Submission Validation
// Add event listener to the form for validation on submission
const loginFormElem = document.getElementById('registrationForm') || document.getElementById('loginForm') || document.querySelector('form');
if (loginFormElem) {
    loginFormElem.addEventListener('submit', function(event) {
        const userElem = document.getElementById('username');
        const passElem = document.getElementById('password');
        const username = userElem ? userElem.value.trim() : ''; // Get trimmed username value
        const password = passElem ? passElem.value.trim() : ''; // Get trimmed password value
        const usernameError = document.getElementById('username_error'); // Get the error message element for username
        const passwordError = document.getElementById('password_error'); // Get the error message element for password

        let formIsValid = true; // Flag to track overall form validity

        // Check if the username field is empty
        if (username === '') {
            if (usernameError) {
                usernameError.textContent = 'Username should not be empty.';
                usernameError.style.color = '#ff4d4d';
            }
            formIsValid = false;
        }

        // Check if the password field is empty
        if (password === '') {
            if (passwordError) {
                passwordError.textContent = 'Password should not be empty.';
                passwordError.style.color = '#ff4d4d';
            }
            formIsValid = false;
        }

        // If the form is not valid, prevent submission
        if (!formIsValid) {
            event.preventDefault(); // Prevent form submission
        }
    });
}



localStorage.removeItem("signupForm");