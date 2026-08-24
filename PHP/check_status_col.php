<?php
include("db.php");
$query = "SHOW COLUMNS FROM `user` LIKE 'status'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    echo "Column 'status' exists.";
} else {
    echo "Column 'status' does NOT exist.";
}
?>
