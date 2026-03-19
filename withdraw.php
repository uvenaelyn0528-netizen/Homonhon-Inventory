<?php
include 'db.php';

if (isset($_POST['id']) && isset($_POST['withdraw_qty'])) {
    $id = intval($_POST['id']);
    $qty_to_withdraw = (int)$_POST['withdraw_qty'];
    $dept = $_POST['Department'] ?? '';
    $purpose = $_POST['Purpose'] ?? '';

    try {
        // 1. Fetch original item details (Case-insensitive check for column names)
        $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check for 'Qty' or 'qty' (Supabase/PostgreSQL is case-sensitive)
        $current_qty = $item['qty'] ?? $item['Qty'] ?? 0;

        if ($item && $current_qty >= $qty_to_withdraw) {

            // 2. Reduce the stock in inventory table
            // We use the lowercase 'qty' to match standard PostgreSQL naming
            $update = $conn->prepare("UPDATE inventory SET qty = qty - :withdraw WHERE id = :id");
            $update->execute(['withdraw' => $qty_to_withdraw, 'id' => $id]);

            // 3. Insert into withdrawals table
            $name = $item['item_name'] ?? '';
            $spec = $item['specification'] ?? $item['Specification'] ?? '';
            $um = $item['um'] ?? $item['UM'] ?? '';
            $price = $item['price'] ?? $item['Price'] ?? $item['Amount'] ?? 0;

            // Note: Fixed 'Amoun' typo to 'amount' and used lowercase for PostgreSQL compatibility
            $sql = "INSERT INTO withdrawals (item_name, specification, um, qty, department, purpose, amount) 
                    VALUES (:name, :spec, :um, :qty, :dept, :purpose, :amount)";
            
            $insert = $conn->prepare($sql);
            $success = $insert->execute([
                'name'    => $name,
                'spec'    => $spec,
                'um'      => $um,
                'qty'     => $qty_to_withdraw,
                'dept'    => $dept,
                'purpose' => $purpose,
                'amount'  => $price
            ]);

            if($success) {
                header("Location: index.php?status=success");
                exit();
            } else {
                echo "Error logging withdrawal.";
            }
        } else {
            echo "<script>alert('Error: Insufficient stock!'); window.location='index.php';</script>";
        }
    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
}
?>
