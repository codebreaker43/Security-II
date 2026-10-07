<?php 
session_start();
include("db.php");

// Audit Log & Superadmin Rotation
if (isset($_SESSION['user_id'])) {
    $log_user_id = $_SESSION['user_id'];
    $log_username = $_SESSION['username'];
    $log_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
    $log_action = "Logout";
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
    $ip_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    $hostname = isset($_SERVER['REMOTE_ADDR']) ? gethostbyaddr($_SERVER['REMOTE_ADDR']) : 'Unknown';
    $log_details = "IP: " . $ip_addr . " | Host: " . $hostname . " | Device: " . $user_agent;
    
    $log_sql = "INSERT INTO audit_logs (user_id, username, role, action, details) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $log_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $log_user_id, $log_username, $log_role, $log_action, $log_details);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Reset is_online status and last_activity upon logout
    $offline_stmt = mysqli_prepare($conn, "UPDATE user SET is_online = 0, last_activity = NULL WHERE idno = ?");
    if ($offline_stmt) {
        mysqli_stmt_bind_param($offline_stmt, "s", $log_user_id);
        mysqli_stmt_execute($offline_stmt);
        mysqli_stmt_close($offline_stmt);
    }
}

session_unset();
session_destroy();

header("Location: homepage.php");
exit;
?>