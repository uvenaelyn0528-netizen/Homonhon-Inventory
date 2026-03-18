<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM inventory WHERE id = $id");
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        echo "<script>alert('Item not found!'); window.location='index.php';</script>";
        exit;
    }
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $item_name = $_POST['item_name'];
    $spec = $_POST['Specification'];
    $um = $_POST['UM'];
    $dept = $_POST['Department'];
    $price = (float)$_POST['price'];
    $qty = (int)$_POST['Qty'];
    
    $amount = $qty * $price;

    $sql = "UPDATE inventory SET 
            item_name='$item_name', 
            Specification='$spec', 
            UM='$um', 
            Department='$dept', 
            price='$price', 
            Amount='$amount',
            Qty='$qty' 
            WHERE id=$id";

    if ($conn->query($sql)) {
        header("Location: index.php?updated=1");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Item | Warehouse System</title>
    <link rel="stylesheet" href="style.css"> <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .edit-card {
            background: #fff;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-top: 5px solid #3498db;
        }
        .edit-card h2 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 24px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box; /* Mahalaga para sa width */
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
        }
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        .btn-update {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            flex: 2;
            transition: background 0.3s;
        }
        .btn-update:hover {
            background: #2980b9;
        }
        .btn-cancel {
            background: #95a5a6;
            color: white;
            text-decoration: none;
            text-align: center;
            padding: 12px 20px;
            border-radius: 6px;
            flex: 1;
            font-size: 14px;
            font-weight: bold;
        }
        .btn-cancel:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>

<div class="edit-card">
    <h2>Edit Item Details</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

        <div class="form-group">
            <label>Item Name</label>
            <input type="text" name="item_name" value="<?php echo $row['item_name']; ?>" required>
        </div>

        <div class="form-group">
            <label>Specification</label>
            <input type="text" name="Specification" value="<?php echo $row['Specification']; ?>" required>
        </div>

        <div style="display: flex; gap: 10px;">
            <div class="form-group" style="flex: 1;">
                <label>Unit of Measure</label>
                <input type="text" name="UM" value="<?php echo $row['UM']; ?>" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Current Qty</label>
                <input type="number" name="Qty" value="<?php echo $row['Qty']; ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Department</label>
            <select name="Department" required>
                <?php 
                $depts = ["Admin", "Engineering", "Warehouse", "Safety", "TSG", "Envi", "Nursery", "Mechanical", "Comrel", "Assay", "Port Operation", "Mine Operation"];
                foreach($depts as $d) {
                    $selected = ($row['Department'] == $d) ? "selected" : "";
                    echo "<option value='$d' $selected>$d</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Price per Unit (₱)</label>
            <input type="number" step="0.01" name="price" value="<?php echo $row['price']; ?>" required>
        </div>

        <div class="btn-container">
            <button type="submit" name="update" class="btn-update">Save Changes</button>
            <a href="index.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>