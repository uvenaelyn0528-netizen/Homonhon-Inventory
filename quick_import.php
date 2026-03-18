<?php
include 'db.php';
$file = fopen("your_file_name.csv", "r"); // Put your actual filename here

while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
    // Mapping: $data[0] is Column A, $data[1] is Column B, etc.
    $sql = "INSERT INTO received_history (received_date, rr_number, supplier, item_name, Specification, Qty, UM, Department, Purpose) 
            VALUES ('$data[0]', '$data[1]', '$data[2]', '$data[3]', '$data[4]', '$data[5]', '$data[6]', '$data[7]', '$data[8]')";
    $conn->query($sql);
}
fclose($file);
echo "Import Finished!";
?>