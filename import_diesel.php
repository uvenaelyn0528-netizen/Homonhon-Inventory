<?php
include 'db.php';

if (isset($_POST['import'])) {
    $fileName = $_FILES["csv_file"]["tmp_name"];

    if ($_FILES["csv_file"]["size"] > 0) {
        $file = fopen($fileName, "r");
        
        // Skip the header row
        fgetcsv($file);

        try {
            $conn->beginTransaction();

            $sql = "INSERT INTO diesel_inventory (
                        date_time, activity, received_from, rr_no, 
                        ws_no, withdrawn_from, deposited_to, qty_l
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                // Mapping CSV columns based on your Excel structure:
                // A: Date/Time, B: Activity, C: Received From, D: RR No.
                // E: WS No., F: Withdrawn From, G: Deposited To, H: QTY (L)
                
                $dateTime      = !empty($column[0]) ? date('Y-m-d H:i:s', strtotime($column[0])) : null;
                $activity      = $column[1] ?? '';
                $receivedFrom  = $column[2] ?? '';
                $rrNo          = $column[3] ?? '';
                $wsNo          = $column[4] ?? '';
                $withdrawnFrom = $column[5] ?? '';
                $depositedTo   = $column[6] ?? '';
                $qty           = (float)($column[7] ?? 0);

                $stmt->execute([
                    $dateTime, $activity, $receivedFrom, $rrNo, 
                    $wsNo, $withdrawnFrom, $depositedTo, $qty
                ]);
            }

            $conn->commit();
            header("Location: diesel_inventory.php?import=success");
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            die("Error importing data: " . $e->getMessage());
        }
    }
}
?>

<form action="import_diesel.php" method="post" enctype="multipart/form-data">
    <label>Select Diesel CSV File:</label>
    <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit" name="import">Upload and Import</button>
</form>
