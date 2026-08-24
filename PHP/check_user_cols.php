<?php
include("db.php");

$query = "SHOW COLUMNS FROM `user`";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "Columns in 'user' table:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error showing columns: " . mysqli_error($conn);
}
?>
