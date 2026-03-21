<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
set_time_limit(0); 
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES["excel_file"])) {
    $file = fopen($_FILES["excel_file"]["tmp_name"], "r");
    fgetcsv($file); // Skip Header

    try {
        $conn->beginTransaction();
        $count = 0;
        $batch_size = 500;
        $rows_to_insert = [];

        $uStmt = $conn->prepare("UPDATE inventory SET specification = :spec, price = :price WHERE item_name = :name");

        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            if (empty($column[3])) continue; // Skip if no Item Name

            // Map Columns: 0:Date, 1:RR, 2:Supplier, 3:Name, 4:Spec, 5:UM, 6:Price, 7:Qty, 8:Dept, 9:Purpose
            $date_raw = trim($column[0]);
            $final_date = ($date_raw) ? date('Y-m-d', strtotime(str_replace('/', '-', $date_raw))) : date('Y-m-d');
            
            $item  = trim($column[3]);
            $spec  = $column[4] ?? '';
            $price = floatval(preg_replace('/[^0-9.]/', '', $column[6] ?? '0'));
            $qty   = floatval(preg_replace('/[^0-9.]/', '', $column[7] ?? '0'));

            // Sync with Master Inventory
            $uStmt->execute(['spec' => $spec, 'price' => $price, 'name' => $item]);

            $rows_to_insert[] = [
                $final_date, $column[1], $column[2], $item, $spec, 
                $column[5], $qty, $price, ($price * $qty), $column[8], $column[9]
            ];

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
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        die("Import Error: " . $e->getMessage());
    }
}

function insertBatch($pdo, $data) {
    $row_places = '(' . implode(',', array_fill(0, 11, '?')) . ')';
    $all_places = implode(',', array_fill(0, count($data), $row_places));
    $sql = "INSERT INTO received_history (received_date, rr_number, supplier, item_name, specification, um, qty, price, amount, department, purpose) VALUES $all_places";
    $stmt = $pdo->prepare($sql);
    $values = [];
    foreach ($data as $row) { foreach ($row as $val) { $values[] = $val; } }
    $stmt->execute($values);
}
?>
