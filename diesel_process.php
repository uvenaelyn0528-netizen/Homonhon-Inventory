<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $activity = $_POST['activity']; 
    $rdate = $_POST['rdate'];
    $rtime = $_POST['rtime'];
    $qty = $_POST['qty'];
    $deposited_to = $_POST['deposited_to'];
    
    // Authorization Check
    $isAuthorized = isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'staff']);

    // Logic to determine which fields to clear based on activity
    $received_from = ($activity === 'INFLOW') ? $_POST['received_from'] : '---';
    $rr_no = ($activity === 'INFLOW') ? $_POST['rr_no'] : '---';
    
    $withdrawn_from = ($activity === 'OUTFLOW' || $activity === 'TRANSFERRED') ? $_POST['from_tank_no'] : '---';
    $ws_no = ($activity === 'OUTFLOW' || $activity === 'TRANSFERRED') ? $_POST['ws_no'] : '---';

    // --- FILE UPLOAD LOGIC ---
    $attachment_path = null;
    
    // Only process upload if authorized and a file is actually sent
    if ($isAuthorized && isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        
        // Ensure directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Clean filename and add timestamp to prevent overwriting
        $fileExtension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $newFileName = 'RR_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
            $attachment_path = $targetPath;
        }
    }

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
                    qty = :qty";
            
            // Only update attachment_path in DB if a new file was actually uploaded
            if ($attachment_path) {
                $sql .= ", attachment_path = :attach";
            }

            $sql .= " WHERE id = :id";
            
            $params = [
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
            ];

            if ($attachment_path) {
                $params[':attach'] = $attachment_path;
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

        } else {
            // INSERT new record
            $sql = "INSERT INTO diesel_inventory (activity, rdate, rtime, received_from, rr_no, ws_no, withdrawn_from, deposited_to, qty, attachment_path) 
                    VALUES (:activity, :rdate, :rtime, :rec, :rr, :ws, :withdrawn, :dep, :qty, :attach)";
            
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
                ':attach' => $attachment_path
            ]);
        }

        header("Location: diesel_inventory.php?msg=success");
        exit();

    } catch (PDOException $e) {
        die("Error saving record: " . $e->getMessage());
    }
}
?>
