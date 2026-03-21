<?php
// 1. Enable error reporting to find why the screen was blank
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Only Admin/Staff should be able to import
$role = $_SESSION['role'] ?? 'Viewer';
if ($role === 'Viewer') {
    die("Access Denied: You do not have permission to import data.");
}

if (isset($_POST['import_btn'])) {
    $fileName = $_FILES["excel_file"]["tmp_name"];

    if ($_FILES["excel_file"]["size"] > 0) {
        $file = fopen($fileName, "r");
        
        // Skip the header row of your CSV
        fgetcsv($file); 

        try {
            $conn->beginTransaction();

           while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                // Mapping all 10 columns from your CSV
                $received_date  = !empty($column[0]) ? $column[0] : date('Y-m-d'); // Uses today if empty
                $rr_number      = $column[1] ?? '';
                $supplier       = $column[2] ?? '';
                $item_name      = $column[3] ?? '';
                $specification  = $column[4] ?? '';
                $um             = $column[5] ?? 'pcs';
                $department     = $column[6] ?? '';
                $price          = floatval($column[7] ?? 0);
                $qty_received   = floatval($column[8] ?? 0);
                $purpose        = $column[9] ?? '';
                
                $total_amount   = $price * $qty_received;

                if (empty($item_name)) continue;

                // 1. UPDATE/INSERT MAIN INVENTORY
                $checkStmt = $conn->prepare("SELECT id FROM inventory WHERE item_name = :name LIMIT 1");
                $checkStmt->execute(['name' => $item_name]);
                $existingItem = $checkStmt->fetch();

                if ($existingItem) {
                    $uStmt = $conn->prepare("UPDATE inventory SET specification = :spec, price = :price, is_deleted = FALSE WHERE id = :id");
                    $uStmt->execute(['spec' => $specification, 'price' => $price, 'id' => $existingItem['id']]);
                } else {
                    $iStmt = $conn->prepare("INSERT INTO inventory (item_name, specification, um, department, price, is_deleted) VALUES (:name, :spec, :um, :dept, :price, FALSE)");
                    $iStmt->execute(['name' => $item_name, 'spec' => $specification, 'um' => $um, 'dept' => $department, 'price' => $price]);
                }

                // 2. INSERT INTO RECEIVED HISTORY (The Inflow Report)
                $historySql = "INSERT INTO received_history 
                               (received_date, rr_number, supplier, item_name, specification, um, qty, price, amount, department, purpose) 
                               VALUES (:rdate, :rr, :supp, :name, :spec, :um, :qty, :price, :amount, :dept, :purpose)";
                
                $historyStmt = $conn->prepare($historySql);
                $historyStmt->execute([
                    'rdate'   => $received_date,
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
            }

            $conn->commit();
            header("Location: index.php?import=success");
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            die("Error during import: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import Inventory</title>
    <link rel="stylesheet" href="style.css"> </head>
<body style="font-family: sans-serif; padding: 20px;">
    <h2>📥 Import Received Items (CSV)</h2>
    <p>Upload a CSV file with columns: Name, Specification, UM, Department, Price, Qty</p>
    
    <form action="" method="post" enctype="multipart/form-data" style="background: #f4f4f4; padding: 20px; border-radius: 8px;">
        <input type="file" name="excel_file" accept=".csv" required>
        <br><br>
        <button type="submit" name="import_btn" style="background: #2ecc71; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
            Start Import
        </button>
        <a href="index.php" style="margin-left: 10px; text-decoration: none; color: #666;">Cancel</a>
    </form>
</body>
</html>
