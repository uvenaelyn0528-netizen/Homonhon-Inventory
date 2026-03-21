<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Only allow specific roles to delete
$role = $_SESSION['role'] ?? 'Viewer';
if (!in_array($role, ['Admin', 'Staff', 'Head Office Purchasing'])) {
    die("Unauthorized access.");
}

if (isset($_GET['id']) && isset($_GET['type'])) {
    $id = intval($_GET['id']);
    $type = $_GET['type']; // 'PO' or 'RR'
    $column = ($type == 'PO') ? 'po_scan_path' : 'rr_scan_path';

    // 1. Get the file path from DB so we can delete the actual file
    $stmt = $conn->prepare("SELECT $column FROM item_requests WHERE request_id = :id");
    $stmt->execute(['id' => $id]);
    $file = $stmt->fetchColumn();

    if ($file && file_exists($file)) {
        unlink($file); // Deletes the file from the uploads/scans/ folder
    }

    // 2. Clear the database entry
    $updateStmt = $conn->prepare("UPDATE item_requests SET $column = NULL WHERE request_id = :id");
    $updateStmt->execute(['id' => $id]);

    header("Location: view_requests.php?msg=ScanDeleted");
    exit();
} else {
    header("Location: view_requests.php");
    exit();
}
