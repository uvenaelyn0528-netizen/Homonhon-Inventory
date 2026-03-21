<?php
// 1. Maximize resources
ini_set('memory_limit', '512M');
set_time_limit(0); 
include 'db.php';

if (isset($_POST['import_btn']) && isset($_FILES["excel_file"])) {
    $file = fopen($_FILES["excel_file"]["tmp_name"], "r");
    fgetcsv($file); // Skip header

    try {
        $conn->beginTransaction();
        
        $count = 0;
        $batch_size = 1000;
        $rows_to_insert = [];

        // Pre-prepare the Inventory Update to keep it fast
        $uStmt = $conn->prepare("UPDATE inventory SET specification = :spec, price = :price WHERE item_name = :name");

        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            if (empty($column[3])) continue;

            // CLEAN DATA
            $price = floatval(preg_replace('/[^0-9.]/', '', $column[7] ?? '0'));
            $qty   = floatval(preg_replace('/[^0-9.]/', '', $column[8] ?? '0'));
            $item  = trim($column[3]);
            
            // Fix Date
            $raw_date = trim($column[0]);
            if (strpos($raw_date, '-') !== false) { $raw_date = explode('-', $raw_date)[0]; }
            $final_date = date('Y-m-d', strtotime(str_replace('/', '-', $raw_date)));

            // Update Inventory Price/Spec immediately
            $uStmt->execute(['spec' => $column[4], 'price' => $price, 'name' => $item]);

            // Add to History Batch
            $rows_to_insert[] = [
                $final_date, $column[1], $column[2], $item, $column[4], 
                $column[5], $qty, $price, ($price * $qty), $column[6], $column[9]
            ];

            $count++;

            // Every 1000 rows, push to Database and Commit
            if ($count % $batch_size == 0) {
                insertBatch($conn, $rows_to_insert);
                $rows_to_insert = []; // Reset batch
                $conn->commit();
                $conn->beginTransaction();
            }
        }

        // Final remaining rows
        if (!empty($rows_to_insert)) {
            insertBatch($conn, $rows_to_insert);
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

// HELPER FUNCTION: Bulk Insert for Speed
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
