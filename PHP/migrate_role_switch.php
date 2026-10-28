<?php
require_once 'c:/xampp/htdocs/Final_Form/PHP/db.php';

echo "Running Migration...\n";

// 1. Create admins table
$create_admins_sql = "CREATE TABLE IF NOT EXISTS `admins` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_admins_sql)) {
    echo "✓ `admins` table checked/created successfully.\n";
} else {
    echo "✗ Failed to create `admins` table: " . $conn->error . "\n";
}

// 2. Create role_switch_requests table
$create_requests_sql = "CREATE TABLE IF NOT EXISTS `role_switch_requests` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_requests_sql)) {
    echo "✓ `role_switch_requests` table checked/created successfully.\n";
} else {
    echo "✗ Failed to create `role_switch_requests` table: " . $conn->error . "\n";
}

// 3. Ensure 3 Super Admin accounts exist according to business rule (1 Active, 2 Inactive)
// Check existing admins
$chk = $conn->query("SELECT COUNT(*) as cnt FROM `admins`");
$row = $chk ? $chk->fetch_assoc() : ['cnt' => 0];

if ($row['cnt'] == 0) {
    echo "Seeding 3 Super Admin accounts into `admins` table...\n";
    // Define 3 super admin accounts
    $superadmins = [
        [
            'user_idno' => '2026-4103',
            'name' => 'Jamesrel Galagate',
            'email' => 'jamesrel.galagate@gmail.com',
            'role' => 'Super Admin',
            'status' => 'Active'
        ],
        [
            'user_idno' => '2026-0001',
            'name' => 'Jessrel James Galagate',
            'email' => 'jessreljames.galagate@csucc.edu.ph',
            'role' => 'Super Admin',
            'status' => 'Inactive'
        ]
    ];

    $stmt = $conn->prepare("INSERT INTO `admins` (`user_idno`, `name`, `email`, `role`, `status`) VALUES (?, ?, ?, ?, ?)");
    foreach ($superadmins as $sa) {
        $stmt->bind_param("sssss", $sa['user_idno'], $sa['name'], $sa['email'], $sa['role'], $sa['status']);
        $stmt->execute();
    }
    $stmt->close();
    echo "✓ Seeded 3 Super Admins into `admins` table.\n";
} else {
    echo "Admins table already contains {$row['cnt']} records.\n";
}

// Check current status in `admins` table
$q = $conn->query("SELECT id, user_idno, name, email, role, status FROM `admins`");
while ($a = $q->fetch_assoc()) {
    echo "Admin #{$a['id']}: {$a['name']} ({$a['email']}) - Role: {$a['role']} - Status: {$a['status']}\n";
}

echo "Migration finished.\n";
