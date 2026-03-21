<?php
include 'db.php';
session_start();

// 1. SECURITY: Only Admin can perform these actions
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die("⛔ Access Denied: Administrator role required.");
}

// --- SINGLE DELETE / RESTORE LOGIC ---
if (isset($_GET['id']) && isset($_GET['type'])) {
    $id = intval($_GET['id']);
    $type = $_GET['type'];
    $redirect = "index.php";

    try {
        if ($type == 'inventory') {
            // STEP 1: SOFT DELETE (Hide from inventory)
            $sql = "UPDATE inventory SET is_deleted = TRUE WHERE id = :id";
            $redirect = "index.php";
        } elseif ($type == 'restore') {
            // STEP 2: RESTORE (Bring back from Trash)
            $sql = "UPDATE inventory SET is_deleted = FALSE WHERE id = :id";
            $redirect = "trash_bin.php";
        } elseif ($type == 'perm_delete') {
            // STEP 3: PERMANENT DELETE (Remove from DB forever)
            $sql = "DELETE FROM inventory WHERE id = :id";
            $redirect = "trash_bin.php";
        } elseif ($type == 'withdrawal') {
            $sql = "DELETE FROM withdrawals WHERE id = :id";
            $redirect = "history.php";
        } elseif ($type == 'received') {
            $sql = "DELETE FROM received_history WHERE id = :id";
            $redirect = "received_summary.php";
        }

        if (isset($sql)) {
            $stmt = $conn->prepare($sql);
            $stmt->execute(['id' => $id]);
            header("Location: $redirect?msg=success");
            exit();
        }
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}

// --- CLEAR ALL LOGIC (TRUNCATE) ---
if (isset($_POST['clear_type'])) {
    $type = $_POST['clear_type'];
    $redirect = "index.php";

    if ($type == 'withdrawal') {
        $sql = "TRUNCATE TABLE withdrawals RESTART IDENTITY";
        $redirect = "history.php";
    } elseif ($type == 'received') {
        $sql = "TRUNCATE TABLE received_history RESTART IDENTITY";
        $redirect = "received_summary.php";
    }

    try {
        if (isset($sql)) {
            $conn->exec($sql);
            header("Location: $redirect?msg=cleared");
            exit();
        }
    } catch (PDOException $e) {
        die("Error clearing logs: " . $e->getMessage());
    }
}
?>
