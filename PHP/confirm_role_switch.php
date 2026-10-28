<?php
/**
 * Super Admin Role Switch Confirmation Handler
 * Verifies 24-hr token, performs atomic database transaction,
 * invalidates previous Super Admin session, promotes new Super Admin,
 * and redirects to Super Admin Dashboard.
 */

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/mail_config.php';

$token = trim($_GET['token'] ?? '');
$error_title = '';
$error_message = '';
$is_success = false;
$new_admin_data = null;
$old_admin_data = null;

if (empty($token) || strlen($token) !== 64 || !ctype_xdigit($token)) {
    $error_title = "Invalid Confirmation Token";
    $error_message = "The confirmation link provided is malformed or invalid. Please request a new role handover from the active Super Admin.";
} else {
    // 1. Fetch request details
    $stmt = $conn->prepare("
        SELECT r.*, 
               fa.id AS from_id, fa.user_idno AS from_idno, fa.name AS from_name, fa.email AS from_email, fa.status AS from_status,
               ta.id AS to_id, ta.user_idno AS to_idno, ta.name AS to_name, ta.email AS to_email, ta.status AS to_status
        FROM `role_switch_requests` r
        JOIN `admins` fa ON r.from_admin_id = fa.id
        JOIN `admins` ta ON r.to_admin_id = ta.id
        WHERE r.token = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    $request = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$request) {
        $error_title = "Handover Request Not Found";
        $error_message = "No matching role handover request was found for this token. It may have been removed or regenerated.";
    } elseif ($request['status'] === 'Accepted') {
        $error_title = "Request Already Accepted";
        $error_message = "This role handover has already been confirmed and completed. <strong>" . htmlspecialchars($request['to_name']) . "</strong> is currently the Active Super Admin.";
        $is_already_accepted = true;
    } elseif ($request['status'] === 'Expired' || strtotime($request['expires_at']) < time()) {
        // Mark as expired if not already marked
        @$conn->query("UPDATE `role_switch_requests` SET status = 'Expired' WHERE id = " . intval($request['id']));
        $error_title = "Confirmation Token Expired";
        $error_message = "This handover request was generated on " . date('M j, Y g:i A', strtotime($request['created_at'])) . " and has exceeded its 24-hour validity period. Please have the current Super Admin send a new request.";
    } else {
        // ---------------------------------------------------------------------
        // 2. ATOMIC DATABASE TRANSACTION (Atomic Switch)
        // ---------------------------------------------------------------------
        $conn->begin_transaction();

        try {
            $req_id = intval($request['id']);
            $from_id = intval($request['from_id']);
            $to_id = intval($request['to_id']);

            // a) Set previous Super Admin's status to 'Inactive'
            $stmt_from = $conn->prepare("UPDATE `admins` SET status = 'Inactive', updated_at = NOW() WHERE id = ?");
            $stmt_from->bind_param("i", $from_id);
            $stmt_from->execute();
            $stmt_from->close();

            // b) Set new Super Admin's status to 'Active'
            $stmt_to = $conn->prepare("UPDATE `admins` SET status = 'Active', updated_at = NOW() WHERE id = ?");
            $stmt_to->bind_param("i", $to_id);
            $stmt_to->execute();
            $stmt_to->close();

            // c) Mark token as 'Accepted'
            $stmt_acc = $conn->prepare("UPDATE `role_switch_requests` SET status = 'Accepted', accepted_at = NOW() WHERE id = ?");
            $stmt_acc->bind_param("i", $req_id);
            $stmt_acc->execute();
            $stmt_acc->close();

            // Mark any other pending requests as Expired to preserve single-active integrity
            $conn->query("UPDATE `role_switch_requests` SET status = 'Expired' WHERE status = 'Pending'");

            // Synchronize with `user` table if linked
            // Invalidate previous superadmin in `user` table:
            $from_idno_safe = mysqli_real_escape_string($conn, $request['from_idno'] ?? '');
            $from_email_safe = mysqli_real_escape_string($conn, $request['from_email']);
            if (!empty($from_idno_safe)) {
                $conn->query("UPDATE `user` SET status = 'inactive', is_online = 0 WHERE idno = '$from_idno_safe' OR email = '$from_email_safe'");
            } else {
                $conn->query("UPDATE `user` SET status = 'inactive', is_online = 0 WHERE email = '$from_email_safe'");
            }

            // Activate new superadmin in `user` table:
            $to_idno_safe = mysqli_real_escape_string($conn, $request['to_idno'] ?? '');
            $to_email_safe = mysqli_real_escape_string($conn, $request['to_email']);
            if (!empty($to_idno_safe)) {
                $conn->query("UPDATE `user` SET status = 'active', is_online = 1, last_activity = NOW() WHERE idno = '$to_idno_safe' OR email = '$to_email_safe'");
            } else {
                $conn->query("UPDATE `user` SET status = 'active', is_online = 1, last_activity = NOW() WHERE email = '$to_email_safe'");
            }

            // Audit log the successful atomic switch
            $log_action = "Super Admin Role Switch Confirmed";
            $log_details = "Atomic Role Switch executed via Gmail Token. Previous Super Admin '{$request['from_name']}' is now Inactive. New Super Admin '{$request['to_name']}' is now Active.";
            $stmt_log = $conn->prepare("INSERT INTO `audit_logs` (user_id, username, role, action, details) VALUES (?, ?, 'superadmin', ?, ?)");
            $u_idno = $request['to_idno'] ?: 'SA-' . $to_id;
            $stmt_log->bind_param("ssss", $u_idno, $request['to_name'], $log_action, $log_details);
            $stmt_log->execute();
            $stmt_log->close();

            // Commit the entire transaction atomically
            $conn->commit();
            $is_success = true;
            $new_admin_data = $request;

            // Send confirmation Gmail to new Active Super Admin
            if (!empty($request['to_email'])) {
                send_superadmin_activated_notification($request['to_email'], $request['to_name']);
            }

            // -----------------------------------------------------------------
            // d) Force-logout/invalidate session of previous Super Admin
            // and authenticate the new Super Admin into their session
            // -----------------------------------------------------------------
            // Reset existing session in this browser
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();

            // Start fresh session for new Super Admin
            session_start();
            session_regenerate_id(true);

            // Fetch latest user details from `user` table if available
            $target_username = $request['to_name'];
            $target_idno = $request['to_idno'] ?: 'SA-' . $to_id;
            $q_user = $conn->query("SELECT idno, username FROM `user` WHERE email = '$to_email_safe' OR idno = '$to_idno_safe' LIMIT 1");
            if ($q_user && $u_row = $q_user->fetch_assoc()) {
                $target_username = $u_row['username'];
                $target_idno = $u_row['idno'];
            }

            $_SESSION['user_id'] = $target_idno;
            $_SESSION['username'] = $target_username;
            $_SESSION['role'] = 'superadmin';
            $_SESSION['status'] = 'active';
            $_SESSION['profile_completed'] = 1;
            $_SESSION['login_time'] = time();

        } catch (Exception $e) {
            $conn->rollback();
            $error_title = "Atomic Switch Failed";
            $error_message = "An unexpected error occurred during database transaction: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Role Handover Confirmation - ONE PIECE</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 20%, #1e293b 0%, #0f172a 60%, #020617 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f8fafc;
        }

        .confirm-card {
            background: rgba(18, 22, 31, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(245, 158, 11, 0.1);
            max-width: 540px;
            width: 90%;
            padding: 2.8rem 2.2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(12px);
            animation: cardAppear 0.5s ease-out;
        }

        .confirm-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: <?php echo $is_success ? 'linear-gradient(90deg, #10b981, #059669)' : 'linear-gradient(90deg, #ef4444, #dc2626)'; ?>;
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            background: <?php echo $is_success ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)'; ?>;
            border: 2px solid <?php echo $is_success ? 'rgba(16, 185, 129, 0.4)' : 'rgba(239, 68, 68, 0.4)'; ?>;
            box-shadow: 0 0 25px <?php echo $is_success ? 'rgba(16, 185, 129, 0.25)' : 'rgba(239, 68, 68, 0.25)'; ?>;
        }

        .title {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0.8rem;
            color: #ffffff;
        }

        .subtitle {
            font-size: 0.98rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .role-switch-badge-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin: 1.5rem 0 2rem;
            padding: 1.2rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
        }

        .admin-pill {
            text-align: center;
            flex: 1;
        }

        .admin-pill-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .admin-pill-status {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 6px;
        }

        .status-inactive {
            background: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.4);
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.2);
        }

        .arrow-icon {
            font-size: 1.5rem;
            color: #f59e0b;
        }

        .btn-action {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #ffffff !important;
            font-size: 1rem;
            font-weight: 700;
            padding: 0.9rem 2.2rem;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.35);
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.5);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.35);
        }

        .countdown-text {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 1.2rem;
        }

        .progress-bar {
            height: 4px;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            margin-top: 1.5rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #10b981;
            width: 100%;
            animation: drain 4s linear forwards;
        }

        @keyframes drain {
            from { width: 100%; }
            to { width: 0%; }
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="confirm-card">
        <?php if ($is_success): ?>
            <div class="icon-circle">👑</div>
            <h1 class="title">Handover Confirmed!</h1>
            <p class="subtitle">
                Super Admin role switch executed successfully via database transaction.<br>
                Welcome, <strong><?php echo htmlspecialchars($new_admin_data['to_name']); ?></strong>! You are now the <strong>Active Super Admin</strong>.
            </p>

            <div class="role-switch-badge-container">
                <div class="admin-pill">
                    <div class="admin-pill-name"><?php echo htmlspecialchars($new_admin_data['from_name']); ?></div>
                    <span class="admin-pill-status status-inactive">Previous &bull; Inactive</span>
                </div>
                <div class="arrow-icon">➔</div>
                <div class="admin-pill">
                    <div class="admin-pill-name"><?php echo htmlspecialchars($new_admin_data['to_name']); ?></div>
                    <span class="admin-pill-status status-active">Active Super Admin</span>
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <a href="superadmin/dashboard.php?activated=1" class="btn-action">Enter Super Admin Dashboard</a>
            </div>

            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="countdown-text" id="countdownMsg">
                Redirecting to dashboard automatically in 4 seconds...
            </div>

            <script>
                let secondsLeft = 4;
                const timer = setInterval(function() {
                    secondsLeft--;
                    const msg = document.getElementById('countdownMsg');
                    if (msg) {
                        msg.innerText = 'Redirecting to dashboard automatically in ' + secondsLeft + ' seconds...';
                    }
                    if (secondsLeft <= 0) {
                        clearInterval(timer);
                        window.location.href = 'superadmin/dashboard.php?activated=1';
                    }
                }, 1000);
            </script>

        <?php else: ?>
            <div class="icon-circle">⚠️</div>
            <h1 class="title"><?php echo htmlspecialchars($error_title); ?></h1>
            <div class="subtitle">
                <?php echo $error_message; ?>
            </div>

            <div style="margin-top: 2rem;">
                <a href="login.php" class="btn-action btn-danger">Return to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
