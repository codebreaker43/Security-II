<?php
header('Content-Type: application/json');
include(__DIR__ . '/../db.php');

$field = isset($_GET['field']) ? trim($_GET['field']) : '';
$value = isset($_GET['value']) ? trim($_GET['value']) : '';
$exclude_id = isset($_GET['exclude_id']) ? trim($_GET['exclude_id']) : '';

if (empty($field) || empty($value)) {
    echo json_encode(['exists' => false]);
    exit();
}

$allowed_fields = ['email', 'username'];
if (!in_array($field, $allowed_fields)) {
    echo json_encode(['exists' => false, 'error' => 'Invalid field']);
    exit();
}

$safe_value = mysqli_real_escape_string($conn, $value);
$exclude_sql = "";
if (!empty($exclude_id)) {
    $safe_exclude = mysqli_real_escape_string($conn, $exclude_id);
    $exclude_sql = " AND idno != '$safe_exclude'";
}

$result = mysqli_query($conn, "SELECT idno FROM user WHERE `$field` = '$safe_value' $exclude_sql LIMIT 1");

if ($result && mysqli_num_rows($result) > 0) {
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}
?>
