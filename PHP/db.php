<?php
$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "finaldb"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn && !$conn->connect_error) {
    // Auto-migrate essential columns and tables if missing
    $user_cols_res = @$conn->query("SHOW COLUMNS FROM `user`");
    if ($user_cols_res) {
        $existing_cols = [];
        while ($c = $user_cols_res->fetch_assoc()) {
            $existing_cols[] = $c['Field'];
        }
        
        if (!in_array('role', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `role` ENUM('user', 'admin', 'superadmin') NOT NULL DEFAULT 'user'");
        }
        if (!in_array('status', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `status` ENUM('active', 'approved', 'blocked', 'pending', 'inactive', 'pending_deletion') NOT NULL DEFAULT 'pending'");
        } else {
            @$conn->query("ALTER TABLE `user` MODIFY `status` ENUM('active', 'approved', 'blocked', 'pending', 'inactive', 'pending_deletion') NOT NULL DEFAULT 'pending'");
        }
        if (!in_array('must_change_password', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `must_change_password` TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!in_array('privileges', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `privileges` TEXT DEFAULT NULL");
        }
        if (!in_array('block_reason', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `block_reason` VARCHAR(255) DEFAULT NULL");
        }
        if (!in_array('delete_reason', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `delete_reason` VARCHAR(255) DEFAULT NULL");
        }
        if (!in_array('block_file', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `block_file` VARCHAR(255) DEFAULT NULL");
        }
        if (!in_array('delete_file', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `delete_file` VARCHAR(255) DEFAULT NULL");
        }
        if (!in_array('delete_requested_by', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `delete_requested_by` VARCHAR(100) DEFAULT NULL");
        }
        if (!in_array('delete_requested_at', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `delete_requested_at` DATETIME DEFAULT NULL");
        }
        if (!in_array('is_online', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `is_online` TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!in_array('last_activity', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` ADD `last_activity` DATETIME DEFAULT NULL");
        }
        if (in_array('id', $existing_cols)) {
            @$conn->query("ALTER TABLE `user` MODIFY `id` INT(11) NOT NULL");
            @$conn->query("ALTER TABLE `user` DROP PRIMARY KEY");
            @$conn->query("ALTER TABLE `user` ADD PRIMARY KEY (`idno`)");
            @$conn->query("ALTER TABLE `user` DROP COLUMN `id`");
        }

        // Ensure all admins have the 3 mandatory default privileges
        $adm_res = @$conn->query("SELECT idno, privileges FROM `user` WHERE `role` = 'admin'");
        if ($adm_res) {
            $def_p = ['view_users', 'create_users', 'approve_users'];
            while ($a_row = $adm_res->fetch_assoc()) {
                $cur = json_decode($a_row['privileges'] ?? '[]', true);
                if (!is_array($cur)) $cur = [];
                $merged = array_values(array_unique(array_merge($def_p, $cur)));
                if (count($merged) !== count($cur)) {
                    $new_json = $conn->real_escape_string(json_encode($merged));
                    $a_idno = $conn->real_escape_string($a_row['idno']);
                    @$conn->query("UPDATE `user` SET `privileges` = '$new_json' WHERE `idno` = '$a_idno'");
                }
            }
        }
    }

    // Ensure audit_logs table exists
    @$conn->query("CREATE TABLE IF NOT EXISTS `audit_logs` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` VARCHAR(50) NOT NULL,
      `username` VARCHAR(255) NOT NULL,
      `role` VARCHAR(50) NOT NULL,
      `action` VARCHAR(50) NOT NULL,
      `details` TEXT DEFAULT NULL,
      `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure otp_verifications table exists
    @$conn->query("CREATE TABLE IF NOT EXISTS `otp_verifications` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` VARCHAR(50) NOT NULL,
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure admins table exists
    @$conn->query("CREATE TABLE IF NOT EXISTS `admins` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_idno` VARCHAR(50) DEFAULT NULL,
      `name` VARCHAR(150) NOT NULL,
      `email` VARCHAR(191) NOT NULL UNIQUE,
      `role` VARCHAR(50) NOT NULL DEFAULT 'Super Admin',
      `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Inactive',
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      INDEX `idx_email` (`email`),
      INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure role_switch_requests table exists
    @$conn->query("CREATE TABLE IF NOT EXISTS `role_switch_requests` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `from_admin_id` INT(11) NOT NULL,
      `to_admin_id` INT(11) NOT NULL,
      `token` VARCHAR(64) NOT NULL UNIQUE,
      `expires_at` DATETIME NOT NULL,
      `status` ENUM('Pending', 'Accepted', 'Expired') NOT NULL DEFAULT 'Pending',
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `accepted_at` DATETIME NULL,
      PRIMARY KEY (`id`),
      INDEX `idx_token` (`token`),
      INDEX `idx_status` (`status`),
      INDEX `idx_from_to` (`from_admin_id`, `to_admin_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
?>