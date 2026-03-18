<?php
include 'db.php';
session_start();

if (isset($_POST['update_summary'])) {
    $id = $_POST['id'];
    $rr = mysqli_real_escape_string($conn, $_POST['rr_number']);
    
    // This value comes from the <input name="RDATE"> in your form
    $rdate = $_POST['RDATE']; 
    $qty = $_POST['Qty'];

    // FIXED: Changed 'RDATE' to 'received_date' to match your DB table
    $sql = "UPDATE received_history SET 
            rr_number = '$rr', 
            received_date = '$rdate', 
            Qty = '$qty' 
            WHERE id = '$id'";

    if ($conn->query($sql)) {
        header("Location: received_summary.php?msg=success");
    } else {
        // This will now show the specific error if something else is wrong
        echo "Error updating record: " . $conn->error;
    }
}
?>