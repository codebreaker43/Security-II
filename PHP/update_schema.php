<?php
include("db.php");

echo "<h2>Database Schema Update for RBAC</h2>";

// 1. Add 'role' column to 'user' table
$query = "SHOW COLUMNS FROM `user` LIKE 'role'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $alter_query = "ALTER TABLE `user` ADD `role` ENUM('user', 'admin', 'superadmin') NOT NULL DEFAULT 'user'";
    if (mysqli_query($conn, $alter_query)) {
        echo "<p style='color: green;'>Successfully added 'role' column to 'user' table.</p>";
    } else {
        echo "<p style='color: red;'>Error adding 'role' column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>Column 'role' already exists.</p>";
}

// 2. Add 'status' column to 'user' table for blocking functionality
$status_query = "SHOW COLUMNS FROM `user` LIKE 'status'";
$status_result = mysqli_query($conn, $status_query);

if (mysqli_num_rows($status_result) == 0) {
    $alter_status = "ALTER TABLE `user` ADD `status` ENUM('active', 'blocked') NOT NULL DEFAULT 'active'";
    if (mysqli_query($conn, $alter_status)) {
        echo "<p style='color: green;'>Successfully added 'status' column to 'user' table.</p>";
    } else {
        echo "<p style='color: red;'>Error adding 'status' column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>Column 'status' already exists.</p>";
}

// 2. Seed Default Users (Admin and Superadmin)
function seedUser($conn, $username, $password, $email, $role, $firstname, $lastname) {
    // Check if user exists
    $check = mysqli_query($conn, "SELECT * FROM `user` WHERE `username` = '$username'");
    if (mysqli_num_rows($check) == 0) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Minimum required fields based on registration.php schema
        // We'll fill dummy data for required fields like idno, birthdate, etc. to satisfy NOT NULL constraints if any
        // Assuming strict mode might be on, we provide widely compatible defaults
        $sql = "INSERT INTO `user` 
        (username, password, email, role, firstname, lastname, 
        idno, middlename, suffix, birthdate, age, sex, country, purokstreet, brgy, municipal, province, zipcode) 
        VALUES 
        ('$username', '$hashed_password', '$email', '$role', '$firstname', '$lastname',
        'ADMIN-" . rand(1000,9999) . "', '', '', '2000-01-01', '24', 'Male', 'Philippines', 'Admin St', 'Admin Brgy', 'Admin City', 'Admin Prov', '0000')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<p style='color: green;'>Seeded user: <strong>$username</strong> ($role)</p>";
        } else {
            echo "<p style='color: red;'>Error seeding $username: " . mysqli_error($conn) . "</p>";
        }
    } else {
        // Update role if exists just in case
        $update = "UPDATE `user` SET `role` = '$role' WHERE `username` = '$username'";
        mysqli_query($conn, $update);
        echo "<p style='color: orange;'>User <strong>$username</strong> already exists. (Updated role to $role)</p>";
    }
}

seedUser($conn, 'admin_user', 'admin123', 'admin@rubiks.com', 'admin', 'System', 'Admin');
seedUser($conn, 'superadmin_user', 'superadmin123', 'superadmin@rubiks.com', 'superadmin', 'Super', 'Admin');


// 3. Create Audit Logs Table
$audit_check = mysqli_query($conn, "SHOW TABLES LIKE 'audit_logs'");
if (mysqli_num_rows($audit_check) == 0) {
    $sql_audit = "CREATE TABLE `audit_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `username` VARCHAR(255) NOT NULL,
        `role` VARCHAR(50) NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `details` TEXT DEFAULT NULL,
        `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $sql_audit)) {
        echo "<p style='color: green;'>Successfully created 'audit_logs' table.</p>";
    } else {
        echo "<p style='color: red;'>Error creating 'audit_logs' table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>Table 'audit_logs' already exists.</p>";
}

echo "<p>Update Complete.</p>";
?>
