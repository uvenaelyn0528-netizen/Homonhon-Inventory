<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Security: Only allow Admin or Staff to perform these actions
$isAuthorized = isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- SCENARIO 1: STANDALONE UPLOAD (From the 📤 icon modal) ---
    if (isset($_POST['upload_only'])) {
        if (!$isAuthorized) {
            die("Unauthorized access.");
        }

        $id = $_POST['id'];
        $attachment_path = null;

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/diesel_rr/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExt = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $newFileName = 'RR_SCAN_' . time() . '_' . uniqid() . '.' . $fileExt;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                try {
                    $sql = "UPDATE diesel_inventory SET attachment_path = :path WHERE id = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':path' => $targetPath, ':id' => $id]);
                    
                    header("Location: diesel_inventory.php?msg=upload_success");
                    exit();
                } catch (PDOException $e) {
                    die("Database Error: " . $e->getMessage());
                }
            }
        }
        header("Location: diesel_inventory.php?msg=upload_failed");
        exit();
    }

    // --- SCENARIO 2: STANDARD SAVE/UPDATE (From the "New Entry" modal) ---
    $id = $_POST['id'] ?? '';
    $activity = $_POST['activity']; 
    $rdate = $_POST['rdate'];
    $rtime = $_POST['rtime'];
    $qty = $_POST['qty'];
    $deposited_to = $_POST['deposited_to'];

    // Business Logic: Clean data based on activity type
    $received_from = ($activity === 'INFLOW') ? $_POST['received_from'] : '---';
    $rr_no = ($activity === 'INFLOW') ? $_POST['rr_no'] : '---';
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
