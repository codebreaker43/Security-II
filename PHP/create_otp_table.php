<?php
require_once __DIR__ . '/db.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn ? $conn->connect_error : "No connection"));
}

$sql = "CREATE TABLE IF NOT EXISTS `otp_verifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `otp_hash` VARCHAR(255) NOT NULL,
  `purpose` VARCHAR(50) NOT NULL DEFAULT 'forgot_password',
  `expires_at` DATETIME NOT NULL,
  `attempts` INT(11) NOT NULL DEFAULT 0,
  `is_used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_purpose` (`user_id`, `purpose`),
  INDEX `idx_email_purpose` (`email`, `purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "SUCCESS: otp_verifications table is ready." . PHP_EOL;
} else {
    echo "ERROR: " . $conn->error . PHP_EOL;
}
?>
