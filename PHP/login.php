<?php
session_start();
include("db.php");

// Initialize session variables if not set
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['denied_until'] = 0;
}

// Show forgot password only after 2 attempts
$show_forgot_password = (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 2) ? 'true' : 'false';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (time() < $_SESSION['denied_until']) {
        $remaining_time = $_SESSION['denied_until'] - time();
    } else {
        $_SESSION['denied_until'] = 0;

        if (!empty($username) && !empty($password)) {
            // Prepare the query to get user data by username
            $query = "SELECT * FROM user WHERE username = '$username' LIMIT 1";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);

                // Verify the entered password against the hashed password in the database
                if (password_verify($password, $user_data['password'])) {
                    // Check if user is blocked or pending
                    $user_status = isset($user_data['status']) ? $user_data['status'] : 'active';
                    if ($user_status === 'blocked') {
                        $_SESSION['login_attempts'] += 1;
                        $error_message = "Your account has been blocked. Please contact an administrator.";
                    } elseif ($user_status === 'pending') {
                        $_SESSION['login_attempts'] += 1;
                        $error_message = "Your account is pending admin approval.";
                    } else {
                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['denied_until'] = 0;

                        // Role-based Redirection
                        $role = isset($user_data['role']) ? $user_data['role'] : 'user';
                        $_SESSION['role'] = $role;
                        $_SESSION['user_id'] = $user_data['id']; // Good practice to store ID
                        $_SESSION['username'] = $user_data['username'];

                        if ($role === 'superadmin') {
                            header("location: superadmin/dashboard.php");
                        } elseif ($role === 'admin') {
                            header("location: admin/dashboard.php");
                        } else {
                            header("location: home.php");
                        }

                        // Audit Log
                        $log_user_id = $user_data['id'];
                        $log_username = $user_data['username'];
                        $log_role = $role;
                        $log_action = "Login";
                        $user_agent = $_SERVER['HTTP_USER_AGENT'];
                        $hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                        $log_details = "IP: " . $_SERVER['REMOTE_ADDR'] . " | Host: " . $hostname . " | Device: " . $user_agent;

                        $log_sql = "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $log_sql);
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "issss", $log_user_id, $log_username, $log_role, $log_action, $log_details);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }

                        die;
                    }
                } else {
                    $_SESSION['login_attempts'] += 1;
                    $error_message = "Incorrect password. You have " . (3 - ($_SESSION['login_attempts'] % 3)) . " attempts left before lockout.";
                }
            } else {
                $_SESSION['login_attempts'] += 1;
                $error_message = "Username does not exist. You have " . (3 - ($_SESSION['login_attempts'] % 3)) . " attempts left before lockout.";
            }

            // Lockout logic
            if ($_SESSION['login_attempts'] == 3) {
                $_SESSION['denied_until'] = time() + 15;
                $remaining_time = 15;
                $error_message = ""; // Clear the error message
            } elseif ($_SESSION['login_attempts'] == 6) {
                $_SESSION['denied_until'] = time() + 30;
                $remaining_time = 30;
                $error_message = ""; // Clear the error message
            } elseif ($_SESSION['login_attempts'] >= 9) {
                $_SESSION['denied_until'] = time() + 60;
                $remaining_time = 60;
                $error_message = ""; // Clear the error message
            }
        } else {
            $error_message = "Please fill in all fields.";
        }

        // PHP will set a flag for JavaScript to detect if there are 2 or more failed attempts
        $show_forgot_password = $_SESSION['login_attempts'] >= 2 ? 'true' : 'false';
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MOTOPARTS</title>
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

        .btn-nav-register {
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

        .btn-nav-register:hover {
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
            max-width: 460px;
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

        .input-control {
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

        .input-control:focus {
            border-color: var(--input-border-focus);
            box-shadow: 0 0 0 3px var(--accent-orange-glow);
            background: #181f2c;
        }

        /* Form Options Row (Show Password & Forgot Password) */
        .form-options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0.25rem 0 1.25rem;
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
            accent-color: var(--accent-orange);
            cursor: pointer;
            border-radius: 4px;
        }

        .checkbox-label {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.85rem;
        }

        .forgot-link-btn {
            color: var(--accent-orange);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s ease;
        }

        .forgot-link-btn:hover {
            color: var(--accent-orange-hover);
            text-decoration: underline;
        }

        /* Submit Button */
        .btn-step-submit {
            width: 100%;
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

        /* Error Messages & Alerts */
        .error-message {
            color: #ff4d4d;
            font-size: 0.76rem;
            font-weight: 600;
            margin-top: 4px;
            display: block;
        }

        .alert-box {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ff6b6b;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-align: center;
            line-height: 1.3;
        }

        .lockout-box {
            background: rgba(255, 85, 0, 0.15);
            border: 1px solid rgba(255, 85, 0, 0.4);
            color: #ff8533;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-align: center;
            box-shadow: 0 0 15px rgba(255, 85, 0, 0.2);
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
                padding: 1.75rem 1.5rem;
            }
            .form-title {
                font-size: 1.5rem;
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
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Home
                </a>
                <a href="registration.php" class="btn-nav-register">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Register
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
                        <h1 class="form-title">ACCOUNT LOGIN</h1>
                    </div>
                    <p class="form-subtitle">Enter your credentials to access your account</p>
                </div>
            </div>

            <!-- Section Divider -->
            <div class="section-header">
                <div class="section-bullet"></div>
                <span class="section-title-text">CREDENTIALS</span>
                <div class="section-line"></div>
            </div>

            <form action="" method="post" id="registrationForm">
                <!-- Username Field -->
                <div class="form-group">
                    <label for="username" class="form-label">
                        Username <span class="req-star required-asterisk">*</span>
                    </label>
                    <input type="text" name="username" id="username" class="input-control" placeholder="Enter your username" required autocomplete="username">
                    <small id="username_error" class="error-message"></small>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        Password <span class="req-star required-asterisk">*</span>
                    </label>
                    <input type="password" name="password" id="password" class="input-control" placeholder="Enter your password" required autocomplete="current-password">
                    <small id="password_error" class="error-message"></small>
                </div>

                <!-- Show Password & Forgot Password row -->
                <div class="form-options-row">
                    <label class="checkbox-container" for="togglePassword">
                        <input type="checkbox" id="togglePassword">
                        <span class="checkbox-label">Show Password</span>
                    </label>
                    <div id="forgot-password-link"></div>
                </div>

                <!-- Submit Button -->
                <button id="mySubmit" type="submit" class="btn-step-submit">
                    <span>Sign In</span>
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
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><?php echo $error_message; ?></span>
                </div>
                <?php else: ?>
                <div id="error-message" class="alert-box" style="display: none;"></div>
                <?php endif; ?>

                <!-- Lockout Countdown Notice -->
                <div id="countdown-timer" class="lockout-box" style="display: none;"></div>

                <!-- Footer Link -->
                <div class="auth-footer-link">
                    Don't have an account? <a href="registration.php">Register Here</a>
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
        var remainingTime = <?php echo isset($remaining_time) ? $remaining_time : 0; ?>;
        var showForgotPassword = "<?php echo isset($show_forgot_password) ? $show_forgot_password : 'false'; ?>";
        var errorMessage = "<?php echo isset($error_message) ? addslashes($error_message) : ''; ?>";

        document.addEventListener("DOMContentLoaded", function () {
            var container = document.getElementById('forgot-password-link');
            if (container && (showForgotPassword === 'true' || showForgotPassword === true)) {
                container.innerHTML = '<a href="BANTILAN_V7/php/forgot_password.php" class="forgot-link-btn">Forgot Password?</a>';
            }
        });
    </script>
    <script src="../js/countdown.js"></script>
    <script src="../js/val_login.js"></script>
    <script src="../js/show_password.js"></script>
</body>

</html>