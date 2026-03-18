<?php
// Enable error reporting to see exactly what is failing
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

if (isset($_POST['submit_import'])) {
    // 1. Check if a file was actually uploaded
    if (isset($_FILES["excel_file"]) && $_FILES["excel_file"]["error"] == 0) {
        $filename = $_FILES["excel_file"]["tmp_name"];
        $file = fopen($filename, "r");

        // 2. Skip the header row (the titles in Excel)
        fgetcsv($file); 

        $count = 0;
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            // Basic check: skip empty rows
            if (empty($column[0])) continue;

            // Mapping columns (Adjust the index numbers if your Excel order is different)
            $item_name     = mysqli_real_escape_string($conn, trim($column[0]));
            $specification = mysqli_real_escape_string($conn, trim($column[1]));
            $um            = mysqli_real_escape_string($conn, trim($column[2]));
            $qty           = mysqli_real_escape_string($conn, trim($column[3]));
            $department    = mysqli_real_escape_string($conn, trim($column[4]));
            $purpose       = mysqli_real_escape_string($conn, trim($column[5]));
            $raw_date      = trim($column[6]);
            $rr_number     = mysqli_real_escape_string($conn, trim($column[7]));
            $supplier      = mysqli_real_escape_string($conn, trim($column[8]));

            // --- DATE FIX: Convert common formats to MySQL YYYY-MM-DD ---
            $received_date = date('Y-m-d', strtotime($raw_date));

            // Insert into history
            $sql_log = "INSERT INTO received_history (item_name, Specification, UM, Qty, Department, Purpose, received_date, rr_number, supplier) 
                        VALUES ('$item_name', '$specification', '$um', '$qty', '$department', '$purpose', '$received_date', '$rr_number', '$supplier')";
            
            if(mysqli_query($conn, $sql_log)) {
                // Check if exists in inventory to update RDATE and RR#
                $check_item = mysqli_query($conn, "SELECT id FROM inventory WHERE item_name = '$item_name' AND Specification = '$specification' LIMIT 1");

                if (mysqli_num_rows($check_item) > 0) {
                    mysqli_query($conn, "UPDATE inventory SET rr_number = '$rr_number', RDATE = '$received_date' WHERE item_name = '$item_name' AND Specification = '$specification'");
                } else {
                    mysqli_query($conn, "INSERT INTO inventory (item_name, Specification, UM, Department, RDATE, rr_number) VALUES ('$item_name', '$specification', '$um', '$department', '$received_date', '$rr_number')");
                }
                $count++;
            }
        }
        fclose($file);
        header("Location: received_summary.php?msg=success&count=$count");
        exit();
    } else {
        echo "<script>alert('Error: No file selected or file too large.'); window.location.href='import_received.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Data</title>
</head>
<body style="background-color: #f4f7f6; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">

<div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center;">
    <h2 style="color: #112941; margin-top: 0;">📥 Bulk Import</h2>
    <p style="color: #666; font-size: 14px;">Save your Excel file as <b>CSV (Comma Delimited)</b></p>
    
    <form action="import_received.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
        <div style="border: 2px dashed #cbd5e0; padding: 30px; border-radius: 10px; margin-bottom: 20px; background: #f8fafc;">
            <input type="file" name="excel_file" accept=".csv" required style="font-size: 14px;">
        </div>
        
        <button type="submit" name="submit_import" style="background: #27ae60; color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s;">
            🚀 Start Import
        </button>
    </form>
    
    <div style="margin-top: 20px;">
        <a href="received_summary.php" style="text-decoration: none; color: #7f8c8d; font-size: 13px;">⬅ Back to Summary</a>
    </div>
</div>

</body>
</html>