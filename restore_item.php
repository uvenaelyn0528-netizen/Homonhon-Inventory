<?php
include 'db.php';
session_start();

if ($_SESSION['role'] === 'Admin' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "UPDATE inventory SET is_deleted = FALSE WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $id]);
    header("Location: trash_bin.php?msg=restored");
} else {
    die("Unauthorized.");
}
?>
