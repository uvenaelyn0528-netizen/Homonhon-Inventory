<?php 
session_start();
include 'db.php';

// 1. Check Access: Only Admin or Staff allowed
$role = strtolower(trim($_SESSION['role'] ?? 'viewer'));
if ($role !== 'admin' && $role !== 'staff') {
    header("Location: received_summary.php?error=Access Denied");
    exit();
}

// 2. Fetch Record
$id = $_GET['id'] ?? null;
if (!$id) { header("Location: received_summary.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM received_history WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) { echo "Record not found."; exit(); }

// 3. Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE received_history SET 
            received_date = ?, rr_number = ?, supplier = ?, 
            item_name = ?, specification = ?, um = ?, 
            qty = ?, price = ?, amount = ?, 
            department = ?, purpose = ? 
            WHERE id = ?";
    
    $amount = (float)$_POST['qty'] * (float)$_POST['price'];
    
    $update_stmt = $conn->prepare($sql);
    $result = $update_stmt->execute([
        $_POST['received_date'], $_POST['rr_number'], $_POST['supplier'],
        $_POST['item_name'], $_POST['specification'], $_POST['um'],
        $_POST['qty'], $_POST['price'], $amount,
        $_POST['department'], $_POST['purpose'], $id
    ]);

    if ($result) {
        header("Location: received_summary.php?msg=Updated Successfully");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Received Item - Goldrich</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 40px; }
        .edit-card { background: white; max-width: 600px; margin: auto; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); border-top: 5px solid darkred; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; font-size: 13px; color: #34495e; margin-bottom: 5px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-save { background: #27ae60; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
        .btn-cancel { display: block; text-align: center; color: #7f8c8d; text-decoration: none; margin-top: 15px; font-size: 13px; }
    </style>
</head>
<body>

<div class="edit-card">
    <h2 style="color: darkred; margin-top: 0; font-family: Broadway;">EDIT RECEIVED ENTRY</h2>
    <form method="POST">
        <div class="form-group">
            <label>Date Received</label>
            <input type="date" name="received_date" value="<?= $row['received_date'] ?>" required>
        </div>
        <div class="form-group">
            <label>RR Number</label>
            <input type="text" name="rr_number" value="<?= htmlspecialchars($row['rr_number']) ?>">
        </div>
        <div class="form-group">
            <label>Supplier</label>
            <input type="text" name="supplier" value="<?= htmlspecialchars($row['supplier']) ?>">
        </div>
        <div class="form-group">
            <label>Item Name</label>
            <input type="text" name="item_name" value="<?= htmlspecialchars($row['item_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Specification</label>
            <input type="text" name="specification" value="<?= htmlspecialchars($row['specification']) ?>">
        </div>
        <div style="display: flex; gap: 10px;">
            <div class="form-group" style="flex:1;">
                <label>Unit (UM)</label>
                <input type="text" name="um" value="<?= htmlspecialchars($row['um']) ?>">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Quantity</label>
                <input type="number" step="0.01" name="qty" value="<?= $row['qty'] ?>" required>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Price</label>
                <input type="number" step="0.01" name="price" value="<?= $row['price'] ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" value="<?= htmlspecialchars($row['department']) ?>">
        </div>
        <div class="form-group">
            <label>Purpose</label>
            <input type="text" name="purpose" value="<?= htmlspecialchars($row['purpose']) ?>">
        </div>
        
        <button type="submit" class="btn-save">Update Record</button>
        <a href="received_summary.php" class="btn-cancel">Cancel and Go Back</a>
    </form>
</div>

</body>
</html>
