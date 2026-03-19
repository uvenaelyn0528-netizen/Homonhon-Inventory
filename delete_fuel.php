<?php
include 'db.php';
session_start();

// 1. Security Check: Only Admins should be allowed to delete fuel records
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized: Only Administrators can delete fuel records.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // 2. Use PDO Prepared Statement to prevent SQL Injection
        $sql = "DELETE FROM diesel_inventory WHERE id = :id";
        $stmt = $conn->prepare($sql);
        
        // 3. Execute the deletion
        if ($stmt->execute([':id' => $id])) {
            // Redirect back to the diesel ledger with a success message
            header("Location: diesel_inventory.php?msg=deleted");
            exit();
        } else {
            echo "Error: Could not delete the record.";
        }
    } catch (PDOException $e) {
        // Handle database errors (like foreign key constraints)
        echo "Database Error: " . $e->getMessage();
    }
} else {
    // Redirect if no ID was provided
    header("Location: diesel_inventory.php");
    exit();
}
?>
