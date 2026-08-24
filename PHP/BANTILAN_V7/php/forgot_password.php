<?php
session_start();
include("db.php");

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error_message = "";
$success_message = "";

// Step 1: Enter Username/Email
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_username'])) {
    $username = trim($_POST['username']);

    if (!empty($username)) {
        $query = "SELECT * FROM user WHERE username = ? OR email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            $_SESSION['reset_user_id'] = $user_data['id'];
            $_SESSION['reset_username'] = $user_data['username'];
            $_SESSION['reset_email'] = $user_data['email'];
            header("Location: forgot_password.php?step=2");
            die;
        } else {
            $error_message = "Username or email not found.";
        }
    } else {
        $error_message = "Please enter your username or email.";
    }
}

// Step 2: Answer Security Questions
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_questions'])) {
    if (!isset($_SESSION['reset_user_id'])) {
        header("Location: forgot_password.php?step=1");
        die;
    }

    $answer1 = trim($_POST['answer1']);
    $answer2 = trim($_POST['answer2']);
    $answer3 = trim($_POST['answer3']);

    $user_id = $_SESSION['reset_user_id'];
    $query = "SELECT security_question1, security_answer1, security_question2, security_answer2, security_question3, security_answer3 FROM user WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);

    $all_correct = true;
    $questions_found = false;

    // Dynamic verification loop
    for ($i = 1; $i <= 3; $i++) {
        $q_col = "security_question" . $i;
        $a_col = "security_answer" . $i;

        // If the question exists in DB
        if (!empty($user_data[$q_col])) {
            $questions_found = true;

            // Validate Question Selection
            $user_selected_q = $_POST['question' . $i];
            $stored_q = $user_data[$q_col];
            if ($user_selected_q !== $stored_q) {
                $all_correct = false;
                break;
            }

            $user_answer = trim($_POST['answer' . $i]);
            $correct_answer = $user_data[$a_col];

            if (strtolower($user_answer) !== strtolower($correct_answer)) {
                $all_correct = false;
                break;
            }
        }
    }

    // If questions answered correctly
    if ($questions_found && $all_correct) {
        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 120; // OTP valid for 2 minutes

        // Send OTP via Gmail SMTP
        $to = $_SESSION['reset_email'];
        $subject = "Password Reset OTP - MOTOPARTS";

        // HTML email content
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; background: #0c0f14; color: #ffffff; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #12161f; border-radius: 12px; padding: 25px; border: 1px solid #2d3748; }
                .header { text-align: center; margin-bottom: 20px; }
                .brand { font-size: 24px; font-weight: bold; color: #ff5500; }
                .otp-box { background: rgba(255, 85, 0, 0.1); border: 2px solid #ff5500; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .otp-code { font-size: 32px; font-weight: 800; color: #ff5500; letter-spacing: 6px; }
                .footer { margin-top: 30px; font-size: 12px; color: #8492a6; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='brand'>MOTOPARTS</div>
                    <h2>Password Reset Request</h2>
                </div>
                <p>Dear " . htmlspecialchars($_SESSION['reset_username']) . ",</p>
                <p>You have requested to reset your password. Please use the OTP below to complete the verification:</p>
                
                <div class='otp-box'>
                    <div class='otp-code'>" . $otp . "</div>
                </div>
                
                <p><strong>⚠️ This OTP will expire in 2 minutes.</strong></p>
                <p>If you did not request this password reset, please ignore this email and your account password will remain unchanged.</p>
                
                <div class='footer'>
                    <p>Best regards,<br>MOTOPARTS Support Team</p>
                    <p>This is an automated security message, please do not reply directly.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        require_once 'smtp_mailer.php';

        if (send_smtp_email($to, $subject, $message)) {
            unset($_SESSION['show_otp']);
            $success_message = "OTP has been sent to your registered email address.";
            header("Location: forgot_password.php?step=3");
            die;
        } else {
            $_SESSION['show_otp'] = $otp;
            $error_message = "Failed to send email. OTP displayed on screen for local testing.";
            header("Location: forgot_password.php?step=3");
            die;
        }
    } else {
        $error_message = "Incorrect answers to security questions. Please check and try again.";
    }
}

// Step 3: Verify OTP
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_otp'])) {
    if (!isset($_SESSION['reset_otp'])) {
        header("Location: forgot_password.php?step=1");
        die;
    }

    $entered_otp = trim($_POST['otp']);

    if (time() > $_SESSION['otp_expiry']) {
        $error_message = "OTP has expired. Please request a new one.";
        unset($_SESSION['reset_otp']);
        unset($_SESSION['otp_expiry']);
    } elseif ($entered_otp == $_SESSION['reset_otp']) {
        $_SESSION['otp_verified'] = true;
        header("Location: forgot_password.php?step=4");
        die;
    } else {
        $error_message = "Incorrect OTP. Please enter the valid 6-digit code.";
    }
}

// Step 4: Reset Password
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_password'])) {
    if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        header("Location: forgot_password.php?step=1");
        die;
    }

    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error_message = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $user_id = $_SESSION['reset_user_id'];

        $query = "UPDATE user SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            // Clear session variables
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_username']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_otp']);
            unset($_SESSION['otp_expiry']);
            unset($_SESSION['otp_verified']);

            $success_message = "Password reset successful! Redirecting to login...";
            header("refresh:2;url=../../login.php");
        } else {
            $error_message = "Failed to update password. Please try again.";
        }
    }
}

// Get security questions for step 2
$security_questions = [];
if ($step == 2 && isset($_SESSION['reset_user_id'])) {
    $user_id = $_SESSION['reset_user_id'];
    $query = "SELECT security_question1, security_question2, security_question3 FROM user WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $questions_data = mysqli_fetch_assoc($result);

    if ($questions_data) {
        for ($i = 1; $i <= 3; $i++) {
            if (!empty($questions_data['security_question' . $i])) {
                $security_questions[$i] = $questions_data['security_question' . $i];
            }
        }
    }
}

// Define Security Question Options (Matching registration.php)
$security_question_options = [
    1 => [
        "What is your mother's maiden name?",
        "What was the name of your first pet?",
        "What is the name of the city where you were born?",
        "What is your favorite food?"
    ],
    2 => [
        "What is the name of your favorite teacher?",
        "What is your favorite movie?",
        "What is the make of your first car?",
        "What is your lucky number?"
    ],
    3 => [
        "What is your favorite color?",
        "What is the name of your best friend?",
        "What is your dream job?",
        "What is your favorite sports team?"
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MOTOPARTS</title>
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
            min-height: 100vh;
        }

        body {
            font-family: var(--font-main);
            background-color: var(--dark-bg);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            background-image:
                linear-gradient(rgba(12, 15, 20, 0.94), rgba(12, 15, 20, 0.96)),
                url('../../../img/moto-bg.jpg');
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
            color: #ffffff;
        }

        /* Form Wrapper & Content */
        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1.5rem;
        }

        .form-card {
            background: rgba(18, 22, 31, 0.92);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 2.25rem 2.25rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        /* Header block with orange vertical bar */
        .form-header-row {
            margin-bottom: 1.25rem;
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

        /* Modern Step Indicator */
        .step-indicator {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin: 1.25rem 0 1.25rem;
        }

        .step-pill {
            text-align: center;
            padding: 0.55rem 0.35rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }

        .step-pill .step-num {
            font-size: 0.7rem;
            font-weight: 800;
            opacity: 0.7;
        }

        .step-pill.active {
            background: linear-gradient(135deg, #ff5500 0%, #e04400 100%);
            border-color: transparent;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(255, 85, 0, 0.35);
            font-weight: 700;
        }

        .step-pill.active .step-num {
            opacity: 0.9;
        }

        .step-pill.completed {
            background: rgba(34, 197, 94, 0.12);
            border-color: rgba(34, 197, 94, 0.3);
            color: #4ade80;
            font-weight: 600;
        }

        /* Section divider header */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0.85rem 0 1.25rem;
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
            font-size: 0.88rem;
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

        /* Form Inputs */
        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
            margin-bottom: 1.1rem;
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
            margin-bottom: 0.35rem;
        }

        .req-star {
            color: var(--accent-orange);
            font-weight: 800;
        }

        .input-control,
        select.input-control {
            width: 100%;
            height: 42px;
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

        /* Checkbox */
        .checkbox-container {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            user-select: none;
            margin: 0.25rem 0 1.1rem;
        }

        .checkbox-container input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--accent-orange);
            cursor: pointer;
            border-radius: 4px;
        }

        .checkbox-label {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* Step Action Buttons */
        .step-button-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .btn-step-submit {
            flex: 1;
            height: 44px;
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

        .btn-step-submit:hover {
            background: linear-gradient(135deg, #ff661a 0%, #eb4d00 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(255, 85, 0, 0.5);
        }

        .btn-step-submit:active {
            transform: translateY(0);
        }

        .btn-step-back {
            height: 44px;
            padding: 0 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }

        .btn-step-back:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.25);
        }

        /* Error Messages & Alerts */
        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ff6b6b;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-align: center;
            line-height: 1.3;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-align: center;
            line-height: 1.3;
        }

        /* OTP Specific Box */
        .otp-info-card {
            background: rgba(15, 19, 26, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .otp-email-target {
            color: #ffffff;
            font-weight: 700;
            word-break: break-all;
        }

        .otp-countdown {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
            padding: 0.65rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .otp-countdown.active {
            background: rgba(255, 85, 0, 0.12);
            border: 1px solid rgba(255, 85, 0, 0.3);
            color: #ff7733;
        }

        .otp-countdown.warning {
            background: rgba(234, 179, 8, 0.15);
            border: 1px solid rgba(234, 179, 8, 0.35);
            color: #facc15;
            animation: pulse 1s ease infinite;
        }

        .otp-countdown.expired {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #f87171;
        }

        .otp-test-display {
            background: rgba(255, 85, 0, 0.15);
            border: 1px dashed var(--accent-orange);
            color: #ff9966;
            padding: 0.65rem;
            border-radius: 6px;
            text-align: center;
            margin-top: 0.75rem;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .otp-input-control {
            text-align: center;
            font-size: 1.4rem !important;
            letter-spacing: 6px;
            font-weight: 800;
            font-family: var(--font-heading);
            height: 48px !important;
        }

        /* Auth Footer */
        .auth-footer-link {
            text-align: center;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
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

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
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

        @media (max-width: 520px) {
            .form-card {
                padding: 1.75rem 1.25rem;
            }
            .form-title {
                font-size: 1.5rem;
            }
            .step-pill {
                font-size: 0.68rem;
                padding: 0.45rem 0.2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar">
        <div class="navdiv">
            <a href="../../homepage.php" class="logo-link">
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
                <a href="../../homepage.php" class="btn-nav-home">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Home
                </a>
                <a href="../../login.php" class="btn-nav-login">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <div class="form-card">
            <!-- Header with orange bar -->
            <div class="form-header-row">
                <div class="form-title-group">
                    <div class="title-with-bar">
                        <div class="orange-bar"></div>
                        <h1 class="form-title">FORGOT PASSWORD</h1>
                    </div>
                    <p class="form-subtitle">Reset your account password in four simple steps</p>
                </div>
            </div>

            <!-- Step Indicators -->
            <div class="step-indicator">
                <div class="step-pill <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">
                    <span class="step-num"><?php echo $step > 1 ? '✓' : 'STEP 1'; ?></span>
                    <span>Username</span>
                </div>
                <div class="step-pill <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">
                    <span class="step-num"><?php echo $step > 2 ? '✓' : 'STEP 2'; ?></span>
                    <span>Security</span>
                </div>
                <div class="step-pill <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">
                    <span class="step-num"><?php echo $step > 3 ? '✓' : 'STEP 3'; ?></span>
                    <span>OTP</span>
                </div>
                <div class="step-pill <?php echo $step >= 4 ? 'active' : ''; ?>">
                    <span class="step-num">STEP 4</span>
                    <span>New Pass</span>
                </div>
            </div>

            <!-- Error and Success Messages -->
            <?php if (!empty($error_message)): ?>
                <div class="alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- STEP 1: Enter Username/Email -->
            <?php if ($step == 1): ?>
                <div class="section-header">
                    <div class="section-bullet"></div>
                    <span class="section-title-text">ACCOUNT IDENTIFICATION</span>
                    <div class="section-line"></div>
                </div>

                <form action="" method="post">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            Username or Email <span class="req-star">*</span>
                        </label>
                        <input type="text" name="username" id="username" class="input-control" placeholder="Enter your username or email" required autofocus>
                    </div>

                    <button type="submit" name="submit_username" class="btn-step-submit">
                        <span>Continue</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>

                    <div class="auth-footer-link">
                        Remember your password? <a href="../../login.php">Login Here</a>
                    </div>
                </form>
            <?php endif; ?>

            <!-- STEP 2: Answer Security Questions -->
            <?php if ($step == 2): ?>
                <div class="section-header">
                    <div class="section-bullet"></div>
                    <span class="section-title-text">SECURITY QUESTIONS</span>
                    <div class="section-line"></div>
                </div>

                <form action="" method="post">
                    <?php foreach ($security_questions as $index => $stored_question): ?>
                        <div class="form-group">
                            <label for="question<?php echo $index; ?>" class="form-label">
                                Security Question <?php echo $index; ?> <span class="req-star">*</span>
                            </label>
                            <select name="question<?php echo $index; ?>" id="question<?php echo $index; ?>" class="input-control" required style="margin-bottom: 0.5rem;">
                                <option value="">Select your question</option>
                                <?php if (isset($security_question_options[$index])): ?>
                                    <?php foreach ($security_question_options[$index] as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <input type="text" name="answer<?php echo $index; ?>" id="answer<?php echo $index; ?>" class="input-control" placeholder="Enter your answer" required>
                        </div>
                    <?php endforeach; ?>

                    <div class="step-button-group">
                        <a href="forgot_password.php?step=1" class="btn-step-back">Back</a>
                        <button type="submit" name="submit_questions" class="btn-step-submit">
                            <span>Verify & Send OTP</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </div>

                    <div class="auth-footer-link">
                        Need to restart? <a href="forgot_password.php?step=1">Start Over</a>
                    </div>
                </form>
            <?php endif; ?>

            <!-- STEP 3: Verify OTP -->
            <?php if ($step == 3): ?>
                <?php
                $otp_remaining = 0;
                if (isset($_SESSION['otp_expiry'])) {
                    $otp_remaining = max(0, $_SESSION['otp_expiry'] - time());
                }
                ?>
                <div class="section-header">
                    <div class="section-bullet"></div>
                    <span class="section-title-text">OTP VERIFICATION</span>
                    <div class="section-line"></div>
                </div>

                <form action="" method="post" id="otpForm">
                    <div class="otp-info-card">
                        <div>OTP code sent to: <span class="otp-email-target"><?php echo htmlspecialchars(isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : ''); ?></span></div>
                        
                        <div class="otp-countdown active" id="otpCountdown">
                            <span>⏱️</span>
                            <span id="countdownText">Time remaining: <span id="countdownValue"><?php echo $otp_remaining; ?></span>s</span>
                        </div>

                        <?php if (isset($_SESSION['show_otp'])): ?>
                            <div class="otp-test-display">
                                <strong>⚠️ TEST OTP: <?php echo $_SESSION['show_otp']; ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="otp" class="form-label">
                            Enter 6-Digit OTP Code <span class="req-star">*</span>
                        </label>
                        <input type="text" name="otp" id="otp" class="input-control otp-input-control" placeholder="••••••" maxlength="6" pattern="[0-9]{6}" required autofocus autocomplete="one-time-code">
                    </div>

                    <button type="submit" name="submit_otp" id="otpSubmitBtn" class="btn-step-submit">
                        <span>Verify OTP Code</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>

                    <div class="auth-footer-link">
                        Didn't receive the code? <a href="forgot_password.php?step=2">Resend OTP</a> | <a href="forgot_password.php?step=1">Start Over</a>
                    </div>
                </form>

                <script>
                    (function () {
                        var remaining = <?php echo intval($otp_remaining); ?>;
                        var countdownEl = document.getElementById('otpCountdown');
                        var countdownValue = document.getElementById('countdownValue');
                        var countdownText = document.getElementById('countdownText');
                        var otpInput = document.getElementById('otp');
                        var otpSubmitBtn = document.getElementById('otpSubmitBtn');

                        function formatTime(seconds) {
                            var mins = Math.floor(seconds / 60);
                            var secs = seconds % 60;
                            return (mins > 0 ? mins + 'm ' : '') + (secs < 10 ? '0' : '') + secs + 's';
                        }

                        function updateDisplay() {
                            if (remaining <= 0) {
                                countdownEl.className = 'otp-countdown expired';
                                countdownText.innerHTML = '<span>❌</span> OTP has expired! Please request a new code.';
                                if (otpInput) otpInput.disabled = true;
                                if (otpSubmitBtn) {
                                    otpSubmitBtn.disabled = true;
                                    otpSubmitBtn.style.opacity = '0.5';
                                    otpSubmitBtn.style.cursor = 'not-allowed';
                                }
                                return;
                            }

                            countdownValue.textContent = formatTime(remaining);

                            if (remaining <= 30) {
                                countdownEl.className = 'otp-countdown warning';
                            } else {
                                countdownEl.className = 'otp-countdown active';
                            }
                        }

                        updateDisplay();

                        if (remaining > 0) {
                            var timer = setInterval(function () {
                                remaining--;
                                updateDisplay();
                                if (remaining <= 0) {
                                    clearInterval(timer);
                                }
                            }, 1000);
                        }
                    })();
                </script>
            <?php endif; ?>

            <!-- STEP 4: Reset Password -->
            <?php if ($step == 4): ?>
                <div class="section-header">
                    <div class="section-bullet"></div>
                    <span class="section-title-text">NEW PASSWORD</span>
                    <div class="section-line"></div>
                </div>

                <form action="" method="post" id="resetForm">
                    <div class="form-group">
                        <label for="new_password" class="form-label">
                            New Password <span class="req-star">*</span>
                        </label>
                        <input type="password" name="new_password" id="new_password" class="input-control" placeholder="Enter new password (min 8 characters)" required minlength="8" autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            Confirm Password <span class="req-star">*</span>
                        </label>
                        <input type="password" name="confirm_password" id="confirm_password" class="input-control" placeholder="Confirm your new password" required autocomplete="new-password">
                    </div>

                    <label class="checkbox-container" for="togglePassword">
                        <input type="checkbox" id="togglePassword">
                        <span class="checkbox-label">Show Password</span>
                    </label>

                    <button type="submit" name="submit_password" class="btn-step-submit">
                        <span>Save & Reset Password</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>

                    <div class="auth-footer-link">
                        Remember your password? <a href="../../login.php">Back to Login</a>
                    </div>
                </form>

                <script>
                    const togglePassword = document.querySelector('#togglePassword');
                    const newPassword = document.querySelector('#new_password');
                    const confirmPassword = document.querySelector('#confirm_password');

                    if (togglePassword) {
                        togglePassword.addEventListener('change', function () {
                            const type = this.checked ? 'text' : 'password';
                            newPassword.type = type;
                            confirmPassword.type = type;
                        });
                    }

                    document.getElementById('resetForm').addEventListener('submit', function (e) {
                        if (newPassword.value !== confirmPassword.value) {
                            e.preventDefault();
                            alert('Passwords do not match! Please confirm your password correctly.');
                        }
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Moto Shop. All rights reserved.</p>
        <p style="margin-top: 0.35rem;"><a href="#">Privacy Policy</a></p>
    </footer>
</body>

</html>