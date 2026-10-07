<?php
session_start();
include '../db.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = trim($_SESSION['user_id'] ?? '');


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch full personal details (except passwords/answer hashes)
    $query = "SELECT idno, firstname, middlename, lastname, suffix, birthdate, age, sex, email, username, country, purokstreet, brgy, municipal, province, zipcode, role, status, security_question1, security_question2, security_question3 FROM user WHERE idno = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
        mysqli_stmt_close($stmt);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Current Password Verification Rule
    $current_password = $data['current_password'] ?? '';
    if (empty($current_password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please enter your current password to confirm changes.']);
        exit();
    }

    // Verify current password against database
    $stmt_chk = mysqli_prepare($conn, "SELECT password, username, role, security_question1, security_answer1, security_question2, security_answer2, security_question3, security_answer3 FROM user WHERE idno = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_chk, "s", $user_id);
    mysqli_stmt_execute($stmt_chk);
    $user_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_chk));
    mysqli_stmt_close($stmt_chk);

    if (!$user_row || !password_verify($current_password, $user_row['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Incorrect current password. Changes have been cancelled.']);
        exit();
    }

    $action = $data['action'] ?? 'update_profile';

    // ==== Action: verify_password (lightweight identity check) ====
    if ($action === 'verify_password') {
        echo json_encode(['success' => true, 'message' => 'Password verified successfully.']);
        exit();
    }

    // ==== Action: reset_security_questions ====
    if ($action === 'reset_security_questions') {
        $q1 = trim($data['security_question1'] ?? '');
        $q2 = trim($data['security_question2'] ?? '');
        $q3 = trim($data['security_question3'] ?? '');
        $a1 = trim($data['security_answer1'] ?? '');
        $a2 = trim($data['security_answer2'] ?? '');
        $a3 = trim($data['security_answer3'] ?? '');

        if (empty($q1) || empty($q2) || empty($q3)) {
            http_response_code(400);
            echo json_encode(['error' => 'All 3 security questions are required.']);
            exit();
        }
        if (empty($a1) || empty($a2) || empty($a3)) {
            http_response_code(400);
            echo json_encode(['error' => 'All 3 security answers are required.']);
            exit();
        }
        if ($q1 === $q2 || $q1 === $q3 || $q2 === $q3) {
            http_response_code(400);
            echo json_encode(['error' => 'Please select 3 different security questions.']);
            exit();
        }

        $a1_hash = password_hash(strtolower($a1), PASSWORD_DEFAULT);
        $a2_hash = password_hash(strtolower($a2), PASSWORD_DEFAULT);
        $a3_hash = password_hash(strtolower($a3), PASSWORD_DEFAULT);

        $stmt_sq = mysqli_prepare($conn, "UPDATE user SET security_question1=?, security_answer1=?, security_question2=?, security_answer2=?, security_question3=?, security_answer3=? WHERE idno=?");
        if ($stmt_sq) {
            mysqli_stmt_bind_param($stmt_sq, "sssssss", $q1, $a1_hash, $q2, $a2_hash, $q3, $a3_hash, $user_id);
            if (mysqli_stmt_execute($stmt_sq)) {
                // Audit log
                $ip_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
                $log_action = "Reset Security Questions";
                $log_details = "Security questions were reset via profile (identity verified with password) from IP: " . $ip_addr;
                $stmt_log = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, ?, ?, ?)");
                if ($stmt_log) {
                    mysqli_stmt_bind_param($stmt_log, "sssss", $user_id, $user_row['username'], $user_row['role'], $log_action, $log_details);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);
                }

                echo json_encode(['success' => true, 'message' => 'Security questions have been reset successfully.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: Could not update security questions.']);
            }
            mysqli_stmt_close($stmt_sq);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to prepare database statement.']);
        }
        exit();
    }

    // Collect personal details
    $firstname = trim($data['firstname'] ?? '');
    $middlename = trim($data['middlename'] ?? '');
    $lastname = trim($data['lastname'] ?? '');
    $suffix = trim($data['suffix'] ?? '');
    $birthdate = trim($data['birthdate'] ?? '');
    $sex = trim($data['sex'] ?? '');
    $country = trim($data['country'] ?? 'Philippines');
    $purokstreet = trim($data['purokstreet'] ?? '');
    $brgy = trim($data['brgy'] ?? '');
    $municipal = trim($data['municipal'] ?? '');
    $province = trim($data['province'] ?? '');
    $zipcode = trim($data['zipcode'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));

    // 1. Name validations
    if (empty($firstname)) {
        http_response_code(400);
        echo json_encode(['error' => 'First Name is required.']);
        exit();
    }
    if (strlen($firstname) < 3 || strlen($firstname) > 25) {
        http_response_code(400);
        echo json_encode(['error' => 'First Name must be between 3 and 25 characters.']);
        exit();
    }
    if (!preg_match('/^[a-zA-Z\s]+$/', $firstname)) {
        http_response_code(400);
        echo json_encode(['error' => 'First Name can only contain letters and spaces.']);
        exit();
    }

    if (!empty($middlename)) {
        if (strlen($middlename) > 25) {
            http_response_code(400);
            echo json_encode(['error' => 'Middle Name cannot exceed 25 characters.']);
            exit();
        }
        if (!preg_match('/^[a-zA-Z\s]+$/', $middlename)) {
            http_response_code(400);
            echo json_encode(['error' => 'Middle Name can only contain letters and spaces.']);
            exit();
        }
    }

    if (empty($lastname)) {
        http_response_code(400);
        echo json_encode(['error' => 'Last Name is required.']);
        exit();
    }
    if (strlen($lastname) < 3 || strlen($lastname) > 25) {
        http_response_code(400);
        echo json_encode(['error' => 'Last Name must be between 3 and 25 characters.']);
        exit();
    }
    if (!preg_match('/^[a-zA-Z\s]+$/', $lastname)) {
        http_response_code(400);
        echo json_encode(['error' => 'Last Name can only contain letters and spaces.']);
        exit();
    }

    if (!empty($suffix)) {
        if (strlen($suffix) > 10) {
            http_response_code(400);
            echo json_encode(['error' => 'Suffix cannot exceed 10 characters.']);
            exit();
        }
        if (!preg_match('/^[a-zA-Z\s.]+$/', $suffix)) {
            http_response_code(400);
            echo json_encode(['error' => 'Suffix can only contain letters, spaces, and periods.']);
            exit();
        }
    }

    // 2. Email validation & duplicate check
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email address is required.']);
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please enter a valid email address.']);
        exit();
    }
    $email_domain = strtolower(substr(strrchr($email, "@"), 1));
    $is_gmail = ($email_domain === 'gmail.com');
    $is_university = (preg_match('/^[a-z0-9.-]+\.edu\.ph$/i', $email_domain) || preg_match('/^[a-z0-9.-]+\.edu$/i', $email_domain));
    if (!$is_gmail && !$is_university) {
        http_response_code(400);
        echo json_encode(['error' => 'Only Gmail (@gmail.com) and university email (@...edu.ph / @...edu) are allowed.']);
        exit();
    }
    if ($is_gmail && !empty($firstname) && !empty($lastname)) {
        $local = strtolower(substr($email, 0, strpos($email, "@")));
        $clean_fn = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstname));
        $clean_ln = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $lastname));
        $dot_fn = strtolower(preg_replace('/\s+/', '.', trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $firstname))));
        $dot_ln = strtolower(preg_replace('/\s+/', '.', trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $lastname))));

        $p1 = '/^' . preg_quote($clean_fn, '/') . '\.' . preg_quote($clean_ln, '/') . '[0-9]*$/';
        $p2 = '/^' . preg_quote($dot_fn, '/') . '\.' . preg_quote($dot_ln, '/') . '[0-9]*$/';

        if (!preg_match($p1, $local) && !preg_match($p2, $local)) {
            $example = $clean_fn . '.' . $clean_ln . '@gmail.com';
            http_response_code(400);
            echo json_encode(['error' => "Gmail address must follow the standard: firstname.lastname@gmail.com (e.g., $example)."]);
            exit();
        }
    }
    $stmt_dup = mysqli_prepare($conn, "SELECT id FROM user WHERE email = ? AND id != ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_dup, "si", $email, $user_id);
    mysqli_stmt_execute($stmt_dup);
    if (mysqli_num_rows(mysqli_stmt_get_result($stmt_dup)) > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Email address is already in use by another account.']);
        exit();
    }
    mysqli_stmt_close($stmt_dup);

    // 3. Birthdate & Age validation
    if (empty($birthdate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Birthdate is required.']);
        exit();
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid birthdate format.']);
        exit();
    }
    $bdateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$bdateObj || $bdateObj->format('Y-m-d') !== $birthdate) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid birthdate.']);
        exit();
    }
    $today = new DateTime();
    $age = $today->diff($bdateObj)->y;
    if ($age < 18) {
        http_response_code(400);
        echo json_encode(['error' => 'You must be at least 18 years old.']);
        exit();
    }
    if ($age > 120) {
        http_response_code(400);
        echo json_encode(['error' => 'Please enter a valid birthdate.']);
        exit();
    }

    // 4. Sex validation
    if (empty($sex) || !in_array($sex, ['Male', 'Female'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Please select a valid sex (Male or Female).']);
        exit();
    }

    // 5. Address validation
    if (empty($purokstreet)) {
        http_response_code(400);
        echo json_encode(['error' => 'Purok/Street is required.']);
        exit();
    }
    if (strlen($purokstreet) > 25) {
        http_response_code(400);
        echo json_encode(['error' => 'Purok/Street cannot exceed 25 characters.']);
        exit();
    }
    if (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $purokstreet)) {
        http_response_code(400);
        echo json_encode(['error' => 'Purok/Street can only contain letters, numbers, spaces, and hyphens.']);
        exit();
    }

    if (empty($brgy)) {
        http_response_code(400);
        echo json_encode(['error' => 'Barangay is required.']);
        exit();
    }
    if (strlen($brgy) < 3 || strlen($brgy) > 25) {
        http_response_code(400);
        echo json_encode(['error' => 'Barangay must be between 3 and 25 characters.']);
        exit();
    }
    if (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $brgy)) {
        http_response_code(400);
        echo json_encode(['error' => 'Barangay can only contain letters, numbers, spaces, and hyphens.']);
        exit();
    }

    if (empty($municipal)) {
        http_response_code(400);
        echo json_encode(['error' => 'Municipality is required.']);
        exit();
    }
    if (strlen($municipal) < 3 || strlen($municipal) > 25) {
        http_response_code(400);
        echo json_encode(['error' => 'Municipality must be between 3 and 25 characters.']);
        exit();
    }
    if (!preg_match('/^[a-zA-Z\s]+$/', $municipal)) {
        http_response_code(400);
        echo json_encode(['error' => 'Municipality can only contain letters and spaces.']);
        exit();
    }

    if (empty($province)) {
        http_response_code(400);
        echo json_encode(['error' => 'Province is required.']);
        exit();
    }
    if (strlen($province) < 3 || strlen($province) > 25) {
        http_response_code(400);
        echo json_encode(['error' => 'Province must be between 3 and 25 characters.']);
        exit();
    }
    if (!preg_match('/^[a-zA-Z\s]+$/', $province)) {
        http_response_code(400);
        echo json_encode(['error' => 'Province can only contain letters and spaces.']);
        exit();
    }

    if (empty($country)) {
        http_response_code(400);
        echo json_encode(['error' => 'Country is required.']);
        exit();
    }
    if (strlen($country) < 4 || strlen($country) > 40) {
        http_response_code(400);
        echo json_encode(['error' => 'Country must be between 4 and 40 characters.']);
        exit();
    }
    if (!preg_match('/^[a-zA-Z\s]+$/', $country)) {
        http_response_code(400);
        echo json_encode(['error' => 'Country can only contain letters and spaces.']);
        exit();
    }

    if (empty($zipcode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Zip Code is required.']);
        exit();
    }
    if (!preg_match('/^[0-9]{4,6}$/', $zipcode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Zip Code must be 4 to 6 numeric digits.']);
        exit();
    }

    // 6. Security Questions and Answers
    $q1 = trim($data['security_question1'] ?? ($user_row['security_question1'] ?? ''));
    $q2 = trim($data['security_question2'] ?? ($user_row['security_question2'] ?? ''));
    $q3 = trim($data['security_question3'] ?? ($user_row['security_question3'] ?? ''));

    $ans1 = trim($data['security_answer1'] ?? '');
    $ans2 = trim($data['security_answer2'] ?? '');
    $ans3 = trim($data['security_answer3'] ?? '');

    // Distinct questions check if any are selected
    if (!empty($q1) && !empty($q2) && $q1 === $q2) {
        http_response_code(400);
        echo json_encode(['error' => 'Question 1 and Question 2 cannot be the same.']);
        exit();
    }
    if (!empty($q1) && !empty($q3) && $q1 === $q3) {
        http_response_code(400);
        echo json_encode(['error' => 'Question 1 and Question 3 cannot be the same.']);
        exit();
    }
    if (!empty($q2) && !empty($q3) && $q2 === $q3) {
        http_response_code(400);
        echo json_encode(['error' => 'Question 2 and Question 3 cannot be the same.']);
        exit();
    }

    // Validate that changed questions require corresponding answers
    if (!empty($q1) && $q1 !== ($user_row['security_question1'] ?? '') && empty($ans1)) {
        http_response_code(400);
        echo json_encode(['error' => 'Question 1 was changed. Please enter a new answer for Question 1.']);
        exit();
    }
    if (!empty($q2) && $q2 !== ($user_row['security_question2'] ?? '') && empty($ans2)) {
        http_response_code(400);
        echo json_encode(['error' => 'Question 2 was changed. Please enter a new answer for Question 2.']);
        exit();
    }
    if (!empty($q3) && $q3 !== ($user_row['security_question3'] ?? '') && empty($ans3)) {
        http_response_code(400);
        echo json_encode(['error' => 'Question 3 was changed. Please enter a new answer for Question 3.']);
        exit();
    }

    if (!empty($ans1) && empty($q1)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please select Question 1 for the provided answer.']);
        exit();
    }
    if (!empty($ans2) && empty($q2)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please select Question 2 for the provided answer.']);
        exit();
    }
    if (!empty($ans3) && empty($q3)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please select Question 3 for the provided answer.']);
        exit();
    }

    if (!empty($ans1) && strlen($ans1) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Answer 1 must be at least 2 characters.']);
        exit();
    }
    if (!empty($ans2) && strlen($ans2) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Answer 2 must be at least 2 characters.']);
        exit();
    }
    if (!empty($ans3) && strlen($ans3) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Answer 3 must be at least 2 characters.']);
        exit();
    }

    // Optional New Password Validation
    $new_password = $data['new_password'] ?? '';
    $update_password = false;
    $new_password_hash = null;

    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'New password must be at least 8 characters long.']);
            exit();
        }
        if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]/', $new_password)) {
            http_response_code(400);
            echo json_encode(['error' => 'New password must include uppercase, number, and special character.']);
            exit();
        }
        $update_password = true;
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    }

    $ans1_hash = !empty($ans1) ? password_hash(strtolower($ans1), PASSWORD_DEFAULT) : ($user_row['security_answer1'] ?? null);
    $ans2_hash = !empty($ans2) ? password_hash(strtolower($ans2), PASSWORD_DEFAULT) : ($user_row['security_answer2'] ?? null);
    $ans3_hash = !empty($ans3) ? password_hash(strtolower($ans3), PASSWORD_DEFAULT) : ($user_row['security_answer3'] ?? null);

    if ($update_password) {
        $query = "UPDATE user SET firstname=?, middlename=?, lastname=?, suffix=?, birthdate=?, age=?, sex=?, country=?, purokstreet=?, brgy=?, municipal=?, province=?, zipcode=?, email=?, security_question1=?, security_answer1=?, security_question2=?, security_answer2=?, security_question3=?, security_answer3=?, password=? WHERE idno=?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssissssssssssssssss", $firstname, $middlename, $lastname, $suffix, $birthdate, $age, $sex, $country, $purokstreet, $brgy, $municipal, $province, $zipcode, $email, $q1, $ans1_hash, $q2, $ans2_hash, $q3, $ans3_hash, $new_password_hash, $user_id);
        }
    } else {
        $query = "UPDATE user SET firstname=?, middlename=?, lastname=?, suffix=?, birthdate=?, age=?, sex=?, country=?, purokstreet=?, brgy=?, municipal=?, province=?, zipcode=?, email=?, security_question1=?, security_answer1=?, security_question2=?, security_answer2=?, security_question3=?, security_answer3=? WHERE idno=?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssisssssssssssssss", $firstname, $middlename, $lastname, $suffix, $birthdate, $age, $sex, $country, $purokstreet, $brgy, $municipal, $province, $zipcode, $email, $q1, $ans1_hash, $q2, $ans2_hash, $q3, $ans3_hash, $user_id);
        }
    }

    if ($stmt) {
        if (mysqli_stmt_execute($stmt)) {
            // Log in audit logs
            $log_action = "Update Personal Details";
            $ip_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $log_details = "Updated personal information and security details verified with password" . ($update_password ? " (including new password update)" : "") . " from IP: " . $ip_addr;
            $stmt_log = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, ?, ?, ?)");
            if ($stmt_log) {
                mysqli_stmt_bind_param($stmt_log, "sssss", $user_id, $user_row['username'], $user_row['role'], $log_action, $log_details);
                mysqli_stmt_execute($stmt_log);
                mysqli_stmt_close($stmt_log);
            }

            // Check if profile is now fully completed
            require_once __DIR__ . '/../includes/auth_guard.php';
            $now_complete = check_user_personal_info_complete($conn, $user_id);
            $uid_safe = mysqli_real_escape_string($conn, $user_id);
            if ($now_complete) {
                $_SESSION['profile_completed'] = 1;
                @mysqli_query($conn, "UPDATE user SET profile_completed = 1 WHERE idno = '$uid_safe'");
            } else {
                $_SESSION['profile_completed'] = 0;
                @mysqli_query($conn, "UPDATE user SET profile_completed = 0 WHERE idno = '$uid_safe'");
            }

            echo json_encode([
                'success' => true,
                'message' => 'Personal details' . ($update_password ? ' and password' : '') . ' updated successfully.' . ($now_complete ? ' All system features are now unlocked!' : ''),
                'profile_completed' => $now_complete ? 1 : 0
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: Could not update details.']);
        }
        mysqli_stmt_close($stmt);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare database statement.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}
?>