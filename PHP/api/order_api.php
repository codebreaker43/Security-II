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

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $data['item_name'] ?? '';
    $price = $data['price'] ?? 0;
    $quantity = $data['quantity'] ?? 1;

    if (empty($item_name) || $price <= 0 || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order details']);
        exit();
    }

    $total_price = $price * $quantity;

    // Insert order into database
    $query = "INSERT INTO orders (user_id, item_name, quantity, total_price, status) VALUES (?, ?, ?, ?, 'pending')";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isid", $user_id, $item_name, $quantity, $total_price);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Order placed successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: Could not save order']);
        }
        mysqli_stmt_close($stmt);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare statement']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>