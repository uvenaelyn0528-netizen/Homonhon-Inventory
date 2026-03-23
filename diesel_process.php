<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $activity = $_POST['activity'] ?? 'OUTFLOW'; // Default to OUTFLOW
    
    // Mapping POST data to your new table columns
    $data = [
        'tank_source'    => $_POST['tank_source'] ?? '',
        'rdate'          => $_POST['rdate'] ?? null,
        'deposited'      => $_POST['deposited'] ?? 0,
        'ws_no'          => $_POST['ws_no'] ?? '',
        'name'           => $_POST['name'] ?? '',
        'equipment_type' => $_POST['equipment_type'] ?? '',
        'equipment_id'   => $_POST['equipment_id'] ?? '',
        'code'           => $_POST['code'] ?? '',
        'odometer'       => $_POST['odometer'] ?? 0,
        'rtime'          => $_POST['rtime'] ?? null,
        'is_no'          => $_POST['is_no'] ?? '',
        'qty'            => $_POST['qty'] ?? 0,
        'shift'          => $_POST['shift'] ?? ''
    ];

    try {
        if (!empty($id)) {
            // UPDATE EXISTING RECORD
            $sql = "UPDATE diesel_history SET 
                    tank_source = :tank_source, rdate = :rdate, deposited = :deposited, 
                    ws_no = :ws_no, name = :name, equipment_type = :equipment_type, 
                    equipment_id = :equipment_id, code = :code, odometer = :odometer, 
                    rtime = :rtime, is_no = :is_no, qty = :qty, shift = :shift 
                    WHERE id = :id";
            $data['id'] = $id;
        } else {
            // INSERT NEW RECORD
            $sql = "INSERT INTO diesel_history 
                    (tank_source, rdate, deposited, ws_no, name, equipment_type, equipment_id, code, odometer, rtime, is_no, qty, shift) 
                    VALUES 
                    (:tank_source, :rdate, :deposited, :ws_no, :name, :equipment_type, :equipment_id, :code, :odometer, :rtime, :is_no, :qty, :shift)";
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($data);

        // Redirect back to the issuance log with success
        header("Location: issuance.php?status=success");
        exit();

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>
