<?php
// Run ONCE via browser: http://localhost/Final_Form/PHP/superadmin/fix_pending.php
// Then DELETE this file for security.
require_once __DIR__ . '/../db.php';
header('Content-Type: text/plain');

// Step 1: Ensure status column exists with correct ENUM
$r = mysqli_query($conn, "SHOW COLUMNS FROM user LIKE 'status'");
if (mysqli_num_rows($r) === 0) {
    mysqli_query($conn, "ALTER TABLE `user` ADD `status` ENUM('active','blocked','pending') NOT NULL DEFAULT 'active'");
    echo "✔ Status column ADDED.\n";
} else {
    mysqli_query($conn, "ALTER TABLE `user` MODIFY `status` ENUM('active','blocked','pending') NOT NULL DEFAULT 'active'");
    echo "✔ Status column verified/updated.\n";
}

// Step 2: Show all users
echo "\nCurrent users:\n";
$res = mysqli_query($conn, "SELECT idno, username, role, status FROM user WHERE role='user' ORDER BY idno DESC");
while ($u = mysqli_fetch_assoc($res)) {
    echo "  IDNO:{$u['idno']} | {$u['username']} | status:" . ($u['status'] ?? 'NULL') . "\n";
}

// Step 3: If ?set_pending=IDNO is passed, update that user
$param = $_GET['set_pending'] ?? ($argv[1] ?? '');
if (!empty($param)) {
    $uid = mysqli_real_escape_string($conn, $param);
    $ok = mysqli_query($conn, "UPDATE user SET status='pending' WHERE idno='$uid' AND role='user'");
    echo "\n" . ($ok && mysqli_affected_rows($conn) > 0
        ? "✔ User IDNO $uid is now PENDING. Go check Manage Users."
        : "✘ Could not update user IDNO $uid.") . "\n";
}

echo "\nTo set a user to pending, append: ?set_pending=IDNO\n";
echo "e.g. http://localhost/Final_Form/PHP/superadmin/fix_pending.php?set_pending=2026-0001\n";
?>
