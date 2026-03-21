<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
set_time_limit(0); 
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    die("Error: Admin access required.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES["excel_file"])) {
    $file = fopen($_FILES["excel_file"]["tmp_name"], "r");
    fgetcsv($file); // Skip Header

    try {
        $conn->beginTransaction();
        
        $uStmt = $conn->prepare("UPDATE inventory SET specification = :spec, price = :price WHERE item_name = :name");
        $iStmt = $conn->prepare("INSERT INTO received_history (received_date, rr_number, supplier, item_name, specification, um, qty, price, amount, department, purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $count = 0;
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            if (empty($column[3])) continue; 

            // MAPPING:
            // Col 0: Date | Col 1: RR# | Col 2: Supplier | Col 3: Item Name | Col 4: Spec | Col 5: UM
            // Col 6 (G): QUANTITY 
            // Col 7 (H): PRICE 
            // Col 8: Dept | Col 9: Purpose
            
            $raw_date = trim($column[0]);
            $final_date = ($raw_date) ? date('Y-m-d', strtotime(str_replace('/', '-', $raw_date))) : date('Y-m-d');
            
            $item_name = trim($column[3]);
            $spec      = trim($column[4] ?? '');
            $um        = trim($column[5] ?? '');
            
            // SWAPPED PER YOUR REQUEST:
            $qty    = floatval(preg_replace('/[^0-9.]/', '', $column[6] ?? '0')); // G is now Qty
            $price  = floatval(preg_replace('/[^0-9.]/', '', $column[7] ?? '0')); // H is now Price
            $amount = $price * $qty;
            
            $dept    = trim($column[8] ?? '');
            $purpose = trim($column[9] ?? '');

            // Update Master Inventory
            $uStmt->execute(['spec' => $spec, 'price' => $price, 'name' => $item_name]);

            // Insert History
            $iStmt->execute([$final_date, $column[1], $column[2], $item_name, $spec, $um, $qty, $price, $amount, $dept, $purpose]);

            $count++;
        }

        $conn->commit();
        fclose($file);
        header("Location: received_summary.php?import=success&count=$count");
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        die("Import Error: " . $e->getMessage());
    }
}
?>
