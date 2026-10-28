<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/mail_config.php';

header('Content-Type: application/json');

// Check if user is logged in and has proper role
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    if (ob_get_length()) ob_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$current_role = $_SESSION['role'];
$current_user_id = trim((string)$_SESSION['user_id']);

// Guard: Require profile completion before accessing features
require_once __DIR__ . '/../includes/auth_guard.php';
enforce_profile_completion($conn, true);

// Only admin and superadmin can access this API
if ($current_role !== 'admin' && $current_role !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Fetch privileges for admins
$admin_privileges = [];
if ($current_role === 'admin') {
    $priv_query = mysqli_query($conn, "SELECT privileges FROM user WHERE idno = '" . mysqli_real_escape_string($conn, $current_user_id) . "'");
    if ($priv_query && mysqli_num_rows($priv_query) > 0) {
        $priv_data = mysqli_fetch_assoc($priv_query);
        $privileges_json = $priv_data['privileges'] ?? '[]';
        $admin_privileges = $privileges_json ? json_decode($privileges_json, true) : [];
        if (!is_array($admin_privileges) || empty($admin_privileges)) {
            $admin_privileges = ['view_users', 'create_users', 'approve_users'];
        }
    } else {
        $admin_privileges = ['view_users', 'create_users', 'approve_users'];
    }
}

// Helper: Verify administrator's own password
function verifyAdminPassword($conn, $admin_id, $input_password) {
    if (empty($input_password)) {
        return false;
    }
    // 1. Try finding admin by idno
    $stmt = mysqli_prepare($conn, "SELECT password FROM user WHERE idno = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $admin_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            mysqli_stmt_close($stmt);
            if (password_verify($input_password, $row['password'])) {
                return true;
            }
        } else {
            mysqli_stmt_close($stmt);
        }
    }

    // 2. Fallback: try finding admin by session username if idno did not match (e.g. session was created before migration)
    if (!empty($_SESSION['username'])) {
        $stmt_u = mysqli_prepare($conn, "SELECT idno, password FROM user WHERE username = ? LIMIT 1");
        if ($stmt_u) {
            mysqli_stmt_bind_param($stmt_u, "s", $_SESSION['username']);
            mysqli_stmt_execute($stmt_u);
            $res_u = mysqli_stmt_get_result($stmt_u);
            if ($row_u = mysqli_fetch_assoc($res_u)) {
                mysqli_stmt_close($stmt_u);
                if (password_verify($input_password, $row_u['password'])) {
                    // Sync session user_id to correct idno
                    $_SESSION['user_id'] = $row_u['idno'];
                    return true;
                }
            } else {
                mysqli_stmt_close($stmt_u);
            }
        }
    }

    return false;
}

// Helper: Generate next sequential ID Number (Pure numeric YYYY-NNNN for all roles)
function getNextUserSequentialId($conn, $role = 'user') {
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

// Server-side mirror of scanNameField from registration.php / signup.js
function scanNameFieldServer($val, $fieldLabel) {
    if ($val === '') {
        return "$fieldLabel fields must be filled in.";
    }
    if ($val[0] === ' ') {
        return "The first character of the " . strtolower($fieldLabel) . " should not be a space.";
    }

    $len = strlen($val);
    for ($i = 0; $i < $len; $i++) {
        $char = $val[$i];

        if ($char === ' ' && $i + 1 < $len && $val[$i + 1] === ' ') {
            return "$fieldLabel cannot contain double space.";
        }

        if ($i + 2 < $len && ctype_alpha($char)) {
            if (strtolower($char) === strtolower($val[$i + 1]) && strtolower($char) === strtolower($val[$i + 2])) {
                return "$fieldLabel cannot contain three consecutive repeated letters.";
            }
        }

        if (ctype_digit($char)) {
            return "$fieldLabel cannot contain numbers.";
        }

        if (!ctype_alpha($char) && $char !== ' ') {
            return "$fieldLabel cannot contain special characters.";
        }

        if ($i === 0) {
            if (ctype_lower($char)) {
                return "The first letter of " . strtolower($fieldLabel) . " should be capitalized.";
            }
        } elseif ($val[$i - 1] === ' ') {
            if (ctype_lower($char)) {
                return "The second word of " . strtolower($fieldLabel) . " should be capitalized.";
            }
        } else {
            if (ctype_upper($char)) {
                return "The first letter of " . strtolower($fieldLabel) . " should be the only capitalized.";
            }
        }
    }

    if ($len < 3) {
        return "$fieldLabel fields must be at least 3 characters.";
    }
    if ($len > 25) {
        return "$fieldLabel should not exceed 25 characters.";
    }

    return null;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'get_next_idno') {
            $role_req = isset($_GET['role']) ? $_GET['role'] : 'user';
            echo json_encode(['success' => true, 'idno' => getNextUserSequentialId($conn, $role_req)]);
            break;
        }

        // Clean up stale superadmin sessions (> 15 mins inactive)
        @mysqli_query($conn, "UPDATE user SET is_online = 0 WHERE role = 'superadmin' AND is_online = 1 AND last_activity IS NOT NULL AND last_activity < NOW() - INTERVAL 15 MINUTE");

        $select_fields = "idno, firstname, middlename, lastname, suffix, username, email, role, status, block_reason, block_file, delete_reason, delete_file, delete_requested_by, delete_requested_at, is_online, last_activity, privileges, birthdate, age, sex, country, purokstreet, brgy, municipal, province, zipcode, must_change_password, security_question1, security_question2, security_question3";

        if ($current_role === 'superadmin') {
            // Super Admin can view all users, admins, and other superadmins
            if (isset($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $query = "SELECT $select_fields FROM user WHERE idno = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "s", $user_id);
            } else {
                $query = "SELECT $select_fields FROM user ORDER BY role DESC, idno ASC";
                $stmt = mysqli_prepare($conn, $query);
            }
        } else {
            // Admin can ONLY view User accounts
            if (!in_array('view_users', $admin_privileges)) {
                echo json_encode(['success' => true, 'users' => []]);
                break;
            }
            if (isset($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $query = "SELECT $select_fields FROM user WHERE idno = ? AND role = 'user'";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "s", $user_id);
            } else {
                $query = "SELECT $select_fields FROM user WHERE role = 'user' ORDER BY lastname ASC, idno ASC";
                $stmt = mysqli_prepare($conn, $query);
            }
        }

        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $users = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $row['id'] = $row['idno']; // Map id to idno for legacy UI references
                $row['role'] = $row['role'] ?? 'user';
                $row['status'] = $row['status'] ?? 'active';
                // Never expose password hashes
                unset($row['password']);
                $users[] = $row;
            }
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => true, 'users' => $users]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch accounts']);
        }
        break;

    case 'POST':
        $raw_input = file_get_contents('php://input');
        $json_data = json_decode($raw_input, true);
        $data = is_array($json_data) ? $json_data : $_POST;

        if (!isset($data['action'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing action field']);
            exit();
        }

        $action = $data['action'];

        // Decode privileges if passed as JSON string via FormData
        if (isset($data['privileges']) && is_string($data['privileges'])) {
            $parsed_p = json_decode($data['privileges'], true);
            if (is_array($parsed_p)) {
                $data['privileges'] = $parsed_p;
            }
        }

        // Sensitive actions requiring administrator's own password confirmation
        $sensitive_actions = ['block', 'unblock', 'approve', 'update_role', 'update_user_info', 'update_privileges', 'reset_session', 'delete'];
        if (in_array($action, $sensitive_actions)) {
            $admin_password = $data['admin_password'] ?? '';
            if (empty($admin_password) || !verifyAdminPassword($conn, $current_user_id, $admin_password)) {
                http_response_code(401);
                echo json_encode(['error' => 'Incorrect administrator password. Action cancelled.']);
                exit();
            }
        }

        // Handle Reset Session
        if ($action === 'reset_session') {
            if ($current_role !== 'superadmin') {
                http_response_code(403);
                echo json_encode(['error' => 'Only Super Admins can reset user sessions.']);
                exit();
            }
            $target_id = trim($data['user_id'] ?? '');
            if (empty($target_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid user ID.']);
                exit();
            }
            $reset_stmt = mysqli_prepare($conn, "UPDATE user SET is_online = 0, last_activity = NULL WHERE idno = ?");
            if ($reset_stmt) {
                mysqli_stmt_bind_param($reset_stmt, "s", $target_id);
                mysqli_stmt_execute($reset_stmt);
                mysqli_stmt_close($reset_stmt);
            }

            $target_user_query = mysqli_query($conn, "SELECT username, role FROM user WHERE idno = '" . mysqli_real_escape_string($conn, $target_id) . "' LIMIT 1");
            $target_user_data = mysqli_fetch_assoc($target_user_query);
            $target_uname = $target_user_data['username'] ?? "ID $target_id";
            $target_role = $target_user_data['role'] ?? "user";

            $log_admin_id = $_SESSION['user_id'];
            $log_admin_uname = $_SESSION['username'];
            $audit_action = "Reset Session";
            $audit_details = "Super Admin $log_admin_uname manually reset active session for $target_role: $target_uname";
            $stmt_audit = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, 'superadmin', ?, ?)");
            if ($stmt_audit) {
                mysqli_stmt_bind_param($stmt_audit, "ssss", $log_admin_id, $log_admin_uname, $audit_action, $audit_details);
                mysqli_stmt_execute($stmt_audit);
                mysqli_stmt_close($stmt_audit);
            }

            echo json_encode(['success' => true, 'message' => "Active session for $target_uname has been successfully cleared."]);
            exit();
        }

        // Handle Account Creation (4 fields: ID Number, Username, Role, Default Password)
        if ($action === 'create_user') {
            $raw_idno = trim($data['idno'] ?? '');
            $raw_username = trim($data['username'] ?? '');
            $raw_role = trim($data['role'] ?? 'user');
            $raw_firstname = trim($data['firstname'] ?? '');
            $raw_lastname = trim($data['lastname'] ?? '');

            if (empty($raw_username)) {
                http_response_code(400);
                echo json_encode(['error' => 'Username is required.']);
                exit();
            }

            // Role restrictions
            if ($current_role === 'admin') {
                if (!in_array('create_users', $admin_privileges)) {
                    http_response_code(403);
                    echo json_encode(['error' => 'You do not have permission to create user accounts.']);
                    exit();
                }
                if ($raw_role !== 'user') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Admins can only create User accounts.']);
                    exit();
                }
            } elseif ($current_role === 'superadmin') {
                if (!in_array($raw_role, ['user', 'admin', 'superadmin'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid role specified.']);
                    exit();
                }
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                exit();
            }

            // Username format validation
            if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $raw_username)) {
                http_response_code(400);
                echo json_encode(['error' => 'Username must be 3-30 characters long and contain only letters, numbers, and underscores.']);
                exit();
            }

            // Check duplicate username
            $stmt_chk = mysqli_prepare($conn, "SELECT idno FROM user WHERE username = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt_chk, "s", $raw_username);
            mysqli_stmt_execute($stmt_chk);
            if (mysqli_num_rows(mysqli_stmt_get_result($stmt_chk)) > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Username is already taken.']);
                exit();
            }
            mysqli_stmt_close($stmt_chk);

            // Generate or validate ID Number (Strictly auto-generated)
            if (empty($raw_idno) || !preg_match('/^\d{4}-\d{4}$/', $raw_idno)) {
                $raw_idno = getNextUserSequentialId($conn, $raw_role);
            } else {
                $stmt_chk_id = mysqli_prepare($conn, "SELECT idno FROM user WHERE idno = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt_chk_id, "s", $raw_idno);
                mysqli_stmt_execute($stmt_chk_id);
                if (mysqli_num_rows(mysqli_stmt_get_result($stmt_chk_id)) > 0) {
                    $raw_idno = getNextUserSequentialId($conn, $raw_role);
                }
                mysqli_stmt_close($stmt_chk_id);
            }

            // Default Password Rule: @Abcde12345 (or custom password entered by admin)
            $chosen_password = isset($data['password']) && !empty(trim($data['password'])) ? trim($data['password']) : '@Abcde12345';
            if (strlen($chosen_password) < 8) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least 8 characters long.']);
                exit();
            }
            if (strlen($chosen_password) > 25) {
                http_response_code(400);
                echo json_encode(['error' => 'Password should not exceed 25 characters.']);
                exit();
            }
            if (!preg_match('/[A-Z]/', $chosen_password) || !preg_match('/[a-z]/', $chosen_password) || !preg_match('/[0-9]/', $chosen_password) || !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]/', $chosen_password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Password is too weak. Must include uppercase, lowercase, number, and special symbol.']);
                exit();
            }
            // Check for 3 consecutive repeated characters
            for ($i = 0; $i < strlen($chosen_password) - 2; $i++) {
                if ($chosen_password[$i] === $chosen_password[$i+1] && $chosen_password[$i] === $chosen_password[$i+2]) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Password is too weak. Cannot contain 3 consecutive repeated characters.']);
                    exit();
                }
            }
            $hashed_password = password_hash($chosen_password, PASSWORD_DEFAULT);
            $must_change_password = 1;

            // Initial status: all newly created accounts are active
            $initial_status = 'active';

            // Privileges: only for admin or superadmin
            $privileges_json = null;
            if ($raw_role === 'superadmin') {
                // If superadmin, always grant full privileges
                $all_privs = ['view_users', 'create_users', 'edit_users', 'delete_users', 'block_users', 'view_audit_logs', 'approve_users'];
                $privileges_json = json_encode($all_privs);
            } elseif ($raw_role === 'admin') {
                $default_admin_privs = ['view_users', 'create_users', 'approve_users'];
                if (isset($data['privileges']) && is_array($data['privileges']) && !empty($data['privileges'])) {
                    $privileges_json = json_encode(array_values(array_unique($data['privileges'])));
                } else {
                    $privileges_json = json_encode($default_admin_privs);
                }
            }

            $fname = !empty($raw_firstname) ? $raw_firstname : ($raw_role === 'superadmin' ? 'Super' : ($raw_role === 'admin' ? 'System' : $raw_username));
            $lname = !empty($raw_lastname) ? $raw_lastname : ($raw_role === 'superadmin' ? 'Admin' : ($raw_role === 'admin' ? 'Admin' : 'User'));
            $email = isset($data['email']) && !empty($data['email']) ? trim($data['email']) : '';

            $profile_completed = 0;
            $stmt_ins = mysqli_prepare($conn, "INSERT INTO user (idno, username, password, role, status, firstname, lastname, email, must_change_password, profile_completed, privileges) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_ins) {
                mysqli_stmt_bind_param($stmt_ins, "ssssssssiis", $raw_idno, $raw_username, $hashed_password, $raw_role, $initial_status, $fname, $lname, $email, $must_change_password, $profile_completed, $privileges_json);
                if (mysqli_stmt_execute($stmt_ins)) {
                    $new_id = mysqli_insert_id($conn);
                    $desc = "Created new $raw_role: $raw_username ($raw_idno) with status: $initial_status";
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Create Account", $desc);

                    // Rule: Only ONE Super Admin can be ACTIVE at any time!
                    $is_self_deactivated = false;
                    if ($raw_role === 'superadmin') {
                        $esc_new_idno = mysqli_real_escape_string($conn, $raw_idno);
                        // Inactivate all other superadmin accounts and clear online status
                        mysqli_query($conn, "UPDATE user SET status = 'inactive', is_online = 0 WHERE role = 'superadmin' AND idno != '$esc_new_idno'");
                        @mysqli_query($conn, "UPDATE `admins` SET status = 'Inactive' WHERE user_idno != '$esc_new_idno'");

                        // Synchronize new superadmin in `admins` table
                        $full_name = trim("$fname $lname") ?: $raw_username;
                        $e_name = mysqli_real_escape_string($conn, $full_name);
                        $e_email = !empty($email) ? "'" . mysqli_real_escape_string($conn, $email) . "'" : "NULL";

                        $chk_adm = mysqli_query($conn, "SELECT id FROM `admins` WHERE user_idno = '$esc_new_idno' LIMIT 1");
                        if ($chk_adm && mysqli_num_rows($chk_adm) > 0) {
                            @mysqli_query($conn, "UPDATE `admins` SET name = '$e_name', email = $e_email, status = 'Active' WHERE user_idno = '$esc_new_idno'");
                        } else {
                            @mysqli_query($conn, "INSERT INTO `admins` (user_idno, name, email, role, status) VALUES ('$esc_new_idno', '$e_name', $e_email, 'Super Admin', 'Active')");
                        }

                        // Send activation notification email if email was provided
                        if (!empty($email)) {
                            send_superadmin_activated_notification($email, $full_name);
                        }

                        // Current superadmin account is now inactive and logged out
                        $is_self_deactivated = true;
                        @mysqli_query($conn, "UPDATE user SET is_online = 0 WHERE idno = '" . mysqli_real_escape_string($conn, $current_user_id) . "'");
                        session_unset();
                        session_destroy();
                    }

                    if ($is_self_deactivated) {
                        echo json_encode([
                            'success' => true,
                            'message' => "Super Admin account '$raw_username' has been created and set to ACTIVE. Since only one Super Admin can be active at a time, your current account has been set to INACTIVE and you have been logged out. The new Super Admin can now log in.",
                            'self_deactivated' => true,
                            'redirect' => '../login.php'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => true,
                            'message' => "Account $raw_username created successfully."
                        ]);
                    }
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to create account: ' . mysqli_error($conn)]);
                }
                mysqli_stmt_close($stmt_ins);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
            }
            break;
        }

        // For user-specific actions
        $target_user_id = trim($data['user_id'] ?? '');
        if (empty($target_user_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing target user_id']);
            exit();
        }

        // Fetch target user info
        $check_stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE idno = ? LIMIT 1");
        mysqli_stmt_bind_param($check_stmt, "s", $target_user_id);
        mysqli_stmt_execute($check_stmt);
        $target_user = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
        mysqli_stmt_close($check_stmt);

        if (!$target_user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found.']);
            exit();
        }

        // Prevent self-modification (cannot edit, block, delete, change role, or change privileges of own account)
        $is_self = ($target_user_id === $current_user_id) 
            || ($target_user['idno'] === $current_user_id) 
            || (!empty($_SESSION['username']) && $target_user['username'] === $_SESSION['username']);

        if ($is_self && in_array($action, ['block', 'delete', 'update_role', 'update_user_info', 'update_privileges'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Dili nimo pwede i-edit, i-block, i-delete, o usbon ang imong kaugalingon nga account dinhi. View ra ang gitugot.']);
            exit();
        }

        // Permission check: Admin can only manage User accounts
        if ($current_role === 'admin') {
            if ($target_user['role'] !== 'user') {
                http_response_code(403);
                echo json_encode(['error' => 'Admins can only manage standard User accounts.']);
                exit();
            }
            if (in_array($action, ['block', 'unblock']) && !in_array('block_users', $admin_privileges)) {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have permission to block/unblock users.']);
                exit();
            }
            if (in_array($action, ['approve', 'reject']) && !in_array('approve_users', $admin_privileges)) {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have permission to approve or reject user registrations.']);
                exit();
            }
            if ($action === 'update_user_info' && !in_array('edit_users', $admin_privileges)) {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have permission to edit users.']);
                exit();
            }
            if (in_array($action, ['update_role', 'update_privileges', 'approve_deletion', 'reject_deletion'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Admins cannot perform this action. Only Super Admin can approve or reject deletion requests and manage roles.']);
                exit();
            }
        }

        switch ($action) {
            case 'block':
                $reason = trim($data['reason'] ?? '');
                if (empty($reason)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'A reason is required to block an account.']);
                    exit();
                }

                $uploaded_file = handleUploadedReasonFile();
                $stmt_blk = mysqli_prepare($conn, "UPDATE user SET status = 'blocked', block_reason = ?, block_file = ? WHERE idno = ?");
                if ($stmt_blk) {
                    mysqli_stmt_bind_param($stmt_blk, "sss", $reason, $uploaded_file, $target_user_id);
                    mysqli_stmt_execute($stmt_blk);
                    mysqli_stmt_close($stmt_blk);

                    $log_details = "Blocked user: " . $target_user['username'] . " | Reason: " . $reason . ($uploaded_file ? " | File: " . $uploaded_file : "");
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Block Account", $log_details);
                    echo json_encode(['success' => true, 'message' => 'Account blocked successfully.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to block account.']);
                }
                break;

            case 'delete':
                $reason = trim($data['reason'] ?? '');
                if (empty($reason)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'A reason is required to delete an account.']);
                    exit();
                }

                // Super Admin cannot delete own account
                if ($target_user_id === $current_user_id) {
                    http_response_code(403);
                    echo json_encode(['error' => 'You cannot delete your own account.']);
                    exit();
                }

                if ($current_role === 'admin' && !in_array('delete_users', $admin_privileges)) {
                    http_response_code(403);
                    echo json_encode(['error' => 'You do not have permission to delete users.']);
                    exit();
                }

                $uploaded_file = handleUploadedReasonFile();

                // Admin-initiated deletion: forwards directly to Super Admin as a Deletion Request
                if ($current_role === 'admin') {
                    $admin_username = !empty($_SESSION['username']) ? $_SESSION['username'] : $current_user_id;
                    $stmt_req = mysqli_prepare($conn, "UPDATE user SET status = 'pending_deletion', delete_reason = ?, delete_file = ?, delete_requested_by = ?, delete_requested_at = NOW() WHERE idno = ?");
                    if ($stmt_req) {
                        mysqli_stmt_bind_param($stmt_req, "ssss", $reason, $uploaded_file, $admin_username, $target_user_id);
                        if (mysqli_stmt_execute($stmt_req)) {
                            $log_details = "Admin ($admin_username) submitted deletion request for user: " . $target_user['username'] . " (" . $target_user_id . ") | Reason: " . $reason . ($uploaded_file ? " | File: " . $uploaded_file : "") . " | Forwarded directly to Super Admin for review.";
                            logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Request Account Deletion", $log_details);
                            echo json_encode([
                                'success' => true,
                                'message' => "Deletion request for user account '" . $target_user['username'] . "' has been submitted directly to the Super Admin for review and approval."
                            ]);
                        } else {
                            http_response_code(500);
                            echo json_encode(['error' => 'Failed to submit deletion request: ' . mysqli_error($conn)]);
                        }
                        mysqli_stmt_close($stmt_req);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
                    }
                    break;
                }

                // Superadmin direct deletion: permanent database removal
                $log_details = "Permanently deleted user: " . $target_user['username'] . " (" . $target_user_id . ", Role: " . $target_user['role'] . ") | Reason: " . $reason . ($uploaded_file ? " | File: " . $uploaded_file : "");
                logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Delete Account", $log_details);

                // Clean up any linked OTP records
                @mysqli_query($conn, "DELETE FROM otp_verifications WHERE user_id = '" . mysqli_real_escape_string($conn, $target_user_id) . "'");

                // Permanently delete user from database
                $stmt_del = mysqli_prepare($conn, "DELETE FROM user WHERE idno = ?");
                if ($stmt_del) {
                    mysqli_stmt_bind_param($stmt_del, "s", $target_user_id);
                    if (mysqli_stmt_execute($stmt_del)) {
                        echo json_encode(['success' => true, 'message' => "Account '" . $target_user['username'] . "' has been permanently deleted from the database."]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Failed to delete account from database: ' . mysqli_error($conn)]);
                    }
                    mysqli_stmt_close($stmt_del);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
                }
                break;

            case 'approve_deletion':
                if ($current_role !== 'superadmin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Only Super Admin can approve account deletion requests.']);
                    exit();
                }

                $req_admin = $target_user['delete_requested_by'] ?? 'Admin';
                $d_reason = $target_user['delete_reason'] ?? 'No reason provided';
                $d_file = $target_user['delete_file'] ?? '';

                $log_details = "Super Admin approved deletion request from Admin ($req_admin) for user: " . $target_user['username'] . " (" . $target_user_id . ") | Reason: " . $d_reason . ($d_file ? " | File: " . $d_file : "");
                logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Approve Deletion Request", $log_details);

                @mysqli_query($conn, "DELETE FROM otp_verifications WHERE user_id = '" . mysqli_real_escape_string($conn, $target_user_id) . "'");

                $stmt_del = mysqli_prepare($conn, "DELETE FROM user WHERE idno = ?");
                if ($stmt_del) {
                    mysqli_stmt_bind_param($stmt_del, "s", $target_user_id);
                    if (mysqli_stmt_execute($stmt_del)) {
                        echo json_encode(['success' => true, 'message' => "Deletion request approved. Account '" . $target_user['username'] . "' has been permanently deleted from the database."]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Failed to delete account: ' . mysqli_error($conn)]);
                    }
                    mysqli_stmt_close($stmt_del);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
                }
                break;

            case 'reject_deletion':
                if ($current_role !== 'superadmin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Only Super Admin can reject account deletion requests.']);
                    exit();
                }

                $reject_note = trim($data['reason'] ?? 'Deletion request was rejected by Super Admin.');
                $req_admin = $target_user['delete_requested_by'] ?? 'Admin';

                $stmt_rej = mysqli_prepare($conn, "UPDATE user SET status = 'active', delete_reason = NULL, delete_file = NULL, delete_requested_by = NULL, delete_requested_at = NULL WHERE idno = ?");
                if ($stmt_rej) {
                    mysqli_stmt_bind_param($stmt_rej, "s", $target_user_id);
                    if (mysqli_stmt_execute($stmt_rej)) {
                        $log_details = "Super Admin rejected deletion request from Admin ($req_admin) for user: " . $target_user['username'] . " (" . $target_user_id . ") | Note: " . $reject_note;
                        logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Reject Deletion Request", $log_details);
                        echo json_encode(['success' => true, 'message' => "Deletion request for '" . $target_user['username'] . "' was rejected. Account has been restored to Active status."]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Failed to reject deletion request: ' . mysqli_error($conn)]);
                    }
                    mysqli_stmt_close($stmt_rej);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
                }
                break;

            case 'unblock':
                $stmt_unb = mysqli_prepare($conn, "UPDATE user SET status = 'active', block_reason = NULL, block_file = NULL WHERE idno = ?");
                if ($stmt_unb) {
                    mysqli_stmt_bind_param($stmt_unb, "s", $target_user_id);
                    mysqli_stmt_execute($stmt_unb);
                    mysqli_stmt_close($stmt_unb);

                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Unblock Account", "Unblocked user: " . $target_user['username']);

                    // Rule: Only ONE Super Admin can be ACTIVE at any time!
                    $is_self_deactivated = false;
                    if ($target_user['role'] === 'superadmin') {
                        $esc_target = mysqli_real_escape_string($conn, $target_user_id);
                        mysqli_query($conn, "UPDATE user SET status = 'inactive', is_online = 0 WHERE role = 'superadmin' AND idno != '$esc_target'");
                        @mysqli_query($conn, "UPDATE `admins` SET status = 'Active' WHERE user_idno = '$esc_target'");
                        @mysqli_query($conn, "UPDATE `admins` SET status = 'Inactive' WHERE user_idno != '$esc_target'");

                        // Dispatch activation notification email via Gmail SMTP
                        if (!empty($target_user['email'])) {
                            $t_name = trim(($target_user['firstname'] ?? '') . ' ' . ($target_user['lastname'] ?? '')) ?: $target_user['username'];
                            send_superadmin_activated_notification($target_user['email'], $t_name);
                        }

                        if ($target_user_id !== $current_user_id) {
                            $is_self_deactivated = true;
                            @mysqli_query($conn, "UPDATE user SET is_online = 0 WHERE idno = '" . mysqli_real_escape_string($conn, $current_user_id) . "'");
                            session_unset();
                            session_destroy();
                        }
                    }

                    if ($is_self_deactivated) {
                        echo json_encode([
                            'success' => true,
                            'message' => "Super Admin '" . $target_user['username'] . "' has been unblocked and set to ACTIVE. Since only one Super Admin can be active at a time, your account has been set to INACTIVE. You will now be redirected to the login page.",
                            'self_deactivated' => true,
                            'redirect' => '../login.php'
                        ]);
                    } else {
                        echo json_encode(['success' => true, 'message' => 'Account unblocked successfully.']);
                    }
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to unblock account.']);
                }
                break;

            case 'approve':
                if ($current_role !== 'superadmin' && !in_array('approve_users', $admin_privileges)) {
                    http_response_code(403);
                    echo json_encode(['error' => 'You do not have permission to approve accounts.']);
                    exit();
                }
                $stmt_app = mysqli_prepare($conn, "UPDATE user SET status = 'active' WHERE idno = ?");
                if ($stmt_app) {
                    mysqli_stmt_bind_param($stmt_app, "s", $target_user_id);
                    mysqli_stmt_execute($stmt_app);
                    mysqli_stmt_close($stmt_app);

                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Approve Account", "Approved registration for: " . $target_user['username']);
                    echo json_encode(['success' => true, 'message' => "Account '" . $target_user['username'] . "' approved successfully."]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to approve account.']);
                }
                break;

            case 'reject':
                if ($current_role !== 'superadmin' && !in_array('approve_users', $admin_privileges)) {
                    http_response_code(403);
                    echo json_encode(['error' => 'You do not have permission to reject registrations.']);
                    exit();
                }
                $reason = trim($data['reason'] ?? 'Registration application was rejected by administrator.');
                $log_details = "Rejected registration for applicant: " . $target_user['username'] . " (" . $target_user_id . ") | Reason: " . $reason;
                logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Reject Registration", $log_details);
                
                // Clean up any linked OTP records
                @mysqli_query($conn, "DELETE FROM otp_verifications WHERE user_id = '" . mysqli_real_escape_string($conn, $target_user_id) . "'");

                // Permanently remove the rejected registration application from the database
                $stmt_rej = mysqli_prepare($conn, "DELETE FROM user WHERE idno = ? AND status = 'pending'");
                if ($stmt_rej) {
                    mysqli_stmt_bind_param($stmt_rej, "s", $target_user_id);
                    if (mysqli_stmt_execute($stmt_rej)) {
                        echo json_encode(['success' => true, 'message' => "Registration for '" . $target_user['username'] . "' has been rejected and removed."]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Failed to reject registration: ' . mysqli_error($conn)]);
                    }
                    mysqli_stmt_close($stmt_rej);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
                }
                break;

            case 'update_role':
                if ($current_role !== 'superadmin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Only Super Admin can change account roles.']);
                    exit();
                }

                $new_role = trim($data['new_role'] ?? '');
                if (!in_array($new_role, ['user', 'admin', 'superadmin'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid role specified.']);
                    exit();
                }

                $stmt_r = null;
                if ($new_role === 'superadmin') {
                    $all_privs = json_encode(['view_users', 'create_users', 'edit_users', 'delete_users', 'block_users', 'view_audit_logs', 'approve_users']);
                    $stmt_r = mysqli_prepare($conn, "UPDATE user SET role = ?, privileges = ? WHERE idno = ?");
                    if ($stmt_r) {
                        mysqli_stmt_bind_param($stmt_r, "sss", $new_role, $all_privs, $target_user_id);
                    }
                } elseif ($new_role === 'admin') {
                    $chk_p = mysqli_query($conn, "SELECT privileges FROM user WHERE idno = '" . mysqli_real_escape_string($conn, $target_user_id) . "' LIMIT 1");
                    $cur_privs = ($chk_p && $p_r = mysqli_fetch_assoc($chk_p)) ? ($p_r['privileges'] ?? null) : null;
                    if (empty($cur_privs) || $cur_privs === 'null' || $cur_privs === '[]') {
                        $def_privs = json_encode(['view_users', 'create_users', 'approve_users']);
                        $stmt_r = mysqli_prepare($conn, "UPDATE user SET role = ?, privileges = ? WHERE idno = ?");
                        if ($stmt_r) {
                            mysqli_stmt_bind_param($stmt_r, "sss", $new_role, $def_privs, $target_user_id);
                        }
                    } else {
                        $stmt_r = mysqli_prepare($conn, "UPDATE user SET role = ? WHERE idno = ?");
                        if ($stmt_r) {
                            mysqli_stmt_bind_param($stmt_r, "ss", $new_role, $target_user_id);
                        }
                    }
                } else {
                    $stmt_r = mysqli_prepare($conn, "UPDATE user SET role = ? WHERE idno = ?");
                    if ($stmt_r) {
                        mysqli_stmt_bind_param($stmt_r, "ss", $new_role, $target_user_id);
                    }
                }

                if ($stmt_r) {
                    mysqli_stmt_execute($stmt_r);
                    mysqli_stmt_close($stmt_r);

                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Change Role", "Changed " . $target_user['username'] . " role from " . $target_user['role'] . " to " . $new_role);
                    echo json_encode(['success' => true, 'message' => "Role updated to $new_role."]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update role.']);
                }
                break;

            case 'update_privileges':
                if ($current_role !== 'superadmin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Only Super Admin can assign or edit privileges.']);
                    exit();
                }

                $privs_in = isset($data['privileges']) && is_array($data['privileges']) ? $data['privileges'] : [];
                if ($target_user['role'] === 'admin') {
                    $privs_in = array_values(array_unique(array_merge(['view_users', 'create_users', 'approve_users'], $privs_in)));
                }
                $privs = !empty($privs_in) ? json_encode($privs_in) : null;
                $stmt_p = mysqli_prepare($conn, "UPDATE user SET privileges = ? WHERE idno = ?");
                if ($stmt_p) {
                    mysqli_stmt_bind_param($stmt_p, "ss", $privs, $target_user_id);
                    mysqli_stmt_execute($stmt_p);
                    mysqli_stmt_close($stmt_p);

                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Change Privileges", "Updated privileges for: " . $target_user['username']);
                    echo json_encode(['success' => true, 'message' => 'Privileges updated successfully.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update privileges.']);
                }
                break;

            case 'update_user_info':
                $idno = $target_user['idno'] ?? $target_user_id; // Cannot be modified
                $username = $target_user['username'] ?? ''; // Cannot be modified
                $firstname = !empty(trim($data['firstname'] ?? '')) ? trim($data['firstname']) : ($target_user['firstname'] ?? '');
                $lastname = !empty(trim($data['lastname'] ?? '')) ? trim($data['lastname']) : ($target_user['lastname'] ?? '');
                $middlename = !empty(trim($data['middlename'] ?? '')) ? trim($data['middlename']) : ($target_user['middlename'] ?? '');
                $suffix = !empty(trim($data['suffix'] ?? '')) ? trim($data['suffix']) : ($target_user['suffix'] ?? '');
                $email = !empty(trim($data['email'] ?? '')) ? trim($data['email']) : ($target_user['email'] ?? '');
                if (empty($email)) {
                    $a_chk = mysqli_query($conn, "SELECT email FROM `admins` WHERE user_idno = '$target_user_id' LIMIT 1");
                    if ($a_chk && ($ar = mysqli_fetch_assoc($a_chk)) && !empty($ar['email'])) {
                        $email = $ar['email'];
                    }
                }
                $role = trim($data['role'] ?? ($target_user['role'] ?? 'user'));
                $status = trim($data['status'] ?? ($target_user['status'] ?? 'inactive'));
                $new_password = trim($data['password'] ?? '');

                $fn_err = scanNameFieldServer($firstname, 'Firstname');
                if ($fn_err) {
                    http_response_code(400);
                    echo json_encode(['error' => $fn_err]);
                    exit();
                }

                $ln_err = scanNameFieldServer($lastname, 'Lastname');
                if ($ln_err) {
                    http_response_code(400);
                    echo json_encode(['error' => $ln_err]);
                    exit();
                }

                // Super Admin can edit all fields including role, status, privileges, password
                // Admin can only edit basic info of User accounts
                if ($current_role === 'admin') {
                    $role = 'user'; // Admin cannot change role
                }

                // Check duplicate email excluding target if email is provided
                if (!empty($email)) {
                    $stmt_dup = mysqli_prepare($conn, "SELECT idno FROM user WHERE email = ? AND idno != ? LIMIT 1");
                    mysqli_stmt_bind_param($stmt_dup, "ss", $email, $target_user_id);
                    mysqli_stmt_execute($stmt_dup);
                    if (mysqli_num_rows(mysqli_stmt_get_result($stmt_dup)) > 0) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Email is already in use by another account.']);
                        exit();
                    }
                    mysqli_stmt_close($stmt_dup);
                }

                // Handle password update if supplied
                $pwd_sql = "";
                if (!empty($new_password)) {
                    if (strlen($new_password) < 8) {
                        http_response_code(400);
                        echo json_encode(['error' => 'New password must be at least 8 characters long.']);
                        exit();
                    }
                    if (strlen($new_password) > 25) {
                        http_response_code(400);
                        echo json_encode(['error' => 'New password should not exceed 25 characters.']);
                        exit();
                    }
                    if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]/', $new_password)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'New password is too weak. Must include uppercase, lowercase, number, and special character.']);
                        exit();
                    }
                    for ($i = 0; $i < strlen($new_password) - 2; $i++) {
                        if ($new_password[$i] === $new_password[$i+1] && $new_password[$i] === $new_password[$i+2]) {
                            http_response_code(400);
                            echo json_encode(['error' => 'New password is too weak. Cannot contain 3 consecutive repeated characters.']);
                            exit();
                        }
                    }
                    $pwd_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $is_default_pwd = ($new_password === '@Abcde12345') ? 1 : 0;
                    $pwd_sql = ", password = '" . mysqli_real_escape_string($conn, $pwd_hash) . "', must_change_password = $is_default_pwd";
                }

                // Handle privileges if superadmin
                $priv_sql = "";
                if ($role === 'superadmin') {
                    $all_privs = ['view_users', 'create_users', 'edit_users', 'delete_users', 'block_users', 'view_audit_logs', 'approve_users'];
                    $priv_json = mysqli_real_escape_string($conn, json_encode($all_privs));
                    $priv_sql = ", privileges = '$priv_json'";
                } elseif ($current_role === 'superadmin') {
                    if (isset($data['privileges']) && is_array($data['privileges'])) {
                        $priv_arr = $data['privileges'];
                        if ($role === 'admin' && empty($priv_arr)) {
                            $priv_arr = ['view_users', 'create_users', 'approve_users'];
                        }
                        $priv_json = mysqli_real_escape_string($conn, json_encode(array_values(array_unique($priv_arr))));
                        $priv_sql = ", privileges = '$priv_json'";
                    } elseif ($role === 'admin') {
                        $chk_p = mysqli_query($conn, "SELECT privileges FROM user WHERE idno = '" . mysqli_real_escape_string($conn, $target_user_id) . "' LIMIT 1");
                        if ($chk_p && $p_row = mysqli_fetch_assoc($chk_p)) {
                            if (empty($p_row['privileges']) || $p_row['privileges'] === 'null' || $p_row['privileges'] === '[]') {
                                $priv_json = mysqli_real_escape_string($conn, json_encode(['view_users', 'create_users', 'approve_users']));
                                $priv_sql = ", privileges = '$priv_json'";
                            }
                        }
                    }
                }

                $update_query = "UPDATE user SET 
                    idno = '" . mysqli_real_escape_string($conn, $idno) . "',
                    username = '" . mysqli_real_escape_string($conn, $username) . "',
                    firstname = '" . mysqli_real_escape_string($conn, $firstname) . "',
                    middlename = '" . mysqli_real_escape_string($conn, $middlename) . "',
                    lastname = '" . mysqli_real_escape_string($conn, $lastname) . "',
                    suffix = '" . mysqli_real_escape_string($conn, $suffix) . "',
                    email = '" . mysqli_real_escape_string($conn, $email) . "',
                    role = '" . mysqli_real_escape_string($conn, $role) . "',
                    status = '" . mysqli_real_escape_string($conn, $status) . "',
                    profile_completed = 1
                    $pwd_sql
                    $priv_sql
                    WHERE idno = '" . mysqli_real_escape_string($conn, $target_user_id) . "'";

                if (mysqli_query($conn, $update_query)) {
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Edit Account", "Updated account details for user: " . $username);

                    // Rule: Only ONE Super Admin can be ACTIVE at any time!
                    $is_self_deactivated = false;
                    if ($role === 'superadmin' && $status === 'active') {
                        $esc_target = mysqli_real_escape_string($conn, $idno);
                        mysqli_query($conn, "UPDATE user SET status = 'inactive', is_online = 0 WHERE role = 'superadmin' AND idno != '$esc_target'");
                        @mysqli_query($conn, "UPDATE `admins` SET status = 'Active' WHERE user_idno = '$esc_target'");
                        @mysqli_query($conn, "UPDATE `admins` SET status = 'Inactive' WHERE user_idno != '$esc_target'");

                        // Dispatch activation notification email via Gmail SMTP
                        $target_email_q = mysqli_query($conn, "SELECT email, firstname, lastname, username FROM user WHERE idno = '$esc_target' LIMIT 1");
                        if ($target_email_q && $t_row = mysqli_fetch_assoc($target_email_q)) {
                            $t_email = $t_row['email'];
                            $t_name = trim($t_row['firstname'] . ' ' . $t_row['lastname']) ?: $t_row['username'];
                            if (!empty($t_email)) {
                                send_superadmin_activated_notification($t_email, $t_name);
                            }
                        }

                        // If the currently logged-in superadmin activated another superadmin, the current user is now inactive
                        if ($target_user_id !== $current_user_id) {
                            $is_self_deactivated = true;
                            @mysqli_query($conn, "UPDATE user SET is_online = 0 WHERE idno = '" . mysqli_real_escape_string($conn, $current_user_id) . "'");
                            session_unset();
                            session_destroy();
                        }
                    }

                    if (ob_get_length()) ob_clean();
                    if ($is_self_deactivated) {
                        echo json_encode([
                            'success' => true,
                            'message' => "Super Admin account '$username' is now ACTIVE. Since only one Super Admin can be active at a time, your current Super Admin account has been set to INACTIVE. You will now be redirected to the login page.",
                            'self_deactivated' => true,
                            'redirect' => '../login.php'
                        ]);
                    } else {
                        echo json_encode(['success' => true, 'message' => 'Account updated successfully.']);
                    }
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update account: ' . mysqli_error($conn)]);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action specified.']);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['user_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing user_id field']);
            exit();
        }

        $target_user_id = trim($data['user_id']);

        // Verify Administrator's Password
        $admin_password = $data['admin_password'] ?? '';
        if (empty($admin_password) || !verifyAdminPassword($conn, $current_user_id, $admin_password)) {
            http_response_code(401);
            echo json_encode(['error' => 'Incorrect administrator password. Action cancelled.']);
            exit();
        }

        // Require reason for deletion
        $reason = trim($data['reason'] ?? '');
        if (empty($reason)) {
            http_response_code(400);
            echo json_encode(['error' => 'A reason is required to delete an account.']);
            exit();
        }

        // Check target user
        $check_stmt = mysqli_prepare($conn, "SELECT idno, role, username, status FROM user WHERE idno = ? LIMIT 1");
        mysqli_stmt_bind_param($check_stmt, "s", $target_user_id);
        mysqli_stmt_execute($check_stmt);
        $target_user = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
        mysqli_stmt_close($check_stmt);

        if (!$target_user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found.']);
            exit();
        }

        // Super Admin cannot delete own account
        if ($target_user_id === $current_user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'You cannot delete your own account.']);
            exit();
        }

        // Admin can only delete User accounts
        if ($current_role === 'admin') {
            if ($target_user['role'] !== 'user') {
                http_response_code(403);
                echo json_encode(['error' => 'Admins can only delete standard User accounts.']);
                exit();
            }
            if (!in_array('delete_users', $admin_privileges)) {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have permission to delete users.']);
                exit();
            }
        }

        // Audit log before permanent deletion
        $log_details = "Permanently deleted user: " . $target_user['username'] . " (" . $target_user_id . ", Role: " . $target_user['role'] . ") | Reason: " . $reason;
        logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Delete Account", $log_details);

        // Clean up any linked OTP records
        @mysqli_query($conn, "DELETE FROM otp_verifications WHERE user_id = '" . mysqli_real_escape_string($conn, $target_user_id) . "'");

        // Permanently delete user from database
        $stmt_del = mysqli_prepare($conn, "DELETE FROM user WHERE idno = ?");
        if ($stmt_del) {
            mysqli_stmt_bind_param($stmt_del, "s", $target_user_id);
            if (mysqli_stmt_execute($stmt_del)) {
                echo json_encode(['success' => true, 'message' => "Account '" . $target_user['username'] . "' has been permanently deleted from the database."]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete account from database: ' . mysqli_error($conn)]);
            }
            mysqli_stmt_close($stmt_del);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed.']);
}

function handleUploadedReasonFile() {
    if (!isset($_FILES['reason_file']) || $_FILES['reason_file']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES['reason_file'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts)) {
        return null;
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        return null;
    }
    $target_dir = __DIR__ . '/../../uploads/';
    if (!file_exists($target_dir)) {
        @mkdir($target_dir, 0777, true);
    }
    $new_filename = uniqid('proof_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) {
        return $new_filename;
    }
    return null;
}

function logAction($conn, $user_id, $username, $role, $action, $details) {
    $log_sql = "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $log_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $user_id, $username, $role, $action, $details);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>