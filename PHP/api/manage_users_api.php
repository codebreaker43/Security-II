<?php
session_start();
include("../db.php");

header('Content-Type: application/json');

// Check if user is logged in and has proper role
if (!isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$current_role = $_SESSION['role'];
$current_user_id = $_SESSION['user_id'];

// Only admin and superadmin can access this API
if ($current_role !== 'admin' && $current_role !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Fetch privileges globally for admins
$admin_privileges = [];
if ($current_role === 'admin') {
    $priv_query = mysqli_query($conn, "SELECT privileges FROM user WHERE id = $current_user_id");
    if ($priv_query && mysqli_num_rows($priv_query) > 0) {
        $priv_data = mysqli_fetch_assoc($priv_query);
        $privileges_json = $priv_data['privileges'];
        $admin_privileges = $privileges_json ? json_decode($privileges_json, true) : [];
        if (!is_array($admin_privileges)) {
            $admin_privileges = [];
        }
    }
}

function getNextUserSequentialId($conn, $role = 'user') {
    if ($role === 'admin' || $role === 'superadmin') {
        $prefix = 'ADMIN-';
        $query = "SELECT idno FROM user WHERE idno LIKE 'ADMIN-%'";
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
        $nextNum = ($maxNum > 0) ? $maxNum + 1 : 1;
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    } else {
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
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'get_next_idno') {
            $role_req = isset($_GET['role']) ? $_GET['role'] : 'user';
            echo json_encode(['success' => true, 'idno' => getNextUserSequentialId($conn, $role_req)]);
            break;
        }

        // Check if status column exists
        $status_check = mysqli_query($conn, "SHOW COLUMNS FROM `user` LIKE 'status'");
        $has_status = mysqli_num_rows($status_check) > 0;

        // Check if role column exists
        $role_check = mysqli_query($conn, "SHOW COLUMNS FROM `user` LIKE 'role'");
        $has_role = mysqli_num_rows($role_check) > 0;

        // Check if privileges column exists
        $privilege_check = mysqli_query($conn, "SHOW COLUMNS FROM `user` LIKE 'privileges'");
        $has_privileges = mysqli_num_rows($privilege_check) > 0;

        // Build query based on available columns
        $select_fields = "id, idno, firstname, lastname, username, email";
        if ($has_role) {
            $select_fields .= ", role";
        }
        if ($has_status) {
            $select_fields .= ", status";
        }
        if ($has_privileges) {
            $select_fields .= ", privileges";
        }

        // Fetch users based on role permissions
        if ($current_role === 'superadmin') {
            // Super admin can see all users except themselves
            if ($has_role) {
                if (isset($_GET['user_id'])) {
                    $user_id = intval($_GET['user_id']);
                    $query = "SELECT $select_fields FROM user WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                } else {
                    $query = "SELECT $select_fields FROM user WHERE id != ? ORDER BY role, lastname";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $current_user_id);
                }
            } else {
                if (isset($_GET['user_id'])) {
                    $user_id = intval($_GET['user_id']);
                    $query = "SELECT $select_fields FROM user WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                } else {
                    $query = "SELECT $select_fields FROM user WHERE id != ? ORDER BY lastname";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $current_user_id);
                }
            }
        } else {
            // Admin can only see users (not admins or superadmins)
            // But first check if they have 'view_users' privilege
            if (!in_array('view_users', $admin_privileges)) {
                echo json_encode(['success' => true, 'users' => []]);
                break;
            }
            if ($has_role) {
                // ... rest remains same
                // If specific user requested
                if (isset($_GET['user_id'])) {
                    $user_id = intval($_GET['user_id']);
                    $query = "SELECT * FROM user WHERE id = ? AND role = 'user'";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                } else {
                    $query = "SELECT $select_fields FROM user WHERE role = 'user' ORDER BY lastname";
                    $stmt = mysqli_prepare($conn, $query);
                }
            } else {
                if (isset($_GET['user_id'])) {
                    $user_id = intval($_GET['user_id']);
                    $query = "SELECT * FROM user WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                } else {
                    $query = "SELECT $select_fields FROM user ORDER BY lastname";
                    $stmt = mysqli_prepare($conn, $query);
                }
            }
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Provide defaults if columns don't exist
            if (!isset($row['role'])) {
                $row['role'] = 'user';
            }
            if (!isset($row['status'])) {
                $row['status'] = 'active';
            }
            $users[] = $row;
        }

        echo json_encode(['success' => true, 'users' => $users]);
        break;

    case 'POST':
        // Update user (role, status)
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['action'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing action field']);
            exit();
        }

        $action = $data['action'];

        // Enforce admin privileges
        if ($current_role === 'admin') {
            if (in_array($action, ['block', 'unblock', 'approve']) && !in_array('block_users', $admin_privileges)) {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have permission to block/unblock users']);
                exit();
            }
            if ($action === 'update_user_info' && !in_array('edit_users', $admin_privileges)) {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have permission to edit users']);
                exit();
            }
        }

        // user_id is required for actions other than create_user and clear_logs
        if ($action !== 'create_user' && $action !== 'clear_logs' && !isset($data['user_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing user_id field']);
            exit();
        }

        $target_user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;

        // For actions that don't target a specific existing user (like clear_logs or create_user)
        if ($action === 'clear_logs' || $action === 'create_user') {
            // Proceed directly to switch
        } else {
            // Get target user info for user-specific actions
            $check_query = "SELECT id, role, username FROM user WHERE id = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "i", $target_user_id);
            mysqli_stmt_execute($check_stmt);
            $target_user = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));

            if (!$target_user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit();
            }

            // Permission check: Admin cannot modify admin or superadmin
            if ($current_role === 'admin' && ($target_user['role'] === 'admin' || $target_user['role'] === 'superadmin')) {
                http_response_code(403);
                echo json_encode(['error' => 'You cannot modify administrators']);
                exit();
            }
        }

        // Prevent self-modification
        if ($target_user_id === $current_user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'You cannot modify your own account']);
            exit();
        }

        switch ($action) {
            case 'block':
                // Ensure status column exists first
                $status_check = mysqli_query($conn, "SHOW COLUMNS FROM `user` LIKE 'status'");
                if (mysqli_num_rows($status_check) == 0) {
                    // Add the column if it doesn't exist
                    mysqli_query($conn, "ALTER TABLE `user` ADD `status` ENUM('active', 'blocked', 'pending') NOT NULL DEFAULT 'active'");
                }

                $update = "UPDATE user SET status = 'blocked' WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update);
                mysqli_stmt_bind_param($update_stmt, "i", $target_user_id);

                if (mysqli_stmt_execute($update_stmt)) {
                    // Log the action
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Block User", "Blocked user: " . $target_user['username']);
                    echo json_encode(['success' => true, 'message' => 'User blocked successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to block user: ' . mysqli_error($conn)]);
                }
                break;

            case 'unblock':
                // Ensure status column exists first
                $status_check = mysqli_query($conn, "SHOW COLUMNS FROM `user` LIKE 'status'");
                if (mysqli_num_rows($status_check) == 0) {
                    // Add the column if it doesn't exist
                    mysqli_query($conn, "ALTER TABLE `user` ADD `status` ENUM('active', 'blocked', 'pending') NOT NULL DEFAULT 'active'");
                }

                $update = "UPDATE user SET status = 'active' WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update);
                mysqli_stmt_bind_param($update_stmt, "i", $target_user_id);

                if (mysqli_stmt_execute($update_stmt)) {
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Unblock User", "Unblocked user: " . $target_user['username']);
                    echo json_encode(['success' => true, 'message' => 'User unblocked successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to unblock user']);
                }
                break;

            case 'approve':
                // Ensure status column exists and is right enum
                $status_check = mysqli_query($conn, "SHOW COLUMNS FROM `user` LIKE 'status'");
                if (mysqli_num_rows($status_check) == 0) {
                    mysqli_query($conn, "ALTER TABLE `user` ADD `status` ENUM('active', 'blocked', 'pending') NOT NULL DEFAULT 'active'");
                } else {
                    // Also safe to update the ENUM to include pending if it doesn't, this relies on MySQL
                    // ALTER TABLE `user` MODIFY `status` ENUM('active', 'blocked', 'pending') NOT NULL DEFAULT 'active';
                    mysqli_query($conn, "ALTER TABLE `user` MODIFY `status` ENUM('active', 'blocked', 'pending') NOT NULL DEFAULT 'active'");
                }

                $update = "UPDATE user SET status = 'active' WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update);
                mysqli_stmt_bind_param($update_stmt, "i", $target_user_id);

                if (mysqli_stmt_execute($update_stmt)) {
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Approve User", "Approved user: " . $target_user['username']);
                    echo json_encode(['success' => true, 'message' => 'User approved successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to approve user']);
                }
                break;

            case 'update_role':
                // Only superadmin can change roles
                if ($current_role !== 'superadmin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Only super admin can change roles']);
                    exit();
                }

                if (!isset($data['new_role']) || !in_array($data['new_role'], ['user', 'admin', 'superadmin'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid role specified']);
                    exit();
                }

                $new_role = $data['new_role'];
                $update = "UPDATE user SET role = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update);
                mysqli_stmt_bind_param($update_stmt, "si", $new_role, $target_user_id);

                if (mysqli_stmt_execute($update_stmt)) {
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Change Role", "Changed " . $target_user['username'] . " role to: " . $new_role);

                    // If promoting to superadmin, demote and block the current superadmin
                    if ($new_role === 'superadmin') {
                        $demote_block_self = "UPDATE user SET role = 'admin', status = 'blocked' WHERE id = ?";
                        $demote_stmt = mysqli_prepare($conn, $demote_block_self);
                        mysqli_stmt_bind_param($demote_stmt, "i", $current_user_id);
                        mysqli_stmt_execute($demote_stmt);

                        logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Auto-Demoted & Blocked", "Account demoted to admin and blocked after promoting " . $target_user['username'] . " to superadmin");

                        // Destroy session so the demoted/blocked superadmin is logged out
                        session_destroy();

                        echo json_encode(['success' => true, 'message' => 'Role updated. Your account has been demoted to admin and blocked. You will be logged out.', 'logout' => true]);
                    } else {
                        echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
                    }
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update role']);
                }
                break;

            case 'clear_logs':
                if ($current_role !== 'superadmin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Only super admin can clear logs']);
                    exit();
                }

                if (mysqli_query($conn, "TRUNCATE TABLE audit_logs")) {
                    // Log the action of clearing logs (this will be the first and only log entry!)
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Clear Logs", "All audit logs have been cleared.");
                    echo json_encode(['success' => true, 'message' => 'All logs cleared successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to clear logs: ' . mysqli_error($conn)]);
                }
                break;

            case 'create_user':
                // Only superadmin can create users/admins
                if ($current_role !== 'superadmin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Only super admin can create accounts']);
                    exit();
                }

                // Required fields
                $required_fields = ['username', 'password', 'email', 'firstname', 'lastname', 'role'];
                foreach ($required_fields as $field) {
                    if (empty($data[$field])) {
                        http_response_code(400);
                        echo json_encode(['error' => "Field '$field' is required"]);
                        exit();
                    }
                }

                $username = mysqli_real_escape_string($conn, $data['username']);
                $email = mysqli_real_escape_string($conn, $data['email']);
                $role = mysqli_real_escape_string($conn, $data['role']);

                // Validate role
                if (!in_array($role, ['user', 'admin'])) {
                    http_response_code(400);
                    echo json_encode(['error' => "Invalid role"]);
                    exit();
                }

                // Check for duplicates
                $check_sql = "SELECT id FROM user WHERE username = '$username' OR email = '$email'";
                $check_result = mysqli_query($conn, $check_sql);
                if (mysqli_num_rows($check_result) > 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Username or Email already exists']);
                    exit();
                }

                // Ensure privileges column exists
                $privilege_check = mysqli_query($conn, "SHOW COLUMNS FROM `user` LIKE 'privileges'");
                if (mysqli_num_rows($privilege_check) == 0) {
                    mysqli_query($conn, "ALTER TABLE `user` ADD `privileges` TEXT DEFAULT NULL");
                }

                $privileges_json = 'null';
                if (isset($data['privileges']) && is_array($data['privileges'])) {
                    $privileges_json = "'" . mysqli_real_escape_string($conn, json_encode($data['privileges'])) . "'";
                }

                // Hash password
                $password = password_hash($data['password'], PASSWORD_DEFAULT);

                // Optional/Role-dependent fields
                $idno = isset($data['idno']) ? trim(mysqli_real_escape_string($conn, $data['idno'])) : '';
                if (empty($idno)) {
                    $idno = getNextUserSequentialId($conn, $role);
                } else {
                    $check_id = mysqli_query($conn, "SELECT id FROM user WHERE idno = '$idno'");
                    if (mysqli_num_rows($check_id) > 0) {
                        $idno = getNextUserSequentialId($conn, $role);
                    }
                }
                $firstname = mysqli_real_escape_string($conn, $data['firstname']);
                $middlename = isset($data['middlename']) ? mysqli_real_escape_string($conn, $data['middlename']) : '';
                $lastname = mysqli_real_escape_string($conn, $data['lastname']);
                $suffix = isset($data['suffix']) ? mysqli_real_escape_string($conn, $data['suffix']) : '';
                $sex = isset($data['sex']) ? mysqli_real_escape_string($conn, $data['sex']) : '';
                $birthdate = isset($data['birthdate']) ? mysqli_real_escape_string($conn, $data['birthdate']) : '2000-01-01'; // Default
                $age = isset($data['age']) ? mysqli_real_escape_string($conn, $data['age']) : '0';

                // Address Info
                $purokstreet = isset($data['purokstreet']) ? mysqli_real_escape_string($conn, $data['purokstreet']) : '';
                $brgy = isset($data['brgy']) ? mysqli_real_escape_string($conn, $data['brgy']) : '';
                $municipal = isset($data['municipal']) ? mysqli_real_escape_string($conn, $data['municipal']) : '';
                $province = isset($data['province']) ? mysqli_real_escape_string($conn, $data['province']) : '';
                $country = isset($data['country']) ? mysqli_real_escape_string($conn, $data['country']) : '';
                $zipcode = isset($data['zipcode']) ? mysqli_real_escape_string($conn, $data['zipcode']) : '';

                // Security Questions
                $security_question1 = isset($data['security_question1']) ? mysqli_real_escape_string($conn, $data['security_question1']) : '';
                $security_answer1 = isset($data['security_answer1']) ? mysqli_real_escape_string($conn, $data['security_answer1']) : '';
                $security_question2 = isset($data['security_question2']) ? mysqli_real_escape_string($conn, $data['security_question2']) : '';
                $security_answer2 = isset($data['security_answer2']) ? mysqli_real_escape_string($conn, $data['security_answer2']) : '';
                $security_question3 = isset($data['security_question3']) ? mysqli_real_escape_string($conn, $data['security_question3']) : '';
                $security_answer3 = isset($data['security_answer3']) ? mysqli_real_escape_string($conn, $data['security_answer3']) : '';

                $sql = "INSERT INTO user 
                (username, password, email, role, firstname, lastname, middlename, suffix, idno, sex, birthdate, age, purokstreet, brgy, municipal, province, country, zipcode, security_question1, security_answer1, security_question2, security_answer2, security_question3, security_answer3, status, privileges) 
                VALUES 
                ('$username', '$password', '$email', '$role', '$firstname', '$lastname', '$middlename', '$suffix', '$idno', '$sex', '$birthdate', '$age', '$purokstreet', '$brgy', '$municipal', '$province', '$country', '$zipcode', '$security_question1', '$security_answer1', '$security_question2', '$security_answer2', '$security_question3', '$security_answer3', 'active', $privileges_json)";

                if (mysqli_query($conn, $sql)) {
                    $new_user_id = mysqli_insert_id($conn);
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Create User", "Created new $role: $username");
                    echo json_encode(['success' => true, 'message' => 'Account created successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to create account: ' . mysqli_error($conn)]);
                }
                break;

            case 'update_user_info':
                // Check permissions
                if ($current_role !== 'superadmin' && $current_role !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Unauthorized']);
                    exit();
                }

                $target_user_id = intval($data['user_id']);

                // Get current info for logging
                $old_query = "SELECT * FROM user WHERE id = ?";
                $stmt_old = mysqli_prepare($conn, $old_query);
                mysqli_stmt_bind_param($stmt_old, "i", $target_user_id);
                mysqli_stmt_execute($stmt_old);
                $old_user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_old));

                // Permission check: Admin cannot modify admin or superadmin
                if ($current_role === 'admin' && ($old_user_data['role'] === 'admin' || $old_user_data['role'] === 'superadmin')) {
                    http_response_code(403);
                    echo json_encode(['error' => 'You cannot modify administrators']);
                    exit();
                }

                // Collect and sanitize input
                $idno = mysqli_real_escape_string($conn, $data['idno']);
                $firstname = mysqli_real_escape_string($conn, $data['firstname']);
                $middlename = mysqli_real_escape_string($conn, $data['middlename']);
                $lastname = mysqli_real_escape_string($conn, $data['lastname']);
                $suffix = mysqli_real_escape_string($conn, $data['suffix']);
                $birthdate = mysqli_real_escape_string($conn, $data['birthdate']);
                $age = mysqli_real_escape_string($conn, $data['age']);
                $sex = mysqli_real_escape_string($conn, $data['sex']);
                $country = mysqli_real_escape_string($conn, $data['country']);
                $purokstreet = mysqli_real_escape_string($conn, $data['purokstreet']);
                $brgy = mysqli_real_escape_string($conn, $data['brgy']);
                $municipal = mysqli_real_escape_string($conn, $data['municipal']);
                $province = mysqli_real_escape_string($conn, $data['province']);
                $zipcode = mysqli_real_escape_string($conn, $data['zipcode']);
                $username = mysqli_real_escape_string($conn, $data['username']);
                $email = mysqli_real_escape_string($conn, $data['email']);

                // Correctly handle duplicates (exclude current user)
                $check_sql = "SELECT id FROM user WHERE (username = '$username' OR email = '$email' OR idno = '$idno') AND id != $target_user_id";
                $check_result = mysqli_query($conn, $check_sql);
                if (mysqli_num_rows($check_result) > 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Username, Email, or ID No already exists']);
                    exit();
                }

                // Ensure privileges column exists
                $privilege_check = mysqli_query($conn, "SHOW COLUMNS FROM `user` LIKE 'privileges'");
                if (mysqli_num_rows($privilege_check) == 0) {
                    mysqli_query($conn, "ALTER TABLE `user` ADD `privileges` TEXT DEFAULT NULL");
                }

                $privilege_update = "";
                if (isset($data['privileges']) && is_array($data['privileges'])) {
                    $privileges_json = mysqli_real_escape_string($conn, json_encode($data['privileges']));
                    $privilege_update = ", privileges='$privileges_json'";
                }

                $update_sql = "UPDATE user SET 
                    idno='$idno', firstname='$firstname', middlename='$middlename', lastname='$lastname', suffix='$suffix', 
                    birthdate='$birthdate', age='$age', sex='$sex', country='$country', purokstreet='$purokstreet', 
                    brgy='$brgy', municipal='$municipal', province='$province', zipcode='$zipcode', username='$username', email='$email'
                    $privilege_update
                    WHERE id=$target_user_id";

                if (mysqli_query($conn, $update_sql)) {
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Update User Info", "Updated info for user: " . $username);
                    echo json_encode(['success' => true, 'message' => 'User information updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update user info: ' . mysqli_error($conn)]);
                }
                break;

            case 'update_privileges':
                // Check permissions
                if ($current_role !== 'superadmin' && $current_role !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Unauthorized']);
                    exit();
                }

                $target_user_id = intval($data['user_id']);

                // Get current info for logging
                $old_query = "SELECT * FROM user WHERE id = ?";
                $stmt_old = mysqli_prepare($conn, $old_query);
                mysqli_stmt_bind_param($stmt_old, "i", $target_user_id);
                mysqli_stmt_execute($stmt_old);
                $old_user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_old));

                // Permission check: Admin cannot modify admin or superadmin privileges
                if ($current_role === 'admin' && ($old_user_data['role'] === 'admin' || $old_user_data['role'] === 'superadmin')) {
                    http_response_code(403);
                    echo json_encode(['error' => 'You cannot modify administrators']);
                    exit();
                }

                // Ensure privileges column exists
                $privilege_check = mysqli_query($conn, "SHOW COLUMNS FROM `user` LIKE 'privileges'");
                if (mysqli_num_rows($privilege_check) == 0) {
                    mysqli_query($conn, "ALTER TABLE `user` ADD `privileges` TEXT DEFAULT NULL");
                }

                $privileges_json = 'null';
                if (isset($data['privileges']) && is_array($data['privileges'])) {
                    $privileges_json = mysqli_real_escape_string($conn, json_encode($data['privileges']));
                    $privilege_update = "UPDATE user SET privileges='$privileges_json' WHERE id=$target_user_id";
                } else {
                    $privilege_update = "UPDATE user SET privileges=NULL WHERE id=$target_user_id";
                }

                if (mysqli_query($conn, $privilege_update)) {
                    logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Update User Privileges", "Updated privileges for user: " . $old_user_data['username']);
                    echo json_encode(['success' => true, 'message' => 'Admin privileges updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update privileges: ' . mysqli_error($conn)]);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['user_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing user_id']);
            exit();
        }

        $target_user_id = intval($data['user_id']);

        // Check permissions: Superadmin can delete anyone. Admin can delete users if they have the privilege.
        if ($current_role !== 'superadmin') {
            if ($current_role === 'admin') {
                if (!in_array('delete_users', $admin_privileges)) {
                    http_response_code(403);
                    echo json_encode(['error' => 'You do not have permission to delete users']);
                    exit();
                }
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                exit();
            }
        }

        // Get user info for logging and validation
        $check_query = "SELECT username, role, status FROM user WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "i", $target_user_id);
        mysqli_stmt_execute($check_stmt);
        $target_user = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));

        if (!$target_user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit();
        }

        // Cannot delete a superadmin
        if ($target_user['role'] === 'superadmin') {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot delete a super admin']);
            exit();
        }

        // Admin cannot delete another admin
        if ($current_role === 'admin' && $target_user['role'] === 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admins cannot delete other administrators']);
            exit();
        }

        // Cannot delete yourself
        if ($target_user_id === $current_user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot delete your own account']);
            exit();
        }

        if ($target_user['status'] === 'inactive') {
            echo json_encode(['success' => true, 'message' => 'User is already inactive']);
            exit();
        }

        // Ensure status column exists and includes 'inactive' enum
        $status_check = mysqli_query($conn, "SHOW COLUMNS FROM `user` LIKE 'status'");
        if (mysqli_num_rows($status_check) == 0) {
            mysqli_query($conn, "ALTER TABLE `user` ADD `status` ENUM('active', 'blocked', 'pending', 'inactive') NOT NULL DEFAULT 'active'");
        } else {
            // Update enum to include inactive
            mysqli_query($conn, "ALTER TABLE `user` MODIFY `status` ENUM('active', 'blocked', 'pending', 'inactive') NOT NULL DEFAULT 'active'");
        }

        // Soft Delete: Update status to inactive
        $delete = "UPDATE user SET status = 'inactive' WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete);
        mysqli_stmt_bind_param($delete_stmt, "i", $target_user_id);

        if (mysqli_stmt_execute($delete_stmt)) {
            logAction($conn, $current_user_id, $_SESSION['username'], $current_role, "Deactivate User", "Deactivated user: " . $target_user['username']);
            echo json_encode(['success' => true, 'message' => 'User account deactivated (soft delete) successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to deactivate user']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function logAction($conn, $user_id, $username, $role, $action, $details)
{
    $log_sql = "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $log_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $username, $role, $action, $details);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>