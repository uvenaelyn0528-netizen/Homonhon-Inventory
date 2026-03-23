<?php
include 'db.php';
session_start();
if (strtolower(trim($_SESSION['role'] ?? '')) === 'admin') {
    $conn->exec("TRUNCATE TABLE diesel_inventory");
    header("Location: diesel_inventory.php?status=cleared");
}
?>
