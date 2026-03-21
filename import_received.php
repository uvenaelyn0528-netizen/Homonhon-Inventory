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
                // Map your CSV columns here (Example: Name, Spec, UM, Dept, Price)
                $item_name     = $column[0] ?? '';
                $specification = $column[1] ?? '';
                $um            = $column[2] ?? 'pcs';
                $department    = $column[3] ?? '';
                $price         = floatval($column[4] ?? 0);
                $qty_received  = intval($column[5] ?? 0);

                if (empty($item_name)) continue;

                // CHECK IF ITEM EXISTS (Including those previously "deleted")
                $checkSql = "SELECT id FROM inventory WHERE item_name = :name LIMIT 1";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute(['name' => $item_name]);
                $existingItem = $checkStmt->fetch();

                if ($existingItem) {
                    // UPDATE existing item and bring it back to life (is_deleted = FALSE)
                    $updateSql = "UPDATE inventory 
                                  SET specification = :spec, 
                                      price = :price, 
                                      is_deleted = FALSE 
                                  WHERE id = :id";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->execute([
                        'spec'  => $specification,
                        'price' => $price,
                        'id'    => $existingItem['id']
                    ]);
                    $current_id = $existingItem['id'];
                } else {
                    // INSERT new item
                    $insertSql = "INSERT INTO inventory (item_name, specification, um, department, price, is_deleted) 
                                  VALUES (:name, :spec, :um, :dept, :price, FALSE) RETURNING id";
                    $insertStmt = $conn->prepare($insertSql);
                    $insertStmt->execute([
                        'name'  => $item_name,
                        'spec'  => $specification,
                        'um'    => $um,
                        'dept'  => $department,
                        'price' => $price
                    ]);
                    $result = $insertStmt->fetch();
                    $current_id = $result['id'];
                }

                // LOG THE RECEIPT in received_history so your 'Total Received' column updates
                $historySql = "INSERT INTO received_history (item_name, qty, date_received) 
                               VALUES (:name, :qty, NOW())";
                $historyStmt = $conn->prepare($historySql);
                $historyStmt->execute([
                    'name' => $item_name,
                    'qty'  => $qty_received
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
