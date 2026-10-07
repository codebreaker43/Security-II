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
    // Store raw input in session for repopulating if validation fails
    $_SESSION['form_data'] = $_POST;

    // Collect and trim inputs
    $idno = isset($_POST["idno"]) ? trim($_POST["idno"]) : '';
    $firstname = isset($_POST["firstname"]) ? trim($_POST["firstname"]) : '';
    $middlename = isset($_POST["middlename"]) ? trim($_POST["middlename"]) : '';
    $lastname = isset($_POST["lastname"]) ? trim($_POST["lastname"]) : '';
    $suffix = isset($_POST["suffix"]) ? trim($_POST["suffix"]) : '';
    $customSuffix = isset($_POST["customSuffix"]) ? trim($_POST["customSuffix"]) : '';
    $birthdate = isset($_POST["birthdate"]) ? trim($_POST["birthdate"]) : '';
    $sex = isset($_POST["sex"]) ? trim($_POST["sex"]) : '';
    $country = isset($_POST["country"]) ? trim($_POST["country"]) : 'Philippines';
    $purokstreet = isset($_POST["purokstreet"]) ? trim($_POST["purokstreet"]) : '';
    $brgy = isset($_POST["brgy"]) ? trim($_POST["brgy"]) : '';
    $municipal = isset($_POST["municipal"]) ? trim($_POST["municipal"]) : '';
    $province = isset($_POST["province"]) ? trim($_POST["province"]) : '';
    $zipcode = isset($_POST["zipcode"]) ? trim($_POST["zipcode"]) : '';
    $username = isset($_POST["username"]) ? strtolower(trim($_POST["username"])) : '';
    $email = isset($_POST["email"]) ? strtolower(trim($_POST["email"])) : '';
    $password = isset($_POST["password"]) ? $_POST["password"] : '';
    $confirmPassword = isset($_POST["confirmPassword"]) ? $_POST["confirmPassword"] : '';

    $security_question1 = isset($_POST["security_question1"]) ? trim($_POST["security_question1"]) : '';
    $security_answer1 = isset($_POST["security_answer1"]) ? trim($_POST["security_answer1"]) : '';
    $security_question2 = isset($_POST["security_question2"]) ? trim($_POST["security_question2"]) : '';
    $security_answer2 = isset($_POST["security_answer2"]) ? trim($_POST["security_answer2"]) : '';
    $security_question3 = isset($_POST["security_question3"]) ? trim($_POST["security_question3"]) : '';
    $security_answer3 = isset($_POST["security_answer3"]) ? trim($_POST["security_answer3"]) : '';

    // Auto-generate or validate ID Number
    if (empty($idno) || !preg_match('/^\d{4}-\d{4}$/', $idno)) {
        $idno = generateNextIdNo($conn);
    } else {
        $stmt_chk_id = mysqli_prepare($conn, "SELECT idno FROM user WHERE idno = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_chk_id, "s", $idno);
        mysqli_stmt_execute($stmt_chk_id);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_chk_id)) > 0) {
            $idno = generateNextIdNo($conn);
        }
        mysqli_stmt_close($stmt_chk_id);
    }
    $_SESSION['form_data']['idno'] = $idno;

    $errors = [];

    // 1. Name validations
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

    $age = 0;
    if (empty($birthdate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
        $errors[] = "A valid birthdate is required.";
    } else {
        $bdateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$bdateObj || $bdateObj->format('Y-m-d') !== $birthdate) {
            $errors[] = "Invalid birthdate format.";
        } else {
            $today = new DateTime();
            $age = $today->diff($bdateObj)->y;
            if ($age < 18) {
                $errors[] = "You must be at least 18 years old to register.";
            } elseif ($age > 120) {
                $errors[] = "Please enter a valid birthdate.";
            }
        }
    }

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

    // 4. Account Credentials
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

    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 6 || strlen($username) > 15) {
        $errors[] = "Username must be between 6 and 15 characters.";
    } elseif (!preg_match('/^[a-z0-9@._]+$/', $username)) {
        $errors[] = "Username must be lowercase and contain only letters, numbers, '@', '.', or '_'.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8 || strlen($password) > 15) {
        $errors[] = "Password must be between 8 and 15 characters.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one number, and one special character.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
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

    // 6. Check unique username and email
    if (empty($errors)) {
        $stmt_u = mysqli_prepare($conn, "SELECT idno FROM user WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_u, "s", $username);
        mysqli_stmt_execute($stmt_u);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_u)) > 0) {
            $errors[] = "Username already exists! Please choose another.";
        }
        mysqli_stmt_close($stmt_u);

        $stmt_e = mysqli_prepare($conn, "SELECT idno FROM user WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_e, "s", $email);
        mysqli_stmt_execute($stmt_e);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_e)) > 0) {
            $errors[] = "Email already exists! Please use a different email.";
        }
        mysqli_stmt_close($stmt_e);
    }

    // If validation fails, alert user and return to form
    if (!empty($errors)) {
        $errMsg = addslashes(implode("\\n• ", $errors));
        echo "<script>alert('Registration Failed:\\n• " . $errMsg . "'); history.back();</script>";
        exit();
    }

    // All validation passed: Hash password & security answers
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $ans1_hash = password_hash(strtolower($security_answer1), PASSWORD_DEFAULT);
    $ans2_hash = password_hash(strtolower($security_answer2), PASSWORD_DEFAULT);
    $ans3_hash = password_hash(strtolower($security_answer3), PASSWORD_DEFAULT);

    $sql = "INSERT INTO user (idno, firstname, middlename, lastname, suffix, birthdate, age, sex, email, username, country, purokstreet, brgy, municipal, province, password, zipcode, security_question1, security_answer1, security_question2, security_answer2, security_question3, security_answer3, role, status)   
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 'pending')";

    $stmt_ins = mysqli_prepare($conn, $sql);
    if ($stmt_ins) {
        mysqli_stmt_bind_param(
            $stmt_ins,
            "ssssssissssssssssssssss",
            $idno,
            $firstname,
            $middlename,
            $lastname,
            $suffixToUse,
            $birthdate,
            $age,
            $sex,
            $email,
            $username,
            $country,
            $purokstreet,
            $brgy,
            $municipal,
            $province,
            $hashed_password,
            $zipcode,
            $security_question1,
            $ans1_hash,
            $security_question2,
            $ans2_hash,
            $security_question3,
            $ans3_hash
        );

        if (mysqli_stmt_execute($stmt_ins)) {
            unset($_SESSION['form_data']);
            echo "<script>alert('You are successfully registered! Your ID No is: " . $idno . ". Your account is pending approval by the Super Admin.'); location.href='login.php';</script>";
            exit();
        } else {
            $dbErr = addslashes(mysqli_error($conn));
            echo "<script>alert('Database Error: " . $dbErr . "'); history.back();</script>";
            exit();
        }
        mysqli_stmt_close($stmt_ins);
    } else {
        die("Database error: " . mysqli_error($conn));
    }
}

// Generate the next ID number for page display
$auto_idno = generateNextIdNo($conn);
if (isset($_SESSION['form_data']['idno']) && !empty($_SESSION['form_data']['idno']) && preg_match('/^\d{4}-\d{4}$/', $_SESSION['form_data']['idno'])) {
    $check_sess_id = mysqli_query($conn, "SELECT idno FROM user WHERE idno = '" . mysqli_real_escape_string($conn, $_SESSION['form_data']['idno']) . "'");
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
    <title>Crew Registration - ONE PIECE</title>
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
            --card-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.20) 0%, rgba(255, 255, 255, 0.08) 100%);
            --input-bg: rgba(255, 255, 255, 0.15);
            --input-border: rgba(255, 255, 255, 0.35);
            --input-border-focus: #fbbf24;
            --text-main: #ffffff;
            --text-muted: #cbd5e1;
            --text-label: #f1f5f9;
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
                linear-gradient(rgba(10, 15, 25, 0.20), rgba(10, 15, 25, 0.35)),
                url('../img/one_piece_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Top Navbar */
        .navbar {
            background: rgba(15, 23, 42, 0.50);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0.55rem 2rem;
            position: sticky;
            top: 0;
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

        .btn-nav-home {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.42rem 1.15rem;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #ffffff;
            font-size: 0.84rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }

        .btn-nav-home:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #fbbf24;
            border-color: #fbbf24;
        }

        .btn-nav-login {
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
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.35);
            transition: all 0.2s ease;
        }

        .btn-nav-login:hover {
            background: linear-gradient(135deg, #f87171 0%, #fbbf24 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
            color: #ffffff;
        }

        /* Form Wrapper & Content */
        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem 1.5rem;
        }

        .form-card {
            background: var(--card-bg);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 16px;
            padding: 1.5rem 2.25rem;
            width: 100%;
            max-width: 900px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35), inset 0 0 0 1px rgba(255, 255, 255, 0.15);
            position: relative;
        }

        /* Header block with Straw Hat gradient bar */
        .form-header-row {
            margin-bottom: 0.95rem;
        }

        .form-title-group {
            display: flex;
            flex-direction: column;
        }

        .title-with-bar {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .pirate-bar,
        .orange-bar {
            width: 5px;
            height: 30px;
            background: linear-gradient(180deg, #f59e0b 0%, #ef4444 100%);
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
        }

        .form-title {
            font-family: var(--font-heading);
            font-size: 1.65rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.5px;
            line-height: 1.1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .form-subtitle {
            color: #cbd5e1;
            font-size: 0.85rem;
            margin-left: calc(5px + 0.85rem);
            font-weight: 500;
        }

        /* Section divider header */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0.5rem 0 0.8rem;
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
            font-size: 0.88rem;
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
            color: #f1f5f9;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 0.3rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
        }

        .req-star {
            color: #ef4444;
            font-weight: 800;
        }

        .label-hint {
            font-size: 0.72rem;
            font-weight: 500;
            color: #cbd5e1;
            text-transform: none;
            letter-spacing: normal;
        }

        /* Custom Inputs */
        .input-control,
        select.input-control {
            width: 100%;
            height: 40px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            border-radius: 8px;
            padding: 0 0.9rem;
            color: #ffffff;
            font-family: var(--font-main);
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-sizing: border-box;
            outline: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .input-control::placeholder {
            color: #cbd5e1;
            font-weight: 400;
        }

        .input-control:focus,
        select.input-control:focus {
            border-color: #fbbf24;
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        /* Readonly ID NO & Age */
        .input-idno {
            color: #38bdf8 !important;
            font-weight: 800 !important;
            letter-spacing: 1px;
            background: rgba(255, 255, 255, 0.08) !important;
            cursor: not-allowed;
            border-color: rgba(255, 255, 255, 0.2) !important;
        }

        .age-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .age-input-wrapper .input-control {
            padding-right: 3.5rem;
            background: rgba(255, 255, 255, 0.08) !important;
            cursor: not-allowed;
            color: #ffffff;
            font-weight: 600;
            border-color: rgba(255, 255, 255, 0.2) !important;
        }

        .age-unit-suffix {
            position: absolute;
            right: 1rem;
            font-size: 0.82rem;
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
            background-position: right 1rem center;
            background-size: 15px;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        select.input-control option {
            background-color: #0f172a;
            color: #ffffff;
            padding: 0.5rem;
        }

        /* Date input icon */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }

        /* Under-field Real-Time Error Message */
        .error-msg {
            color: #f87171;
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
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25) !important;
            background: rgba(239, 68, 68, 0.15) !important;
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
            background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%);
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
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.35);
            transition: all 0.25s ease;
        }

        .btn-step-next:hover,
        .btn-step-submit:hover {
            background: linear-gradient(135deg, #f87171 0%, #fbbf24 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.45);
        }

        .btn-step-back {
            width: 120px;
            height: 42px;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
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
            backdrop-filter: blur(10px);
        }

        .btn-step-back:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #fbbf24;
            border-color: #fbbf24;
        }

        .auth-footer-link {
            text-align: center;
            margin-top: 0.75rem;
            font-size: 0.85rem;
            color: #cbd5e1;
        }

        .auth-footer-link a {
            color: #fbbf24;
            text-decoration: none;
            font-weight: 700;
            margin-left: 0.3rem;
            transition: color 0.2s;
        }

        .auth-footer-link a:hover {
            color: #fde047;
            text-decoration: underline;
        }

        /* Footer */
        footer {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            padding: 1.5rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.88rem;
            margin-top: auto;
            background: rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(15px);
        }

        footer a {
            color: #fbbf24;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        footer a:hover {
            color: #fde047;
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
                    Log In
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
                        <h1 class="form-title">CREW REGISTRATION</h1>
                    </div>
                    <p class="form-subtitle">Join the Straw Hat Pirates — crew paperwork before setting sail</p>
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
                                placeholder="name@gmail.com or name@csucc.edu.ph"
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
                            <div class="input-with-icon-wrap" style="position: relative; display: flex; align-items: center;">
                                <input type="password" name="password" id="password" class="input-control"
                                    placeholder="••••••••"
                                    value="<?php echo isset($_SESSION['form_data']['password']) ? htmlspecialchars($_SESSION['form_data']['password']) : ''; ?>"
                                    onkeyup="strongpassword(this.value)" style="padding-right: 2.75rem;">
                                <button type="button" class="password-toggle-btn" id="toggleRegPass" title="Toggle password visibility" style="position: absolute; right: 0.75rem; background: none; border: none; color: #8492a6; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0.3rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                            <!-- Password Strength Bar -->
                            <div class="strength-meter-wrap" style="margin-top: 6px; margin-bottom: 6px;">
                                <div class="strength-bars" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; height: 5px;">
                                    <div class="strength-bar" id="bar1" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                                    <div class="strength-bar" id="bar2" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                                    <div class="strength-bar" id="bar3" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                                    <div class="strength-bar" id="bar4" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                                </div>
                            </div>
                            <div id="pswrd" style="display: none;"></div>
                            <small class="error-msg" id="password_error"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirmPassword">
                                Confirm Password <span class="req-star">*</span>
                            </label>
                            <div class="input-with-icon-wrap" style="position: relative; display: flex; align-items: center;">
                                <input type="password" name="confirmPassword" id="confirmPassword" class="input-control"
                                    placeholder="••••••••"
                                    value="<?php echo isset($_SESSION['form_data']['confirmPassword']) ? htmlspecialchars($_SESSION['form_data']['confirmPassword']) : ''; ?>" style="padding-right: 2.75rem;">
                                <button type="button" class="password-toggle-btn" id="toggleRegConfirmPass" title="Toggle password visibility" style="position: absolute; right: 0.75rem; background: none; border: none; color: #8492a6; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0.3rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
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
        <p>&copy; 2024 ONE PIECE System. All rights reserved.</p>
        <p style="margin-top: 0.25rem;"><a href="#">Grand Line Privacy Policy</a></p>
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