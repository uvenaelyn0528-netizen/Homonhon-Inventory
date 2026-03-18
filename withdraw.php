<?php
include 'db.php';

if (isset($_POST['id']) && isset($_POST['withdraw_qty'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $qty_to_withdraw = (int)$_POST['withdraw_qty'];
    $dept = $conn->real_escape_string($_POST['Department']);
    $purpose = $conn->real_escape_string($_POST['Purpose']);

    // 1. Fetch original item details to populate the withdrawal log
    $res = $conn->query("SELECT * FROM inventory WHERE id = $id");
    $item = $res->fetch_assoc();

    if ($item && $item['Qty'] >= $qty_to_withdraw) {

        // 2. Reduce the stock in inventory table
        $conn->query("UPDATE inventory SET Qty = Qty - $qty_to_withdraw WHERE id = $id");

        // 3. Insert into your NEW withdrawals table structure
        $name = $conn->real_escape_string($item['item_name']);
        $spec = $conn->real_escape_string($item['Specification']);
        $um = $conn->real_escape_string($item['UM']);
        $amount = $item['Amount']; // Transfers the unit price/amount

        $sql = "INSERT INTO withdrawals (item_name, Specification, UM, QTY, Department, Purpose, Amoun) 
                VALUES ('$name', '$spec', '$um', '$qty_to_withdraw', '$dept', '$purpose', '$amount')";
        
        if($conn->query($sql)) {
            header("Location: index.php?status=success");
        } else {
            echo "Error logging withdrawal: " . $conn->error;
        }
    } else {
        echo "<script>alert('Error: Insufficient stock!'); window.location='index.php';</script>";
    }
}
?>