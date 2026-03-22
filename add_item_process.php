<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php'; // Correct connection file

if (isset($_POST['submit'])) {
    try {
        // 1. Capture and cast data
        $rdate      = $_POST['RDATE'] ?? date('Y-m-d');
        $item_name  = $_POST['item_name'] ?? '';
        $spec       = $_POST['Specification'] ?? '';
        $qty        = (float)($_POST['Qty'] ?? 0);   
        $price      = (float)($_POST['price'] ?? 0); 
        $total      = $qty * $price;
        
        $rr_number  = $_POST['rr_number'] ?? '';
        $supplier   = $_POST['supplier'] ?? '';
        $um         = $_POST['UM'] ?? '';            
        
        // Ensure department is saved as Uppercase to match your existing records (e.g., MECHANICAL)
        $dept       = strtoupper($_POST['Department'] ?? ''); 
        $purpose    = $_POST['Purpose'] ?? '';

        // 2. Insert into received_history using Prepared Statements
        $stmt_log = $conn->prepare("INSERT INTO received_history 
            (received_date, rr_number, supplier, item_name, specification, um, qty, department, purpose, amount) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt_log->execute([$rdate, $rr_number, $supplier, $item_name, $spec, $um, $qty, $dept, $purpose, $total]);

        // 3. Update or Insert into Inventory
        // Checking for existing item to update stock
        $stmt_check = $conn->prepare("SELECT id, qty FROM inventory WHERE item_name = ? AND specification = ? LIMIT 1");
        $stmt_check->execute([$item_name, $spec]);
        $row = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Update Existing: New Qty = Old Qty + Added Qty
            $new_qty = (float)$row['qty'] + $qty;
            $stmt_upd = $conn->prepare("UPDATE inventory SET qty = ?, price = ?, department = ? WHERE id = ?");
            $stmt_upd->execute([$new_qty, $price, $dept, $row['id']]);
        } else {
            // Insert New item with the text-based department
            $stmt_ins = $conn->prepare("INSERT INTO inventory 
                (rdate, rr_number, supplier, item_name, specification, um, qty, department, purpose, price, amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins->execute([$rdate, $rr_number, $supplier, $item_name, $spec, $um, $qty, $dept, $purpose, $price, $total]);
        }

        header("Location: index.php?success=1");
        exit();

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>
