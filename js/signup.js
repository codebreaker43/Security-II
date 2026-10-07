function setFieldError(inputId, message) {
    var errorEl = document.getElementById(inputId + "_error");
    var inputEl = document.getElementById(inputId);
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.style.display = "block";
    }
    if (inputEl) {
        inputEl.classList.add("input-error-highlight");
    }
}

function clearSingleFieldError(inputId) {
    var errorEl = document.getElementById(inputId + "_error");
    var inputEl = document.getElementById(inputId);
    if (errorEl) {
        errorEl.textContent = "";
        errorEl.style.display = "none";
    }
    if (inputEl) {
        inputEl.classList.remove("input-error-highlight");
    }
}

function clearFieldErrors() {
    var errorElements = document.querySelectorAll(".error-msg");
    for (var i = 0; i < errorElements.length; i++) {
        errorElements[i].textContent = "";
        errorElements[i].style.display = "none";
    }
    var highlighted = document.querySelectorAll(".input-error-highlight");
    for (var j = 0; j < highlighted.length; j++) {
        highlighted[j].classList.remove("input-error-highlight");
    }
}

function containsNumber(input) {
    return /\d/.test(input);
}

function isValidDate(dateString) {
    var dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    return dateRegex.test(dateString);
}

function isValidEmail(email) {
    var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(email)) return false;
    var domain = (email.trim().toLowerCase().split('@')[1] || '');
    var isGmail = (domain === 'gmail.com');
    var isUniversity = /(^|\.)[a-z0-9-]+\.edu\.ph$/.test(domain) || /(^|\.)[a-z0-9-]+\.edu$/.test(domain);
    return isGmail || isUniversity;
}

function calculateAge() {
    const birthdateInput = document.getElementById("birthdate");
    const ageInput = document.getElementById("age");
    if (!birthdateInput || !ageInput || !birthdateInput.value) return;
    const birthdate = new Date(birthdateInput.value);
    const today = new Date();
    let age = today.getFullYear() - birthdate.getFullYear();
    if (today.getMonth() < birthdate.getMonth() || (today.getMonth() === birthdate.getMonth() && today.getDate() < birthdate.getDate())) {
        age--;
    }
    ageInput.value = isNaN(age) ? "" : age;
}

function toggleCustomSuffix() {
    var suffixSelect = document.getElementById('suffix');
    var customSuffixInput = document.getElementById('customSuffix');
    if (!suffixSelect || !customSuffixInput) return;

    if (suffixSelect.value === 'others') {
        customSuffixInput.style.display = 'block';
    } else {
        customSuffixInput.style.display = 'none';
        customSuffixInput.value = '';
        clearSingleFieldError("customSuffix");
    }
}

function strongpassword(password) {
    var pswrdEl = document.getElementById("pswrd");
    var bars = [
        document.getElementById("bar1"),
        document.getElementById("bar2"),
        document.getElementById("bar3"),
        document.getElementById("bar4")
    ];
    var strengthLabel = document.getElementById("strengthLabel");

    if (!password || password.length === 0) {
        if (pswrdEl) pswrdEl.innerHTML = "";
        bars.forEach(function(b) {
            if (b) b.style.backgroundColor = "rgba(255, 255, 255, 0.1)";
        });
        if (strengthLabel) {
            strengthLabel.textContent = "";
            strengthLabel.style.color = "var(--text-muted)";
        }
        return;
    }
  
    var matchedCases = ["[A-Z]", "[0-9]", "[!@#$%^&*()_+]", ".{8,}"];
     
    var alt = 0;
    for (var secure = 0; secure < matchedCases.length; secure++) {
        if (new RegExp(matchedCases[secure]).test(password)) {
            alt++;
        }
    }
  
    var color = "";
    var level = "";
    switch (alt) {
        case 0:
        case 1:
            level = "Weak password";
            color = "#ef4444";
            break;
        case 2:
            level = "Weak password";
            color = "#f97316";
            break;
        case 3:
            level = "Medium password";
            color = "#f59e0b";
            break;
        case 4:
            level = "Strong password";
            color = "#10b981";
            break;
    }
  
    bars.forEach(function(b, idx) {
        if (b) {
            if (idx < alt) {
                b.style.backgroundColor = color;
            } else {
                b.style.backgroundColor = "rgba(255, 255, 255, 0.1)";
            }
        }
    });

    if (strengthLabel) {
        strengthLabel.textContent = level;
        strengthLabel.style.color = color;
    }

    if (pswrdEl) {
        pswrdEl.innerHTML = level;
        pswrdEl.style.color = color;
    }
}

function initRegPasswordToggles() {
    var togglePass = document.getElementById('toggleRegPass');
    var passInput = document.getElementById('password');
    var toggleConfirm = document.getElementById('toggleRegConfirmPass');
    var confirmInput = document.getElementById('confirmPassword');

    var eyeOpen = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    var eyeClosed = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';

    function setupToggle(btn, input) {
        if (btn && input && !btn.dataset.toggleBound) {
            btn.dataset.toggleBound = 'true';
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + eyeClosed + '</svg>';
                } else {
                    input.type = 'password';
                    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + eyeOpen + '</svg>';
                }
            });
        }
    }

    setupToggle(togglePass, passInput);
    setupToggle(toggleConfirm, confirmInput);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRegPasswordToggles);
} else {
    initRegPasswordToggles();
}

// ---------------- Left-to-Right Name Field Scanner ---------------- //

function scanNameField(val, fieldLabel) {
    if (val === "") return "";

    // 1. Check leading space at index 0
    if (val.charAt(0) === ' ') {
        return "The first character of the " + fieldLabel.toLowerCase() + " should not be a space.";
    }

    // 2. Scan characters left-to-right (from index 0 to end)
    for (var i = 0; i < val.length; i++) {
        var char = val.charAt(i);

        // Double space starting at index i
        if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
            return fieldLabel + " cannot contain double space.";
        }

        // 3 consecutive repeated letters starting at index i
        if (i + 2 < val.length && /[a-zA-Z]/.test(char)) {
            if (char.toLowerCase() === val.charAt(i + 1).toLowerCase() && char.toLowerCase() === val.charAt(i + 2).toLowerCase()) {
                return fieldLabel + " cannot contain three consecutive repeated letters.";
            }
        }

        // Number check at index i
        if (/[0-9]/.test(char)) {
            return fieldLabel + " cannot contain numbers.";
        }

        // Special character check at index i
        if (/[^a-zA-Z\s]/.test(char)) {
            return fieldLabel + " cannot contain special characters.";
        }

        // Capitalization check
        if (i === 0) {
            if (/[a-z]/.test(char)) {
                return "The first letter of " + fieldLabel.toLowerCase() + " should be capitalized.";
            }
        } else if (val.charAt(i - 1) === ' ') {
            // First character of the second/next word
            if (/[a-z]/.test(char)) {
                return "The second word of " + fieldLabel.toLowerCase() + " should be capitalized.";
            }
        } else {
            // Letters within the word after the first letter must be lowercase
            if (/[A-Z]/.test(char)) {
                return "The first letter of " + fieldLabel.toLowerCase() + " should be the only capitalized.";
            }
        }
    }

    // 3. Length checks (only if character-by-character scan passed)
    if (val.length < 3) {
        return fieldLabel + " fields must be at least 3 characters.";
    }
    if (val.length > 25) {
        return fieldLabel + " should not exceed 25 characters.";
    }

    return "";
}

// ---------------- Real-time Step 1 Field Validators ---------------- //

function checkIdno(showEmpty) {
    var el = document.getElementById("idno");
    if (!el) return true;
    var val = el.value.trim();
    if (val === "") {
        if (showEmpty) {
            setFieldError("idno", "ID No fields must be filled in.");
            return false;
        }
        clearSingleFieldError("idno");
        return false;
    }
    if (val.charAt(0) === ' ') {
        setFieldError("idno", "The first character of the ID No should not be a space.");
        return false;
    }
    for (var i = 0; i < val.length; i++) {
        var char = val.charAt(i);
        if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
            setFieldError("idno", "ID No cannot contain double space.");
            return false;
        }
        if (/[a-zA-Z]/.test(char)) {
            setFieldError("idno", "ID No cannot contain letters.");
            return false;
        }
        if (/[^0-9-]/.test(char)) {
            setFieldError("idno", "ID No cannot contain special characters, except '-'.");
            return false;
        }
    }
    if (!/^\d{4}-\d{4}$/.test(val)) {
        setFieldError("idno", "ID No must be in the format 1234-1234.");
        return false;
    }
    clearSingleFieldError("idno");
    return true;
}

function checkFirstname(showEmpty) {
    var el = document.getElementById("firstname");
    if (!el) return true;
    var val = el.value;

    if (val === "") {
        if (showEmpty) {
            setFieldError("firstname", "Firstname fields must be filled in.");
            return false;
        }
        clearSingleFieldError("firstname");
        return false;
    }

    var err = scanNameField(val, "Firstname");
    if (err) {
        setFieldError("firstname", err);
        return false;
    }

    clearSingleFieldError("firstname");
    return true;
}

function checkMiddlename() {
    var el = document.getElementById("middlename");
    if (!el) return true;
    var val = el.value;
    if (val === "") {
        clearSingleFieldError("middlename");
        return true;
    }
    if (val.charAt(0) === ' ') {
        setFieldError("middlename", "The middlen initial should not be a space.");
        return false;
    }
    for (var i = 0; i < val.length; i++) {
        var char = val.charAt(i);
        if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
            setFieldError("middlename", "Middlen initial cannot contain more than one space.");
            return false;
        }
        if (i + 3 < val.length && /[a-zA-Z]/.test(char)) {
            if (char.toLowerCase() === val.charAt(i + 1).toLowerCase() && char.toLowerCase() === val.charAt(i + 2).toLowerCase() && char.toLowerCase() === val.charAt(i + 3).toLowerCase()) {
                setFieldError("middlename", "Middlen initial cannot contain repeated letters.");
                return false;
            }
        }
        if (/[0-9]/.test(char)) {
            setFieldError("middlename", "Middlen initial cannot contain numbers.");
            return false;
        }
        if (/[^a-zA-Z\s]/.test(char)) {
            setFieldError("middlename", "Middlen initial cannot contain special characters.");
            return false;
        }
        if (i === 0 && /[a-z]/.test(char)) {
            setFieldError("middlename", "The middlen initial should be capitalized.");
            return false;
        }
    }
    if (val.length > 25) {
        setFieldError("middlename", "Middlen initial should not exceed 25 characters.");
        return false;
    }
    clearSingleFieldError("middlename");
    return true;
}

function checkLastname(showEmpty) {
    var el = document.getElementById("lastname");
    if (!el) return true;
    var val = el.value;

    if (val === "") {
        if (showEmpty) {
            setFieldError("lastname", "Lastname fields must be filled in.");
            return false;
        }
        clearSingleFieldError("lastname");
        return false;
    }

    var err = scanNameField(val, "Lastname");
    if (err) {
        setFieldError("lastname", err);
        return false;
    }

    clearSingleFieldError("lastname");
    return true;
}

function checkSuffix(showEmpty) {
    var sel = document.getElementById("suffix");
    var custom = document.getElementById("customSuffix");
    if (!sel) return true;
    var val = sel.value;

    if (val !== "" && val !== " " && val !== "Jr" && val !== "Sr" && val !== "others") {
        setFieldError("suffix", "Please select a valid name suffix.");
        return false;
    }
    clearSingleFieldError("suffix");

    if (val === "others" && custom) {
        var cVal = custom.value;
        if (cVal.trim() === "") {
            if (showEmpty) {
                setFieldError("customSuffix", "Please specify your custom suffix.");
                return false;
            }
            clearSingleFieldError("customSuffix");
            return false;
        }
        if (cVal.charAt(0) === ' ') {
            setFieldError("customSuffix", "The first character of the Suffix should not be a space.");
            return false;
        }
        for (var i = 0; i < cVal.length; i++) {
            var char = cVal.charAt(i);
            if (char === ' ' && i + 1 < cVal.length && cVal.charAt(i + 1) === ' ') {
                setFieldError("customSuffix", "Suffix cannot contain double space.");
                return false;
            }
            if (i + 2 < cVal.length && /[a-zA-Z]/.test(char)) {
                if (char.toLowerCase() === cVal.charAt(i + 1).toLowerCase() && char.toLowerCase() === cVal.charAt(i + 2).toLowerCase()) {
                    setFieldError("customSuffix", "Suffix cannot contain three consecutive repeated letters.");
                    return false;
                }
            }
            if (/[0-9]/.test(char)) {
                setFieldError("customSuffix", "Suffix cannot contain numbers.");
                return false;
            }
            if (/[^a-zA-Z\s]/.test(char)) {
                setFieldError("customSuffix", "Suffix cannot contain special characters.");
                return false;
            }
        }
        if (cVal.length > 10) {
            setFieldError("customSuffix", "Suffix should not exceed 10 characters.");
            return false;
        }
        clearSingleFieldError("customSuffix");
    } else {
        clearSingleFieldError("customSuffix");
    }
    return true;
}

function checkSex(showEmpty) {
    var el = document.getElementById("sex");
    if (!el) return true;
    var val = el.value;
    if (val !== "Male" && val !== "Female") {
        if (showEmpty) {
            setFieldError("sex", "Please select a valid sex.");
            return false;
        }
        return false;
    }
    clearSingleFieldError("sex");
    return true;
}

function checkBirthdate(showEmpty) {
    var el = document.getElementById("birthdate");
    var ageEl = document.getElementById("age");
    if (!el) return true;
    calculateAge();
    var val = el.value;
    var ageVal = ageEl ? parseInt(ageEl.value, 10) : 0;

    if (val === "") {
        if (showEmpty) {
            setFieldError("birthdate", "Birthdate cannot be blank.");
            return false;
        }
        clearSingleFieldError("birthdate");
        return false;
    }
    if (!isValidDate(val)) {
        setFieldError("birthdate", "Invalid birthdate format.");
        return false;
    }
    if (isNaN(ageVal) || ageVal < 18) {
        setFieldError("birthdate", "Age must be at least 18.");
        return false;
    }
    if (ageVal > 120) {
        setFieldError("birthdate", "Age cannot exceed 120.");
        return false;
    }
    clearSingleFieldError("birthdate");
    return true;
}

// ---------------- Real-time Step 2 Field Validators ---------------- //

function scanAddressField(val, fieldLabel, minLen, maxLen) {
    if (val === "") return "";
    if (val.charAt(0) === ' ') {
        return "The first character of the " + fieldLabel.toLowerCase() + " should not be a space.";
    }
    for (var i = 0; i < val.length; i++) {
        var char = val.charAt(i);
        if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
            return fieldLabel + " cannot contain double space.";
        }
        if (i + 2 < val.length && /[a-zA-Z]/.test(char)) {
            if (char.toLowerCase() === val.charAt(i + 1).toLowerCase() && char.toLowerCase() === val.charAt(i + 2).toLowerCase()) {
                return fieldLabel + " cannot contain three consecutive repeated letters.";
            }
        }
        if (fieldLabel === "Barangay") {
            if (i === 0 && /[0-9]/.test(char) && /[A-Za-z]/.test(val)) {
                return "Barangay cannot start with a number followed by letters.";
            }
            if (/[^a-zA-Z0-9\s-]/.test(char)) {
                return "Barangay cannot contain special characters, except for a hyphen.";
            }
        } else {
            if (/[0-9]/.test(char)) {
                return fieldLabel + " cannot contain numbers.";
            }
            if (/[^a-zA-Z\s]/.test(char)) {
                return fieldLabel + " cannot contain special characters.";
            }
        }
        if (i === 0) {
            if (/[a-z]/.test(char)) {
                return "The first letter of " + fieldLabel.toLowerCase() + " should be capitalized.";
            }
        } else if (val.charAt(i - 1) === ' ') {
            if (/[a-z]/.test(char)) {
                return "The second word of " + fieldLabel + " should be capitalized.";
            }
        } else {
            if (/[A-Z]/.test(char)) {
                return "The first letter of " + fieldLabel.toLowerCase() + " should be the only capitalized.";
            }
        }
    }
    if (val.length < minLen) {
        return fieldLabel + " fields must be at least " + minLen + " characters.";
    }
    if (val.length > maxLen) {
        return fieldLabel + " should not exceed " + maxLen + " characters.";
    }
    return "";
}

function checkPurok(showEmpty) {
    var el = document.getElementById("purokstreet");
    if (!el) return true;
    var val = el.value;
    if (val === "") {
        if (showEmpty) {
            setFieldError("purokstreet", "Purok cannot be blank.");
            return false;
        }
        clearSingleFieldError("purokstreet");
        return false;
    }
    if (val.charAt(0) === ' ') {
        setFieldError("purokstreet", "Purok cannot start with a space.");
        return false;
    }
    for (var i = 0; i < val.length; i++) {
        var char = val.charAt(i);
        if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
            setFieldError("purokstreet", "Purok cannot contain double spaces.");
            return false;
        }
        if (/[^a-zA-Z0-9\s\-]/.test(char)) {
            setFieldError("purokstreet", "Purok can only contain letters, numbers, spaces, and hyphens ('-').");
            return false;
        }
        if (i + 2 < val.length && /[a-zA-Z]/.test(char)) {
            if (char.toLowerCase() === val.charAt(i + 1).toLowerCase() && char.toLowerCase() === val.charAt(i + 2).toLowerCase()) {
                setFieldError("purokstreet", "Purok cannot contain three consecutive repeated letters.");
                return false;
            }
        }
    }
    if (val.length > 25) {
        setFieldError("purokstreet", "Purok should not exceed 25 characters.");
        return false;
    }
    clearSingleFieldError("purokstreet");
    return true;
}

function checkBrgy(showEmpty) {
    var el = document.getElementById("brgy");
    if (!el) return true;
    var val = el.value;
    if (val === "") {
        if (showEmpty) {
            setFieldError("brgy", "Barangay cannot be blank.");
            return false;
        }
        clearSingleFieldError("brgy");
        return false;
    }
    var err = scanAddressField(val, "Barangay", 3, 25);
    if (err) {
        setFieldError("brgy", err);
        return false;
    }
    clearSingleFieldError("brgy");
    return true;
}

function checkMunicipal(showEmpty) {
    var el = document.getElementById("municipal");
    if (!el) return true;
    var val = el.value;
    if (val === "") {
        if (showEmpty) {
            setFieldError("municipal", "Municipal cannot be blank.");
            return false;
        }
        clearSingleFieldError("municipal");
        return false;
    }
    var err = scanAddressField(val, "Municipality", 3, 25);
    if (err) {
        setFieldError("municipal", err);
        return false;
    }
    clearSingleFieldError("municipal");
    return true;
}

function checkProvince(showEmpty) {
    var el = document.getElementById("province");
    if (!el) return true;
    var val = el.value;
    if (val === "") {
        if (showEmpty) {
            setFieldError("province", "Province cannot be blank.");
            return false;
        }
        clearSingleFieldError("province");
        return false;
    }
    var err = scanAddressField(val, "Province", 3, 25);
    if (err) {
        setFieldError("province", err);
        return false;
    }
    clearSingleFieldError("province");
    return true;
}

function checkCountry(showEmpty) {
    var el = document.getElementById("country");
    if (!el) return true;
    var val = el.value;
    if (val === "") {
        if (showEmpty) {
            setFieldError("country", "Country cannot be blank.");
            return false;
        }
        clearSingleFieldError("country");
        return false;
    }
    var err = scanAddressField(val, "Country", 8, 40);
    if (err) {
        setFieldError("country", err);
        return false;
    }
    clearSingleFieldError("country");
    return true;
}

function checkZipcode(showEmpty) {
    var el = document.getElementById("zipcode");
    if (!el) return true;
    var val = el.value;
    if (val === "") {
        if (showEmpty) {
            setFieldError("zipcode", "Zip Code cannot be blank.");
            return false;
        }
        clearSingleFieldError("zipcode");
        return false;
    }
    if (val.charAt(0) === ' ') {
        setFieldError("zipcode", "The first character of the Zip code should not be a space.");
        return false;
    }
    for (var i = 0; i < val.length; i++) {
        var char = val.charAt(i);
        if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
            setFieldError("zipcode", "Zip code cannot contain double space.");
            return false;
        }
        if (/[a-zA-Z]/.test(char)) {
            setFieldError("zipcode", "Zip code cannot contain letters.");
            return false;
        }
        if (/[^0-9a-zA-Z\s]/.test(char)) {
            setFieldError("zipcode", "Zip code cannot contain special characters.");
            return false;
        }
    }
    if (val.length < 4) {
        setFieldError("zipcode", "Zip code fields must be at least 4 characters.");
        return false;
    }
    if (val.length > 6) {
        setFieldError("zipcode", "Zip code should not exceed 6 characters.");
        return false;
    }
    clearSingleFieldError("zipcode");
    return true;
}

// ---------------- Real-time Step 3 Field Validators ---------------- //

function checkEmail(showEmpty) {
    var el = document.getElementById("email");
    if (!el) return true;
    var val = el.value.trim();
    if (val === "") {
        if (showEmpty) {
            setFieldError("email", "Email cannot be blank.");
            return false;
        }
        clearSingleFieldError("email");
        return false;
    }
    if (val.charAt(0) === ' ') {
        setFieldError("email", "The first character of the email should not be a space.");
        return false;
    }
    for (var i = 0; i < val.length; i++) {
        var char = val.charAt(i);
        if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
            setFieldError("email", "Email cannot contain double space.");
            return false;
        }
        if (/[A-Z]/.test(char)) {
            setFieldError("email", "Email must consist of lowercase letters only.");
            return false;
        }
        if (i + 2 < val.length && /[a-zA-Z]/.test(char)) {
            if (char.toLowerCase() === val.charAt(i + 1).toLowerCase() && char.toLowerCase() === val.charAt(i + 2).toLowerCase()) {
                setFieldError("email", "Email cannot contain three consecutive repeated letters.");
                return false;
            }
        }
        if (/[^a-zA-Z0-9@._-]/.test(char)) {
            setFieldError("email", "Email can only contain letters, numbers, '@', '.', '-', and '_'.");
            return false;
        }
    }
    if (!val.includes('@')) {
        setFieldError("email", "Email must contain an '@' symbol.");
        return false;
    }

    var parts = val.toLowerCase().split('@');
    if (parts.length === 2 && parts[1]) {
        var domain = parts[1];
        var isGmail = (domain === 'gmail.com');
        var isUniversity = /(^|\.)[a-z0-9-]+\.edu\.ph$/.test(domain) || /(^|\.)[a-z0-9-]+\.edu$/.test(domain);

        if (!isGmail && !isUniversity) {
            setFieldError("email", "Only Gmail (@gmail.com) and university email (@...edu.ph / @...edu) are allowed.");
            return false;
        }
    }

    if (!isValidEmail(val)) {
        setFieldError("email", "Please enter a valid email address.");
        return false;
    }
    clearSingleFieldError("email");
    return true;
}

// Async duplicate check for email (called on blur)
function checkEmailDuplicate() {
    var el = document.getElementById("email");
    if (!el) return Promise.resolve(true);
    var val = el.value.trim();
    if (!checkEmail(false)) return Promise.resolve(false);
    if (!val) return Promise.resolve(false);
    return fetch('api/check_duplicate.php?field=email&value=' + encodeURIComponent(val))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.exists) {
                setFieldError("email", "This email address is already registered.");
                return false;
            }
            clearSingleFieldError("email");
            return true;
        })
        .catch(function() { return true; });
}

function checkUsername(showEmpty) {
    var el = document.getElementById("username");
    if (!el) return true;
    var val = el.value;
    if (val === "") {
        if (showEmpty) {
            setFieldError("username", "Username cannot be blank.");
            return false;
        }
        clearSingleFieldError("username");
        return false;
    }
    if (val.charAt(0) === ' ') {
        setFieldError("username", "The first character of the Username should not be a space.");
        return false;
    }
    for (var i = 0; i < val.length; i++) {
        var char = val.charAt(i);
        if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
            setFieldError("username", "Username cannot contain double space.");
            return false;
        }
        if (char === ' ') {
            setFieldError("username", "Username cannot contain spaces.");
            return false;
        }
        if (/[A-Z]/.test(char)) {
            setFieldError("username", "Username must consist of lowercase letters only.");
            return false;
        }
        if (/[^a-zA-Z0-9@._\s]/.test(char)) {
            setFieldError("username", "Username can only contain letters, numbers, '@', '.', and '_'.");
            return false;
        }
        if (i + 2 < val.length && /[a-zA-Z]/.test(char)) {
            if (char.toLowerCase() === val.charAt(i + 1).toLowerCase() && char.toLowerCase() === val.charAt(i + 2).toLowerCase()) {
                setFieldError("username", "Username cannot contain three consecutive repeated letters.");
                return false;
            }
        }
        if (i === 0 && /[0-9]/.test(char) && /[A-Za-z]/.test(val)) {
            setFieldError("username", "Username cannot start with a number followed by letters.");
            return false;
        }
    }
    if (val.length < 6) {
        setFieldError("username", "Username must be at least 6 characters.");
        return false;
    }
    if (val.length > 15) {
        setFieldError("username", "Username should not exceed 15 characters.");
        return false;
    }
    clearSingleFieldError("username");
    return true;
}

// Async duplicate check for username (called on blur)
function checkUsernameDuplicate() {
    var el = document.getElementById("username");
    if (!el) return Promise.resolve(true);
    var val = el.value.trim();
    if (!checkUsername(false)) return Promise.resolve(false);
    if (!val) return Promise.resolve(false);
    return fetch('api/check_duplicate.php?field=username&value=' + encodeURIComponent(val))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.exists) {
                setFieldError("username", "This username is already taken.");
                return false;
            }
            clearSingleFieldError("username");
            return true;
        })
        .catch(function() { return true; });
}

function checkPassword(showEmpty) {
    var el = document.getElementById("password");
    if (!el) return true;
    var val = el.value;
    strongpassword(val);
    var pswrdStrength = document.getElementById("pswrd") ? document.getElementById("pswrd").innerHTML : "";

    if (val === "") {
        if (showEmpty) {
            setFieldError("password", "Password must be filled.");
            return false;
        }
        clearSingleFieldError("password");
        return false;
    }
    if (val === "Reniel123!") {
        setFieldError("password", "Password already exists");
        return false;
    }
    if (val.length > 15) {
        setFieldError("password", "Password should not exceed 15 characters.");
        return false;
    }
    if (pswrdStrength === "Weak password" || pswrdStrength === "Medium password") {
        setFieldError("password", "Password is not strong enough. Please choose a stronger password.");
        return false;
    }
    clearSingleFieldError("password");
    return true;
}

function checkConfirmPassword(showEmpty) {
    var pEl = document.getElementById("password");
    var cpEl = document.getElementById("confirmPassword");
    if (!cpEl) return true;
    var pVal = pEl ? pEl.value : "";
    var cpVal = cpEl.value;

    if (cpVal === "") {
        if (showEmpty) {
            setFieldError("confirmPassword", "Please confirm your password.");
            return false;
        }
        clearSingleFieldError("confirmPassword");
        return false;
    }
    if (pVal !== cpVal) {
        setFieldError("confirmPassword", "Passwords do not match.");
        return false;
    }
    clearSingleFieldError("confirmPassword");
    return true;
}

function checkDistinctSecurityQuestions(showEmpty) {
    var q1 = document.getElementById("security_question1");
    var q2 = document.getElementById("security_question2");
    var q3 = document.getElementById("security_question3");
    if (!q1 || !q2 || !q3) return true;
    var v1 = q1.value;
    var v2 = q2.value;
    var v3 = q3.value;

    if (v1 && v2 && v1 === v2) {
        if (showEmpty) setFieldError("security_answer2", "Question 2 must be different from Question 1.");
        return false;
    }
    if (v1 && v3 && v1 === v3) {
        if (showEmpty) setFieldError("security_answer3", "Question 3 must be different from Question 1.");
        return false;
    }
    if (v2 && v3 && v2 === v3) {
        if (showEmpty) setFieldError("security_answer3", "Question 3 must be different from Question 2.");
        return false;
    }
    return true;
}

function checkSecurityAnswer(num, showEmpty) {
    var qEl = document.getElementById("security_question" + num);
    var aEl = document.getElementById("security_answer" + num);
    if (!qEl || !aEl) return true;
    var qVal = qEl.value;
    var aVal = aEl.value.trim();

    if (qVal === "") {
        if (showEmpty) {
            setFieldError("security_answer" + num, "Please select Question " + num + ".");
            return false;
        }
        clearSingleFieldError("security_answer" + num);
        return false;
    }

    if (aVal === "") {
        if (showEmpty) {
            setFieldError("security_answer" + num, "Please provide an answer for Question " + num + ".");
            return false;
        }
        clearSingleFieldError("security_answer" + num);
        return false;
    }

    if (aVal.length < 2) {
        if (showEmpty) {
            setFieldError("security_answer" + num, "Answer for Question " + num + " must be at least 2 characters.");
            return false;
        }
        return false;
    }

    clearSingleFieldError("security_answer" + num);
    return true;
}

// ---------------- Step Validators (For NEXT buttons & Submit) ---------------- //

function validateStep1() {
    var vId = checkIdno(true);
    var vFn = checkFirstname(true);
    var vMn = checkMiddlename();
    var vLn = checkLastname(true);
    var vSuf = checkSuffix(true);
    var vSex = checkSex(true);
    var vBd = checkBirthdate(true);

    if (!vId) { document.getElementById("idno").focus(); return false; }
    if (!vFn) { document.getElementById("firstname").focus(); return false; }
    if (!vMn) { document.getElementById("middlename").focus(); return false; }
    if (!vLn) { document.getElementById("lastname").focus(); return false; }
    if (!vSuf) { return false; }
    if (!vSex) { document.getElementById("sex").focus(); return false; }
    if (!vBd) { document.getElementById("birthdate").focus(); return false; }

    return true;
}

function validateStep2() {
    var vP = checkPurok(true);
    var vB = checkBrgy(true);
    var vM = checkMunicipal(true);
    var vPr = checkProvince(true);
    var vC = checkCountry(true);
    var vZ = checkZipcode(true);

    if (!vP) { document.getElementById("purokstreet").focus(); return false; }
    if (!vB) { document.getElementById("brgy").focus(); return false; }
    if (!vM) { document.getElementById("municipal").focus(); return false; }
    if (!vPr) { document.getElementById("province").focus(); return false; }
    if (!vC) { document.getElementById("country").focus(); return false; }
    if (!vZ) { document.getElementById("zipcode").focus(); return false; }

    return true;
}

function validateStep3() {
    var vEm = checkEmail(true);
    var vUn = checkUsername(true);
    var vPw = checkPassword(true);
    var vCp = checkConfirmPassword(true);
    var vSq1 = checkSecurityAnswer(1, true);
    var vSq2 = checkSecurityAnswer(2, true);
    var vSq3 = checkSecurityAnswer(3, true);
    var vDistinct = checkDistinctSecurityQuestions(true);

    if (!vEm) { document.getElementById("email").focus(); return false; }
    if (!vUn) { document.getElementById("username").focus(); return false; }
    if (!vPw) { document.getElementById("password").focus(); return false; }
    if (!vCp) { document.getElementById("confirmPassword").focus(); return false; }
    if (!vSq1) { document.getElementById("security_answer1").focus(); return false; }
    if (!vSq2) { document.getElementById("security_answer2").focus(); return false; }
    if (!vSq3) { document.getElementById("security_answer3").focus(); return false; }
    if (!vDistinct) { return false; }

    // Check for error messages left by async duplicate checks
    var emailErr = document.getElementById("email_error");
    var userErr = document.getElementById("username_error");
    if (emailErr && emailErr.style.display === "block" && emailErr.textContent !== "") {
        document.getElementById("email").focus(); return false;
    }
    if (userErr && userErr.style.display === "block" && userErr.textContent !== "") {
        document.getElementById("username").focus(); return false;
    }

    return true;
}

function validateform() {
    if (!validateStep1()) {
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'none';
        document.getElementById('step1').style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return false;
    }
    if (!validateStep2()) {
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step3').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return false;
    }
    if (!validateStep3()) {
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'block';
        return false;
    }
    return true;
}

// ---------------- Initialize Live Input Listeners ---------------- //

document.addEventListener('DOMContentLoaded', function () {
    var customSuffixInput = document.getElementById("customSuffix");
    if (customSuffixInput) {
        customSuffixInput.addEventListener("input", function () {
            this.value = this.value.toUpperCase();
            checkSuffix(false);
        });
    }
    toggleCustomSuffix();

    // Step 1 live listeners
    var idnoInput = document.getElementById("idno");
    if (idnoInput) {
        idnoInput.addEventListener("input", function () { checkIdno(false); });
        idnoInput.addEventListener("blur", function () { checkIdno(true); });
    }

    var fnInput = document.getElementById("firstname");
    if (fnInput) {
        fnInput.addEventListener("input", function () { checkFirstname(false); });
        fnInput.addEventListener("blur", function () { checkFirstname(true); });
    }

    var mnInput = document.getElementById("middlename");
    if (mnInput) {
        mnInput.addEventListener("input", function () { checkMiddlename(); });
    }

    var lnInput = document.getElementById("lastname");
    if (lnInput) {
        lnInput.addEventListener("input", function () { checkLastname(false); });
        lnInput.addEventListener("blur", function () { checkLastname(true); });
    }

    var suffixSelect = document.getElementById("suffix");
    if (suffixSelect) {
        suffixSelect.addEventListener("change", function () { checkSuffix(false); });
    }

    var sexSelect = document.getElementById("sex");
    if (sexSelect) {
        sexSelect.addEventListener("change", function () { checkSex(false); });
    }

    var bdInput = document.getElementById("birthdate");
    if (bdInput) {
        bdInput.addEventListener("input", function () { checkBirthdate(false); });
        bdInput.addEventListener("change", function () { checkBirthdate(false); });
    }

    // Step 2 live listeners
    var purokInput = document.getElementById("purokstreet");
    if (purokInput) {
        purokInput.addEventListener("input", function () { checkPurok(false); });
        purokInput.addEventListener("blur", function () { checkPurok(true); });
    }

    var brgyInput = document.getElementById("brgy");
    if (brgyInput) {
        brgyInput.addEventListener("input", function () { checkBrgy(false); });
        brgyInput.addEventListener("blur", function () { checkBrgy(true); });
    }

    var municipalInput = document.getElementById("municipal");
    if (municipalInput) {
        municipalInput.addEventListener("input", function () { checkMunicipal(false); });
        municipalInput.addEventListener("blur", function () { checkMunicipal(true); });
    }

    var provinceInput = document.getElementById("province");
    if (provinceInput) {
        provinceInput.addEventListener("input", function () { checkProvince(false); });
        provinceInput.addEventListener("blur", function () { checkProvince(true); });
    }

    var countryInput = document.getElementById("country");
    if (countryInput) {
        countryInput.addEventListener("input", function () { checkCountry(false); });
        countryInput.addEventListener("blur", function () { checkCountry(true); });
    }

    var zipInput = document.getElementById("zipcode");
    if (zipInput) {
        zipInput.addEventListener("input", function () { checkZipcode(false); });
        zipInput.addEventListener("blur", function () { checkZipcode(true); });
    }

    // Step 3 live listeners
    var emailInput = document.getElementById("email");
    if (emailInput) {
        emailInput.addEventListener("input", function () { checkEmail(false); });
        emailInput.addEventListener("blur", function () {
            // First run format validation
            if (!checkEmail(true)) return;
            // Then async duplicate check
            checkEmailDuplicate();
        });
    }

    var usernameInput = document.getElementById("username");
    if (usernameInput) {
        usernameInput.addEventListener("input", function () { checkUsername(false); });
        usernameInput.addEventListener("blur", function () {
            // First run format validation
            if (!checkUsername(true)) return;
            // Then async duplicate check
            checkUsernameDuplicate();
        });
    }

    var pwInput = document.getElementById("password");
    if (pwInput) {
        pwInput.addEventListener("input", function () {
            checkPassword(false);
            checkConfirmPassword(false);
        });
        pwInput.addEventListener("blur", function () { checkPassword(true); });
    }

    var cpwInput = document.getElementById("confirmPassword");
    if (cpwInput) {
        cpwInput.addEventListener("input", function () { checkConfirmPassword(false); });
        cpwInput.addEventListener("blur", function () { checkConfirmPassword(true); });
    }

    for (var s = 1; s <= 3; s++) {
        (function (num) {
            var q = document.getElementById("security_question" + num);
            var a = document.getElementById("security_answer" + num);
            if (q) q.addEventListener("change", function () {
                checkSecurityAnswer(num, false);
                checkDistinctSecurityQuestions(true);
            });
            if (a) a.addEventListener("input", function () { checkSecurityAnswer(num, false); });
        })(s);
    }
});
