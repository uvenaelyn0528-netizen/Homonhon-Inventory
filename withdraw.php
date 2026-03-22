<?php
include 'db.php';

if (isset($_POST['id']) && isset($_POST['withdraw_qty'])) {
    $id = intval($_POST['id']);
    $qty_to_withdraw = (int)$_POST['withdraw_qty'];
    
    // Ensure department is treated as a string and not a numeric ID
    $dept = isset($_POST['Department']) ? strval($_POST['Department']) : 'UNASSIGNED';
    $purpose = $_POST['Purpose'] ?? '';

    try {
        $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        // Standardizing column access for PostgreSQL compatibility
        $current_qty = $item['qty'] ?? $item['Qty'] ?? 0;

        if ($item && $current_qty >= $qty_to_withdraw) {

            // 1. Reduce stock in inventory
            $update = $conn->prepare("UPDATE inventory SET qty = qty - :withdraw WHERE id = :id");
            $update->execute(['withdraw' => $qty_to_withdraw, 'id' => $id]);

            // 2. Prepare data for withdrawals log
            $name = $item['item_name'] ?? '';
            $spec = $item['specification'] ?? $item['Specification'] ?? '';
            $um = $item['um'] ?? $item['UM'] ?? '';
            
            // Get the unit price
            $unit_price = $item['price'] ?? $item['Price'] ?? 0;
            
            // Note: Ensure your 'withdrawals' table has these EXACT column names in Supabase
            $sql = "INSERT INTO withdrawals (item_name, specification, um, qty, department, purpose, amount) 
                    VALUES (:name, :spec, :um, :qty, :dept, :purpose, :amount)";
            
            $insert = $conn->prepare($sql);
            $success = $insert->execute([
                'name'    => $name,
                'spec'    => $spec,
                'um'      => $um,
                'qty'     => $qty_to_withdraw,
                'dept'    => $dept, // Passed as string per requirement
                'purpose' => $purpose,
                'amount'  => $unit_price // Recording the price at time of withdrawal
            ]);

            if($success) {
                header("Location: index.php?status=success");
                exit();
            } else {
                echo "Error: Could not log the withdrawal. Check if columns exist in Supabase.";
            }
        } else {
            echo "<script>alert('Error: Insufficient stock (Available: $current_qty)'); window.location='index.php';</script>";
        }
    } catch (PDOException $e) {
        // Helpful for debugging the 'Undefined Column' errors seen earlier
        echo "Database Error: " . htmlspecialchars($e->getMessage());
    }
}
?>
