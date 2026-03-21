<?php
include 'db.php';
session_start();

if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    die("Admin only.");
}

try {
    // This wipes the corrupted price data so you can start fresh
    $conn->exec("UPDATE inventory SET price = 0.00");
    echo "✅ All inventory prices have been reset to 0.00. You can now re-import your CSV.";
    echo "<br><br><a href='received_summary.php'>Go back to Received Summary</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
