<?php
include 'db.php';
session_start();

if (isset($_POST['update_item'])) {
    if ($_SESSION['role'] !== 'Admin') {
        die("Unauthorized");
    }

    $id = $_POST['item_id'];
    $name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $spec = mysqli_real_escape_string($conn, $_POST['specification']);
    $min = (int)$_POST['min_stock'];
    $max = (int)$_POST['max_stock'];

    $sql = "UPDATE inventory SET 
            item_name = '$name', 
            Specification = '$spec', 
            min_stock = $min, 
            max_stock = $max 
            WHERE id = $id";

    if ($conn->query($sql)) {
        header("Location: index.php?msg=updated");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>