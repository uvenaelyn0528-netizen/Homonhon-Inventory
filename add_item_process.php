<?php
// Force error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php'; // Your database connection file

if (isset($_POST['submit'])) {
    // Check if data is actually arriving
    // print_r($_POST); exit; // Uncomment this line to test if data reaches PHP
    
    $item_name = $_POST['item_name'];
    // ... rest of your variables
}
?>
<?php
include 'db.php';
if (isset($_POST['submit'])) {
    $rdate = $_POST['RDATE'];
    $item_name = $_POST['item_name'];
    $spec = $_POST['Specification'];
    $qty = (int)$_POST['Qty'];
    $price = (float)$_POST['price'];
    $total = $qty * $price;

    // Insert History
    $conn->query("INSERT INTO received_history (received_date, rr_number, supplier, item_name, Specification, UM, Qty, Department, Purpose, Amount) 
                  VALUES ('$rdate', '{$_POST['rr_number']}', '{$_POST['supplier']}', '$item_name', '$spec', '{$_POST['UM']}', '$qty', '{$_POST['Department']}', '{$_POST['Purpose']}', '$total')");

    // Update Inventory
    $check = $conn->query("SELECT id, Qty FROM inventory WHERE item_name='$item_name' AND Specification='$spec' LIMIT 1");
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $new_qty = $row['Qty'] + $qty;
        $conn->query("UPDATE inventory SET Qty='$new_qty', price='$price' WHERE id=".$row['id']);
    } else {
        $conn->query("INSERT INTO inventory (RDATE, rr_number, supplier, item_name, Specification, UM, Qty, Department, Purpose, price, Amount) 
                      VALUES ('$rdate', '{$_POST['rr_number']}', '{$_POST['supplier']}', '$item_name', '$spec', '{$_POST['UM']}', '$qty', '{$_POST['Department']}', '{$_POST['Purpose']}', '$price', '$total')");
    }
    header("Location: index.php?success=1");
}
?>