<?php
// 1. Increase resources to prevent 502 Timeout on Render
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '256M');
set_time_limit(300); 

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Only Admin/Staff should be able to import
$role = $_SESSION['role'] ?? 'Viewer';
if (strtolower($role) === 'viewer') {
    die("Access Denied: You do not have permission to import data.");
}

if (isset($_POST['import_btn'])) {
    if (isset($_FILES["excel_file"]) && $_FILES["excel_file"]["size"] > 0) {
        $fileName = $_FILES["excel_file"]["tmp_name"];
        $import_count = 0;
        
        $file = fopen($fileName, "r");
        
        // Skip the header row
        fgetcsv($file); 

        try {
            $conn->beginTransaction();

            $checkStmt = $conn->prepare("SELECT id FROM inventory WHERE item_name = :name LIMIT 1");
            $uStmt = $conn->prepare("UPDATE inventory SET specification = :spec, price = :price, is_deleted = FALSE WHERE id = :id");
            $iStmt = $conn->prepare("INSERT INTO inventory (item_name, specification, um, department, price, is_deleted) VALUES (:name, :spec, :um, :dept, :price, FALSE)");
            
            $historySql = "INSERT INTO received_history 
                           (received_date, rr_number, supplier, item_name, specification, um, qty, price, amount, department, purpose) 
                           VALUES (:rdate, :rr, :supp, :name, :spec, :um, :qty, :price, :amount, :dept, :purpose)";
            $historyStmt = $conn->prepare($historySql);

            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                if (empty($column[1]) && empty($column[3])) {
                    break; 
                }

                // --- DATE CLEANING LOGIC ---
                $raw_date = trim($column[0]);
                
                // If the date is a range (contains a hyphen), take the first date only
                if (strpos($raw_date, '-') !== false) {
                    $date_parts = explode('-', $raw_date);
                    $raw_date = trim($date_parts[0]);
                }

                // Convert DD/MM/YYYY or DD-MM-YYYY to YYYY-MM-DD
                $clean_date = str_replace('/', '-', $raw_date);
                $final_date = date('Y-m-d', strtotime($clean_date));

                // If conversion fails, use today's date as a fallback
                if (!$final_date || $final_date == '1970-01-01') {
                    $final_date = date('Y-m-d');
                }
                // ---------------------------

                $rr_number      = $column[1] ?? '';
                $supplier       = $column[2] ?? '';
                $item_name      = $column[3] ?? '';
                $specification  = $column[4] ?? '';
                $um             = $column[5] ?? 'pcs';
                $department     = $column[6] ?? '';
                $price = floatval(preg_replace('/[^0-9.]/', '', $column[7] ?? '0'));
$qty_received = floatval(preg_replace('/[^0-9.]/', '', $column[8] ?? '0'));
                $purpose        = $column[9] ?? '';
                
           $total_amount = $price * $qty_received;

                if (empty($item_name)) continue;

                $checkStmt->execute(['name' => $item_name]);
                $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($existingItem) {
                    $uStmt->execute(['spec' => $specification, 'price' => $price, 'id' => $existingItem['id']]);
                } else {
                    $iStmt->execute(['name' => $item_name, 'spec' => $specification, 'um' => $um, 'dept' => $department, 'price' => $price]);
                }

                $historyStmt->execute([
                    'rdate'   => $final_date, // Used cleaned date here
                    'rr'      => $rr_number,
                    'supp'    => $supplier,
                    'name'    => $item_name,
                    'spec'    => $specification,
                    'um'      => $um,
                    'qty'     => $qty_received,
                    'price'   => $price,
                    'amount'  => $total_amount,
                    'dept'    => $department,
                    'purpose' => $purpose
                ]);

                $import_count++;
            }

            $conn->commit();
            fclose($file);
            header("Location: received_summary.php?import=success&count=" . $import_count);
            exit();

        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            if (isset($file)) fclose($file);
            die("Error during import: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Inventory</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">

    <div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px;">
        <h2 style="color: #112941; margin-top: 0;">📥 Import Received Items</h2>
        
        <div style="background: #ebf5fb; padding: 15px; border-radius: 8px; border: 1px dashed #2980b9; margin-bottom: 20px;">
            <p style="font-size: 13px; color: #34495e; margin-bottom: 10px;">Ensure your CSV matches our system requirements:</p>
            <a href="download_template.php" style="display: inline-block; background: #2980b9; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: bold;">
                📂 Download CSV Template
            </a>
        </div>
        
        <form action="" method="post" enctype="multipart/form-data">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #7f8c8d; margin-bottom: 8px;">SELECT CSV FILE</label>
            <input type="file" name="excel_file" accept=".csv" required style="width: 100%; padding: 10px; border: 1px solid #dcdde1; border-radius: 5px; margin-bottom: 20px;">
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="import_btn" style="flex: 2; background: #27ae60; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer;">
                    🚀 Start Import
                </button>
                <a href="received_summary.php" style="flex: 1; text-align: center; background: #95a5a6; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 13px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>

</body>
</html>
