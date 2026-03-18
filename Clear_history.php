<?php
include 'db.php';

if (isset($_GET['type'])) {
    $type = $_GET['type'];
    $table = "";
    $redirect = "";

    if ($type == 'withdrawal') {
        $table = "withdrawals";
        $redirect = "history.php";
    } elseif ($type == 'received') {
        $table = "received_history";
        $redirect = "received_summary.php";
    }

    if ($table != "") {
        // TRUNCATE empties the table and resets the auto-increment ID
        $sql = "TRUNCATE TABLE $table";
        
        if ($conn->query($sql)) {
            header("Location: $redirect?msg=all_cleared");
        } else {
            echo "Error clearing table: " . $conn->error;
        }
    }
}
?>