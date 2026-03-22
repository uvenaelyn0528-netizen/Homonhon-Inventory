<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
    $activity = $_POST['activity']; // Must be 'INFLOW' or 'OUTFLOW'
    $rdate = $_POST['rdate'];
    $qty = floatval($_POST['qty']);
    $deposited_to = $_POST['deposited_to'];
    
    // Optional fields
    $received_from = !empty($_POST['received_from']) ? $_POST['received_from'] : null;
    $rr_no = !empty($_POST['rr_no']) ? $_POST['rr_no'] : null;
    $ws_no = !empty($_POST['ws_no']) ? $_POST['ws_no'] : null;
    $withdrawn_from = !empty($_POST['from_tank_no']) ? $_POST['from_tank_no'] : null;

    try {
        if ($id) {
            // UPDATE existing record
            $sql = "UPDATE diesel_inventory SET 
                    rdate = :rdate, 
                    activity = :activity, 
                    received_from = :received_from, 
                    rr_no = :rr_no, 
                    deposited_to = :deposited_to, 
                    ws_no = :ws_no, 
                    qty = :qty, 
                    withdrawn_from = :withdrawn_from 
                    WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'rdate' => $rdate,
                'activity' => $activity,
                'received_from' => $received_from,
                'rr_no' => $rr_no,
                'deposited_to' => $deposited_to,
                'ws_no' => $ws_no,
                'qty' => $qty,
                'withdrawn_from' => $withdrawn_from,
                'id' => $id
            ]);
        } else {
            // INSERT new record
            $sql = "INSERT INTO diesel_inventory 
                    (rdate, activity, received_from, rr_no, deposited_to, ws_no, qty, withdrawn_from) 
                    VALUES (:rdate, :activity, :received_from, :rr_no, :deposited_to, :ws_no, :qty, :withdrawn_from)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'rdate' => $rdate,
                'activity' => $activity,
                'received_from' => $received_from,
                'rr_no' => $rr_no,
                'deposited_to' => $deposited_to,
                'ws_no' => $ws_no,
                'qty' => $qty,
                'withdrawn_from' => $withdrawn_from
            ]);
        }

        header("Location: diesel_inventory.php?status=success");
        exit();

    } catch (PDOException $e) {
        echo "Database Error: " . htmlspecialchars($e->getMessage());
    }
}
?>
