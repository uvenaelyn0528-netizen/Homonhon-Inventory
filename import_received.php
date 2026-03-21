<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
set_time_limit(0); 
include 'db.php';

// Check for admin role even on the backend for security
session_start();
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    die("Unauthorized Access.");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Admin check to prevent unauthorized changes
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') { 
    die("Error: Admin access required."); 
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES["excel_file"])) {
$file = fopen($_FILES["excel_file"]["tmp_name"], "r");
    fgetcsv($file); // Skip Header
    fgetcsv($file); // Skip Header Row

try {
$conn->beginTransaction();
        $count = 0;
        $batch_size = 500;
        $rows_to_insert = [];

        
        // This statement updates your MAIN INVENTORY table
$uStmt = $conn->prepare("UPDATE inventory SET specification = :spec, price = :price WHERE item_name = :name");
        
        // This statement inserts into your RECEIVED HISTORY table
        $iStmt = $conn->prepare("INSERT INTO received_history (received_date, rr_number, supplier, item_name, specification, um, qty, price, amount, department, purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $count = 0;
while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
if (empty($column[3])) continue; 

            // THE CORRECT 10-COLUMN MAPPING:
            // 0:Date | 1:RR# | 2:Supplier | 3:Item Name | 4:Spec | 5:UM | 6:Price | 7:Qty | 8:Dept | 9:Purpose
            
$raw_date = trim($column[0]);
$final_date = ($raw_date) ? date('Y-m-d', strtotime(str_replace('/', '-', $raw_date))) : date('Y-m-d');

            $item  = trim($column[3]);
            $spec  = trim($column[4] ?? '');
            $um    = trim($column[5] ?? '');
            $price = floatval(preg_replace('/[^0-9.]/', '', $column[6] ?? '0'));
            $qty   = floatval(preg_replace('/[^0-9.]/', '', $column[7] ?? '0'));
            $dept  = trim($column[8] ?? '');
            $purp  = trim($column[9] ?? '');
            $item_name = trim($column[3]);
            $spec      = trim($column[4] ?? '');
            $um        = trim($column[5] ?? '');
            
            // Clean numbers of any symbols or commas
            $price  = floatval(preg_replace('/[^0-9.]/', '', $column[6] ?? '0'));
            $qty    = floatval(preg_replace('/[^0-9.]/', '', $column[7] ?? '0'));
            $amount = $price * $qty;
            
            $dept   = trim($column[8] ?? '');
            $purpose = trim($column[9] ?? '');

            $uStmt->execute(['spec' => $spec, 'price' => $price, 'name' => $item]);
            // 1. UPDATE MAIN INVENTORY (Fixes the shuffled dashboard data)
            $uStmt->execute([
                'spec'  => $spec,
                'price' => $price,
                'name'  => $item_name
            ]);

            $rows_to_insert[] = [
                $final_date, $column[1] ?? '', $column[2] ?? '', $item, $spec, $um, $qty, $price, ($price * $qty), $dept, $purp
            ];
            // 2. INSERT INTO RECEIVED HISTORY
            $iStmt->execute([
                $final_date, $column[1], $column[2], $item_name, 
                $spec, $um, $qty, $price, $amount, $dept, $purpose
            ]);

$count++;
            if ($count % $batch_size == 0) {
                insertBatch($conn, $rows_to_insert);
                $rows_to_insert = [];
                $conn->commit();
                $conn->beginTransaction();
            }
}

        if (!empty($rows_to_insert)) { insertBatch($conn, $rows_to_insert); }
$conn->commit();
fclose($file);
header("Location: received_summary.php?import=success&count=$count");

die("Import Error: " . $e->getMessage());
}
}

function insertBatch($pdo, $data) {
    if (empty($data)) return;
    $row_places = '(' . implode(',', array_fill(0, 11, '?')) . ')';
    $all_places = implode(',', array_fill(0, count($data), $row_places));
    $sql = "INSERT INTO received_history (received_date, rr_number, supplier, item_name, specification, um, qty, price, amount, department, purpose) VALUES $all_places";
    $stmt = $pdo->prepare($sql);
    $values = [];
    foreach ($data as $row) { foreach ($row as $val) { $values[] = $val; } }
    $stmt->execute($values);
}
?>
