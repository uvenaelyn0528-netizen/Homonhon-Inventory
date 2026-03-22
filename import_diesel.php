<?php
include 'db.php';

$message = "";
if (isset($_POST['import'])) {
    $fileName = $_FILES["csv_file"]["tmp_name"];

    if ($_FILES["csv_file"]["size"] > 0) {
        $file = fopen($fileName, "r");
        fgetcsv($file); // Skip header

        try {
            $conn->beginTransaction();

            // Updated column name to 'rdate' - common in your other tables
            // If your table uses 'date', change 'rdate' below to 'date'
            $sql = "INSERT INTO diesel_inventory (
                        rdate, activity, received_from, rr_no, 
                        ws_no, withdrawn_from, deposited_to, qty_l
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                $dateTime      = !empty($column[0]) ? date('Y-m-d H:i:s', strtotime($column[0])) : date('Y-m-d H:i:s');
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
            $message = "<div class='alert success'>✅ Data Imported Successfully!</div>";
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "<div class='alert error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Diesel Data</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 40px; }
        .container { max-width: 500px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #8B0000; }
        h2 { color: #8B0000; text-align: center; margin-top: 0; text-transform: uppercase; letter-spacing: 1px; }
        .upload-box { border: 2px dashed #ccc; padding: 30px; text-align: center; border-radius: 6px; cursor: pointer; transition: 0.3s; background: #fafafa; }
        .upload-box:hover { border-color: #8B0000; background: #fff5f5; }
        input[type="file"] { margin-bottom: 20px; }
        .btn-import { background: #8B0000; color: white; border: none; padding: 12px 25px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s; }
        .btn-import:hover { background: #a00000; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #666; text-decoration: none; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <h2>Diesel CSV Import</h2>
    
    <?php echo $message; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="upload-box" onclick="document.getElementById('file-input').click();">
            <p>Click to select or drag CSV here</p>
            <input type="file" name="csv_file" id="file-input" accept=".csv" required>
        </div>
        <br>
        <button type="submit" name="import" class="btn-import">UPLOAD AND IMPORT</button>
    </form>

    <a href="index.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>
