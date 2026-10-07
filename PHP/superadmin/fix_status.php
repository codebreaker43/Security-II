<?php
// ACCESS VIA BROWSER: http://localhost/Final_Form/PHP/superadmin/fix_status.php
// This script fixes/ensures the status column is correct and shows what's in the DB.
require_once __DIR__ . '/../db.php';
header('Content-Type: text/plain');

echo "=== STATUS COLUMN CHECK ===\n";
$r = mysqli_query($conn, "SHOW COLUMNS FROM user LIKE 'status'");
if (mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    echo "EXISTS - Type: {$row['Type']} | Default: {$row['Default']} | Null: {$row['Null']}\n";
    if (strpos($row['Type'], 'approved') === false || strpos($row['Type'], 'inactive') === false) {
        echo "ENUM missing approved/inactive - fixing...\n";
        $ok = mysqli_query($conn, "ALTER TABLE `user` MODIFY `status` ENUM('active','approved','blocked','pending','inactive') NOT NULL DEFAULT 'pending'");
        echo $ok ? "Fixed!\n" : "Error: ".mysqli_error($conn)."\n";
    } else {
        echo "ENUM looks correct.\n";
    }
} else {
    echo "MISSING - Adding now...\n";
    $ok = mysqli_query($conn, "ALTER TABLE `user` ADD `status` ENUM('active','approved','blocked','pending','inactive') NOT NULL DEFAULT 'pending'");
    echo $ok ? "Added!\n" : "Error: ".mysqli_error($conn)."\n";
}

mysqli_query($conn, "UPDATE user SET status = 'inactive' WHERE idno = '2026-0009' AND (status = '' OR status IS NULL)");

echo "\n=== ALL USERS ===\n";
$res = mysqli_query($conn, "SELECT idno, username, role, status FROM user ORDER BY idno DESC");
while ($u = mysqli_fetch_assoc($res)) {
    echo "ID:{$u['idno']} | {$u['username']} | role:{$u['role']} | status:".($u['status']??'NULL')."\n";
}

echo "\n=== PENDING COUNT ===\n";
$pq = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM user WHERE status='pending'");
$pr = mysqli_fetch_assoc($pq);
echo "Pending users: {$pr['cnt']}\n";

echo "\n=== To TEST the approval flow ===\n";
echo "To manually set a user as pending, go to:\n";
echo "http://localhost/Final_Form/PHP/superadmin/fix_status.php?set_pending=IDNO\n\n";

if (isset($_GET['set_pending']) && !empty($_GET['set_pending'])) {
    $uid = mysqli_real_escape_string($conn, trim($_GET['set_pending']));
    $ok = mysqli_query($conn, "UPDATE user SET status='pending' WHERE idno='$uid' AND role='user'");
    if ($ok && mysqli_affected_rows($conn) > 0) {
        echo "SUCCESS: User ID $uid set to PENDING.\n";
    } else {
        echo "No change - user not found or not a 'user' role.\n";
    }
}
?>
