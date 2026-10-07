<?php
require_once __DIR__ . '/db.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn ? $conn->connect_error : "No connection"));
}

echo "=== Hashing Existing Security Question Answers in Database ===" . PHP_EOL;

$res = mysqli_query($conn, "SELECT id, username, security_answer1, security_answer2, security_answer3 FROM user");

$updated_count = 0;

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $id = $row['id'];
        $username = $row['username'];
        $needs_update = false;
        
        $a1 = $row['security_answer1'];
        $a2 = $row['security_answer2'];
        $a3 = $row['security_answer3'];

        // Helper to check if already hashed
        $is_hashed = function($str) {
            if (empty($str)) return true;
            return (str_starts_with($str, '$2y$') || str_starts_with($str, '$2a$') || str_starts_with($str, '$2b$')) && strlen($str) >= 50;
        };

        if (!empty($a1) && !$is_hashed($a1)) {
            $a1 = password_hash(strtolower(trim($a1)), PASSWORD_DEFAULT);
            $needs_update = true;
        }

        if (!empty($a2) && !$is_hashed($a2)) {
            $a2 = password_hash(strtolower(trim($a2)), PASSWORD_DEFAULT);
            $needs_update = true;
        }

        if (!empty($a3) && !$is_hashed($a3)) {
            $a3 = password_hash(strtolower(trim($a3)), PASSWORD_DEFAULT);
            $needs_update = true;
        }

        if ($needs_update) {
            $stmt = mysqli_prepare($conn, "UPDATE user SET security_answer1 = ?, security_answer2 = ?, security_answer3 = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sssi", $a1, $a2, $a3, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                echo "[UPDATED] Hashed security answers for user: {$username} (ID: {$id})" . PHP_EOL;
                $updated_count++;
            }
        } else {
            echo "[SKIPPED/ALREADY HASHED] User: {$username} (ID: {$id})" . PHP_EOL;
        }
    }
}

echo "=== Complete! {$updated_count} user accounts updated with hashed security answers ===" . PHP_EOL;
?>
