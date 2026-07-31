<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Security: Only allow Admin to erase history
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    die("Access Denied: Only administrators can clear history.");
}

try {
    // RESTART IDENTITY resets auto-increment IDs to 1
    // CASCADE handles any linked tables with foreign keys
    $sql = "TRUNCATE TABLE diesel_inventory RESTART IDENTITY CASCADE";
    $conn->exec($sql);
    
    // Redirect back with a success message
    header("Location: diesel_inventory.php?msg=" . urlencode("All inventory records wiped successfully!"));
    exit();
} catch (PDOException $e) {
    // Fallback: Standard DELETE if TRUNCATE CASCADE fails due to database permission restrictions
    try {
        $conn->exec("DELETE FROM diesel_inventory");
        header("Location: diesel_inventory.php?msg=" . urlencode("All inventory records wiped successfully!"));
        exit();
    } catch (PDOException $ex) {
        die("Database Error: " . $ex->getMessage());
    }
}
?>
