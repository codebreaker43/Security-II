
function validateform() {
    var uname = document.forms["myform"]["uname"].value;
    var password = document.forms["myform"]["password"].value;
   
    // Check if the username is filled
    if (uname === "") {
        alert("Username must be filled");
        return false;
    }
    
    // Check if the username has at least 3 characters
    if (uname.length < 3) {
        alert("Username must be at least 3 characters");
        return false;
    }

    // Check if the username consists of lowercase letters only
    if (/[A-Z]/.test(uname)) {
        alert("Username must consist of lowercase letters only");
        return false;
    }

    // Check if the username contains spaces
    if (/\s/.test(uname)) {
        alert("Username cannot contain spaces");
        return false;
    }

    // Check if the password is filled
    if (password === "") {
        alert("Password must be filled");
        return false;
    }
}
function updateCountdown() {
    var countdownElement = document.getElementById("countdown-timer");
    var remainingTime = parseInt(countdownElement.textContent);

    if (remainingTime > 0) {
        remainingTime--;
        countdownElement.textContent = remainingTime;
        // Disable the login form elements
        disableLoginForm();
    } else {
        // Countdown has ended, hide the countdown div and enable the login form elements
        var countdownDiv = document.getElementById("countdown");
        countdownDiv.style.display = "none";
        enableLoginForm();
    }
}

function disableLoginForm() {
    setLoginFormState(true);
}

function enableLoginForm() {
    setLoginFormState(false);
}

function setLoginFormState(disabled) {
    var usernameInput = document.getElementsByName("uname")[0];
    var passwordInput = document.getElementsByName("password")[0];
    var submitButton = document.querySelector("form button");
    var additionalContent = document.querySelector(".mb-0");
    var classList = document.querySelector(".uname");
    var password = document.querySelector(".password");

    // Adjust the display property to hide/show the submit button
    usernameInput.style.display = disabled ? "none" : "block";
    passwordInput.style.display = disabled ? "none" : "block";
    classList.style.display = disabled ? "none" : "block";
    password.style.display = disabled ? "none" : "block";
    submitButton.style.display = disabled ? "none" : "block";
    additionalContent.style.display = disabled ? "none" : "block";
}
// Check if the countdown is active and start updating it
var countdownDiv = document.getElementById("countdown");
if (countdownDiv.classList.contains("countdown-active")) {
    setInterval(updateCountdown, 1000); 
}
function getCountdownEndTime() {
    var name = "countdownEndTime=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var cookieArray = decodedCookie.split(';');
    for (var i = 0; i < cookieArray.length; i++) {
        var cookie = cookieArray[i];
        while (cookie.charAt(0) === ' ') {
            cookie = cookie.substring(1);
        }
        if (cookie.indexOf(name) === 0) {
            return cookie.substring(name.length, cookie.length);
        }
    }
    return null;
}

// Check if the countdown is active
var countdownDiv = document.getElementById("countdown");
if (countdownDiv.classList.contains("countdown-active")) {
    // Retrieve the countdown end time from the cookie
    var countdownEndTime = getCountdownEndTime();
    if (countdownEndTime) {
        // Calculate the remaining time based on the retrieved end time and the current time
        var currentTime = Math.floor(new Date().getTime() / 1000 );
        var remainingTime = countdownEndTime - currentTime;

        // Update the countdown
        var countdownElement = document.getElementById("countdown-timer");
        countdownElement.textContent = remainingTime;

        // Start updating the countdown
        setInterval(updateCountdown, 1000); // Update every second
    }
}




