<?php
// 1. Enable error reporting to find the cause of the blank screen
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Maximize resources
ini_set('memory_limit', '512M');
set_time_limit(0); 
include 'db.php';

// Change: Check if a file was uploaded, regardless of the button name
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES["excel_file"])) {
    
    if ($_FILES["excel_file"]["error"] !== UPLOAD_ERR_OK) {
        die("Upload Error Code: " . $_FILES["excel_file"]["error"]);
    }

    $file = fopen($_FILES["excel_file"]["tmp_name"], "r");
    fgetcsv($file); // Skip header row

    try {
        $conn->beginTransaction();
        
        $count = 0;
        $batch_size = 500; // Lowered batch size slightly for better stability
        $rows_to_insert = [];

        // Pre-prepare Inventory Update
        $uStmt = $conn->prepare("UPDATE inventory SET specification = :spec, price = :price WHERE item_name = :name");

        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            // Basic validation: Skip rows without an item name
            if (empty($column[3])) continue;

            // CLEAN DATA
            $price = floatval(preg_replace('/[^0-9.]/', '', $column[7] ?? '0'));
            $qty   = floatval(preg_replace('/[^0-9.]/', '', $column[8] ?? '0'));
            $item  = trim($column[3]);
            
            // Fix Date
            $raw_date = trim($column[0]);
            if (empty($raw_date)) {
                $final_date = date('Y-m-d');
            } else {
                if (strpos($raw_date, '-') !== false && strlen($raw_date) > 10) { 
                    $raw_date = explode('-', $raw_date)[0]; 
                }
                $timestamp = strtotime(str_replace('/', '-', $raw_date));
                $final_date = $timestamp ? date('Y-m-d', $timestamp) : date('Y-m-d');
            }

            // Update Inventory Price/Spec
            $uStmt->execute([
                'spec'  => $column[4] ?? '', 
                'price' => $price, 
                'name'  => $item
            ]);

            // Add to History Batch array (ensuring 11 columns to match your helper function)
            $rows_to_insert[] = [
                $final_date,            // 0: received_date
                $column[1] ?? '',       // 1: rr_number
                $column[2] ?? '',       // 2: supplier
                $item,                  // 3: item_name
                $column[4] ?? '',       // 4: specification
                $column[5] ?? '',       // 5: um
                $qty,                   // 6: qty
                $price,                 // 7: price
                ($price * $qty),        // 8: amount
                $column[6] ?? '',       // 9: department
                $column[9] ?? ''        // 10: purpose
            ];

            $count++;

            // Batch Processing
            if ($count % $batch_size == 0) {
                insertBatch($conn, $rows_to_insert);
                $rows_to_insert = []; 
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
        
        // Success Redirect
        header("Location: received_summary.php?import=success&count=$count");
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        die("Import Error at row $count: " . $e->getMessage());
    }
} else {
    die("No file detected. Please ensure your form uses method='POST' and enctype='multipart/form-data'.");
}

// HELPER FUNCTION: Bulk Insert
function insertBatch($pdo, $data) {
    if (empty($data)) return;
    $row_places = '(' . implode(',', array_fill(0, 11, '?')) . ')';
    $all_places = implode(',', array_fill(0, count($data), $row_places));
    $sql = "INSERT INTO received_history (received_date, rr_number, supplier, item_name, specification, um, qty, price, amount, department, purpose) VALUES $all_places";
    $stmt = $pdo->prepare($sql);
    
    $values = [];
    foreach ($data as $row) { 
        foreach ($row as $val) { 
            $values[] = $val; 
        } 
    }
    $stmt->execute($values);
}
?>
