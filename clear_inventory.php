<?php
include 'db.php';
session_start();

// Security: Only allow Admin to clear the inventory
if ($_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

try {
    $sql = "TRUNCATE TABLE diesel_inventory";
    $conn->exec($sql);
    header("Location: diesel_inventory.php?msg=cleared");
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
