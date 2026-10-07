<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../../includes/mail_config.php';

// Ensure OTP table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'otp_verifications'");
if ($check_table && mysqli_num_rows($check_table) == 0) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `otp_verifications` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` VARCHAR(50) NOT NULL,
      `email` VARCHAR(191) NOT NULL,
      `otp_hash` VARCHAR(255) NOT NULL,
      `purpose` VARCHAR(50) NOT NULL DEFAULT 'forgot_password',
      `expires_at` DATETIME NOT NULL,
      `attempts` INT(11) NOT NULL DEFAULT 0,
      `is_used` TINYINT(1) NOT NULL DEFAULT 0,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      INDEX `idx_user_purpose` (`user_id`, `purpose`),
      INDEX `idx_email_purpose` (`email`, `purpose`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
if ($step < 1 || $step > 4) {
    $step = 1;
}

$error_message = "";
$success_message = "";

// Helper to mask ID Number
function maskIdNo($idno) {
    if (empty($idno)) return '----';
    if (strpos($idno, '-') !== false) {
        $parts = explode('-', $idno, 2);
        return $parts[0] . '-' . str_repeat('*', strlen($parts[1]));
    }
    if (strlen($idno) <= 4) return str_repeat('*', strlen($idno));
    return substr($idno, 0, 4) . str_repeat('*', strlen($idno) - 4);
}

// Helper to mask Email
function maskEmail($email) {
    if (empty($email)) return '----';
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;
    $name = $parts[0];
    $domain = $parts[1];
    $len = strlen($name);
    if ($len <= 2) {
        $masked_name = substr($name, 0, 1) . '***';
    } else {
        $masked_name = substr($name, 0, 1) . str_repeat('*', max(6, $len - 2)) . substr($name, -1);
    }
    return $masked_name . '@' . $domain;
}

// Helper to generate and send OTP
function create_and_send_otp($conn, $user_id, $username, $email) {
    $otp = (string)random_int(100000, 999999);
    $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
    $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    // Invalidate previous unused OTPs
    $stmt_inv = mysqli_prepare($conn, "UPDATE otp_verifications SET is_used = 1 WHERE user_id = ? AND purpose = 'forgot_password' AND is_used = 0");
    if ($stmt_inv) {
        mysqli_stmt_bind_param($stmt_inv, "s", $user_id);
        mysqli_stmt_execute($stmt_inv);
        mysqli_stmt_close($stmt_inv);
    }

    // Insert new OTP record
    $stmt_ins = mysqli_prepare($conn, "INSERT INTO otp_verifications (user_id, email, otp_hash, purpose, expires_at, attempts, is_used) VALUES (?, ?, ?, 'forgot_password', ?, 0, 0)");
    if ($stmt_ins) {
        mysqli_stmt_bind_param($stmt_ins, "ssss", $user_id, $email, $otp_hash, $expires_at);
        mysqli_stmt_execute($stmt_ins);
        $otp_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_ins);

        $_SESSION['reset_otp_id'] = $otp_id;
        $_SESSION['otp_expiry'] = time() + 600;
        $_SESSION['last_otp_sent'] = time();

        $mail_result = send_otp_email($email, $username, $otp, 10);
        if (!$mail_result['success']) {
            $_SESSION['show_otp'] = $otp;
            return [
                'success' => true,
                'otp_sent' => false,
                'message' => "OTP code generated. (Simulated OTP: $otp)"
            ];
        } else {
            unset($_SESSION['show_otp']);
            return [
                'success' => true,
                'otp_sent' => true,
                'message' => "A 6-digit OTP code has been sent to your registered email address."
            ];
        }
    }

    return ['success' => false, 'message' => "Database error generating OTP. Please try again."];
}

// ----------------------------------------------------
// STEP 1: Enter ID Number (Hidden input field)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_idno'])) {
    $idno = isset($_POST['idno']) ? trim($_POST['idno']) : '';

    if (!empty($idno)) {
        $stmt = mysqli_prepare($conn, "SELECT idno, username, email, role, status FROM user WHERE idno = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $idno);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);

                if (isset($user_data['status']) && $user_data['status'] === 'blocked') {
                    $error_message = "This account is blocked. Please contact an administrator.";
                } elseif (isset($user_data['status']) && $user_data['status'] === 'inactive') {
                    $error_message = "This account is inactive. Please contact an administrator.";
                } else {
                    $_SESSION['reset_user_id'] = $user_data['idno'];
                    $_SESSION['reset_idno'] = $user_data['idno'];
                    $_SESSION['reset_username'] = $user_data['username'];
                    $_SESSION['reset_email'] = $user_data['email'];

                    // Send OTP and proceed to Step 2
                    $otp_res = create_and_send_otp($conn, $user_data['idno'], $user_data['username'], $user_data['email']);
                    $_SESSION['otp_status_msg'] = $otp_res['message'];

                    header("Location: forgot_password.php?step=2");
                    exit;
                }
            } else {
                $error_message = "ID Number is not registered. Please check and try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database query error. Please try again.";
        }
    } else {
        $error_message = "Please enter your ID Number.";
    }
}

// ----------------------------------------------------
// STEP 2: Verify OTP
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['resend_otp'])) {
    if (!isset($_SESSION['reset_user_id'])) {
        header("Location: forgot_password.php?step=1");
        exit;
    }

    $last_sent = isset($_SESSION['last_otp_sent']) ? $_SESSION['last_otp_sent'] : 0;
    $cooldown = 60;
    $elapsed = time() - $last_sent;

    if ($elapsed < $cooldown) {
        $wait = $cooldown - $elapsed;
        $error_message = "Please wait {$wait} seconds before requesting a new OTP.";
    } else {
        $otp_res = create_and_send_otp($conn, $_SESSION['reset_user_id'], $_SESSION['reset_username'], $_SESSION['reset_email']);
        if ($otp_res['success']) {
            $success_message = $otp_res['message'];
        } else {
            $error_message = $otp_res['message'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_otp'])) {
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_otp_id'])) {
        header("Location: forgot_password.php?step=1");
        exit;
    }

    $entered_otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    $otp_id = $_SESSION['reset_otp_id'];
    $user_id = $_SESSION['reset_user_id'];

    if (empty($entered_otp) || strlen($entered_otp) !== 6 || !ctype_digit($entered_otp)) {
        $error_message = "Please enter the valid 6-digit OTP code.";
    } else {
        $stmt_otp = mysqli_prepare($conn, "SELECT id, otp_hash, expires_at, attempts, is_used FROM otp_verifications WHERE id = ? AND user_id = ? LIMIT 1");
        if ($stmt_otp) {
            mysqli_stmt_bind_param($stmt_otp, "is", $otp_id, $user_id);
            mysqli_stmt_execute($stmt_otp);
            $otp_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_otp));
            mysqli_stmt_close($stmt_otp);

            if (!$otp_data || $otp_data['is_used'] == 1) {
                $error_message = "Invalid or expired OTP. Please request a new one.";
            } elseif ($otp_data['attempts'] >= 5) {
                mysqli_query($conn, "UPDATE otp_verifications SET is_used = 1 WHERE id = $otp_id");
                $error_message = "Too many incorrect attempts. Please request a new OTP.";
            } elseif (time() > strtotime($otp_data['expires_at'])) {
                mysqli_query($conn, "UPDATE otp_verifications SET is_used = 1 WHERE id = $otp_id");
                $error_message = "OTP has expired. Please request a new code.";
            } else {
                if (password_verify($entered_otp, $otp_data['otp_hash'])) {
                    // Mark OTP as verified
                    mysqli_query($conn, "UPDATE otp_verifications SET is_used = 1 WHERE id = $otp_id");
                    $_SESSION['otp_verified'] = true;
                    unset($_SESSION['show_otp']);

                    header("Location: forgot_password.php?step=3");
                    exit;
                } else {
                    $new_attempts = $otp_data['attempts'] + 1;
                    mysqli_query($conn, "UPDATE otp_verifications SET attempts = $new_attempts WHERE id = $otp_id");

                    if ($new_attempts >= 5) {
                        mysqli_query($conn, "UPDATE otp_verifications SET is_used = 1 WHERE id = $otp_id");
                        $error_message = "Too many incorrect attempts. Please request a new OTP.";
                    } else {
                        $rem = 5 - $new_attempts;
                        $error_message = "Incorrect OTP code. ($rem attempts remaining)";
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// STEP 3: Verify Security Question
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_security'])) {
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        header("Location: forgot_password.php?step=1");
        exit;
    }

    $user_id = $_SESSION['reset_user_id'];
    $selected_q = isset($_POST['security_question']) ? trim($_POST['security_question']) : '';
    $entered_ans = isset($_POST['security_answer']) ? strtolower(trim($_POST['security_answer'])) : '';

    if (empty($entered_ans)) {
        $error_message = "Please enter your security answer.";
    } else {
        $stmt_sq = mysqli_prepare($conn, "SELECT security_question1, security_answer1, security_question2, security_answer2, security_question3, security_answer3 FROM user WHERE idno = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_sq, "s", $user_id);
        mysqli_stmt_execute($stmt_sq);
        $user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_sq));
        mysqli_stmt_close($stmt_sq);

        if ($user_data) {
            $is_correct = false;

            for ($i = 1; $i <= 3; $i++) {
                $q_val = $user_data["security_question$i"] ?? '';
                $a_val = $user_data["security_answer$i"] ?? '';

                if (!empty($q_val) && ($q_val === $selected_q || empty($selected_q))) {
                    if (str_starts_with($a_val, '$2y$') || str_starts_with($a_val, '$2a$') || str_starts_with($a_val, '$2b$')) {
                        if (password_verify($entered_ans, $a_val) || password_verify(trim($_POST['security_answer']), $a_val)) {
                            $is_correct = true;
                            break;
                        }
                    } else {
                        if ($entered_ans === strtolower(trim($a_val))) {
                            $is_correct = true;
                            break;
                        }
                    }
                }
            }

            if ($is_correct) {
                $_SESSION['security_verified'] = true;
                header("Location: forgot_password.php?step=4");
                exit;
            } else {
                $error_message = "Incorrect security answer. Please verify and try again.";
            }
        } else {
            $error_message = "Account information error. Please restart the process.";
        }
    }
}

// ----------------------------------------------------
// STEP 4: Reset Password
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['submit_new_password'])) {
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_verified']) || !isset($_SESSION['security_verified'])) {
        header("Location: forgot_password.php?step=1");
        exit;
    }

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_id = $_SESSION['reset_user_id'];

    $has_upper = preg_match('/[A-Z]/', $new_password);
    $has_lower = preg_match('/[a-z]/', $new_password);
    $has_digit = preg_match('/[0-9]/', $new_password);
    $has_special = preg_match('/[!@#$%^&*(),.?":{}|<>_\-\=\+]/', $new_password);

    if (empty($new_password) || empty($confirm_password)) {
        $error_message = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match. Please verify both fields.";
    } elseif (strlen($new_password) < 8 || !$has_upper || !$has_lower || !$has_digit || !$has_special) {
        $error_message = "Password must be at least 8 characters long and include uppercase, lowercase, number, and a special character.";
    } else {
        // Fetch current password to prevent password reuse
        $stmt_cur = mysqli_prepare($conn, "SELECT password, username, role FROM user WHERE idno = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_cur, "s", $user_id);
        mysqli_stmt_execute($stmt_cur);
        $cur_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cur));
        mysqli_stmt_close($stmt_cur);

        if ($cur_row && password_verify($new_password, $cur_row['password'])) {
            $error_message = "New password cannot be the same as your current password.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt_upd = mysqli_prepare($conn, "UPDATE user SET password = ?, must_change_password = 0 WHERE idno = ?");
            if ($stmt_upd) {
                mysqli_stmt_bind_param($stmt_upd, "ss", $hashed_password, $user_id);
                if (mysqli_stmt_execute($stmt_upd)) {
                    // Audit log
                    $ip_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
                    $log_details = "Password reset via ID No -> OTP -> Security Question flow from IP: " . $ip_addr;
                    $stmt_log = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, ?, 'Password Reset', ?)");
                    if ($stmt_log) {
                        mysqli_stmt_bind_param($stmt_log, "ssss", $user_id, $cur_row['username'], $cur_row['role'], $log_details);
                        mysqli_stmt_execute($stmt_log);
                        mysqli_stmt_close($stmt_log);
                    }

                    // Clear reset session
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_idno']);
                    unset($_SESSION['reset_username']);
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_otp_id']);
                    unset($_SESSION['otp_expiry']);
                    unset($_SESSION['otp_verified']);
                    unset($_SESSION['security_verified']);

                    $success_message = "Password has been successfully changed! Redirecting to login...";
                    header("refresh:2;url=../../login.php");
                } else {
                    $error_message = "Database error. Failed to update password.";
                }
                mysqli_stmt_close($stmt_upd);
            }
        }
    }
}

// Security question fetching for step 3
$user_security_questions = [];
if ($step == 3 && isset($_SESSION['reset_user_id'])) {
    $uid = $_SESSION['reset_user_id'];
    $stmt_g = mysqli_prepare($conn, "SELECT security_question1, security_question2, security_question3 FROM user WHERE idno = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_g, "s", $uid);
    mysqli_stmt_execute($stmt_g);
    $q_res = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_g));
    mysqli_stmt_close($stmt_g);

    if ($q_res) {
        for ($i = 1; $i <= 3; $i++) {
            if (!empty($q_res["security_question$i"])) {
                $user_security_questions[] = $q_res["security_question$i"];
            }
        }
    }
}

// Flash status msg
if (isset($_SESSION['otp_status_msg'])) {
    if (empty($success_message)) {
        $success_message = $_SESSION['otp_status_msg'];
    }
    unset($_SESSION['otp_status_msg']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery - ONE PIECE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent-orange: #f59e0b;
            --accent-orange-hover: #d97706;
            --accent-orange-glow: rgba(245, 158, 11, 0.25);
            --card-bg: #ffffff;
            --input-bg: #f8fafc;
            --input-border: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --font-main: 'Plus Jakarta Sans', system-ui, sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { min-height: 100vh; }

        body {
            font-family: var(--font-main);
            background-color: #0c1222;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            background-image:
                linear-gradient(rgba(15, 23, 42, 0.22), rgba(15, 23, 42, 0.32)),
                url('../../../img/one_piece_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .navbar {
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0.85rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
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
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 0 10px rgba(245, 158, 11, 0.5));
            transition: transform 0.3s ease;
        }

        .logo-link:hover .logo-icon {
            transform: rotate(-8deg) scale(1.08);
        }

        .logo-text {
            font-family: var(--font-heading);
            font-size: 1.45rem;
            font-weight: 900;
            letter-spacing: 1px;
            line-height: 1;
        }

        .logo-text .op-one {
            font-weight: 900;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        }

        .logo-text .op-piece {
            font-weight: 900;
            color: #fbbf24;
            text-shadow: 0 0 12px rgba(245, 158, 11, 0.5);
            margin-left: 3px;
        }

        .btn-nav-login {
            padding: 0.5rem 1.25rem;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.28);
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-nav-login:hover {
            background: rgba(255, 255, 255, 0.3);
            color: #fff;
            border-color: #fff;
        }

        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2.25rem 1.5rem;
        }

        .form-card {
            background: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 2.25rem 2.5rem;
            width: 100%;
            max-width: 485px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35), 0 0 35px rgba(245, 158, 11, 0.15);
        }

        /* Step Progress */
        .step-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.75rem;
            position: relative;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            flex: 1;
            position: relative;
        }

        .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #f1f5f9;
            border: 1.5px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.8rem;
            color: #64748b;
        }

        .step-item.active .step-num {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            border-color: #f59e0b;
            color: #fff;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }

        .step-item.active {
            color: #0f172a;
            font-weight: 800;
        }

        .step-item.completed .step-num {
            background: #16a34a;
            border-color: #16a34a;
            color: #fff;
        }

        .step-item.completed {
            color: #16a34a;
        }

        .form-group {
            margin-bottom: 1.15rem;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #1e293b;
            margin-bottom: 0.45rem;
        }

        .input-control {
            width: 100%;
            height: 44px;
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 0 1rem;
            color: #0f172a;
            font-size: 0.94rem;
            font-weight: 600;
            outline: none;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .input-control:focus {
            background: #ffffff;
            border-color: var(--accent-orange);
            box-shadow: 0 0 0 3px var(--accent-orange-glow);
        }

        .input-control::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .btn-submit {
            width: 100%;
            height: 44px;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.35);
            transition: all 0.2s ease;
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.5);
        }

        .alert-box {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .masked-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .masked-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.4rem;
            font-size: 0.84rem;
        }

        .masked-item:last-child { margin-bottom: 0; }
        .masked-item .label { color: #64748b; font-weight: 600; }
        .masked-item .val { color: #0f172a; font-family: monospace; font-weight: 700; }
    </style>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar">
        <div class="navdiv">
            <a href="../../login.php" class="logo-link">
                <div class="logo-icon">
                    <svg width="28" height="28" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Crossed Bones -->
                        <path d="M14 14 L50 50 M50 14 L14 50" stroke="#e2e8f0" stroke-width="5" stroke-linecap="round"/>
                        <circle cx="12" cy="12" r="3.5" fill="#cbd5e1"/>
                        <circle cx="12" cy="16" r="3.5" fill="#cbd5e1"/>
                        <circle cx="52" cy="12" r="3.5" fill="#cbd5e1"/>
                        <circle cx="52" cy="16" r="3.5" fill="#cbd5e1"/>
                        <circle cx="12" cy="48" r="3.5" fill="#cbd5e1"/>
                        <circle cx="12" cy="52" r="3.5" fill="#cbd5e1"/>
                        <circle cx="52" cy="48" r="3.5" fill="#cbd5e1"/>
                        <circle cx="52" cy="52" r="3.5" fill="#cbd5e1"/>
                        <!-- Skull Base -->
                        <circle cx="32" cy="34" r="14" fill="#f8fafc"/>
                        <rect x="26" y="42" width="12" height="7" rx="2" fill="#f8fafc"/>
                        <!-- Eye Sockets -->
                        <circle cx="27.5" cy="35" r="3.2" fill="#0f172a"/>
                        <circle cx="36.5" cy="35" r="3.2" fill="#0f172a"/>
                        <!-- Teeth Lines -->
                        <line x1="28.5" y1="44" x2="28.5" y2="48" stroke="#0f172a" stroke-width="1.3"/>
                        <line x1="32" y1="44" x2="32" y2="48" stroke="#0f172a" stroke-width="1.3"/>
                        <line x1="35.5" y1="44" x2="35.5" y2="48" stroke="#0f172a" stroke-width="1.3"/>
                        <!-- Straw Hat Crown -->
                        <ellipse cx="32" cy="24" rx="12" ry="7.5" fill="#f59e0b"/>
                        <!-- Red Ribbon -->
                        <ellipse cx="32" cy="27" rx="12.2" ry="3" fill="#ef4444"/>
                        <!-- Straw Hat Brim -->
                        <ellipse cx="32" cy="28" rx="22" ry="5" fill="#f59e0b"/>
                    </svg>
                </div>
                <div class="logo-text">
                    <span class="op-one">ONE</span><span class="op-piece">PIECE</span>
                </div>
            </a>
            <a href="../../login.php" class="btn-nav-login">Back to Login</a>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <div class="form-card">
            <!-- Header -->
            <div style="text-align: left; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
                    <div style="width: 5px; height: 26px; background: linear-gradient(180deg, #f59e0b 0%, #ef4444 100%); border-radius: 4px;"></div>
                    <h1 style="font-family: var(--font-heading); font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.5px;">PASSWORD RECOVERY</h1>
                </div>
                <p style="color: #64748b; font-size: 0.85rem; margin-left: calc(5px + 0.75rem);">Follow the 4-step sequence to safely reset your password</p>
            </div>

            <!-- Step Indicators: ID -> OTP -> Security Question -> Password -->
            <div class="step-progress">
                <div class="step-item <?php echo $step === 1 ? 'active' : ($step > 1 ? 'completed' : ''); ?>">
                    <div class="step-num">1</div>
                    <span>ID Number</span>
                </div>
                <div class="step-item <?php echo $step === 2 ? 'active' : ($step > 2 ? 'completed' : ''); ?>">
                    <div class="step-num">2</div>
                    <span>OTP</span>
                </div>
                <div class="step-item <?php echo $step === 3 ? 'active' : ($step > 3 ? 'completed' : ''); ?>">
                    <div class="step-num">3</div>
                    <span>Security Q</span>
                </div>
                <div class="step-item <?php echo $step === 4 ? 'active' : ''; ?>">
                    <div class="step-num">4</div>
                    <span>New Password</span>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($error_message)): ?>
                <div class="alert-box alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert-box alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- STEP 1: Enter ID Number (Hidden password input) -->
            <?php if ($step === 1): ?>
                <form method="post" action="forgot_password.php?step=1">
                    <div class="form-group">
                        <label class="form-label" for="idnoInput">Enter Registered ID Number <span style="color: #ef4444;">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="idno" id="idnoInput" class="input-control" placeholder="e.g. 2026-0001" required autocomplete="off">
                            <button type="button" id="toggleIdnoBtn" style="position: absolute; right: 10px; top: 12px; background: transparent; border: none; color: #64748b; cursor: pointer;">
                                <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <small style="color: #64748b; font-size: 0.76rem; margin-top: 4px; display: block;">Your entered ID number is hidden for privacy.</small>
                    </div>

                    <button type="submit" name="submit_idno" class="btn-submit">
                        <span>Verify ID & Send OTP</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </form>
                <script>
                    document.getElementById('toggleIdnoBtn')?.addEventListener('click', function() {
                        var inp = document.getElementById('idnoInput');
                        inp.type = inp.type === 'password' ? 'text' : 'password';
                    });
                </script>
            <?php endif; ?>

            <!-- STEP 2: OTP Verification (Display masked info) -->
            <?php if ($step === 2): ?>
                <div class="masked-box">
                    <div class="masked-item">
                        <span class="label">ID Number:</span>
                        <span class="val"><?php echo maskIdNo($_SESSION['reset_idno'] ?? ''); ?></span>
                    </div>
                    <div class="masked-item">
                        <span class="label">Email Address:</span>
                        <span class="val"><?php echo maskEmail($_SESSION['reset_email'] ?? ''); ?></span>
                    </div>
                </div>

                <form method="post" action="forgot_password.php?step=2">
                    <div class="form-group">
                        <label class="form-label" for="otpInput">Enter 6-Digit OTP Code <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="otp" id="otpInput" class="input-control" maxlength="6" placeholder="123456" required autocomplete="one-time-code" style="letter-spacing: 4px; font-size: 1.25rem; text-align: center; font-weight: 800; font-family: monospace; color: #0f172a; background: #ffffff;">
                        <small style="color: #64748b; font-size: 0.76rem; margin-top: 4px; display: block;">The OTP code is valid for 10 minutes.</small>
                    </div>

                    <button type="submit" name="submit_otp" class="btn-submit">
                        <span>Verify OTP</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </form>

                <form method="post" action="forgot_password.php?step=2" style="margin-top: 1rem;">
                    <button type="submit" name="resend_otp" id="resendOtpBtn" class="btn-action" style="background: #f1f5f9; border: 1.5px solid #cbd5e1; color: #475569; width: 100%; padding: 0.65rem; border-radius: 8px; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: all 0.2s;">
                        Resend OTP Code
                    </button>
                </form>
            <?php endif; ?>

            <!-- STEP 3: Security Question (Dropdown showing registered questions) -->
            <?php if ($step === 3): ?>
                <form method="post" action="forgot_password.php?step=3">
                    <div class="form-group">
                        <label class="form-label">Security Question <span style="color: #ef4444;">*</span></label>
                        <select name="security_question" class="input-control" style="cursor: pointer;" required>
                            <?php if (!empty($user_security_questions)): ?>
                                <?php foreach ($user_security_questions as $idx => $sq): ?>
                                    <option value="<?php echo htmlspecialchars($sq); ?>"><?php echo htmlspecialchars($sq); ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                <option value="What is the name of your favorite teacher?">What is the name of your favorite teacher?</option>
                                <option value="What is your favorite color?">What is your favorite color?</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="securityAnswerInput">Your Answer <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="security_answer" id="securityAnswerInput" class="input-control" placeholder="Enter your registered answer" required autocomplete="off">
                    </div>

                    <button type="submit" name="submit_security" class="btn-submit">
                        <span>Verify Security Answer</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </form>
            <?php endif; ?>

            <!-- STEP 4: Change Password -->
            <?php if ($step === 4): ?>
                <form method="post" action="forgot_password.php?step=4">
                    <div class="form-group">
                        <label class="form-label" for="newPassword">New Password <span style="color: #ef4444;">*</span></label>
                        <input type="password" name="new_password" id="newPassword" class="input-control" placeholder="Enter new password" required autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirmPassword">Confirm New Password <span style="color: #ef4444;">*</span></label>
                        <input type="password" name="confirm_password" id="confirmPassword" class="input-control" placeholder="Re-type new password" required autocomplete="new-password">
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.85rem; color: #475569;">
                        <input type="checkbox" id="toggleNewPwd" style="accent-color: #f59e0b; width: 16px; height: 16px;">
                        <label for="toggleNewPwd" style="cursor: pointer; font-weight: 600;">Show Passwords</label>
                    </div>

                    <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.8rem; color: #475569; text-align: left;">
                        <div style="font-weight: 700; color: #0f172a; margin-bottom: 0.35rem;">Password Requirements:</div>
                        <ul style="margin-left: 1.2rem; line-height: 1.5;">
                            <li>At least 8 characters long</li>
                            <li>Include uppercase & lowercase letters</li>
                            <li>Include at least 1 number and 1 special symbol</li>
                            <li>Must differ from your previous password</li>
                        </ul>
                    </div>

                    <button type="submit" name="submit_new_password" class="btn-submit">
                        <span>Save New Password</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </button>
                </form>
                <script>
                    document.getElementById('toggleNewPwd')?.addEventListener('change', function() {
                        var p1 = document.getElementById('newPassword');
                        var p2 = document.getElementById('confirmPassword');
                        p1.type = this.checked ? 'text' : 'password';
                        p2.type = this.checked ? 'text' : 'password';
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer style="text-align: center; padding: 1.5rem; color: #ffffff; text-shadow: 0 2px 4px rgba(0,0,0,0.8); font-size: 0.85rem; font-weight: 600; margin-top: auto;">
        <p>&copy; <?php echo date('Y'); ?> ONE PIECE System. All rights reserved.</p>
    </footer>
</body>

</html>