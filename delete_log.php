<?php
include 'db.php';

// SINGLE DELETE (Existing logic mo na may konting dagdag)
if (isset($_GET['id']) && isset($_GET['type'])) {
    $id = intval($_GET['id']);
    $type = $_GET['type'];

    if ($type == 'withdrawal') {
        $sql = "DELETE FROM withdrawals WHERE id = $id";
        $redirect = "history.php";
    } elseif ($type == 'received') {
        $sql = "DELETE FROM received_history WHERE id = $id";
        $redirect = "received_summary.php";
    }

    if (isset($sql) && $conn->query($sql)) {
        header("Location: $redirect?msg=deleted");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// CLEAR ALL LOGIC
if (isset($_POST['clear_type'])) {
    $type = $_POST['clear_type'];
    
    if ($type == 'withdrawal') {
        $sql = "TRUNCATE TABLE withdrawals";
        $redirect = "history.php";
    } elseif ($type == 'received') {
        $sql = "TRUNCATE TABLE received_history";
        $redirect = "received_summary.php";
    }

    if ($conn->query($sql)) {
        header("Location: $redirect?msg=cleared");
    }
}
?>