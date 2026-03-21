<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

if (isset($_FILES['withdrawal_csv']) && $_FILES['withdrawal_csv']['error'] == 0) {
    $file = $_FILES['withdrawal_csv']['tmp_name'];
    $handle = fopen($file, "r");
    
    // Skip header row
    fgetcsv($handle); 

    // Prepare statement based on your specific table columns
    $stmt = $conn->prepare("INSERT INTO withdrawals (withdrawal_date, item_name, specification, qty, department, purpose, withdrawn_by) 
                            VALUES (:date, :item, :spec, :qty, :dept, :purpose, :user)");

    $count = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Expected CSV Format: Date (YYYY-MM-DD), Item Name, Specification, Quantity, Department, Purpose, Withdrawn By
        if (count($data) >= 7) {
            $stmt->execute([
                'date'    => $data[0],
                'item'    => $data[1],
                'spec'    => $data[2],
                'qty'     => floatval($data[3]),
                'dept'    => $data[4],
                'purpose' => $data[5],
                'user'    => $data[6]
            ]);
            $count++;
        }
    }

    fclose($handle);
    header("Location: history.php?msg=Imported_" . $count . "_Records");
    exit();
} else {
    header("Location: history.php?error=UploadFailed");
    exit();
}
