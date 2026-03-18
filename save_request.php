<?php
include 'db.php';

if (isset($_POST['submit_request'])) {
    $item_name = $_POST['item_name'];
    $specification = $_POST['specification'];
    $qty = $_POST['qty'];
    $remarks = $_POST['remarks']; // Purchase Type
    $department = $_POST['department'];
    $purpose = $_POST['purpose'];
    $rf_number = $_POST['RF']; // Ito yung galing sa input field
    $requested_by = $_POST['requested_by'];
    $date = date('Y-m-d H:i:s');

    // Siguraduhin na ang 'RF_Number' dito ay kapareho ng column name sa MySQL table mo
    // Halimbawa kung Capital 'P' ang nasa Database:
$query = "INSERT INTO item_requests (item_name, specification, qty, remarks, department, Purpose, RF_Number, requested_by, request_date, status) 
          VALUES ('$item_name', '$specification', '$qty', '$remarks', '$department', '$purpose', '$rf_number', '$requested_by', '$date', 'Pending')";

    if ($conn->query($query)) {
        header("Location: view_requests.php?success=1");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>