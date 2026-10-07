<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth_guard.php';

// Redirect to login if user is not authenticated
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = trim((string)$_SESSION['user_id']);
$role = $_SESSION['role'];
$username = $_SESSION['username'] ?? 'User';

// If profile is already completely filled out, redirect directly to dashboard
$is_truly_complete = check_user_personal_info_complete($conn, $user_id);
if ($is_truly_complete) {
    $_SESSION['profile_completed'] = 1;
    @mysqli_query($conn, "UPDATE user SET profile_completed = 1 WHERE idno = '" . mysqli_real_escape_string($conn, $user_id) . "'");
    if ($role === 'superadmin') {
        header("Location: superadmin/dashboard.php");
    } elseif ($role === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: home.php");
    }
    exit();
}

// Fetch current user record to pre-populate any existing values
$user_row = [];
$uid_safe = mysqli_real_escape_string($conn, $user_id);
$fetch_q = mysqli_query($conn, "SELECT * FROM user WHERE idno = '$uid_safe' LIMIT 1");
if ($fetch_q && ($r = mysqli_fetch_assoc($fetch_q))) {
    $user_row = $r;
}

$errors = [];
$success_msg = "";

// Form Data defaults (from DB, or fallback to empty)
$idno = $user_row['idno'] ?? $user_id;
$firstname = $user_row['firstname'] ?? '';
$middlename = $user_row['middlename'] ?? '';
$lastname = $user_row['lastname'] ?? '';
$suffix = $user_row['suffix'] ?? '';
$customSuffix = '';
$sex = $user_row['sex'] ?? '';
$birthdate = $user_row['birthdate'] ?? '';
$age = $user_row['age'] ?? '';
$purokstreet = $user_row['purokstreet'] ?? '';
$brgy = $user_row['brgy'] ?? '';
$municipal = $user_row['municipal'] ?? '';
$province = $user_row['province'] ?? '';
$country = !empty($user_row['country']) ? $user_row['country'] : 'Philippines';
$zipcode = $user_row['zipcode'] ?? '';
$email = $user_row['email'] ?? '';
$security_question1 = $user_row['security_question1'] ?? '';
$security_question2 = $user_row['security_question2'] ?? '';
$security_question3 = $user_row['security_question3'] ?? '';
$security_answer1 = '';
$security_answer2 = '';
$security_answer3 = '';

// Check if existing suffix is custom
if (!in_array($suffix, ['', ' ', 'Jr', 'Sr'])) {
    $customSuffix = $suffix;
    $suffix = 'others';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $customSuffix = trim($_POST['customSuffix'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $sex = trim($_POST['sex'] ?? '');

    $purokstreet = trim($_POST['purokstreet'] ?? '');
    $brgy = trim($_POST['brgy'] ?? '');
    $municipal = trim($_POST['municipal'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $country = trim($_POST['country'] ?? 'Philippines');
    $zipcode = trim($_POST['zipcode'] ?? '');

    $email = strtolower(trim($_POST['email'] ?? ''));
    $security_question1 = trim($_POST['security_question1'] ?? '');
    $security_answer1 = trim($_POST['security_answer1'] ?? '');
    $security_question2 = trim($_POST['security_question2'] ?? '');
    $security_answer2 = trim($_POST['security_answer2'] ?? '');
    $security_question3 = trim($_POST['security_question3'] ?? '');
    $security_answer3 = trim($_POST['security_answer3'] ?? '');

    // 1. Name validations (exact same as registration.php)
    if (empty($firstname)) {
        $errors[] = "First name is required.";
    } elseif (strlen($firstname) < 3 || strlen($firstname) > 25) {
        $errors[] = "First name must be between 3 and 25 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstname)) {
        $errors[] = "First name can only contain letters and spaces.";
    }

    if (!empty($middlename)) {
        if (strlen($middlename) > 25) {
            $errors[] = "Middle name/initial cannot exceed 25 characters.";
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $middlename)) {
            $errors[] = "Middle name can only contain letters and spaces.";
        }
    }

    if (empty($lastname)) {
        $errors[] = "Last name is required.";
    } elseif (strlen($lastname) < 3 || strlen($lastname) > 25) {
        $errors[] = "Last name must be between 3 and 25 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastname)) {
        $errors[] = "Last name can only contain letters and spaces.";
    }

    $suffixToUse = "";
    if ($suffix === "others") {
        if (empty($customSuffix)) {
            $errors[] = "Please specify your custom suffix.";
        } elseif (strlen($customSuffix) > 10) {
            $errors[] = "Suffix cannot exceed 10 characters.";
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $customSuffix)) {
            $errors[] = "Suffix can only contain letters and spaces.";
        } else {
            $suffixToUse = strtoupper($customSuffix);
        }
    } elseif (in_array($suffix, ["Jr", "Sr"])) {
        $suffixToUse = $suffix;
    }

    // 2. Sex & Birthdate / Age
    if (!in_array($sex, ["Male", "Female"])) {
        $errors[] = "Please select a valid sex (Male or Female).";
    }

    $calculated_age = 0;
    if (empty($birthdate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
        $errors[] = "A valid birthdate is required.";
    } else {
        $bdateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$bdateObj || $bdateObj->format('Y-m-d') !== $birthdate) {
            $errors[] = "Invalid birthdate format.";
        } else {
            $today = new DateTime();
            $calculated_age = $today->diff($bdateObj)->y;
            if ($calculated_age < 18) {
                $errors[] = "You must be at least 18 years old.";
            } elseif ($calculated_age > 120) {
                $errors[] = "Please enter a valid birthdate.";
            }
        }
    }
    $age = $calculated_age;

    // 3. Address fields
    if (empty($purokstreet)) {
        $errors[] = "Purok/Street is required.";
    } elseif (strlen($purokstreet) > 25) {
        $errors[] = "Purok/Street cannot exceed 25 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $purokstreet)) {
        $errors[] = "Purok/Street can only contain letters, numbers, spaces, and hyphens.";
    }

    if (empty($brgy)) {
        $errors[] = "Barangay is required.";
    } elseif (strlen($brgy) < 3 || strlen($brgy) > 25) {
        $errors[] = "Barangay must be between 3 and 25 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $brgy)) {
        $errors[] = "Barangay can only contain letters, numbers, spaces, and hyphens.";
    }

    if (empty($municipal)) {
        $errors[] = "Municipality/City is required.";
    } elseif (strlen($municipal) < 3 || strlen($municipal) > 25) {
        $errors[] = "Municipality must be between 3 and 25 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $municipal)) {
        $errors[] = "Municipality can only contain letters and spaces.";
    }

    if (empty($province)) {
        $errors[] = "Province is required.";
    } elseif (strlen($province) < 3 || strlen($province) > 25) {
        $errors[] = "Province must be between 3 and 25 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $province)) {
        $errors[] = "Province can only contain letters and spaces.";
    }

    if (empty($country)) {
        $errors[] = "Country is required.";
    } elseif (strlen($country) < 4 || strlen($country) > 40) {
        $errors[] = "Country must be between 4 and 40 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $country)) {
        $errors[] = "Country can only contain letters and spaces.";
    }

    if (empty($zipcode)) {
        $errors[] = "Zip code is required.";
    } elseif (!preg_match('/^[0-9]{4,6}$/', $zipcode)) {
        $errors[] = "Zip code must be 4 to 6 numeric digits.";
    }

    // 4. Email validation
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        $email_domain = strtolower(substr(strrchr($email, "@"), 1));
        $is_gmail = ($email_domain === 'gmail.com');
        $is_university = (preg_match('/^[a-z0-9.-]+\.edu\.ph$/i', $email_domain) || preg_match('/^[a-z0-9.-]+\.edu$/i', $email_domain));
        if (!$is_gmail && !$is_university) {
            $errors[] = "Only Gmail (@gmail.com) and university email (@...edu.ph / @...edu) are allowed.";
        }
    }

    // 5. Security Questions & Answers
    if (empty($security_question1) || empty($security_question2) || empty($security_question3)) {
        $errors[] = "All 3 security questions must be selected.";
    } elseif ($security_question1 === $security_question2 || $security_question1 === $security_question3 || $security_question2 === $security_question3) {
        $errors[] = "Please choose 3 different security questions.";
    }

    if (empty($security_answer1) || empty($security_answer2) || empty($security_answer3)) {
        $errors[] = "Answers are required for all 3 security questions.";
    } elseif (strlen($security_answer1) < 2 || strlen($security_answer2) < 2 || strlen($security_answer3) < 2) {
        $errors[] = "Each security answer must be at least 2 characters.";
    }

    // 6. Check unique email (excluding current user's own idno)
    if (empty($errors)) {
        $stmt_e = mysqli_prepare($conn, "SELECT idno FROM user WHERE email = ? AND idno != ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_e, "ss", $email, $user_id);
        mysqli_stmt_execute($stmt_e);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_e)) > 0) {
            $errors[] = "Email already registered by another account! Please enter a unique email address.";
        }
        mysqli_stmt_close($stmt_e);
    }

    // 7. Save to database
    if (empty($errors)) {
        $sa1_hash = password_hash(strtolower(trim($security_answer1)), PASSWORD_DEFAULT);
        $sa2_hash = password_hash(strtolower(trim($security_answer2)), PASSWORD_DEFAULT);
        $sa3_hash = password_hash(strtolower(trim($security_answer3)), PASSWORD_DEFAULT);

        $sql_upd = "UPDATE user SET 
            firstname = ?, 
            middlename = ?, 
            lastname = ?, 
            suffix = ?, 
            email = ?, 
            birthdate = ?, 
            age = ?, 
            sex = ?, 
            country = ?, 
            purokstreet = ?, 
            brgy = ?, 
            municipal = ?, 
            province = ?, 
            zipcode = ?, 
            security_question1 = ?, 
            security_answer1 = ?, 
            security_question2 = ?, 
            security_answer2 = ?, 
            security_question3 = ?, 
            security_answer3 = ?, 
            profile_completed = 1 
            WHERE idno = ?";

        $stmt = mysqli_prepare($conn, $sql_upd);
        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "ssssssissssssssssssss",
                $firstname,
                $middlename,
                $lastname,
                $suffixToUse,
                $email,
                $birthdate,
                $calculated_age,
                $sex,
                $country,
                $purokstreet,
                $brgy,
                $municipal,
                $province,
                $zipcode,
                $security_question1,
                $sa1_hash,
                $security_question2,
                $sa2_hash,
                $security_question3,
                $sa3_hash,
                $user_id
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                $_SESSION['profile_completed'] = 1;

                // Audit Log
                $ip_addr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $log_action = "Profile & Security Setup Completed";
                $log_details = "User completed personal info, address, and security questions from IP: " . $ip_addr;
                $stmt_log = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, ?, ?, ?)");
                if ($stmt_log) {
                    mysqli_stmt_bind_param($stmt_log, "sssss", $user_id, $username, $role, $log_action, $log_details);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);
                }

                // Redirect to Dashboard
                if ($role === 'superadmin') {
                    header("Location: superadmin/dashboard.php?setup_success=1");
                } elseif ($role === 'admin') {
                    header("Location: admin/dashboard.php?setup_success=1");
                } else {
                    header("Location: home.php?setup_success=1");
                }
                exit();
            } else {
                $errors[] = "Database error updating profile: " . mysqli_error($conn);
            }
        } else {
            $errors[] = "Database statement error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pirate Log & Setup - ONE PIECE</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --dark-bg: #0b1120;
            --card-bg: linear-gradient(145deg, rgba(15, 23, 42, 0.94) 0%, rgba(24, 34, 53, 0.92) 100%);
            --input-bg: rgba(8, 12, 22, 0.75);
            --input-border: rgba(255, 255, 255, 0.25);
            --input-border-focus: #fbbf24;
            --accent-gold: #fbbf24;
            --accent-gold-glow: rgba(245, 158, 11, 0.35);
            --accent-red: #ef4444;
            --accent-orange: #f97316;
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
            margin: 0;
            padding: 0;
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
            background-position: center bottom;
            background-attachment: fixed;
        }

        /* Top Navbar - One Piece Straw Hat Theme */
        .navbar {
            background: rgba(15, 23, 42, 0.50);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0.55rem 2rem;
            flex-shrink: 0;
            z-index: 100;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.25);
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
            gap: 0.75rem;
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
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .logo-text .op-piece {
            font-weight: 900;
            color: #fbbf24;
            text-shadow: 0 0 14px rgba(245, 158, 11, 0.5);
            margin-left: 3px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-nav-user {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.5);
            color: #fbbf24;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.2);
            backdrop-filter: blur(10px);
        }

        .btn-nav-logout {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.95rem;
            border-radius: 20px;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }

        .btn-nav-logout:hover {
            background: rgba(239, 68, 68, 0.35);
            border-color: #ef4444;
            color: #ffffff;
        }

        /* Form Wrapper & Content - Zero page scrolling */
        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0.4rem 1.25rem;
            overflow: hidden !important;
        }

        .form-card {
            background: var(--card-bg);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 14px;
            padding: 1.15rem 1.85rem;
            width: 100%;
            max-width: 860px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.55), 0 0 35px rgba(245, 158, 11, 0.12);
            position: relative;
        }

        /* Header block with Straw Hat gradient bar & inline step badge */
        .form-header-row {
            margin-bottom: 0.55rem;
        }

        .form-title-group {
            display: flex;
            flex-direction: column;
        }

        .title-with-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.2rem;
        }

        .pirate-bar {
            width: 4px;
            height: 25px;
            background: linear-gradient(180deg, #f59e0b 0%, #ef4444 100%);
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
            flex-shrink: 0;
        }

        .form-title {
            font-family: var(--font-heading);
            font-size: 1.45rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.3px;
            line-height: 1.1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .step-pill {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.22rem 0.75rem;
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.5);
            border-radius: 16px;
            font-size: 0.72rem;
            font-weight: 800;
            color: #fbbf24;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            backdrop-filter: blur(10px);
        }

        .form-subtitle {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-left: calc(4px + 0.75rem);
            font-weight: 400;
        }

        /* Server Alert Messages */
        .server-error-box {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 8px;
            padding: 0.5rem 0.85rem;
            margin-bottom: 0.6rem;
            color: #fca5a5;
            font-size: 0.78rem;
            backdrop-filter: blur(10px);
        }

        .server-error-box ul {
            margin: 0.2rem 0 0 1.1rem;
        }

        /* Section divider header */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.35rem 0 0.55rem;
            position: relative;
        }

        .section-bullet {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #f59e0b;
            box-shadow: 0 0 9px #f59e0b;
            flex-shrink: 0;
        }

        .section-title-text {
            font-family: var(--font-heading);
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 1.2px;
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

        /* Input Grid */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.42rem 1.15rem;
            margin-bottom: 0;
        }

        .grid-container-3col {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.42rem 0.85rem;
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
            gap: 0.3rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: #fbbf24;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
        }

        .req-star {
            color: #ef4444;
            font-weight: 800;
        }

        .label-hint {
            font-size: 0.74rem;
            font-weight: 500;
            color: #cbd5e1;
            text-transform: none;
            letter-spacing: normal;
        }

        /* Custom Inputs */
        .input-control,
        select.input-control {
            width: 100%;
            height: 38px;
            background: rgba(8, 12, 22, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            border-radius: 7px;
            padding: 0 0.85rem;
            color: #ffffff !important;
            font-family: var(--font-main);
            font-size: 0.90rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-sizing: border-box;
            outline: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .input-control::placeholder {
            color: #94a3b8 !important;
            font-weight: 500;
        }

        .input-control:focus,
        select.input-control:focus {
            border-color: #fbbf24 !important;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.35) !important;
            background: rgba(15, 23, 42, 0.95) !important;
            color: #ffffff !important;
        }

        /* Autofill High Contrast Fix */
        .input-control:-webkit-autofill,
        .input-control:-webkit-autofill:hover, 
        .input-control:-webkit-autofill:focus, 
        .input-control:-webkit-autofill:active,
        select.input-control:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px #090d16 inset !important;
            -webkit-text-fill-color: #ffffff !important;
            caret-color: #ffffff !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Readonly ID NO & Age */
        .input-idno {
            color: #38bdf8 !important;
            font-weight: 800 !important;
            font-size: 0.92rem !important;
            letter-spacing: 1px;
            background: rgba(8, 12, 22, 0.90) !important;
            cursor: not-allowed;
            border-color: rgba(56, 189, 248, 0.45) !important;
        }

        .age-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .age-input-wrapper .input-control {
            padding-right: 3.2rem;
            background: rgba(8, 12, 22, 0.90) !important;
            cursor: not-allowed;
            color: #ffffff !important;
            font-weight: 600;
            border-color: rgba(255, 255, 255, 0.2) !important;
        }

        .age-unit-suffix {
            position: absolute;
            right: 0.85rem;
            font-size: 0.78rem;
            font-weight: 800;
            color: #cbd5e1;
            letter-spacing: 1px;
            pointer-events: none;
        }

        /* Custom Select Styling */
        select.input-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23cbd5e1' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.85rem center;
            background-size: 14px;
            padding-right: 2.2rem;
            cursor: pointer;
        }

        select.input-control option {
            background-color: #0f172a !important;
            color: #ffffff !important;
            font-weight: 600;
            padding: 8px 12px;
        }

        /* Date input icon */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }

        /* Under-field Real-Time Error Message */
        .error-msg {
            color: #f87171;
            font-size: 0.72rem;
            font-weight: 600;
            display: none;
            margin-top: 2px;
            line-height: 1.15;
            animation: fadeInMsg 0.2s ease;
        }

        @keyframes fadeInMsg {
            from { opacity: 0; transform: translateY(-2px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-error-highlight {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25) !important;
            background: rgba(239, 68, 68, 0.15) !important;
        }

        /* Answer input with eye toggle */
        .answer-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
            margin-top: 6px;
        }

        .answer-input-wrap .input-control {
            padding-right: 2.5rem;
        }

        .answer-toggle-btn {
            position: absolute;
            right: 0.55rem;
            background: none;
            border: none;
            color: #cbd5e1;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .answer-toggle-btn:hover {
            color: #fbbf24;
            transform: scale(1.1);
        }

        /* Step Action Buttons - Straw Hat / Luffy Flame Gradient */
        .step-button-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.85rem;
        }

        .btn-step-next,
        .btn-step-submit {
            flex: 1;
            height: 38px;
            background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%);
            color: #ffffff;
            border: none;
            border-radius: 7px;
            font-family: var(--font-main);
            font-size: 0.88rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            box-shadow: 0 5px 18px rgba(239, 68, 68, 0.35);
            transition: all 0.25s ease;
        }

        .btn-step-next:hover,
        .btn-step-submit:hover {
            background: linear-gradient(135deg, #f87171 0%, #fbbf24 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.45);
        }

        .btn-step-back {
            width: 105px;
            height: 38px;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 7px;
            font-family: var(--font-main);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }

        .btn-step-back:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #fbbf24;
            border-color: #fbbf24;
        }

        @media (max-width: 768px) {
            .form-card {
                padding: 1.25rem;
            }
            .grid-container,
            .grid-container-3col {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            .form-title {
                font-size: 1.35rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navbar with Straw Hat Jolly Roger -->
    <nav class="navbar">
        <div class="navdiv">
            <a href="#" class="logo-link">
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
                <div class="btn-nav-user">
                    ⚓ <?php echo htmlspecialchars(strtoupper($role) . ': ' . $username); ?>
                </div>
                <a href="logout.php" class="btn-nav-logout">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Leave Ship
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
                        <span class="pirate-bar"></span>
                        <h1 class="form-title" id="mainTitle">Personal Information</h1>
                        <span class="step-pill" id="stepPill">Step 1 of 3: Pirate Log</span>
                    </div>
                    <p class="form-subtitle" id="mainSubtitle">Complete your crew details before setting sail across the Grand Line</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="server-error-box">
                    <strong>Please review the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form name="setupForm" action="" method="post" onsubmit="return validateform()" id="completeProfileForm" novalidate>

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
                                ID NO. <span class="label-hint">(account id)</span>
                            </label>
                            <input type="text" name="idno" id="idno" class="input-control input-idno"
                                value="<?php echo htmlspecialchars($idno); ?>"
                                readonly autocomplete="off">
                            <small class="error-msg" id="idno_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="firstname">
                                First Name <span class="req-star">*</span>
                            </label>
                            <input type="text" name="firstname" id="firstname" class="input-control"
                                placeholder="Diego"
                                value="<?php echo htmlspecialchars($firstname); ?>"
                                maxlength="25">
                            <small class="error-msg" id="firstname_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="middlename">
                                Middle Initial <span class="label-hint">(optional)</span>
                            </label>
                            <input type="text" name="middlename" id="middlename" class="input-control"
                                placeholder="M"
                                value="<?php echo htmlspecialchars($middlename); ?>"
                                maxlength="25">
                            <small class="error-msg" id="middlename_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="lastname">
                                Last Name <span class="req-star">*</span>
                            </label>
                            <input type="text" name="lastname" id="lastname" class="input-control"
                                placeholder="Rossi"
                                value="<?php echo htmlspecialchars($lastname); ?>"
                                maxlength="25">
                            <small class="error-msg" id="lastname_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="suffix">
                                Suffix <span class="label-hint">(optional)</span>
                            </label>
                            <select name="suffix" id="suffix" class="input-control" onchange="toggleCustomSuffix();">
                                <option value=" ">Select suffix</option>
                                <option value="Sr" <?php echo ($suffix === 'Sr') ? 'selected' : ''; ?>>Sr.</option>
                                <option value="Jr" <?php echo ($suffix === 'Jr') ? 'selected' : ''; ?>>Jr.</option>
                                <option value="others" <?php echo ($suffix === 'others') ? 'selected' : ''; ?>>Others</option>
                            </select>
                            <input type="text" name="customSuffix" id="customSuffix" class="input-control"
                                value="<?php echo htmlspecialchars($customSuffix); ?>"
                                style="display: <?php echo ($suffix === 'others') ? 'block' : 'none'; ?>; margin-top: 6px;" placeholder="Specify suffix">
                            <small class="error-msg" id="suffix_error"></small>
                            <small class="error-msg" id="customSuffix_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="sex">
                                Sex <span class="req-star">*</span>
                            </label>
                            <select name="sex" id="sex" class="input-control">
                                <option value="" <?php echo (empty($sex)) ? 'selected' : ''; ?>>Select sex</option>
                                <option value="Male" <?php echo ($sex === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($sex === 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                            <small class="error-msg" id="sex_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="birthdate">
                                Birthdate <span class="req-star">*</span>
                            </label>
                            <input type="date" name="birthdate" id="birthdate" class="input-control"
                                value="<?php echo htmlspecialchars($birthdate); ?>"
                                oninput="calculateAge()">
                            <small class="error-msg" id="birthdate_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="age">
                                Age
                            </label>
                            <div class="age-input-wrapper">
                                <input type="text" name="age" id="age" class="input-control"
                                    value="<?php echo htmlspecialchars($age); ?>"
                                    placeholder="—" readonly>
                                <span class="age-unit-suffix">YRS</span>
                            </div>
                            <small class="error-msg" id="age_error"></small>
                        </div>
                    </div>

                    <div class="step-button-group">
                        <button type="button" class="btn-step-next" onclick="nextStep(1)">
                            Next Step
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
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
                                value="<?php echo htmlspecialchars($purokstreet); ?>"
                                maxlength="25">
                            <small class="error-msg" id="purokstreet_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="brgy">
                                Barangay <span class="req-star">*</span>
                            </label>
                            <input type="text" name="brgy" id="brgy" class="input-control"
                                placeholder="Barangay name"
                                value="<?php echo htmlspecialchars($brgy); ?>"
                                maxlength="25">
                            <small class="error-msg" id="brgy_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="municipal">
                                Municipality / City <span class="req-star">*</span>
                            </label>
                            <input type="text" name="municipal" id="municipal" class="input-control"
                                placeholder="City / Municipality"
                                value="<?php echo htmlspecialchars($municipal); ?>"
                                maxlength="25">
                            <small class="error-msg" id="municipal_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="province">
                                Province <span class="req-star">*</span>
                            </label>
                            <input type="text" name="province" id="province" class="input-control"
                                placeholder="Province"
                                value="<?php echo htmlspecialchars($province); ?>"
                                maxlength="25">
                            <small class="error-msg" id="province_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="country">
                                Country <span class="req-star">*</span>
                            </label>
                            <input type="text" name="country" id="country" class="input-control"
                                placeholder="Philippines"
                                value="<?php echo htmlspecialchars($country); ?>"
                                maxlength="40">
                            <small class="error-msg" id="country_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="zipcode">
                                Zip Code <span class="req-star">*</span>
                            </label>
                            <input type="text" name="zipcode" id="zipcode" class="input-control"
                                placeholder="e.g. 8600"
                                value="<?php echo htmlspecialchars($zipcode); ?>"
                                maxlength="6">
                            <small class="error-msg" id="zipcode_error"></small>
                        </div>
                    </div>

                    <div class="step-button-group">
                        <button type="button" class="btn-step-back" onclick="prevStep(2)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Back
                        </button>
                        <button type="button" class="btn-step-next" onclick="nextStep(2)">
                            Next Step
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Email & Security Questions -->
                <div id="step3" style="display: none;">
                    <div class="section-header">
                        <span class="section-bullet"></span>
                        <span class="section-title-text">Contact Information</span>
                        <span class="section-line"></span>
                    </div>

                    <div class="grid-container">
                        <div class="form-group">
                            <label class="form-label" for="email">
                                Email Address <span class="req-star">*</span>
                            </label>
                            <input type="email" name="email" id="email" class="input-control"
                                placeholder="name@gmail.com or name@csucc.edu.ph"
                                value="<?php echo htmlspecialchars($email); ?>">
                            <small class="error-msg" id="email_error"></small>
                        </div>
                    </div>

                    <div class="section-header" style="margin-top: 0.65rem;">
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
                                <option value="What is your mother's maiden name?" <?php echo ($security_question1 === "What is your mother's maiden name?") ? 'selected' : ''; ?>>Mother's maiden name?</option>
                                <option value="What was the name of your first pet?" <?php echo ($security_question1 === "What was the name of your first pet?") ? 'selected' : ''; ?>>First pet's name?</option>
                                <option value="What is the name of the city where you were born?" <?php echo ($security_question1 === "What is the name of the city where you were born?") ? 'selected' : ''; ?>>City born in?</option>
                                <option value="What is your favorite food?" <?php echo ($security_question1 === "What is your favorite food?") ? 'selected' : ''; ?>>Favorite food?</option>
                            </select>
                            <div class="answer-input-wrap">
                                <input type="password" name="security_answer1" id="security_answer1" class="input-control"
                                    placeholder="Answer 1" autocomplete="off">
                                <button type="button" class="answer-toggle-btn" onclick="toggleAnswerVisibility('security_answer1', this)" title="Show/Hide Answer">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <small class="error-msg" id="security_answer1_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="security_question2">
                                Question 2 <span class="req-star">*</span>
                            </label>
                            <select name="security_question2" id="security_question2" class="input-control">
                                <option value="">Select Question 2</option>
                                <option value="What is the name of your favorite teacher?" <?php echo ($security_question2 === "What is the name of your favorite teacher?") ? 'selected' : ''; ?>>Favorite teacher?</option>
                                <option value="What is your favorite movie?" <?php echo ($security_question2 === "What is your favorite movie?") ? 'selected' : ''; ?>>Favorite movie?</option>
                                <option value="What is the make of your first car?" <?php echo ($security_question2 === "What is the make of your first car?") ? 'selected' : ''; ?>>First car make?</option>
                                <option value="What is your lucky number?" <?php echo ($security_question2 === "What is your lucky number?") ? 'selected' : ''; ?>>Lucky number?</option>
                            </select>
                            <div class="answer-input-wrap">
                                <input type="password" name="security_answer2" id="security_answer2" class="input-control"
                                    placeholder="Answer 2" autocomplete="off">
                                <button type="button" class="answer-toggle-btn" onclick="toggleAnswerVisibility('security_answer2', this)" title="Show/Hide Answer">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <small class="error-msg" id="security_answer2_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="security_question3">
                                Question 3 <span class="req-star">*</span>
                            </label>
                            <select name="security_question3" id="security_question3" class="input-control">
                                <option value="">Select Question 3</option>
                                <option value="What is your favorite color?" <?php echo ($security_question3 === "What is your favorite color?") ? 'selected' : ''; ?>>Favorite color?</option>
                                <option value="What is the name of your best friend?" <?php echo ($security_question3 === "What is the name of your best friend?") ? 'selected' : ''; ?>>Best friend's name?</option>
                                <option value="What is your dream job?" <?php echo ($security_question3 === "What is your dream job?") ? 'selected' : ''; ?>>Dream job?</option>
                                <option value="What is your favorite sports team?" <?php echo ($security_question3 === "What is your favorite sports team?") ? 'selected' : ''; ?>>Favorite sports team?</option>
                            </select>
                            <div class="answer-input-wrap">
                                <input type="password" name="security_answer3" id="security_answer3" class="input-control"
                                    placeholder="Answer 3" autocomplete="off">
                                <button type="button" class="answer-toggle-btn" onclick="toggleAnswerVisibility('security_answer3', this)" title="Show/Hide Answer">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <small class="error-msg" id="security_answer3_error"></small>
                        </div>
                    </div>

                    <div class="step-button-group">
                        <button type="button" class="btn-step-back" onclick="prevStep(3)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Back
                        </button>
                        <button class="btn-step-submit" type="submit">
                            Complete Setup & Enter Dashboard
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Exact client-side validation logic from signup.js -->
    <script>
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

        function toggleAnswerVisibility(inputId, btn) {
            var input = document.getElementById(inputId);
            if (!input) return;
            var isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            btn.innerHTML = isPass
                ? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
                : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        }

        // ---------------- Left-to-Right Name Field Scanner ---------------- //
        function scanNameField(val, fieldLabel) {
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

                if (/[0-9]/.test(char)) {
                    return fieldLabel + " cannot contain numbers.";
                }

                if (/[^a-zA-Z\s]/.test(char)) {
                    return fieldLabel + " cannot contain special characters.";
                }

                if (i === 0) {
                    if (/[a-z]/.test(char)) {
                        return "The first letter of " + fieldLabel.toLowerCase() + " should be capitalized.";
                    }
                } else if (val.charAt(i - 1) === ' ') {
                    if (/[a-z]/.test(char)) {
                        return "The second word of " + fieldLabel.toLowerCase() + " should be capitalized.";
                    }
                } else {
                    if (/[A-Z]/.test(char)) {
                        return "The first letter of " + fieldLabel.toLowerCase() + " should be the only capitalized.";
                    }
                }
            }

            if (val.length < 3) {
                return fieldLabel + " fields must be at least 3 characters.";
            }
            if (val.length > 25) {
                return fieldLabel + " should not exceed 25 characters.";
            }

            return "";
        }

        // ---------------- Real-time Step 1 Field Validators ---------------- //
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
                        if (char.toLowerCase() === val.charAt(i + 1).toLowerCase() && char.toLowerCase() === val.charAt(i + 2).toLowerCase()) {
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
            var err = scanAddressField(val, "Country", 4, 40);
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
            var idnoVal = document.getElementById("idno") ? document.getElementById("idno").value.trim() : '';
            return fetch('api/check_duplicate.php?field=email&value=' + encodeURIComponent(val) + '&exclude_id=' + encodeURIComponent(idnoVal))
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
            var vFn = checkFirstname(true);
            var vMn = checkMiddlename();
            var vLn = checkLastname(true);
            var vSuf = checkSuffix(true);
            var vSex = checkSex(true);
            var vBd = checkBirthdate(true);

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
            var vSq1 = checkSecurityAnswer(1, true);
            var vSq2 = checkSecurityAnswer(2, true);
            var vSq3 = checkSecurityAnswer(3, true);
            var vDistinct = checkDistinctSecurityQuestions(true);

            if (!vEm) { document.getElementById("email").focus(); return false; }
            if (!vSq1) { document.getElementById("security_answer1").focus(); return false; }
            if (!vSq2) { document.getElementById("security_answer2").focus(); return false; }
            if (!vSq3) { document.getElementById("security_answer3").focus(); return false; }
            if (!vDistinct) { return false; }

            var emailErr = document.getElementById("email_error");
            if (emailErr && emailErr.style.display === "block" && emailErr.textContent !== "") {
                document.getElementById("email").focus(); return false;
            }

            return true;
        }

        function validateform() {
            if (!validateStep1()) {
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'none';
                document.getElementById('step1').style.display = 'block';
                updateTracker(1);
                return false;
            }
            if (!validateStep2()) {
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step3').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                updateTracker(2);
                return false;
            }
            if (!validateStep3()) {
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'block';
                updateTracker(3);
                return false;
            }
            return true;
        }

        function updateTracker(step) {
            var title = document.getElementById('mainTitle');
            var subtitle = document.getElementById('mainSubtitle');
            var pill = document.getElementById('stepPill');

            if (step === 1) {
                if (title) title.textContent = 'Personal Information';
                if (subtitle) subtitle.textContent = 'Complete your crew details before setting sail across the Grand Line';
                if (pill) pill.textContent = 'Step 1 of 3: Pirate Log';
            } else if (step === 2) {
                if (title) title.textContent = 'Address Information';
                if (subtitle) subtitle.textContent = 'Please provide your home island and residential coordinates';
                if (pill) pill.textContent = 'Step 2 of 3: Island Coords';
            } else if (step === 3) {
                if (title) title.textContent = 'Security & Recovery Setup';
                if (subtitle) subtitle.textContent = 'Set your Den Den Mushi email and 3 secret security questions';
                if (pill) pill.textContent = 'Step 3 of 3: Transponder';
            }
        }

        function nextStep(step) {
            if (step === 1) {
                if (!validateStep1()) {
                    return false;
                }
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                updateTracker(2);
            } else if (step === 2) {
                if (!validateStep2()) {
                    return false;
                }
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'block';
                updateTracker(3);
            }
        }

        function prevStep(step) {
            if (step === 2) {
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step1').style.display = 'block';
                updateTracker(1);
            } else if (step === 3) {
                document.getElementById('step3').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                updateTracker(2);
            }
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
            calculateAge();

            // Step 1 live listeners
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
                    if (!checkEmail(true)) return;
                    checkEmailDuplicate();
                });
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
    </script>
</body>
</html>
