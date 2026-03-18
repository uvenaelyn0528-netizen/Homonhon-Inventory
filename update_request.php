<?php
include 'db.php';

if (isset($_POST['update_request'])) {
    // Kunin ang mga data mula sa Edit Modal
    $id = intval($_POST['request_id']);
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $qty = intval($_POST['qty']);
    $rf_number = mysqli_real_escape_string($conn, $_POST['RF_Number']);
    $dept = mysqli_real_escape_string($conn, $_POST['department']);
    $purpose_val = mysqli_real_escape_string($conn, $_POST['purpose']);

    // TANDAAN: Dahil nag-error ka kanina sa 'purpose', 
    // siguraduhin na ang column name sa ibaba ay tugma sa Database mo.
    // Kung sa DB mo ay "Purpose" (Capital P), palitan ang 'purpose' sa ibaba.
    
    $sql = "UPDATE item_requests SET 
            item_name = '$item_name', 
            qty = '$qty', 
            RF_Number = '$rf_number', 
            department = '$dept', 
            purpose = '$purpose_val' 
            WHERE request_id = $id";

    if ($conn->query($sql)) {
        // Kapag successful, babalik sa view_requests.php na may success message
        header("Location: view_requests.php?updated=1");
        exit();
    } else {
        // Kung mag-error ulit sa column name, lalabas dito ang detalye
        echo "Error updating record: " . $conn->error;
    }
} else {
    // Kung sinubukang i-access ang file na ito nang hindi galing sa form
    header("Location: view_requests.php");
    exit();
}
?>