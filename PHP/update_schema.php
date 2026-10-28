<?php
include("db.php");

echo "<h2>Database Schema Update for RBAC, Security & Rotation</h2>";

// 1. Ensure 'role' column in 'user' table
$query = "SHOW COLUMNS FROM `user` LIKE 'role'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) == 0) {
    $alter_query = "ALTER TABLE `user` ADD `role` ENUM('user', 'admin', 'superadmin') NOT NULL DEFAULT 'user'";
    if (mysqli_query($conn, $alter_query)) {
        echo "<p style='color: green;'>Successfully added 'role' column to 'user' table.</p>";
    } else {
        echo "<p style='color: red;'>Error adding 'role' column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>Column 'role' already exists.</p>";
}

// 2. Ensure 'status' column in 'user' table with 'inactive' and 'pending'
$status_query = "SHOW COLUMNS FROM `user` LIKE 'status'";
$status_result = mysqli_query($conn, $status_query);

if ($status_result && mysqli_num_rows($status_result) == 0) {
    $alter_status = "ALTER TABLE `user` ADD `status` ENUM('active', 'blocked', 'pending', 'inactive') NOT NULL DEFAULT 'active'";
    if (mysqli_query($conn, $alter_status)) {
        echo "<p style='color: green;'>Successfully added 'status' column to 'user' table.</p>";
    } else {
        echo "<p style='color: red;'>Error adding 'status' column: " . mysqli_error($conn) . "</p>";
    }
} else {
    // Modify status ENUM to ensure active, approved, blocked, pending, inactive, pending_deletion are all supported
    $res_status = mysqli_query($conn, "ALTER TABLE `user` MODIFY `status` ENUM('active', 'approved', 'blocked', 'pending', 'inactive', 'pending_deletion') NOT NULL DEFAULT 'active'");
    if (!$res_status) {
        echo "<p style='color: red;'>Error altering status ENUM: " . mysqli_error($conn) . "</p>";
    } else {
        echo "<p style='color: green;'>Column 'status' updated with valid ENUM values (including pending_deletion).</p>";
    }
}

// 3. Ensure 'must_change_password' column
$mcp_query = "SHOW COLUMNS FROM `user` LIKE 'must_change_password'";
$mcp_result = mysqli_query($conn, $mcp_query);
if ($mcp_result && mysqli_num_rows($mcp_result) == 0) {
    $alter_mcp = "ALTER TABLE `user` ADD `must_change_password` TINYINT(1) NOT NULL DEFAULT 0";
    if (mysqli_query($conn, $alter_mcp)) {
        echo "<p style='color: green;'>Successfully added 'must_change_password' column.</p>";
    } else {
        echo "<p style='color: red;'>Error adding 'must_change_password' column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>Column 'must_change_password' already exists.</p>";
}

// 3.5 Ensure 'profile_completed' column
$pc_query = "SHOW COLUMNS FROM `user` LIKE 'profile_completed'";
$pc_result = mysqli_query($conn, $pc_query);
if ($pc_result && mysqli_num_rows($pc_result) == 0) {
    mysqli_query($conn, "ALTER TABLE `user` ADD `profile_completed` TINYINT(1) NOT NULL DEFAULT 1 AFTER `must_change_password`");
}

// 4. Ensure 'privileges' column
$priv_query = "SHOW COLUMNS FROM `user` LIKE 'privileges'";
$priv_result = mysqli_query($conn, $priv_query);
if ($priv_result && mysqli_num_rows($priv_result) == 0) {
    $alter_priv = "ALTER TABLE `user` ADD `privileges` TEXT DEFAULT NULL";
    if (mysqli_query($conn, $alter_priv)) {
        echo "<p style='color: green;'>Successfully added 'privileges' column.</p>";
    } else {
        echo "<p style='color: red;'>Error adding 'privileges' column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>Column 'privileges' already exists.</p>";
}

// Assign default privileges to admins if currently empty or NULL
mysqli_query($conn, "UPDATE `user` SET `privileges` = '[\"view_users\",\"create_users\",\"approve_users\"]' WHERE `role` = 'admin' AND (`privileges` IS NULL OR `privileges` = '' OR `privileges` = '[]')");

// 5. Ensure 'block_reason' column
$br_query = "SHOW COLUMNS FROM `user` LIKE 'block_reason'";
$br_result = mysqli_query($conn, $br_query);
if ($br_result && mysqli_num_rows($br_result) == 0) {
    mysqli_query($conn, "ALTER TABLE `user` ADD `block_reason` VARCHAR(255) DEFAULT NULL");
}

// 6. Ensure 'delete_reason' column
$dr_query = "SHOW COLUMNS FROM `user` LIKE 'delete_reason'";
$dr_result = mysqli_query($conn, $dr_query);
if ($dr_result && mysqli_num_rows($dr_result) == 0) {
    mysqli_query($conn, "ALTER TABLE `user` ADD `delete_reason` VARCHAR(255) DEFAULT NULL");
}

// 6.1 Ensure 'delete_requested_by' and 'delete_requested_at' columns
$drb_query = "SHOW COLUMNS FROM `user` LIKE 'delete_requested_by'";
$drb_result = mysqli_query($conn, $drb_query);
if ($drb_result && mysqli_num_rows($drb_result) == 0) {
    mysqli_query($conn, "ALTER TABLE `user` ADD `delete_requested_by` VARCHAR(100) DEFAULT NULL");
}

$dra_query = "SHOW COLUMNS FROM `user` LIKE 'delete_requested_at'";
$dra_result = mysqli_query($conn, $dra_query);
if ($dra_result && mysqli_num_rows($dra_result) == 0) {
    mysqli_query($conn, "ALTER TABLE `user` ADD `delete_requested_at` DATETIME DEFAULT NULL");
}

// 7. Seed Default Users (Admin and Superadmin)
function seedUser($conn, $username, $password, $email, $role, $firstname, $lastname) {
    $check = mysqli_query($conn, "SELECT * FROM `user` WHERE `username` = '$username'");
    if ($check && mysqli_num_rows($check) == 0) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $curr_year = date('Y');
        $idno = $curr_year . '-' . sprintf('%04d', rand(1000, 9999));
        $sql = "INSERT INTO `user` 
        (username, password, email, role, firstname, lastname, 
        idno, middlename, suffix, birthdate, age, sex, country, purokstreet, brgy, municipal, province, zipcode, status) 
        VALUES 
        ('$username', '$hashed_password', '$email', '$role', '$firstname', '$lastname',
        '$idno', '', '', '2000-01-01', '24', 'Male', 'Philippines', 'Admin St', 'Admin Brgy', 'Admin City', 'Admin Prov', '0000', 'active')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<p style='color: green;'>Seeded user: <strong>$username</strong> ($role)</p>";
        } else {
            echo "<p style='color: red;'>Error seeding $username: " . mysqli_error($conn) . "</p>";
        }
    } else {
        $update = "UPDATE `user` SET `role` = '$role' WHERE `username` = '$username'";
        mysqli_query($conn, $update);
        echo "<p style='color: orange;'>User <strong>$username</strong> already exists. (Updated role to $role)</p>";
    }
}

seedUser($conn, 'admin_user', 'admin123', 'admin@rubiks.com', 'admin', 'System', 'Admin');
seedUser($conn, 'superadmin_user', 'superadmin123', 'superadmin@rubiks.com', 'superadmin', 'Super', 'Admin');

// 8. Create Audit Logs Table
$audit_check = mysqli_query($conn, "SHOW TABLES LIKE 'audit_logs'");
if ($audit_check && mysqli_num_rows($audit_check) == 0) {
    $sql_audit = "CREATE TABLE `audit_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` VARCHAR(50) NOT NULL,
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
