<?php
include 'db.php';

if (isset($_POST['submit_inventory_import'])) {
    $filename = $_FILES["inventory_file"]["tmp_name"];

    if ($_FILES["inventory_file"]["size"] > 0) {
        $file = fopen($filename, "r");
        $count = 0;

        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            // Mapping for Inventory Master List
            $item_name     = mysqli_real_escape_string($conn, trim($column[0]));
            $specification = mysqli_real_escape_string($conn, trim($column[1]));
            $um            = mysqli_real_escape_string($conn, trim($column[2]));
            $department    = mysqli_real_escape_string($conn, trim($column[3]));

            // Check if item already exists to avoid duplicates
            $check = mysqli_query($conn, "SELECT * FROM inventory WHERE item_name = '$item_name' AND Specification = '$specification'");

            if (mysqli_num_rows($check) > 0) {
                // Update existing item details
                $sql = "UPDATE inventory SET UM = '$um', Department = '$department' 
                        WHERE item_name = '$item_name' AND Specification = '$specification'";
            } else {
                // Insert as new item
                $sql = "INSERT INTO inventory (item_name, Specification, UM, Department) 
                        VALUES ('$item_name', '$specification', '$um', '$department')";
            }
            
            if(mysqli_query($conn, $sql)) { $count++; }
        }
        fclose($file);
        header("Location: index.php?msg=success&count=$count");
        exit();
    }
}
?>

<div style="font-family: sans-serif; padding: 30px; border: 2px dashed #3498db; border-radius: 15px; max-width: 450px; margin: 50px auto; text-align: center; background: #f9f9f9;">
    <h2 style="color: #2c3e50;">📦 Import Materials List</h2>
    <p style="color: #7f8c8d; font-size: 14px;">Upload your Excel/CSV Master List</p>
    
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="inventory_file" accept=".csv" required style="margin: 20px 0; display: block; width: 100%;">
        <button type="submit" name="submit_inventory_import" style="background: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%;">
            Upload to Inventory
        </button>
    </form>
    <br>
    <a href="index.php" style="color: #95a5a6; text-decoration: none; font-size: 13px;">← Back to Dashboard</a>
</div>