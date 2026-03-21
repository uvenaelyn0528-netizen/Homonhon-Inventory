<?php
// 1. Increase resources to prevent 502 Timeout on Render
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '512M'); // Increased memory
set_time_limit(600); // 10 minutes for very large files

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? 'Viewer';
if (strtolower($role) === 'viewer') {
    die("Access Denied.");
}

if (isset($_POST['import_btn'])) {
    if (isset($_FILES["excel_file"]) && $_FILES["excel_file"]["size"] > 0) {
        $fileName = $_FILES["excel_file"]["tmp_name"];
        $import_count = 0;
        $file = fopen($fileName, "r");
        fgetcsv($file); // Skip header

        try {
            $conn->beginTransaction();

            $checkStmt = $conn->prepare("SELECT id FROM inventory WHERE item_name = :name LIMIT 1");
            $uStmt = $conn->prepare("UPDATE inventory SET specification = :spec, price = :price, is_deleted = FALSE WHERE id = :id");
            $iStmt = $conn->prepare("INSERT INTO inventory (item_name, specification, um, department, price, is_deleted) VALUES (:name, :spec, :um, :dept, :price, FALSE)");
            
            $historyStmt = $conn->prepare("INSERT INTO received_history 
                           (received_date, rr_number, supplier, item_name, specification, um, qty, price, amount, department, purpose) 
                           VALUES (:rdate, :rr, :supp, :name, :spec, :um, :qty, :price, :amount, :dept, :purpose)");

            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                if (empty($column[3])) continue; // Skip if item name is empty

                // --- 1. DATE CLEANER ---
                $raw_date = trim($column[0]);
                if (strpos($raw_date, '-') !== false) {
                    $parts = explode('-', $raw_date);
                    $raw_date = trim($parts[0]);
                }
                $clean_date = str_replace('/', '-', $raw_date);
                $final_date = date('Y-m-d', strtotime($clean_date));
                if (!$final_date || $final_date == '1970-01-01') { $final_date = date('Y-m-d'); }

                // --- 2. NUMERIC CLEANER (Fixes the ₱0.00 issue) ---
                // Removes commas, peso signs, and spaces so "1,250.50" becomes "1250.50"
                $raw_price = preg_replace('/[^\d.]/', '', $column[7] ?? '0');
                $price = floatval($raw_price);
                
                $raw_qty = preg_replace('/[^\d.]/', '', $column[8] ?? '0');
                $qty_received = floatval($raw_qty);
                
                $total_amount = $price * $qty_received;

                // --- 3. DATABASE LOGIC ---
                $item_name = trim($column[3]);
                $checkStmt->execute(['name' => $item_name]);
                $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($existingItem) {
                    $uStmt->execute(['spec' => $column[4] ?? '', 'price' => $price, 'id' => $existingItem['id']]);
                } else {
                    $iStmt->execute([
                        'name' => $item_name, 
                        'spec' => $column[4] ?? '', 
                        'um' => $column[5] ?? 'pcs', 
                        'dept' => $column[6] ?? '', 
                        'price' => $price
                    ]);
                }

                $historyStmt->execute([
                    'rdate'   => $final_date,
                    'rr'      => $column[1] ?? '',
                    'supp'    => $column[2] ?? '',
                    'name'    => $item_name,
                    'spec'    => $column[4] ?? '',
                    'um'      => $column[5] ?? 'pcs',
                    'qty'     => $qty_received,
                    'price'   => $price,
                    'amount'  => $total_amount,
                    'dept'    => $column[6] ?? '',
                    'purpose' => $column[9] ?? ''
                ]);

                $import_count++;
            }

            $conn->commit();
            fclose($file);
            header("Location: received_summary.php?import=success&count=" . $import_count);
            exit();

        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            die("Import Error: " . $e->getMessage());
        }
    }
}
?>
