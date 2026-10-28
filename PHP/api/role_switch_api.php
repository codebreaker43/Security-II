<?php
/**
 * Super Admin Role Switch API
 * Handles listing superadmins, initiating role transfer with 24-hr secure token,
 * sending Gmail confirmation email, and request cancellation.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/mail_config.php';
require_once __DIR__ . '/../includes/auth_guard.php';

// Check if user is logged in as Super Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Super Admin privileges required.']);
    exit();
}

$current_user_id = $_SESSION['user_id'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

/**
 * Helper: Find matching admin in `admins` table by user_idno or username or email
 */
function get_current_admin_record($conn, $user_id) {
    $uid_safe = mysqli_real_escape_string($conn, $user_id);
    // Try matching user_idno first
    $q = mysqli_query($conn, "SELECT * FROM `admins` WHERE user_idno = '$uid_safe' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q);
    }
    // Try matching via user table
    $uq = mysqli_query($conn, "SELECT * FROM `user` WHERE idno = '$uid_safe' LIMIT 1");
    if ($uq && $u = mysqli_fetch_assoc($uq)) {
        $email_safe = mysqli_real_escape_string($conn, $u['email']);
        $aq = mysqli_query($conn, "SELECT * FROM `admins` WHERE email = '$email_safe' LIMIT 1");
        if ($aq && mysqli_num_rows($q) > 0) {
            return mysqli_fetch_assoc($aq);
        }
    }
    // Fallback: get the currently Active Super Admin
    $fq = mysqli_query($conn, "SELECT * FROM `admins` WHERE status = 'Active' LIMIT 1");
    if ($fq && mysqli_num_rows($fq) > 0) {
        return mysqli_fetch_assoc($fq);
    }
    return null;
}

// -----------------------------------------------------------------------------
// ACTION: LIST SUPERADMINS & REQUEST HISTORY
// -----------------------------------------------------------------------------
if ($action === 'list') {
    // 1. Remove any admins from `admins` table that do not exist in `user` table with role = 'superadmin'
    @mysqli_query($conn, "DELETE FROM `admins` WHERE user_idno NOT IN (SELECT idno FROM `user` WHERE role = 'superadmin')");

    // 2. Sync all valid superadmins from `user` table into `admins` table
    $sync_users = mysqli_query($conn, "SELECT idno, username, email, firstname, lastname, status FROM `user` WHERE role = 'superadmin'");
    if ($sync_users) {
        while ($su = mysqli_fetch_assoc($sync_users)) {
            $e_idno = mysqli_real_escape_string($conn, $su['idno']);
            $e_email = !empty($su['email']) ? "'" . mysqli_real_escape_string($conn, $su['email']) . "'" : "NULL";
            $full_name = trim(($su['firstname'] ?? '') . ' ' . ($su['lastname'] ?? ''));
            if (empty($full_name) || strtolower($full_name) === 'super admin') {
                $full_name = $su['username'];
            }
            $e_name = mysqli_real_escape_string($conn, $full_name);
            $def_st = (strtolower($su['status']) === 'active') ? 'Active' : 'Inactive';

            $check_admin = mysqli_query($conn, "SELECT id FROM `admins` WHERE user_idno = '$e_idno' LIMIT 1");
            if ($check_admin && ($ar = mysqli_fetch_assoc($check_admin))) {
                @mysqli_query($conn, "UPDATE `admins` SET name = '$e_name', email = $e_email, status = '$def_st' WHERE id = " . intval($ar['id']));
            } else {
                @mysqli_query($conn, "INSERT INTO `admins` (user_idno, name, email, role, status) VALUES ('$e_idno', '$e_name', $e_email, 'Super Admin', '$def_st')");
            }
        }
    }

    // Auto-expire requests older than 24 hours
    @mysqli_query($conn, "UPDATE `role_switch_requests` SET status = 'Expired' WHERE status = 'Pending' AND expires_at < NOW()");

    // Fetch all admins
    $admins = [];
    $admins_res = mysqli_query($conn, "SELECT id, user_idno, name, email, role, status, updated_at FROM `admins` ORDER BY FIELD(status, 'Active', 'Inactive'), id ASC");
    if ($admins_res) {
        while ($row = mysqli_fetch_assoc($admins_res)) {
            $admins[] = $row;
        }
    }

    // Fetch latest role switch requests
    $requests = [];
    $req_sql = "SELECT r.*, 
                       fa.name AS from_name, fa.email AS from_email,
                       ta.name AS to_name, ta.email AS to_email
                FROM `role_switch_requests` r
                LEFT JOIN `admins` fa ON r.from_admin_id = fa.id
                LEFT JOIN `admins` ta ON r.to_admin_id = ta.id
                ORDER BY r.id DESC LIMIT 10";
    $req_res = mysqli_query($conn, $req_sql);
    if ($req_res) {
        while ($r = mysqli_fetch_assoc($req_res)) {
            $requests[] = $r;
        }
    }

    // Identify current active superadmin
    $current_admin = get_current_admin_record($conn, $current_user_id);

    echo json_encode([
        'success' => true,
        'admins' => $admins,
        'current_admin' => $current_admin,
        'requests' => $requests
    ]);
    exit();
}

// -----------------------------------------------------------------------------
// ACTION: INITIATE SWITCH (GENERATE TOKEN & SEND GMAIL)
// -----------------------------------------------------------------------------
if ($action === 'initiate') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
        exit();
    }

    $target_admin_id = intval($_POST['target_admin_id'] ?? 0);
    if ($target_admin_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid Inactive Super Admin to switch with.']);
        exit();
    }

    // Find the currently active Super Admin
    $active_admin = null;
    $curr_q = mysqli_query($conn, "SELECT * FROM `admins` WHERE status = 'Active' LIMIT 1");
    if ($curr_q && mysqli_num_rows($curr_q) > 0) {
        $active_admin = mysqli_fetch_assoc($curr_q);
    }

    if (!$active_admin) {
        echo json_encode(['success' => false, 'message' => 'Error: No active Super Admin found in the system.']);
        exit();
    }

    // Ensure target admin exists and is Inactive
    $stmt_target = $conn->prepare("SELECT * FROM `admins` WHERE id = ? LIMIT 1");
    $stmt_target->bind_param("i", $target_admin_id);
    $stmt_target->execute();
    $target_res = $stmt_target->get_result();
    $target_admin = $target_res ? $target_res->fetch_assoc() : null;
    $stmt_target->close();

    if (!$target_admin) {
        echo json_encode(['success' => false, 'message' => 'The selected Super Admin does not exist.']);
        exit();
    }

    if ($target_admin['id'] == $active_admin['id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot switch role with yourself. Choose an Inactive Super Admin.']);
        exit();
    }

    if ($target_admin['status'] === 'Active') {
        echo json_encode(['success' => false, 'message' => 'The selected account is already Active.']);
        exit();
    }

    if (empty($target_admin['email']) || !filter_var($target_admin['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'The selected Super Admin does not have a valid Gmail/email address.']);
        exit();
    }

    // Auto-expire any previous Pending requests from this active admin to avoid duplicate confusion
    $from_id = $active_admin['id'];
    $stmt_exp = $conn->prepare("UPDATE `role_switch_requests` SET status = 'Expired' WHERE from_admin_id = ? AND status = 'Pending'");
    if ($stmt_exp) {
        $stmt_exp->bind_param("i", $from_id);
        $stmt_exp->execute();
        $stmt_exp->close();
    }

    // Generate secure 64-char token (valid for 24 hours)
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Insert request record
    $stmt_ins = $conn->prepare("INSERT INTO `role_switch_requests` (from_admin_id, to_admin_id, token, expires_at, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt_ins->bind_param("iiss", $from_id, $target_admin_id, $token, $expires_at);
    $saved = $stmt_ins->execute();
    $req_id = $conn->insert_id;
    $stmt_ins->close();

    if (!$saved) {
        echo json_encode(['success' => false, 'message' => 'Database error creating handover request: ' . $conn->error]);
        exit();
    }

    // Build the confirmation link
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Resolve project base URL dynamically
    $script_dir = dirname($_SERVER['SCRIPT_NAME']); // e.g. /Final_Form/PHP/api
    $parent_dir = dirname($script_dir);             // e.g. /Final_Form/PHP
    $base_url = rtrim($protocol . $host . $parent_dir, '/\\');
    $confirmation_url = $base_url . "/confirm_role_switch.php?token=" . urlencode($token);

    // Send confirmation email via Gmail SMTP (PHPMailer)
    $mail_result = send_role_switch_confirmation_email(
        $target_admin['email'],
        $target_admin['name'],
        $active_admin['name'],
        $active_admin['email'],
        $confirmation_url,
        '24 hours (' . date('M j, Y g:i A', strtotime($expires_at)) . ')'
    );

    // Audit Log
    $log_action = "Initiate Role Switch";
    $log_details = "Super Admin '{$active_admin['name']}' requested role handover to '{$target_admin['name']}' ({$target_admin['email']}). Token generated, expires in 24h.";
    $stmt_log = $conn->prepare("INSERT INTO `audit_logs` (user_id, username, role, action, details) VALUES (?, ?, 'superadmin', ?, ?)");
    if ($stmt_log) {
        $stmt_log->bind_param("ssss", $current_user_id, $_SESSION['username'], $log_action, $log_details);
        $stmt_log->execute();
        $stmt_log->close();
    }

    if (!$mail_result['success']) {
        echo json_encode([
            'success' => true,
            'warning' => true,
            'request_id' => $req_id,
            'message' => 'Request created, but email could not be sent via Gmail SMTP: ' . $mail_result['message'] . '. You can manually share the confirmation URL: ' . $confirmation_url,
            'confirmation_url' => $confirmation_url
        ]);
        exit();
    }

    echo json_encode([
        'success' => true,
        'request_id' => $req_id,
        'recipient' => $target_admin['name'] . ' (' . $target_admin['email'] . ')',
        'message' => 'Confirmation email successfully sent via Gmail SMTP to ' . $target_admin['email'] . '. Role switch will be completed once they accept.'
    ]);
    exit();
}

// -----------------------------------------------------------------------------
// ACTION: CANCEL PENDING REQUEST
// -----------------------------------------------------------------------------
if ($action === 'cancel') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit();
    }

    $request_id = intval($_POST['request_id'] ?? 0);
    $stmt_c = $conn->prepare("UPDATE `role_switch_requests` SET status = 'Expired' WHERE id = ? AND status = 'Pending'");
    $stmt_c->bind_param("i", $request_id);
    $stmt_c->execute();
    $affected = $stmt_c->affected_rows;
    $stmt_c->close();

    if ($affected > 0) {
        echo json_encode(['success' => true, 'message' => 'Pending request has been cancelled.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found or not currently pending.']);
    }
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit();
