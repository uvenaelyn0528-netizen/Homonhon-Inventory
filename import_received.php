<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
set_time_limit(0); 
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES["excel_file"])) {
    $file = fopen($_FILES["excel_file"]["tmp_name"], "r");
    fgetcsv($file); // Skip the Header Row

    try {
        $conn->beginTransaction();
        $count = 0;
        $batch_size = 500;
        $rows_to_insert = [];

        // Prepare Update for Master Inventory
        $uStmt = $conn->prepare("UPDATE inventory SET specification = :spec, price = :price WHERE item_name = :name");

        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            // Must have Item Name (Col 3) and Qty (Col 7)
            if (empty($column[3])) continue; 

            // 10-COLUMN CSV MAPPING:
            // 0:Date, 1:RR#, 2:Supplier, 3:Item, 4:Spec, 5:UM, 6:Price, 7:Qty, 8:Dept, 9:Purpose
            
            $raw_date = trim($column[0]);
            $final_date = ($raw_date) ? date('Y-m-d', strtotime(str_replace('/', '-', $raw_date))) : date('Y-m-d');
            
            $item  = trim($column[3]);
            $spec  = trim($column[4] ?? '');
            $um    = trim($column[5] ?? '');
            $price = floatval(preg_replace('/[^0-9.]/', '', $column[6] ?? '0'));
            $qty   = floatval(preg_replace('/[^0-9.]/', '', $column[7] ?? '0'));
            $dept  = trim($column[8] ?? '');
            $purp  = trim($column[9] ?? '');

            // Update Master Inventory Price/Specs
            $uStmt->execute(['spec' => $spec, 'price' => $price, 'name' => $item]);

            // Construct 11 values for the Database table (received_history)
            $rows_to_insert[] = [
                $final_date,        // received_date
                $column[1] ?? '',   // rr_number
                $column[2] ?? '',   // supplier
                $item,              // item_name
                $spec,              // specification
                $um,                // um
                $qty,               // qty
                $price,             // price
                ($price * $qty),    // amount
                $dept,              // department
                $purp               // purpose
            ];

            $count++;

            if ($count % $batch_size == 0) {
                insertBatch($conn, $rows_to_insert);
                $rows_to_insert = [];
                $conn->commit();
                $conn->beginTransaction();
            }
        }

        if (!empty($rows_to_insert)) {
            insertBatch($conn, $rows_to_insert);
        }

        $conn->commit();
        fclose($file);
        header("Location: received_summary.php?import=success&count=$count");
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        die("Import Error at Row " . ($count + 1) . ": " . $e->getMessage());
    }
}

function insertBatch($pdo, $data) {
    if (empty($data)) return;
    // Database has 11 columns
    $row_places = '(' . implode(',', array_fill(0, 11, '?')) . ')';
    $all_places = implode(',', array_fill(0, count($data), $row_places));
    $sql = "INSERT INTO received_history (received_date, rr_number, supplier, item_name, specification, um, qty, price, amount, department, purpose) VALUES $all_places";
    $stmt = $pdo->prepare($sql);
    
    $values = [];
    foreach ($data as $row) { foreach ($row as $val) { $values[] = $val; } }
    $stmt->execute($values);
}
?>
