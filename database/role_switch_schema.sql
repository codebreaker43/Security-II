-- ============================================================================
-- SUPER ADMIN ROLE SWITCH VIA GMAIL CONFIRMATION SCHEMA
-- ============================================================================

-- 1. Create or update the `admins` table
CREATE TABLE IF NOT EXISTS `admins` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create the `role_switch_requests` table
CREATE TABLE IF NOT EXISTS `role_switch_requests` (
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
  INDEX `idx_from_to` (`from_admin_id`, `to_admin_id`),
  CONSTRAINT `fk_switch_from_admin` FOREIGN KEY (`from_admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_switch_to_admin` FOREIGN KEY (`to_admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Seed 3 Super Admins (Only 1 Active, other 2 Inactive)
INSERT INTO `admins` (`user_idno`, `name`, `email`, `role`, `status`)
VALUES 
('2026-4103', 'Jamesrel Galagate', 'jamesrel.galagate@gmail.com', 'Super Admin', 'Active'),
('2026-0001', 'Jessrel James Galagate', 'jessreljames.galagate@csucc.edu.ph', 'Super Admin', 'Inactive'),
('2026-4102', 'Rubiks Security SuperAdmin', 'superadmin@rubiks.com', 'Super Admin', 'Inactive')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `role` = VALUES(`role`);
