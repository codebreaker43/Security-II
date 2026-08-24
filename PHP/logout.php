<?php 
session_start();


include("db.php");

// Audit Log
if (isset($_SESSION['user_id'])) {
    $log_user_id = $_SESSION['user_id'];
    $log_username = $_SESSION['username'];
    $log_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
    $log_action = "Logout";
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
}

session_unset();
session_destroy();

header("Location: homepage.php");
?>