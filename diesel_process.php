<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect Form Data
    $id            = $_POST['id'] ?? ''; // Hidden ID from the modal
    $activity      = $_POST['activity'];
    $rdate         = $_POST['rdate'];
    $rtime         = $_POST['rtime'];
    $deposited_to  = $_POST['deposited_to'];
    $qty           = $_POST['qty'];

    // Handle Activity-Specific Fields
    if ($activity == 'INFLOW') {
        $received_from = $_POST['received_from'];
        $rr_no         = $_POST['rr_no'];
        $ws_no         = null;
        $from_tank_no  = null;
    } else {
        $received_from = null;
        $rr_no         = null;
        $ws_no         = $_POST['ws_no'];
        $from_tank_no  = $_POST['from_tank_no'];
    }

    if (!empty($id)) {
        // --- UPDATE LOGIC ---
        $sql = "UPDATE diesel_inventory SET 
                activity = ?, rdate = ?, rtime = ?, received_from = ?, 
                rr_no = ?, deposited_to = ?, ws_no = ?, from_tank_no = ?, qty = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssdi", 
            $activity, $rdate, $rtime, $received_from, 
            $rr_no, $deposited_to, $ws_no, $from_tank_no, $qty, $id
        );
    } else {
        // --- INSERT LOGIC ---
        $sql = "INSERT INTO diesel_inventory 
                (activity, rdate, rtime, received_from, rr_no, deposited_to, ws_no, from_tank_no, qty) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssd", 
            $activity, $rdate, $rtime, $received_from, 
            $rr_no, $deposited_to, $ws_no, $from_tank_no, $qty
        );
    }

    if ($stmt->execute()) {
        // Redirect back to the ledger with a success status
        header("Location: diesel_inventory.php?status=success");
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>