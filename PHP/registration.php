<?php
session_start();

include 'db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to generate the next sequential ID No (e.g., 2026-0001)
function generateNextIdNo($conn) {
    $currentYear = date('Y');
    $prefix = $currentYear . '-';
    $query = "SELECT idno FROM user WHERE idno LIKE '" . mysqli_real_escape_string($conn, $prefix) . "%'";
    $result = mysqli_query($conn, $query);
    $maxNum = 0;
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $parts = explode('-', $row['idno']);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $num = intval($parts[1]);
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
    }
    $nextNum = $maxNum + 1;
    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

// Initialize variables to hold form data
$idno = $firstname = $middlename = $lastname = $suffix = $birthdate = $age = $sex = $country = $purokstreet = $brgy = $municipal = $province = $zipcode = $username = $email = "";

// Process the form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Store input in variables
    $idno = isset($_POST["idno"]) ? trim(mysqli_real_escape_string($conn, $_POST["idno"])) : '';
    
    // Auto-generate ID if empty or if submitted ID is invalid / already taken
    if (empty($idno) || !preg_match('/^\d{4}-\d{4}$/', $idno)) {
        $idno = generateNextIdNo($conn);
    } else {
        $exist_idno = mysqli_query($conn, "SELECT id FROM user WHERE `idno` = '$idno'");
        if (mysqli_num_rows($exist_idno) > 0) {
            $idno = generateNextIdNo($conn);
        }
    }

    $firstname = mysqli_real_escape_string($conn, $_POST["firstname"]);
    $middlename = mysqli_real_escape_string($conn, $_POST["middlename"]);
    $lastname = mysqli_real_escape_string($conn, $_POST["lastname"]);
    $suffix = mysqli_real_escape_string($conn, $_POST["suffix"]);
    $birthdate = mysqli_real_escape_string($conn, $_POST["birthdate"]);
    $age = mysqli_real_escape_string($conn, $_POST["age"]);
    $sex = mysqli_real_escape_string($conn, $_POST["sex"]);
    $country = mysqli_real_escape_string($conn, $_POST["country"]);
    $purokstreet = mysqli_real_escape_string($conn, $_POST["purokstreet"]);
    $brgy = mysqli_real_escape_string($conn, $_POST["brgy"]);
    $municipal = mysqli_real_escape_string($conn, $_POST["municipal"]);
    $province = mysqli_real_escape_string($conn, $_POST["province"]);
    $zipcode = mysqli_real_escape_string($conn, $_POST["zipcode"]);
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Security Questions
    $security_question1 = mysqli_real_escape_string($conn, $_POST['security_question1']);
    $security_answer1 = mysqli_real_escape_string($conn, $_POST['security_answer1']);
    $security_question2 = mysqli_real_escape_string($conn, $_POST['security_question2']);
    $security_answer2 = mysqli_real_escape_string($conn, $_POST['security_answer2']);
    $security_question3 = mysqli_real_escape_string($conn, $_POST['security_question3']);
    $security_answer3 = mysqli_real_escape_string($conn, $_POST['security_answer3']);

    // Save the form inputs to the session
    $_SESSION['form_data'] = $_POST;
    $_SESSION['form_data']['idno'] = $idno;

    // Check for existing username and email in DB
    $exist_username = mysqli_query($conn, "SELECT id FROM user WHERE `username` = '$username'");
    $exist_email = mysqli_query($conn, "SELECT id FROM user WHERE `email` = '$email'");

    if (mysqli_num_rows($exist_username) > 0) {
        echo "<script>alert('Username already exists!');</script>";
        echo "<script>location.href='registration.php'</script>";
    } elseif (mysqli_num_rows($exist_email) > 0) {
        echo "<script>alert('Email already exists!');</script>";
        echo "<script>location.href='registration.php'</script>";
    } else {
        $suffixToUse = ($suffix === "others") ? $_POST["customSuffix"] : $suffix;

        $sql = "INSERT INTO user (idno, firstname, middlename, lastname, suffix, birthdate, age, sex, email, username, country, purokstreet, brgy, municipal, province, password, zipcode, security_question1, security_answer1, security_question2, security_answer2, security_question3, security_answer3, role, status)   
                VALUES ('$idno', '$firstname', '$middlename', '$lastname', '$suffixToUse', '$birthdate', '$age', '$sex', '$email', '$username', '$country', '$purokstreet', '$brgy', '$municipal', '$province', '$password', '$zipcode', '$security_question1', '$security_answer1', '$security_question2', '$security_answer2', '$security_question3', '$security_answer3', 'user', 'active')";

        $result = mysqli_query($conn, $sql);

        if ($result) {
            unset($_SESSION['form_data']);
            echo "<script>alert('You are Successfully Registered! Your ID No is: " . $idno . "');</script>";
            echo "<script>location.href='login.php'</script>";
        } else {
            die(mysqli_error($conn));
        }
    }
}

// Generate the next ID number for page display
$auto_idno = generateNextIdNo($conn);
if (isset($_SESSION['form_data']['idno']) && !empty($_SESSION['form_data']['idno']) && preg_match('/^\d{4}-\d{4}$/', $_SESSION['form_data']['idno'])) {
    $check_sess_id = mysqli_query($conn, "SELECT id FROM user WHERE idno = '" . mysqli_real_escape_string($conn, $_SESSION['form_data']['idno']) . "'");
    if (mysqli_num_rows($check_sess_id) === 0) {
        $auto_idno = $_SESSION['form_data']['idno'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form - MOTOPARTS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent-orange: #ff5500;
            --accent-orange-hover: #ff6a1a;
            --accent-orange-glow: rgba(255, 85, 0, 0.35);
            --dark-bg: #0c0f14;
            --card-bg: #12161f;
            --input-bg: #151a24;
            --input-border: rgba(255, 255, 255, 0.1);
            --input-border-focus: #ff5500;
            --text-main: #f8fafc;
            --text-muted: #8492a6;
            --text-label: #cbd5e1;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            min-height: 100%;
        }

        body {
            font-family: var(--font-main);
            background-color: var(--dark-bg);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-image:
                linear-gradient(rgba(12, 15, 20, 0.94), rgba(12, 15, 20, 0.96)),
                url('../img/moto-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Top Navbar */
        .navbar {
            background: rgba(14, 18, 26, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding: 0.85rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navdiv {
            max-width: 1060px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-link {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
        }

        .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .logo-text {
            font-family: var(--font-heading);
            font-size: 1.45rem;
            letter-spacing: 0.5px;
            line-height: 1;
        }

        .logo-text .moto {
            font-weight: 900;
            color: #ffffff;
        }

        .logo-text .parts {
            font-weight: 800;
            color: var(--accent-orange);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-nav-home {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 1.25rem;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-nav-home:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.25);
        }

        .btn-nav-login {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 1.35rem;
            border-radius: 25px;
            background: linear-gradient(135deg, #ff5500 0%, #e64a00 100%);
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            box-shadow: 0 4px 15px rgba(255, 85, 0, 0.35);
            transition: all 0.2s ease;
        }

        .btn-nav-login:hover {
            background: linear-gradient(135deg, #ff661a 0%, #eb4d00 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255, 85, 0, 0.5);
        }

        /* Form Wrapper & Content */
        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem 1.5rem;
            overflow: hidden;
        }

        .form-card {
            background: rgba(18, 22, 31, 0.92);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 1.5rem 2.25rem;
            width: 100%;
            max-width: 900px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        /* Header block with orange vertical bar */
        .form-header-row {
            margin-bottom: 1rem;
        }

        .form-title-group {
            display: flex;
            flex-direction: column;
        }

        .title-with-bar {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 0.35rem;
        }

        .orange-bar {
            width: 5px;
            height: 32px;
            background: var(--accent-orange);
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(255, 85, 0, 0.5);
        }

        .form-title {
            font-family: var(--font-heading);
            font-size: 1.75rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .form-subtitle {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin-left: calc(5px + 0.85rem);
            font-weight: 400;
        }

        /* Section divider header */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0.6rem 0 0.85rem;
            position: relative;
        }

        .section-bullet {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--accent-orange);
            box-shadow: 0 0 8px var(--accent-orange);
            flex-shrink: 0;
        }

        .section-title-text {
            font-family: var(--font-heading);
            font-size: 0.92rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #ffffff;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .section-line {
            flex: 1;
            height: 1px;
            background: repeating-linear-gradient(to right, rgba(255, 255, 255, 0.15) 0, rgba(255, 255, 255, 0.15) 4px, transparent 4px, transparent 8px);
        }

        /* Input Grid */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem 1.5rem;
            margin-bottom: 0;
        }

        .grid-container-3col {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.65rem 1rem;
            margin-bottom: 0;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.73rem;
            font-weight: 700;
            color: var(--text-label);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 0.3rem;
        }

        .req-star {
            color: var(--accent-orange);
            font-weight: 800;
        }

        .label-hint {
            font-size: 0.72rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: none;
            letter-spacing: normal;
        }

        /* Custom Inputs */
        .input-control,
        select.input-control {
            width: 100%;
            height: 40px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            padding: 0 0.9rem;
            color: #f8fafc;
            font-family: var(--font-main);
            font-size: 0.9rem;
            transition: all 0.2s ease;
            box-sizing: border-box;
            outline: none;
        }

        .input-control::placeholder {
            color: #475569;
            font-weight: 400;
        }

        .input-control:focus,
        select.input-control:focus {
            border-color: var(--input-border-focus);
            box-shadow: 0 0 0 3px var(--accent-orange-glow);
            background: #181f2c;
        }

        /* Readonly ID NO & Age */
        .input-idno {
            color: #ff9900 !important;
            font-weight: 800 !important;
            letter-spacing: 1px;
            background: #0f131a !important;
            cursor: not-allowed;
            border-color: rgba(255, 153, 0, 0.25) !important;
        }

        .age-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .age-input-wrapper .input-control {
            padding-right: 3.5rem;
            background: #0f131a !important;
            cursor: not-allowed;
            color: #cbd5e1;
            font-weight: 600;
        }

        .age-unit-suffix {
            position: absolute;
            right: 1rem;
            font-size: 0.82rem;
            font-weight: 800;
            color: var(--text-muted);
            letter-spacing: 1px;
            pointer-events: none;
        }

        /* Custom Select Styling */
        select.input-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238492a6' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 15px;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        select.input-control option {
            background-color: #12161f;
            color: #ffffff;
            padding: 0.5rem;
        }

        /* Date input icon */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.7);
            cursor: pointer;
        }

        /* Under-field Real-Time Error Message */
        .error-msg {
            color: #ff4d4d;
            font-size: 0.76rem;
            font-weight: 600;
            display: none;
            margin-top: 4px;
            line-height: 1.2;
            animation: fadeInMsg 0.2s ease;
        }

        @keyframes fadeInMsg {
            from { opacity: 0; transform: translateY(-2px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-error-highlight {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.35) !important;
            background: rgba(45, 15, 20, 0.7) !important;
        }

        /* Step Action Buttons */
        .step-button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-step-next,
        .btn-step-submit {
            flex: 1;
            height: 42px;
            background: linear-gradient(135deg, #ff5500 0%, #e04400 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 6px 20px rgba(255, 85, 0, 0.35);
            transition: all 0.25s ease;
        }

        .btn-step-next:hover,
        .btn-step-submit:hover {
            background: linear-gradient(135deg, #ff661a 0%, #eb4d00 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(255, 85, 0, 0.5);
        }

        .btn-step-back {
            width: 120px;
            height: 42px;
            background: rgba(255, 255, 255, 0.06);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }

        .btn-step-back:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.25);
        }

        .auth-footer-link {
            text-align: center;
            margin-top: 0.75rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .auth-footer-link a {
            color: var(--accent-orange);
            text-decoration: none;
            font-weight: 700;
            margin-left: 0.3rem;
            transition: color 0.2s;
        }

        .auth-footer-link a:hover {
            color: #ff7733;
            text-decoration: underline;
        }

        /* Footer */
        footer {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            text-align: center;
            color: #64748b;
            font-size: 0.88rem;
            margin-top: auto;
        }

        footer a {
            color: #64748b;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        footer a:hover {
            color: var(--accent-orange);
        }

        @media (max-width: 768px) {
            .form-card {
                padding: 1.5rem;
            }
            .grid-container,
            .grid-container-3col {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .form-title {
                font-size: 1.75rem;
            }
            .form-header-row {
                flex-direction: column-reverse;
                gap: 1rem;
            }
            .progress-ring-badge {
                align-self: flex-end;
            }
        }
    </style>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar">
        <div class="navdiv">
            <a href="homepage.php" class="logo-link">
                <div class="logo-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18.5" cy="17.5" r="3.5"/>
                        <circle cx="5.5" cy="17.5" r="3.5"/>
                        <circle cx="15" cy="5" r="1"/>
                        <path d="M12 17.5V14l-3-3 4-3 2 3h2"/>
                    </svg>
                </div>
                <div class="logo-text">
                    <span class="moto">MOTO</span><span class="parts">PARTS</span>
                </div>
            </a>
            <div class="nav-actions">
                <a href="homepage.php" class="btn-nav-home">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Home
                </a>
                <a href="login.php" class="btn-nav-login">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <div class="form-card">
            
            <!-- Form Title -->
            <div class="form-header-row">
                <div class="form-title-group">
                    <div class="title-with-bar">
                        <span class="orange-bar"></span>
                        <h1 class="form-title">Registration form</h1>
                    </div>
                    <p class="form-subtitle">New rider paperwork — takes about 2 minutes</p>
                </div>
            </div>

            <form name="myform" action="" method="post" onsubmit="return validateform()" id="signupForm" novalidate>

                <!-- Step 1: Personal Information -->
                <div id="step1">
                    <div class="section-header">
                        <span class="section-bullet"></span>
                        <span class="section-title-text">Personal Information</span>
                        <span class="section-line"></span>
                    </div>

                    <div class="grid-container">
                        <div class="form-group">
                            <label class="form-label" for="idno">
                                ID NO. <span class="label-hint">(auto-generated)</span>
                            </label>
                            <input type="text" name="idno" id="idno" class="input-control input-idno"
                                value="<?php echo htmlspecialchars($auto_idno); ?>"
                                readonly autocomplete="off">
                            <small class="error-msg" id="idno_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="firstname">
                                First Name <span class="req-star">*</span>
                            </label>
                            <input type="text" name="firstname" id="firstname" class="input-control"
                                placeholder="Diego"
                                value="<?php echo isset($_SESSION['form_data']['firstname']) ? htmlspecialchars($_SESSION['form_data']['firstname']) : ''; ?>"
                                maxlength="25">
                            <small class="error-msg" id="firstname_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="middlename">
                                Middle Initial <span class="label-hint">(optional)</span>
                            </label>
                            <input type="text" name="middlename" id="middlename" class="input-control"
                                placeholder="M"
                                value="<?php echo isset($_SESSION['form_data']['middlename']) ? htmlspecialchars($_SESSION['form_data']['middlename']) : ''; ?>"
                                maxlength="25">
                            <small class="error-msg" id="middlename_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="lastname">
                                Last Name <span class="req-star">*</span>
                            </label>
                            <input type="text" name="lastname" id="lastname" class="input-control"
                                placeholder="Rossi"
                                value="<?php echo isset($_SESSION['form_data']['lastname']) ? htmlspecialchars($_SESSION['form_data']['lastname']) : ''; ?>"
                                maxlength="25">
                            <small class="error-msg" id="lastname_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="suffix">
                                Suffix <span class="label-hint">(optional)</span>
                            </label>
                            <select name="suffix" id="suffix" class="input-control" onchange="toggleCustomSuffix();">
                                <option value=" ">Select suffix</option>
                                <option value="Sr" <?php echo (isset($_SESSION['form_data']['suffix']) && $_SESSION['form_data']['suffix'] == 'Sr') ? 'selected' : ''; ?>>Sr.</option>
                                <option value="Jr" <?php echo (isset($_SESSION['form_data']['suffix']) && $_SESSION['form_data']['suffix'] == 'Jr') ? 'selected' : ''; ?>>Jr.</option>
                                <option value="others" <?php echo (isset($_SESSION['form_data']['suffix']) && $_SESSION['form_data']['suffix'] == 'others') ? 'selected' : ''; ?>>Others</option>
                            </select>
                            <input type="text" name="customSuffix" id="customSuffix" class="input-control"
                                value="<?php echo isset($_SESSION['form_data']['customSuffix']) ? htmlspecialchars($_SESSION['form_data']['customSuffix']) : ''; ?>"
                                style="display: none; margin-top: 8px;" placeholder="Specify suffix">
                            <small class="error-msg" id="suffix_error"></small>
                            <small class="error-msg" id="customSuffix_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="sex">
                                Sex <span class="req-star">*</span>
                            </label>
                            <select name="sex" id="sex" class="input-control">
                                <option value="" <?php echo (empty($_SESSION['form_data']['sex']) || $_SESSION['form_data']['sex'] == 'Select Sex') ? 'selected' : ''; ?>>Select sex</option>
                                <option value="Male" <?php echo (isset($_SESSION['form_data']['sex']) && $_SESSION['form_data']['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_SESSION['form_data']['sex']) && $_SESSION['form_data']['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                            <small class="error-msg" id="sex_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="birthdate">
                                Birthdate <span class="req-star">*</span>
                            </label>
                            <input type="date" name="birthdate" id="birthdate" class="input-control"
                                value="<?php echo isset($_SESSION['form_data']['birthdate']) ? htmlspecialchars($_SESSION['form_data']['birthdate']) : ''; ?>"
                                oninput="calculateAge()">
                            <small class="error-msg" id="birthdate_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="age">
                                Age
                            </label>
                            <div class="age-input-wrapper">
                                <input type="text" name="age" id="age" class="input-control"
                                    value="<?php echo isset($_SESSION['form_data']['age']) ? htmlspecialchars($_SESSION['form_data']['age']) : ''; ?>"
                                    placeholder="—" readonly>
                                <span class="age-unit-suffix">YRS</span>
                            </div>
                            <small class="error-msg" id="age_error"></small>
                        </div>
                    </div>

                    <div class="step-button-group">
                        <button type="button" class="btn-step-next" onclick="nextStep(1)">
                            Next Step
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Address Information -->
                <div id="step2" style="display: none;">
                    <div class="section-header">
                        <span class="section-bullet"></span>
                        <span class="section-title-text">Address Information</span>
                        <span class="section-line"></span>
                    </div>

                    <div class="grid-container">
                        <div class="form-group">
                            <label class="form-label" for="purokstreet">
                                Purok / Street <span class="req-star">*</span>
                            </label>
                            <input type="text" name="purokstreet" id="purokstreet" class="input-control"
                                placeholder="e.g. Purok 1A"
                                value="<?php echo isset($_SESSION['form_data']['purokstreet']) ? htmlspecialchars($_SESSION['form_data']['purokstreet']) : ''; ?>"
                                maxlength="25">
                            <small class="error-msg" id="purokstreet_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="brgy">
                                Barangay <span class="req-star">*</span>
                            </label>
                            <input type="text" name="brgy" id="brgy" class="input-control"
                                placeholder="Barangay name"
                                value="<?php echo isset($_SESSION['form_data']['brgy']) ? htmlspecialchars($_SESSION['form_data']['brgy']) : ''; ?>"
                                maxlength="25">
                            <small class="error-msg" id="brgy_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="municipal">
                                Municipality / City <span class="req-star">*</span>
                            </label>
                            <input type="text" name="municipal" id="municipal" class="input-control"
                                placeholder="City / Municipality"
                                value="<?php echo isset($_SESSION['form_data']['municipal']) ? htmlspecialchars($_SESSION['form_data']['municipal']) : ''; ?>"
                                maxlength="25">
                            <small class="error-msg" id="municipal_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="province">
                                Province <span class="req-star">*</span>
                            </label>
                            <input type="text" name="province" id="province" class="input-control"
                                placeholder="Province"
                                value="<?php echo isset($_SESSION['form_data']['province']) ? htmlspecialchars($_SESSION['form_data']['province']) : ''; ?>"
                                maxlength="25">
                            <small class="error-msg" id="province_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="country">
                                Country <span class="req-star">*</span>
                            </label>
                            <input type="text" name="country" id="country" class="input-control"
                                placeholder="Philippines"
                                value="<?php echo isset($_SESSION['form_data']['country']) ? htmlspecialchars($_SESSION['form_data']['country']) : 'Philippines'; ?>"
                                maxlength="40">
                            <small class="error-msg" id="country_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="zipcode">
                                Zip Code <span class="req-star">*</span>
                            </label>
                            <input type="text" name="zipcode" id="zipcode" class="input-control"
                                placeholder="e.g. 8600"
                                value="<?php echo isset($_SESSION['form_data']['zipcode']) ? htmlspecialchars($_SESSION['form_data']['zipcode']) : ''; ?>"
                                maxlength="6">
                            <small class="error-msg" id="zipcode_error"></small>
                        </div>
                    </div>

                    <div class="step-button-group">
                        <button type="button" class="btn-step-back" onclick="prevStep(2)">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Back
                        </button>
                        <button type="button" class="btn-step-next" onclick="nextStep(2)">
                            Next Step
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Account and Security Information -->
                <div id="step3" style="display: none;">
                    <div class="section-header">
                        <span class="section-bullet"></span>
                        <span class="section-title-text">Account Credentials</span>
                        <span class="section-line"></span>
                    </div>

                    <div class="grid-container">
                        <div class="form-group">
                            <label class="form-label" for="email">
                                Email Address <span class="req-star">*</span>
                            </label>
                            <input type="email" name="email" id="email" class="input-control"
                                placeholder="yourname@domain.com"
                                value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">
                            <small class="error-msg" id="email_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="username">
                                Username <span class="req-star">*</span>
                            </label>
                            <input type="text" name="username" id="username" class="input-control"
                                placeholder="username"
                                value="<?php echo isset($_SESSION['form_data']['username']) ? htmlspecialchars($_SESSION['form_data']['username']) : ''; ?>"
                                maxlength="15">
                            <small class="error-msg" id="username_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">
                                Password <span class="req-star">*</span>
                            </label>
                            <input type="password" name="password" id="password" class="input-control"
                                placeholder="••••••••"
                                value="<?php echo isset($_SESSION['form_data']['password']) ? htmlspecialchars($_SESSION['form_data']['password']) : ''; ?>"
                                onkeyup="strongpassword(this.value)">
                            <div id="pswrd" class="error" style="height: 18px; font-size: 0.8rem; margin-top: 3px;"></div>
                            <small class="error-msg" id="password_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirmPassword">
                                Confirm Password <span class="req-star">*</span>
                            </label>
                            <input type="password" name="confirmPassword" id="confirmPassword" class="input-control"
                                placeholder="••••••••"
                                value="<?php echo isset($_SESSION['form_data']['confirmPassword']) ? htmlspecialchars($_SESSION['form_data']['confirmPassword']) : ''; ?>">
                            <small class="error-msg" id="confirmPassword_error"></small>
                        </div>
                    </div>

                    <!-- Security Questions -->
                    <div class="section-header" style="margin-top: 1.5rem;">
                        <span class="section-bullet"></span>
                        <span class="section-title-text">Security Questions</span>
                        <span class="section-line"></span>
                    </div>

                    <div class="grid-container-3col">
                        <div class="form-group">
                            <label class="form-label" for="security_question1">
                                Question 1 <span class="req-star">*</span>
                            </label>
                            <select name="security_question1" id="security_question1" class="input-control">
                                <option value="">Select Question 1</option>
                                <option value="What is your mother's maiden name?">Mother's maiden name?</option>
                                <option value="What was the name of your first pet?">First pet's name?</option>
                                <option value="What is the name of the city where you were born?">City born in?</option>
                                <option value="What is your favorite food?">Favorite food?</option>
                            </select>
                            <input type="password" name="security_answer1" id="security_answer1" class="input-control"
                                placeholder="Answer 1" style="margin-top: 8px;">
                            <small class="error-msg" id="security_answer1_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="security_question2">
                                Question 2 <span class="req-star">*</span>
                            </label>
                            <select name="security_question2" id="security_question2" class="input-control">
                                <option value="">Select Question 2</option>
                                <option value="What is the name of your favorite teacher?">Favorite teacher?</option>
                                <option value="What is your favorite movie?">Favorite movie?</option>
                                <option value="What is the make of your first car?">First car make?</option>
                                <option value="What is your lucky number?">Lucky number?</option>
                            </select>
                            <input type="password" name="security_answer2" id="security_answer2" class="input-control"
                                placeholder="Answer 2" style="margin-top: 8px;">
                            <small class="error-msg" id="security_answer2_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="security_question3">
                                Question 3 <span class="req-star">*</span>
                            </label>
                            <select name="security_question3" id="security_question3" class="input-control">
                                <option value="">Select Question 3</option>
                                <option value="What is your favorite color?">Favorite color?</option>
                                <option value="What is the name of your best friend?">Best friend's name?</option>
                                <option value="What is your dream job?">Dream job?</option>
                                <option value="What is your favorite sports team?">Favorite sports team?</option>
                            </select>
                            <input type="password" name="security_answer3" id="security_answer3" class="input-control"
                                placeholder="Answer 3" style="margin-top: 8px;">
                            <small class="error-msg" id="security_answer3_error"></small>
                        </div>
                    </div>

                    <div class="step-button-group">
                        <button type="button" class="btn-step-back" onclick="prevStep(3)">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Back
                        </button>
                        <button class="btn-step-submit" type="submit">
                            Complete Registration
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="auth-footer-link">
                    Already have an account? <a href="login.php">Log in</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Moto Shop. All rights reserved.</p>
        <p style="margin-top: 0.35rem;"><a href="#">Privacy Policy</a></p>
    </footer>

    <script>
        <?php include __DIR__ . '/../js/signup.js'; ?>


        function nextStep(step) {
            if (step === 1) {
                if (!validateStep1()) {
                    return false;
                }
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else if (step === 2) {
                if (!validateStep2()) {
                    return false;
                }
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function prevStep(step) {
            if (step === 2) {
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step1').style.display = 'block';
            } else if (step === 3) {
                document.getElementById('step3').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>

</html>