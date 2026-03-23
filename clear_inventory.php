<?php
include 'db.php';
session_start();

// Security: Only allow Admin to erase history
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    die("Access Denied: Only administrators can clear history.");
}

try {
    // TRUNCATE is better than DELETE because it resets the ID counter to 1
    $sql = "TRUNCATE TABLE diesel_inventory";
    $conn->exec($sql);
    
    // Redirect back with a success message
    header("Location: diesel_inventory.php?msg=inventory_wiped");
    exit();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
