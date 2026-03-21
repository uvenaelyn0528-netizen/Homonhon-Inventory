<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Collect Base Form Data
        $id            = !empty($_POST['id']) ? $_POST['id'] : null;
        $activity      = $_POST['activity'] ?? 'OUTFLOW';
        $rdate         = $_POST['rdate'] ?? date('Y-m-d');
        $rtime         = $_POST['rtime'] ?? date('H:i');
        $deposited_to  = $_POST['deposited_to'] ?? ''; // Unit ID / Plate No
        $qty           = floatval(preg_replace('/[^0-9.]/', '', $_POST['qty'] ?? '0'));

        // Activity-Specific Fields
        if ($activity == 'INFLOW') {
            $received_from = $_POST['received_from'] ?? ''; // Supplier
            $rr_no         = $_POST['rr_no'] ?? '';
            $ws_no         = null;
            $from_tank_no  = null;
            $shift         = null;
            $eqpt_type     = null;
            $eqpt_code     = null;
            $odometer      = 0;
            $slip_no       = null;
        } else {
            // OUTFLOW Fields (From the Issuance Log)
            $received_from = $_POST['received_from'] ?? ''; // Operator Name
            $rr_no         = null;
            $ws_no         = $_POST['ws_no'] ?? '';
            $from_tank_no  = $_POST['from_tank_no'] ?? '';
            $shift         = $_POST['shift'] ?? 'DAY';
            $eqpt_type     = $_POST['eqpt_type'] ?? '';
            $eqpt_code     = $_POST['eqpt_code'] ?? '';
            $odometer      = !empty($_POST['odometer']) ? $_POST['odometer'] : 0;
            $slip_no       = $_POST['slip_no'] ?? '';
        }

        if ($id) {
            // --- PDO UPDATE LOGIC ---
            $sql = "UPDATE diesel_inventory SET 
                    activity = :act, rdate = :rdate, rtime = :rtime, received_from = :rec, 
                    rr_no = :rr, deposited_to = :dep, ws_no = :ws, from_tank_no = :tank, 
                    qty = :qty, shift = :shift, eqpt_type = :etype, eqpt_code = :ecode, 
                    odometer = :odo, slip_no = :slip
                    WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $params = [
                'act' => $activity, 'rdate' => $rdate, 'rtime' => $rtime, 'rec' => $received_from,
                'rr' => $rr_no, 'dep' => $deposited_to, 'ws' => $ws_no, 'tank' => $from_tank_no,
                'qty' => $qty, 'shift' => $shift, 'etype' => $eqpt_type, 'ecode' => $eqpt_code,
                'odo' => $odometer, 'slip' => $slip_no, 'id' => $id
            ];
        } else {
            // --- PDO INSERT LOGIC ---
            $sql = "INSERT INTO diesel_inventory 
                    (activity, rdate, rtime, received_from, rr_no, deposited_to, ws_no, from_tank_no, qty, shift, eqpt_type, eqpt_code, odometer, slip_no) 
                    VALUES 
                    (:act, :rdate, :rtime, :rec, :rr, :dep, :ws, :tank, :qty, :shift, :etype, :ecode, :odo, :slip)";
            
            $stmt = $conn->prepare($sql);
            $params = [
                'act' => $activity, 'rdate' => $rdate, 'rtime' => $rtime, 'rec' => $received_from,
                'rr' => $rr_no, 'dep' => $deposited_to, 'ws' => $ws_no, 'tank' => $from_tank_no,
                'qty' => $qty, 'shift' => $shift, 'etype' => $eqpt_type, 'ecode' => $eqpt_code,
                'odo' => $odometer, 'slip' => $slip_no
            ];
        }

        if ($stmt->execute($params)) {
            // Success: Redirect based on activity
            $redirect = ($activity == 'INFLOW') ? "diesel_inventory.php" : "issuance.php";
            header("Location: $redirect?status=success");
            exit();
        }

    } catch (PDOException $e) {
        die("Critical Database Error: " . $e->getMessage());
    }
}
?>
