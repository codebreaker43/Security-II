<?php
include 'PHP/db.php';

// Check if status column exists
$r = mysqli_query($conn, "SHOW COLUMNS FROM user LIKE 'status'");
if (mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    echo "STATUS COLUMN EXISTS: " . json_encode($row) . "\n";
} else {
    echo "NO STATUS COLUMN - ADDING IT NOW...\n";
    $alter = mysqli_query($conn, "ALTER TABLE `user` ADD `status` ENUM('active','blocked','pending') NOT NULL DEFAULT 'active'");
    if ($alter) {
        echo "Status column added successfully.\n";
    } else {
        echo "Failed to add: " . mysqli_error($conn) . "\n";
    }
}

// Show all users and their statuses
$res = mysqli_query($conn, "SELECT idno, username, status FROM user ORDER BY idno DESC LIMIT 10");
echo "\nRecent users:\n";
while ($u = mysqli_fetch_assoc($res)) {
    echo "IDNO: {$u['idno']} | Username: {$u['username']} | Status: " . ($u['status'] ?? 'NULL') . "\n";
}
?>
