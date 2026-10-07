<?php
// Run this via browser: http://localhost/Final_Form/PHP/superadmin/dbcheck.php
session_start();
include(__DIR__ . "/../db.php");

header('Content-Type: text/plain');

// 1. Check status column
$r = mysqli_query($conn, "SHOW COLUMNS FROM user LIKE 'status'");
if (mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    echo "STATUS COLUMN EXISTS:\n";
    echo "  Type: " . $row['Type'] . "\n";
    echo "  Default: " . $row['Default'] . "\n\n";
} else {
    echo "NO STATUS COLUMN! Adding it now...\n";
    $ok = mysqli_query($conn, "ALTER TABLE `user` ADD `status` ENUM('active','blocked','pending') NOT NULL DEFAULT 'active'");
    echo $ok ? "Added successfully.\n\n" : "FAILED: " . mysqli_error($conn) . "\n\n";
}

// 2. Show all users with status
$res = mysqli_query($conn, "SELECT idno, username, role, status FROM user ORDER BY idno DESC LIMIT 15");
echo "RECENT USERS:\n";
echo str_pad("ID No",12) . str_pad("Username",20) . str_pad("Role",15) . "Status\n";
echo str_repeat("-", 60) . "\n";
while ($u = mysqli_fetch_assoc($res)) {
    echo str_pad($u['idno'],12) . str_pad($u['username'],20) . str_pad($u['role'],15) . ($u['status'] ?? 'NULL') . "\n";
}

// 3. Count pending
$pq = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM user WHERE status='pending'");
$pr = mysqli_fetch_assoc($pq);
echo "\nPENDING COUNT: " . $pr['cnt'] . "\n";
?>
