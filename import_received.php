<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

if (isset($_POST['submit_import'])) {
    if (isset($_FILES["excel_file"]) && $_FILES["excel_file"]["error"] == 0) {
        $filename = $_FILES["excel_file"]["tmp_name"];
        $file = fopen($filename, "r");

        fgetcsv($file); // Skip header row

        $count = 0;

        try {
            // 1. Prepare statements OUTSIDE the loop for better performance
            $stmt_log = $conn->prepare("INSERT INTO received_history 
                (item_name, specification, um, qty, department, purpose, received_date, rr_number, supplier) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt_check = $conn->prepare("SELECT id FROM inventory WHERE item_name = ? AND specification = ? LIMIT 1");

            $stmt_update = $conn->prepare("UPDATE inventory SET rr_number = ?, rdate = ? WHERE id = ?");

            $stmt_insert = $conn->prepare("INSERT INTO inventory 
                (item_name, specification, um, department, rdate, rr_number) 
                VALUES (?, ?, ?, ?, ?, ?)");

            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                if (empty($column[0])) continue;

                // Mapping columns - No more mysqli_real_escape_string needed!
                $item_name     = trim($column[0]);
                $specification = trim($column[1]);
                $um            = trim($column[2]);
                $qty           = trim($column[3]);
                $department    = trim($column[4]);
                $purpose       = trim($column[5]);
                $raw_date      = trim($column[6]);
                $rr_number     = trim($column[7]);
                $supplier      = trim($column[8]);

                $received_date = date('Y-m-d', strtotime($raw_date));

                // 2. Execute History Log
                $stmt_log->execute([
                    $item_name, $specification, $um, $qty, $department, 
                    $purpose, $received_date, $rr_number, $supplier
                ]);

                // 3. Update or Insert into Inventory
                $stmt_check->execute([$item_name, $specification]);
                $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $stmt_update->execute([$rr_number, $received_date, $existing['id']]);
                } else {
                    $stmt_insert->execute([
                        $item_name, $specification, $um, $department, 
                        $received_date, $rr_number
                    ]);
                }
                $count++;
            }
            
            fclose($file);
            header("Location: received_summary.php?msg=success&count=$count");
            exit();

        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    } else {
        echo "<script>alert('Error: No file selected or file too large.'); window.location.href='import_received.php';</script>";
    }
}
?>
