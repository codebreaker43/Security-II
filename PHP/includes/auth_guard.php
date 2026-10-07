<?php
/**
 * Auth Guard - Personal Information & Profile Completion Gatekeeper
 * 
 * Include this file at the top of protected pages/APIs.
 * It blocks access to all features until the user has completely filled out
 * their Personal Details (Name, Email, Sex, Birthdate, Address, and Security Questions).
 * 
 * For HTML pages: redirects to profile.php?incomplete_profile=1
 * For API endpoints: returns a 403 JSON error
 */

function check_user_personal_info_complete($conn, $user_id) {
    if (empty($user_id)) {
        return false;
    }

    $uid_safe = mysqli_real_escape_string($conn, $user_id);
    $q = mysqli_query($conn, "SELECT idno, firstname, lastname, email, birthdate, sex, country, purokstreet, brgy, municipal, province, zipcode, security_question1, security_answer1, security_question2, security_answer2, security_question3, security_answer3 FROM user WHERE idno = '$uid_safe' LIMIT 1");
    if (!$q || !($r = mysqli_fetch_assoc($q))) {
        return false;
    }

    // List of strictly required text fields
    $required_fields = [
        'firstname',
        'lastname',
        'email',
        'sex',
        'purokstreet',
        'brgy',
        'municipal',
        'province',
        'country',
        'security_question1',
        'security_answer1',
        'security_question2',
        'security_answer2',
        'security_question3',
        'security_answer3'
    ];

    foreach ($required_fields as $f) {
        $val = trim((string)($r[$f] ?? ''));
        if ($val === '') {
            return false;
        }
    }

    // Check birthdate
    $bdate = trim((string)($r['birthdate'] ?? ''));
    if (empty($bdate) || $bdate === '0000-00-00' || $bdate === '0000-00-00 00:00:00') {
        return false;
    }

    // Check zipcode
    $zip = trim((string)($r['zipcode'] ?? ''));
    if (empty($zip) || $zip === '0' || $zip === '0000') {
        return false;
    }

    return true;
}

function enforce_profile_completion($conn, $is_api = false) {
    // Must be logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return; // Not logged in - let existing auth checks handle this
    }

    $user_id = trim((string)$_SESSION['user_id']);
    $uid_safe = mysqli_real_escape_string($conn, $user_id);

    // Verify account status directly from database (invalidate session if inactive/blocked)
    $st_res = mysqli_query($conn, "SELECT status FROM user WHERE idno = '$uid_safe' LIMIT 1");
    if ($st_res && ($st_row = mysqli_fetch_assoc($st_res))) {
        if ($st_row['status'] !== 'active') {
            @mysqli_query($conn, "UPDATE user SET is_online = 0 WHERE idno = '$uid_safe'");
            session_unset();
            session_destroy();
            if ($is_api) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode([
                    'error' => 'Account inactive',
                    'message' => 'Your account is inactive. Only one Super Admin can be active at a time.',
                    'redirect' => '../login.php'
                ]);
                exit();
            }
            $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $in_subdir = (strpos($script_path, '/admin/') !== false || strpos($script_path, '/superadmin/') !== false);
            $base_path = $in_subdir ? '../' : './';
            header("Location: " . $base_path . "login.php");
            exit();
        }
    }

    $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    // Allow complete_profile.php and logout.php without redirecting
    if (strpos($script_path, 'complete_profile.php') !== false || strpos($script_path, 'logout.php') !== false) {
        return;
    }

    // Check completeness directly from database
    $is_complete = check_user_personal_info_complete($conn, $user_id);

    if ($is_complete) {
        $_SESSION['profile_completed'] = 1;
        @mysqli_query($conn, "UPDATE user SET profile_completed = 1 WHERE idno = '$uid_safe'");
        return; // Profile is completely filled - allow access
    }

    // Profile is INCOMPLETE - block access to all other features
    $_SESSION['profile_completed'] = 0;
    @mysqli_query($conn, "UPDATE user SET profile_completed = 0 WHERE idno = '$uid_safe'");

    if ($is_api) {
        // API response
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'error' => 'Profile incomplete',
            'message' => 'Please complete all required fields in your Personal Information before using this feature.',
            'redirect' => 'complete_profile.php'
        ]);
        exit();
    }

    // HTML page - redirect to complete_profile.php
    $in_subdir = (strpos($script_path, '/admin/') !== false || strpos($script_path, '/superadmin/') !== false);
    $base_path = $in_subdir ? '../' : './';

    header("Location: " . $base_path . "complete_profile.php");
    exit();
}
