<?php
session_start();
include("db.php");

// Initialize session variables if not set
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['denied_until'])) {
    $_SESSION['denied_until'] = 0;
}

// Check remaining lockout time (for both GET and POST requests)
$remaining_time = 0;
if (isset($_SESSION['denied_until']) && time() < $_SESSION['denied_until']) {
    $remaining_time = $_SESSION['denied_until'] - time();
} else {
    $_SESSION['denied_until'] = 0;
}

// Initialize error message variables
$username_error = "";
$password_error = "";
$error_message = "";
$show_change_pwd_modal = false;
$pwd_change_error = "";

// Show forgot password only after 2 or more attempts
$show_forgot_password = (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 2) ? 'true' : 'false';

// Handle First-Login Password Change Submission
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['action']) && $_POST['action'] === 'change_first_login_password') {
    $pending_id = trim($_SESSION['pending_user_id'] ?? ($_POST['pending_user_id'] ?? ''));
    $pending_role = trim($_SESSION['pending_user_role'] ?? ($_POST['pending_user_role'] ?? 'user'));
    $pending_name = trim($_SESSION['pending_username'] ?? ($_POST['pending_username'] ?? ''));

    if (!empty($pending_id)) {
        $new_pwd = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_pwd = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // Password complexity validation
        $has_upper = preg_match('/[A-Z]/', $new_pwd);
        $has_lower = preg_match('/[a-z]/', $new_pwd);
        $has_digit = preg_match('/[0-9]/', $new_pwd);
        $has_special = preg_match('/[!@#$%^&*(),.?":{}|<>_\-\=\+]/', $new_pwd);

        if (empty($new_pwd) || empty($confirm_pwd)) {
            $pwd_change_error = "Please fill in both password fields.";
            $show_change_pwd_modal = true;
        } elseif ($new_pwd !== $confirm_pwd) {
            $pwd_change_error = "Passwords do not match.";
            $show_change_pwd_modal = true;
        } elseif (strlen($new_pwd) < 8 || !$has_upper || !$has_lower || !$has_digit || !$has_special) {
            $pwd_change_error = "Password must be at least 8 characters long and include uppercase, lowercase, number, and a special character.";
            $show_change_pwd_modal = true;
        } elseif ($new_pwd === '@Abcde12345') {
            $pwd_change_error = "New password cannot be the default password. Please choose a unique password.";
            $show_change_pwd_modal = true;
        } else {
            // Update password and clear must_change_password flag
            $hashed_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
            $stmt_upd = mysqli_prepare($conn, "UPDATE user SET password = ?, must_change_password = 0 WHERE idno = ?");
            if ($stmt_upd) {
                mysqli_stmt_bind_param($stmt_upd, "ss", $hashed_pwd, $pending_id);
                mysqli_stmt_execute($stmt_upd);
                mysqli_stmt_close($stmt_upd);
            }

            // Mark user online and update last_activity
            $stmt_online = mysqli_prepare($conn, "UPDATE user SET is_online = 1, last_activity = NOW() WHERE idno = ?");
            if ($stmt_online) {
                mysqli_stmt_bind_param($stmt_online, "s", $pending_id);
                mysqli_stmt_execute($stmt_online);
                mysqli_stmt_close($stmt_online);
            }

            // Set login session
            $role = !empty($_SESSION['pending_user_role']) ? $_SESSION['pending_user_role'] : $pending_role;
            $user_id = $pending_id;
            $user_name = !empty($_SESSION['pending_username']) ? $_SESSION['pending_username'] : $pending_name;

            $_SESSION['role'] = $role;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $user_name;

            // Check if user has already completed personal information and security setup
            require_once __DIR__ . '/includes/auth_guard.php';
            $is_already_complete = check_user_personal_info_complete($conn, $user_id);
            $uid_c = mysqli_real_escape_string($conn, $user_id);

            $_SESSION['profile_completed'] = $is_already_complete ? 1 : 0;
            @mysqli_query($conn, "UPDATE user SET profile_completed = " . ($is_already_complete ? "1" : "0") . " WHERE idno = '$uid_c'");

            unset($_SESSION['pending_user_id']);
            unset($_SESSION['pending_user_role']);
            unset($_SESSION['pending_username']);
            $_SESSION['login_attempts'] = 0;

            // Audit log
            $log_action = "First Login Password Change";
            $ip_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $hostname = isset($_SERVER['REMOTE_ADDR']) ? gethostbyaddr($_SERVER['REMOTE_ADDR']) : 'Unknown';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
            $log_details = "Initial default password updated upon first login from IP: " . $ip_addr;
            $stmt_log = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, ?, ?, ?)");
            if ($stmt_log) {
                mysqli_stmt_bind_param($stmt_log, "sssss", $user_id, $user_name, $role, $log_action, $log_details);
                mysqli_stmt_execute($stmt_log);
                mysqli_stmt_close($stmt_log);
            }

            // Redirect to complete_profile.php if personal details NOT yet completed!
            if (!$is_already_complete) {
                header("Location: complete_profile.php");
                exit();
            }

            if ($role === 'superadmin') {
                header("Location: superadmin/dashboard.php");
            } elseif ($role === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: home.php");
            }
            exit();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && (!isset($_POST['action']) || $_POST['action'] !== 'change_first_login_password')) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // If currently locked out, do not process login attempt
    if (time() < $_SESSION['denied_until']) {
        $remaining_time = $_SESSION['denied_until'] - time();
        $error_message = "";
    } else {
        $_SESSION['denied_until'] = 0;

        if (!empty($username) && !empty($password)) {
            // Prepare the query to get user data by username
            $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE username = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
            } else {
                $escaped_username = mysqli_real_escape_string($conn, $username);
                $query = "SELECT * FROM user WHERE username = '$escaped_username' LIMIT 1";
                $result = mysqli_query($conn, $query);
            }

            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);

                // Verify the entered password against the hashed password in the database
                if (password_verify($password, $user_data['password'])) {
                    // Check if user is blocked or inactive
                    $user_status = isset($user_data['status']) ? $user_data['status'] : 'active';
                    if ($user_status === 'blocked') {
                        $_SESSION['login_attempts'] += 1;
                        $error_message = "Your account has been blocked. Please contact an administrator.";
                    } elseif ($user_status === 'inactive') {
                        $_SESSION['login_attempts'] += 1;
                        $error_message = "This account is inactive. Please contact an administrator.";
                    } else {
                        // Restrict Concurrent Super Admin Logins: Enforce single active Super Admin session
                        $role = isset($user_data['role']) ? $user_data['role'] : 'user';
                        $is_superadmin_blocked = false;

                        if ($role === 'superadmin') {
                            // Automatically expire stale sessions (> 15 minutes of inactivity)
                            @mysqli_query($conn, "UPDATE user SET is_online = 0 WHERE role = 'superadmin' AND is_online = 1 AND last_activity IS NOT NULL AND last_activity < NOW() - INTERVAL 15 MINUTE");

                            // Check if another Super Admin is currently online
                            $check_sa = mysqli_prepare($conn, "SELECT idno, username FROM user WHERE role = 'superadmin' AND is_online = 1 AND idno != ? LIMIT 1");
                            if ($check_sa) {
                                mysqli_stmt_bind_param($check_sa, "s", $user_data['idno']);
                                mysqli_stmt_execute($check_sa);
                                $res_sa = mysqli_stmt_get_result($check_sa);
                                if ($res_sa && mysqli_num_rows($res_sa) > 0) {
                                    $is_superadmin_blocked = true;
                                    $error_message = "Another Super Admin is currently active. Only one Super Admin can be logged in at a time.";
                                }
                                mysqli_stmt_close($check_sa);
                            }
                        }

                        if (!$is_superadmin_blocked) {
                            // Check if password change is required (must_change_password == 1 or password is default @Abcde12345)
                            $must_change = (isset($user_data['must_change_password']) && intval($user_data['must_change_password']) === 1) || password_verify('@Abcde12345', $user_data['password']);

                            if ($must_change) {
                                $_SESSION['pending_user_id'] = $user_data['idno'];
                                $_SESSION['pending_user_role'] = $user_data['role'];
                                $_SESSION['pending_username'] = $user_data['username'];
                                $show_change_pwd_modal = true;
                            } else {
                                // Successful login - reset failed attempts and lockout
                                $_SESSION['login_attempts'] = 0;
                                $_SESSION['denied_until'] = 0;

                                // Mark user online and update last_activity
                                $stmt_online = mysqli_prepare($conn, "UPDATE user SET is_online = 1, last_activity = NOW() WHERE idno = ?");
                                if ($stmt_online) {
                                    mysqli_stmt_bind_param($stmt_online, "s", $user_data['idno']);
                                    mysqli_stmt_execute($stmt_online);
                                    mysqli_stmt_close($stmt_online);
                                }

                                // Role-based Redirection
                                $_SESSION['role'] = $role;
                                $_SESSION['user_id'] = $user_data['idno'];
                                $_SESSION['username'] = $user_data['username'];

                            // Audit Log
                            $log_user_id = $user_data['idno'];
                            $log_username = $user_data['username'];
                            $log_role = $role;
                            $log_action = "Login";
                            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
                            $hostname = isset($_SERVER['REMOTE_ADDR']) ? gethostbyaddr($_SERVER['REMOTE_ADDR']) : 'Unknown';
                            $ip_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
                            $log_details = "IP: " . $ip_addr . " | Host: " . $hostname . " | Device: " . $user_agent;

                            $log_sql = "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, ?, ?, ?)";
                            $stmt_log = mysqli_prepare($conn, $log_sql);
                            if ($stmt_log) {
                                mysqli_stmt_bind_param($stmt_log, "sssss", $log_user_id, $log_username, $log_role, $log_action, $log_details);
                                mysqli_stmt_execute($stmt_log);
                                mysqli_stmt_close($stmt_log);
                            }

                            // Check if personal information and security setup is fully completed
                            require_once __DIR__ . '/includes/auth_guard.php';
                            $has_complete_profile = check_user_personal_info_complete($conn, $user_data['idno']);
                            $uid_c = mysqli_real_escape_string($conn, $user_data['idno']);

                            $_SESSION['profile_completed'] = $has_complete_profile ? 1 : 0;
                            @mysqli_query($conn, "UPDATE user SET profile_completed = " . ($has_complete_profile ? "1" : "0") . " WHERE idno = '$uid_c'");

                            // Require profile completion before dashboard access (only if NOT completed!)
                            if (!$has_complete_profile) {
                                header("Location: complete_profile.php");
                                exit();
                            }

                            if ($role === 'superadmin') {
                                header("location: superadmin/dashboard.php");
                            } elseif ($role === 'admin') {
                                header("location: admin/dashboard.php");
                            } else {
                                header("location: home.php");
                            }
                            exit;
                        }
                    }
                }
            } else {
                    $_SESSION['login_attempts'] += 1;
                    $cycle_attempt = (($_SESSION['login_attempts'] - 1) % 3) + 1;
                    $attempts_left = 3 - $cycle_attempt;
                    $error_message = "Incorrect username or password.";
                }
            } else {
                $_SESSION['login_attempts'] += 1;
                $cycle_attempt = (($_SESSION['login_attempts'] - 1) % 3) + 1;
                $attempts_left = 3 - $cycle_attempt;
                $error_message = "Incorrect username or password.";
            }

            // Lockout logic: Triggered after every 3 failed attempts
            if ($_SESSION['login_attempts'] > 0 && $_SESSION['login_attempts'] % 3 === 0) {
                if ($_SESSION['login_attempts'] == 3) {
                    $lockout_duration = 15; // 1st lockout: 15 seconds
                } elseif ($_SESSION['login_attempts'] == 6) {
                    $lockout_duration = 30; // 2nd lockout: 30 seconds
                } else {
                    $lockout_duration = 60; // 3rd and all subsequent lockouts: 60 seconds
                }
                $_SESSION['denied_until'] = time() + $lockout_duration;
                $remaining_time = $lockout_duration;
                $error_message = ""; // Lockout countdown banner will be displayed instead
            }
        } else {
            if (empty($username)) {
                $username_error = "Username should not be empty.";
            }
            if (empty($password)) {
                $password_error = "Password should not be empty.";
            }
        }

        // Show forgot password if 2 or more failed attempts
        $show_forgot_password = $_SESSION['login_attempts'] >= 2 ? 'true' : 'false';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pirate Log In - ONE PIECE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent-gold: #fbbf24;
            --accent-gold-hover: #f59e0b;
            --accent-gold-glow: rgba(245, 158, 11, 0.35);
            --accent-red: #ef4444;
            --accent-orange: #f97316;
            --accent-orange-hover: #ea580c;
            --accent-orange-glow: rgba(234, 88, 12, 0.35);
            --dark-bg: #0b1120;
            --card-bg: linear-gradient(145deg, rgba(15, 23, 42, 0.92) 0%, rgba(24, 34, 53, 0.90) 100%);
            --input-bg: rgba(8, 12, 22, 0.75);
            --input-border: rgba(255, 255, 255, 0.25);
            --input-border-focus: #fbbf24;
            --text-main: #ffffff;
            --text-muted: #cbd5e1;
            --text-label: #fbbf24;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100vh;
            max-height: 100vh;
            overflow: hidden !important;
        }

        body {
            font-family: var(--font-main);
            background-color: var(--dark-bg);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            background-image:
                linear-gradient(rgba(10, 15, 25, 0.20), rgba(10, 15, 25, 0.35)),
                url('../img/one_piece_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Top Navbar */
        .navbar {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0.55rem 2rem;
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
            filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.4));
            transition: transform 0.25s ease;
        }

        .logo-link:hover .logo-icon {
            transform: scale(1.08) rotate(-3deg);
        }

        .logo-text {
            font-family: var(--font-heading);
            font-size: 1.4rem;
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

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-nav-home {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.42rem 1.15rem;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            font-size: 0.84rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-nav-home:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.4);
        }

        .btn-nav-register {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.42rem 1.25rem;
            border-radius: 25px;
            background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%);
            color: #ffffff;
            font-size: 0.84rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
            transition: all 0.2s ease;
        }

        .btn-nav-register:hover {
            background: linear-gradient(135deg, #f87171 0%, #fbbf24 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.55);
            color: #ffffff;
        }

        /* Form Wrapper & Content - Zero page scrolling */
        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0.75rem 1.25rem;
            overflow: hidden !important;
        }

        .form-card {
            background: var(--card-bg);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 16px;
            padding: 1.6rem 2.2rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 30px rgba(245, 158, 11, 0.12);
            position: relative;
        }

        /* Header block with Straw Hat gradient bar */
        .form-header-row {
            margin-bottom: 0.85rem;
        }

        .form-title-group {
            display: flex;
            flex-direction: column;
        }

        .title-with-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.25rem;
        }

        .pirate-bar,
        .orange-bar {
            width: 5px;
            height: 28px;
            background: linear-gradient(180deg, #f59e0b 0%, #ef4444 100%);
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
        }

        .form-title {
            font-family: var(--font-heading);
            font-size: 1.55rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.5px;
            line-height: 1.1;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
        }

        .form-subtitle {
            color: #cbd5e1;
            font-size: 0.86rem;
            margin-left: calc(5px + 0.75rem);
            font-weight: 500;
        }

        /* Section divider header */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.6rem 0 0.95rem;
            position: relative;
        }

        .section-bullet {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #f59e0b;
            box-shadow: 0 0 8px #f59e0b;
            flex-shrink: 0;
        }

        .section-title-text {
            font-family: var(--font-heading);
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #fbbf24;
            text-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
            text-transform: uppercase;
            white-space: nowrap;
        }

        .section-line {
            flex: 1;
            height: 1px;
            background: repeating-linear-gradient(to right, rgba(245, 158, 11, 0.4) 0, rgba(245, 158, 11, 0.4) 4px, transparent 4px, transparent 8px);
        }

        /* Form Inputs */
        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
            margin-bottom: 0.85rem;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: #fbbf24;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 0.35rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
        }

        .req-star {
            color: #ef4444;
            font-weight: 800;
        }

        .input-control {
            width: 100%;
            height: 42px;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 8px;
            padding: 0 0.95rem;
            color: #ffffff !important;
            font-weight: 600;
            font-family: var(--font-main);
            font-size: 0.95rem;
            transition: all 0.2s ease;
            box-sizing: border-box;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            outline: none;
        }

        .input-control::placeholder {
            color: #94a3b8 !important;
            font-weight: 500;
        }

        .input-control:focus {
            border-color: #fbbf24 !important;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.35) !important;
            background: rgba(15, 23, 42, 0.95) !important;
            color: #ffffff !important;
        }

        /* Autofill High Contrast Fix */
        .input-control:-webkit-autofill,
        .input-control:-webkit-autofill:hover, 
        .input-control:-webkit-autofill:focus, 
        .input-control:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #090d16 inset !important;
            -webkit-text-fill-color: #ffffff !important;
            caret-color: #ffffff !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Form Options Row (Show Password & Forgot Password) */
        .form-options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0.15rem 0 1rem;
            font-size: 0.85rem;
        }

        .checkbox-container {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-container input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #f59e0b;
            cursor: pointer;
            border-radius: 4px;
        }

        .checkbox-label {
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .forgot-link-btn {
            color: #f59e0b;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.82rem;
            transition: color 0.2s ease;
        }

        .forgot-link-btn:hover {
            color: #fbbf24;
            text-decoration: underline;
        }

        /* Submit Button */
        .btn-step-submit {
            width: 100%;
            height: 42px;
            background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 5px 18px rgba(239, 68, 68, 0.4);
            transition: all 0.25s ease;
        }

        .btn-step-submit:hover {
            background: linear-gradient(135deg, #f87171 0%, #fbbf24 100%);
            transform: translateY(-1px);
            box-shadow: 0 7px 22px rgba(245, 158, 11, 0.55);
        }

        .btn-step-submit:active {
            transform: translateY(0);
        }

        /* Error Messages & Alerts */
        .error-message {
            color: #f87171;
            font-size: 0.74rem;
            font-weight: 600;
            margin-top: 3px;
            display: block;
        }

        .alert-box {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
            padding: 0.65rem 0.95rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-top: 0.85rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.45rem;
            text-align: center;
            line-height: 1.3;
        }

        .alert-box-top {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .alert-box-stats {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: rgba(254, 202, 202, 0.8);
        }

        .alert-stat {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .alert-stat-label {
            opacity: 0.7;
            font-weight: 500;
        }

        .alert-stat-value {
            font-weight: 700;
            color: #fca5a5;
        }

        .alert-stat-divider {
            opacity: 0.35;
            font-size: 0.85rem;
        }

        /* Attempt Counter Badge */
        .attempt-counter-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fbbf24;
            padding: 0.45rem 0.8rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.85rem;
            text-align: center;
        }

        .attempt-counter-box strong {
            color: #fef08a;
            font-size: 0.95rem;
            font-weight: 800;
            font-family: monospace;
        }

        /* Disabled & Lockout States */
        .disabled,
        [disabled] {
            pointer-events: none !important;
            opacity: 0.55 !important;
            cursor: not-allowed !important;
        }

        body.countdown-active {
            user-select: none;
        }

        body.countdown-active .navbar a,
        body.countdown-active .navbar button,
        body.countdown-active .form-card input,
        body.countdown-active .form-card button,
        body.countdown-active .form-card a,
        body.countdown-active footer a {
            pointer-events: none !important;
            cursor: not-allowed !important;
        }

        body.countdown-active .form-card input {
            background: #090e18 !important;
            border-color: rgba(255, 255, 255, 0.04) !important;
            color: #475569 !important;
        }

        body.countdown-active .btn-step-submit {
            background: #1e293b !important;
            color: #64748b !important;
            box-shadow: none !important;
            transform: none !important;
            cursor: not-allowed !important;
        }

        /* In-form Lockout Countdown Banner */
        .lockout-box {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(239, 68, 68, 0.18) 100%);
            border: 1px solid rgba(245, 158, 11, 0.6);
            color: #fed7aa;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.86rem;
            font-weight: 600;
            margin-top: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-align: left;
            box-shadow: 0 0 25px rgba(245, 158, 11, 0.25);
            animation: pulse-glow 2s infinite ease-in-out;
        }

        .lockout-box .lock-icon-wrap {
            width: 38px;
            height: 38px;
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.5);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .lockout-box .lock-content {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            flex: 1;
        }

        .lockout-box .lock-header {
            font-family: var(--font-heading);
            font-size: 0.9rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .lockout-box .countdown-pill {
            font-family: monospace;
            font-size: 1.1rem;
            font-weight: 900;
            color: #ffffff;
            background: #f59e0b;
            padding: 0.15rem 0.55rem;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.6);
        }

        .lockout-box .lock-desc {
            font-size: 0.78rem;
            color: #cbd5e1;
            font-weight: 500;
            line-height: 1.3;
        }

        .lockout-box strong {
            color: #ffffff;
            font-size: 1rem;
            font-weight: 800;
            background: rgba(245, 158, 11, 0.25);
            padding: 0.12rem 0.4rem;
            border-radius: 6px;
            border: 1px solid rgba(245, 158, 11, 0.4);
            margin: 0 0.2rem;
            font-family: monospace;
        }

        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 15px rgba(245, 158, 11, 0.2);
                border-color: rgba(245, 158, 11, 0.35);
            }
            50% {
                box-shadow: 0 0 25px rgba(245, 158, 11, 0.45);
                border-color: rgba(245, 158, 11, 0.65);
            }
        }

        .unlock-box {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #4ade80;
            padding: 0.65rem 0.9rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-top: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-align: center;
        }

        /* Auth Footer */
        .auth-footer-link {
            text-align: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(245, 158, 11, 0.15);
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .auth-footer-link a {
            color: #f59e0b;
            text-decoration: none;
            font-weight: 700;
            margin-left: 0.3rem;
            transition: color 0.2s;
        }

        .auth-footer-link a:hover {
            color: #fbbf24;
            text-decoration: underline;
        }

        /* Footer */
        footer {
            border-top: 1px solid rgba(245, 158, 11, 0.15);
            padding: 0.6rem 1rem;
            text-align: center;
            color: #64748b;
            font-size: 0.78rem;
            margin-top: auto;
        }

        footer a {
            color: #64748b;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        footer a:hover {
            color: #f59e0b;
        }

        /* First Login Password Change Modal */
        .first-login-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 1rem;
        }

        .first-login-modal-card {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.96) 0%, rgba(24, 34, 53, 0.94) 100%);
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 16px;
            padding: 2rem;
            max-width: 460px;
            width: 100%;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), 0 0 35px rgba(245, 158, 11, 0.15);
            text-align: center;
        }

        .modal-header-badge {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: rgba(245, 158, 11, 0.15);
            border: 1.5px solid rgba(245, 158, 11, 0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .modal-title {
            font-family: var(--font-heading);
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .modal-desc {
            font-size: 0.85rem;
            color: #cbd5e1;
            margin-bottom: 1.25rem;
            line-height: 1.4;
        }

        @media (max-width: 520px) {
            .form-card {
                padding: 1.35rem 1.25rem;
            }
            .form-title {
                font-size: 1.35rem;
            }
        }
    </style>
</head>

<body class="<?php echo ($remaining_time > 0) ? 'countdown-active' : ''; ?>">
    <!-- Top Navbar with Straw Hat Jolly Roger -->
    <nav class="navbar">
        <div class="navdiv">
            <a href="homepage.php" class="logo-link">
                <div class="logo-icon">
                    <svg width="30" height="30" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
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
            <div class="nav-actions">
                <a href="homepage.php" class="btn-nav-home">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Home
                </a>
                <a href="registration.php" class="btn-nav-register">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Join Crew
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <div class="form-card">
            <!-- Header with Straw Hat flame bar -->
            <div class="form-header-row">
                <div class="form-title-group">
                    <div class="title-with-bar">
                        <div class="pirate-bar"></div>
                        <h1 class="form-title">PIRATE LOG IN</h1>
                    </div>
                    <p class="form-subtitle">Enter your credentials to board the Thousand Sunny</p>
                </div>
            </div>

            <!-- Section Divider -->
            <div class="section-header">
                <div class="section-bullet"></div>
                <span class="section-title-text">CREW CREDENTIALS</span>
                <div class="section-line"></div>
            </div>

            <form action="" method="post" id="registrationForm" novalidate>
                <!-- Username Field -->
                <div class="form-group">
                    <label for="username" class="form-label">
                        Username <span class="req-star required-asterisk">*</span>
                    </label>
                    <input type="text" name="username" id="username" class="input-control<?php echo ($remaining_time > 0) ? ' disabled' : ''; ?>" placeholder="Enter your crew username" autocomplete="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" <?php echo ($remaining_time > 0) ? 'disabled tabindex="-1"' : ''; ?>>
                    <small id="username_error" class="error-message"><?php echo htmlspecialchars($username_error); ?></small>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        Password <span class="req-star required-asterisk">*</span>
                    </label>
                    <input type="password" name="password" id="password" class="input-control<?php echo ($remaining_time > 0) ? ' disabled' : ''; ?>" placeholder="Enter your secret key" autocomplete="current-password" <?php echo ($remaining_time > 0) ? 'disabled tabindex="-1"' : ''; ?>>
                    <small id="password_error" class="error-message"><?php echo htmlspecialchars($password_error); ?></small>
                </div>

                <!-- Show Password & Forgot Password row -->
                <div class="form-options-row">
                    <label class="checkbox-container">
                        <input type="checkbox" id="togglePassword" <?php echo ($remaining_time > 0) ? 'disabled tabindex="-1"' : ''; ?>>
                        <span class="checkbox-label">Show Password</span>
                    </label>
                    <div id="forgot-password-link"></div>
                </div>

                <!-- Submit Button -->
                <button id="mySubmit" type="submit" class="btn-step-submit<?php echo ($remaining_time > 0) ? ' disabled' : ''; ?>" <?php echo ($remaining_time > 0) ? 'disabled tabindex="-1"' : ''; ?>>
                    <span>Board Ship (Sign In)</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>

                <!-- Hidden indicator for JS -->
                <input type="hidden" id="show-forgot-password" value="<?php echo isset($show_forgot_password) ? $show_forgot_password : 'false'; ?>">

                <!-- Error Message -->
                <?php if (!empty($error_message)): ?>
                <div id="error-message" class="alert-box">
                    <div class="alert-box-top">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                    <?php if (isset($cycle_attempt) && isset($attempts_left)): ?>
                    <div class="alert-box-stats">
                        <span class="alert-stat"><span class="alert-stat-label">Attempt</span><span class="alert-stat-value"><?php echo $cycle_attempt; ?> / 3</span></span>
                        <span class="alert-stat-divider">|</span>
                        <span class="alert-stat"><span class="alert-stat-label">Remaining</span><span class="alert-stat-value"><?php echo $attempts_left; ?> <?php echo $attempts_left === 1 ? 'attempt' : 'attempts'; ?></span></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div id="error-message" class="alert-box" style="display: none;"></div>
                <?php endif; ?>

                <!-- Lockout Countdown Notice -->
                <div id="countdown-timer" class="lockout-box" style="<?php echo ($remaining_time > 0) ? 'display: flex;' : 'display: none;'; ?>">
                    <?php if ($remaining_time > 0): ?>
                    <div class="lock-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <div class="lock-content">
                        <div class="lock-header">
                            <span id="countdown-status-text" style="color:#fbbf24; font-weight:800; font-size:0.95rem;"><?php echo ($remaining_time == 1) ? 'Please wait 1 second.' : 'Please wait ' . intval($remaining_time) . ' seconds.'; ?></span>
                        </div>
                        <div class="lock-desc">Too many failed attempts. All buttons and fields are disabled.</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Footer Link -->
                <div class="auth-footer-link">
                    Not part of the crew yet? <a href="registration.php">Join Crew Here</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 ONE PIECE System. All rights reserved.</p>
        <p style="margin-top: 0.25rem;"><a href="#">Grand Line Security Policy</a></p>
    </footer>

    <script>
        var remainingTime = <?php echo isset($remaining_time) ? intval($remaining_time) : 0; ?>;
        var loginAttempts = <?php echo intval($_SESSION['login_attempts']); ?>;
        var showForgotPassword = "<?php echo isset($show_forgot_password) ? $show_forgot_password : 'false'; ?>";
        var errorMessage = "<?php echo isset($error_message) ? addslashes($error_message) : ''; ?>";

        // Initialize Forgot Password link
        function initForgotPassword() {
            var container = document.getElementById('forgot-password-link');
            if (container && (showForgotPassword === 'true' || showForgotPassword === true)) {
                container.innerHTML = '<a href="Galagate/php/forgot_password.php" class="forgot-link-btn">Forgot Password?</a>';
            }
        }

        // Initialize Show/Hide Password Toggle
        function initShowPassword() {
            var toggle = document.getElementById('togglePassword');
            var passwordField = document.getElementById('password');
            if (toggle && passwordField) {
                toggle.addEventListener('change', function () {
                    passwordField.type = this.checked ? 'text' : 'password';
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener("DOMContentLoaded", function() {
                initForgotPassword();
                initShowPassword();
            });
        } else {
            initForgotPassword();
            initShowPassword();
        }

        // Self-Contained Lockout Countdown
        (function () {
            var seconds = parseInt(remainingTime, 10) || 0;
            if (seconds <= 0) return;

            function formatWaitText(s) {
                if (s === 1) return 'Please wait 1 second.';
                return 'Please wait ' + s + ' seconds.';
            }

            function stopEvent(e) {
                e.stopPropagation();
                e.preventDefault();
                return false;
            }

            var countdownTimer = document.getElementById('countdown-timer');
            var submitButton   = document.getElementById('mySubmit');
            var usernameField  = document.getElementById('username');
            var errorBox       = document.getElementById('error-message');
            var attemptBadge   = document.getElementById('attempt-counter');

            if (errorBox) errorBox.style.display = 'none';
            if (attemptBadge) attemptBadge.style.display = 'none';

            // Lock all interactions immediately
            document.body.classList.add('countdown-active');
            var interactives = document.querySelectorAll('input, button, select, textarea, a');
            interactives.forEach(function (el) {
                el.classList.add('disabled');
                if (el.tagName !== 'A') el.disabled = true;
                el.setAttribute('tabindex', '-1');
            });

            window.addEventListener('click',     stopEvent, true);
            window.addEventListener('mousedown', stopEvent, true);
            window.addEventListener('keydown',   stopEvent, true);
            window.addEventListener('keyup',     stopEvent, true);
            window.addEventListener('keypress',  stopEvent, true);
            window.addEventListener('submit',    stopEvent, true);

            function updateUI(s) {
                var waitText = formatWaitText(s);

                if (countdownTimer) {
                    var statusText = document.getElementById('countdown-status-text');
                    if (statusText) {
                        statusText.textContent = waitText;
                    } else {
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
                    }
                }
            }

            function unlockUI() {
                document.body.classList.remove('countdown-active');
                var interactives = document.querySelectorAll('input, button, select, textarea, a');
                interactives.forEach(function (el) {
                    el.classList.remove('disabled');
                    if (el.tagName !== 'A') el.disabled = false;
                    el.removeAttribute('tabindex');
                });

                window.removeEventListener('click',     stopEvent, true);
                window.removeEventListener('mousedown', stopEvent, true);
                window.removeEventListener('keydown',   stopEvent, true);
                window.removeEventListener('keyup',     stopEvent, true);
                window.removeEventListener('keypress',  stopEvent, true);
                window.removeEventListener('submit',    stopEvent, true);

                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.classList.remove('disabled');
                }

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

            // Initial render
            updateUI(seconds);

            // Real-time second-by-second countdown
            var intervalId = setInterval(function () {
                seconds -= 1;
                if (seconds <= 0) {
                    clearInterval(intervalId);
                    unlockUI();
                } else {
                    updateUI(seconds);
                }
            }, 1000);
        })();
    </script>
    <!-- First Login Password Change Modal -->
    <?php if ($show_change_pwd_modal): ?>
    <div class="first-login-modal-overlay" id="firstLoginModal" style="display: flex;">
        <div class="first-login-modal-card">
            <div class="modal-header-badge">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h2 class="modal-title">CHANGE DEFAULT PASSWORD</h2>
            <p class="modal-desc">This account is using a default password. For your security, you must create a new password before accessing your account.</p>

            <?php if (!empty($pwd_change_error)): ?>
            <div class="alert-box" style="margin-bottom: 1rem; width: 100%;">
                <div class="alert-box-top">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><?php echo htmlspecialchars($pwd_change_error); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <form method="post" action="" style="width: 100%;">
                <input type="hidden" name="action" value="change_first_login_password">
                <input type="hidden" name="pending_user_id" value="<?php echo htmlspecialchars($_SESSION['pending_user_id'] ?? ''); ?>">
                <input type="hidden" name="pending_user_role" value="<?php echo htmlspecialchars($_SESSION['pending_user_role'] ?? ''); ?>">
                <input type="hidden" name="pending_username" value="<?php echo htmlspecialchars($_SESSION['pending_username'] ?? ''); ?>">

                <div class="form-group" style="text-align: left;">
                    <label class="form-label">New Password <span class="req-star">*</span></label>
                    <input type="password" name="new_password" id="firstLoginNewPwd" class="input-control" placeholder="Enter new secure password" required autocomplete="new-password">
                </div>

                <div class="form-group" style="text-align: left;">
                    <label class="form-label">Confirm New Password <span class="req-star">*</span></label>
                    <input type="password" name="confirm_password" id="firstLoginConfirmPwd" class="input-control" placeholder="Re-type new password" required autocomplete="new-password">
                </div>

                <div class="form-options-row" style="margin-bottom: 1rem;">
                    <label class="checkbox-container">
                        <input type="checkbox" id="toggleFirstLoginPwd">
                        <span class="checkbox-label">Show Passwords</span>
                    </label>
                </div>

                <div class="password-guidelines" style="text-align: left; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.78rem; color: #94a3b8;">
                    <div style="font-weight: 700; color: #cbd5e1; margin-bottom: 0.35rem;">Password Requirements:</div>
                    <ul style="margin-left: 1.2rem; line-height: 1.4;">
                        <li>At least 8 characters long</li>
                        <li>At least 1 uppercase and 1 lowercase letter</li>
                        <li>At least 1 number and 1 special symbol (!@#$%^&*)</li>
                        <li>Cannot be the default password (@Abcde12345)</li>
                    </ul>
                </div>

                <button type="submit" class="btn-step-submit" style="width: 100%;">
                    <span>Save Password & Continue</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    <style>
        .first-login-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(5, 10, 20, 0.88);
            backdrop-filter: blur(14px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            animation: fadeIn 0.3s ease;
        }
        .first-login-modal-card {
            background: rgba(9, 18, 32, 0.95);
            border: 1px solid rgba(245, 158, 11, 0.4);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.85), 0 0 35px rgba(245, 158, 11, 0.2);
            border-radius: 16px;
            max-width: 460px;
            width: 100%;
            padding: 2.2rem 2rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .modal-header-badge {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.3);
        }
        .modal-title {
            font-family: var(--font-heading, sans-serif);
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.4rem;
            letter-spacing: 0.5px;
        }
        .modal-desc {
            font-size: 0.84rem;
            color: #94a3b8;
            margin-bottom: 1.25rem;
            line-height: 1.4;
        }
    </style>
    <script>
        document.getElementById('toggleFirstLoginPwd')?.addEventListener('change', function() {
            var np = document.getElementById('firstLoginNewPwd');
            var cp = document.getElementById('firstLoginConfirmPwd');
            if (np) np.type = this.checked ? 'text' : 'password';
            if (cp) cp.type = this.checked ? 'text' : 'password';
        });
    </script>
    <?php endif; ?>

    <script src="../js/val_login.js?v=<?php echo time(); ?>"></script>
    <script src="../js/show_password.js?v=<?php echo time(); ?>"></script>
</body>

</html>