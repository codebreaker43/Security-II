<?php
session_start();
include '../db.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch profile data
    $query = "SELECT idno, firstname, middlename, lastname, suffix, birthdate, age, sex, email, username, country, purokstreet, brgy, municipal, province, zipcode FROM user WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
        mysqli_stmt_close($stmt);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare database statement']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update profile data
    $data = json_decode(file_get_contents('php://input'), true);

    // We only allow editing certain fields for safety
    $firstname = $data['firstname'] ?? '';
    $lastname = $data['lastname'] ?? '';
    $email = $data['email'] ?? '';
    $purokstreet = $data['purokstreet'] ?? '';
    $brgy = $data['brgy'] ?? '';
    $municipal = $data['municipal'] ?? '';
    $province = $data['province'] ?? '';
    $zipcode = $data['zipcode'] ?? '';

    if (empty($firstname) || empty($lastname) || empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'First Name, Last Name, and Email are required.']);
        exit();
    }

    $query = "UPDATE user SET firstname=?, lastname=?, email=?, purokstreet=?, brgy=?, municipal=?, province=?, zipcode=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssssi", $firstname, $lastname, $email, $purokstreet, $brgy, $municipal, $province, $zipcode, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: Could not update profile']);
        }
        mysqli_stmt_close($stmt);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare database statement']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>