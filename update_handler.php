<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- PART 1: UPDATE MAIN INVENTORY (Admin only) ---
if (isset($_POST['update_item'])) {
    // Basic Security Check
    if ($_SESSION['role'] !== 'Admin') {
        die("Unauthorized access.");
    }

    $id = $_POST['item_id'];
    $name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $spec = mysqli_real_escape_string($conn, $_POST['specification']);
    $min = (int)$_POST['min_stock'];
    $max = (int)$_POST['max_stock'];

    $sql = "UPDATE inventory SET 
            item_name = '$name', 
            Specification = '$spec', 
            min_stock = $min, 
            max_stock = $max 
            WHERE id = $id";

    if ($conn->query($sql)) {
        header("Location: index.php?msg=inventory_updated");
        exit();
    } else {
        echo "Error updating inventory: " . $conn->error;
    }
}

// --- PART 2: UPDATE RECEIVED SUMMARY HISTORY ---
if (isset($_POST['update_summary'])) {
    $id = $_POST['id'];
    $rr = mysqli_real_escape_string($conn, $_POST['rr_number']);
    
    // Captured from the <input name="RDATE"> in your Received Summary edit modal
    $rdate = $_POST['RDATE']; 
    $qty = (int)$_POST['Qty'];

    // Updates the history table
    $sql = "UPDATE received_history SET 
            rr_number = '$rr', 
            received_date = '$rdate', 
            Qty = '$qty' 
            WHERE id = '$id'";

    if ($conn->query($sql)) {
        header("Location: received_summary.php?msg=success");
        exit();
    } else {
        echo "Error updating received record: " . $conn->error;
    }
}
?>