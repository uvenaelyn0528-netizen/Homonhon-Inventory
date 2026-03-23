<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $activity = $_POST['activity']; // INFLOW, OUTFLOW, or TRANSFERRED
    $rdate = $_POST['rdate'];
    $rtime = $_POST['rtime'];
    $qty = $_POST['qty'];
    $deposited_to = $_POST['deposited_to'];
    
    // logic to determine which fields to clear based on activity
    $received_from = ($activity === 'INFLOW') ? $_POST['received_from'] : '---';
    $rr_no = ($activity === 'INFLOW') ? $_POST['rr_no'] : '---';
    
    // Outflow and Transfer both use these fields
    $withdrawn_from = ($activity === 'OUTFLOW' || $activity === 'TRANSFERRED') ? $_POST['from_tank_no'] : '---';
    $ws_no = ($activity === 'OUTFLOW' || $activity === 'TRANSFERRED') ? $_POST['ws_no'] : '---';

    try {
        if (!empty($id)) {
            // UPDATE existing record
            $sql = "UPDATE diesel_inventory SET 
                    activity = :activity, 
                    rdate = :rdate, 
                    rtime = :rtime, 
                    received_from = :rec, 
                    rr_no = :rr, 
                    ws_no = :ws, 
                    withdrawn_from = :withdrawn, 
                    deposited_to = :dep, 
                    qty = :qty 
                    WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':activity' => $activity,
                ':rdate' => $rdate,
                ':rtime' => $rtime,
                ':rec' => $received_from,
                ':rr' => $rr_no,
                ':ws' => $ws_no,
                ':withdrawn' => $withdrawn_from,
                ':dep' => $deposited_to,
                ':qty' => $qty,
                ':id' => $id
            ]);
        } else {
            // INSERT new record
            $sql = "INSERT INTO diesel_inventory (activity, rdate, rtime, received_from, rr_no, ws_no, withdrawn_from, deposited_to, qty) 
                    VALUES (:activity, :rdate, :rtime, :rec, :rr, :ws, :withdrawn, :dep, :qty)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':activity' => $activity,
                ':rdate' => $rdate,
                ':rtime' => $rtime,
                ':rec' => $received_from,
                ':rr' => $rr_no,
                ':ws' => $ws_no,
                ':withdrawn' => $withdrawn_from,
                ':dep' => $deposited_to,
                ':qty' => $qty
            ]);
        }

        header("Location: diesel_inventory.php?msg=success");
        exit();

    } catch (PDOException $e) {
        die("Error saving record: " . $e->getMessage());
    }
}
?>
