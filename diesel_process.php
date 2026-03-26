<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- CONFIGURATION ---
$sb_project_ref = "YOUR_PROJECT_REFERENCE"; // Replace with your actual ref
$sb_api_key = "YOUR_SERVICE_ROLE_KEY";      // Replace with your actual key
$bucket = "inventory_files";

$isAuthorized = isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Function to handle Supabase Upload
    function uploadToSupabase($file, $ref, $key, $bucketName) {
        $file_tmp = $file['tmp_name'];
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = 'RR_' . time() . '_' . uniqid() . '.' . $file_ext;
        $upload_url = "https://$ref.supabase.co/storage/v1/object/$bucketName/$file_name";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $upload_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file_tmp));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $key",
            "Content-Type: " . mime_content_type($file_tmp)
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($http_code == 200) ? "https://$ref.supabase.co/storage/v1/object/public/$bucketName/$file_name" : null;
    }

    // --- SCENARIO 1: RR SCAN UPLOAD ONLY (From your Modal) ---
    if (isset($_POST['upload_only'])) {
        if (!$isAuthorized) { die("Unauthorized access."); }
        $id = $_POST['id'];
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $publicUrl = uploadToSupabase($_FILES['attachment'], $sb_project_ref, $sb_api_key, $bucket);
            if ($publicUrl) {
                $stmt = $conn->prepare("UPDATE diesel_inventory SET attachment_path = :path WHERE id = :id");
                $stmt->execute([':path' => $publicUrl, ':id' => $id]);
                header("Location: diesel_inventory.php?msg=upload_success");
                exit();
            }
        }
        header("Location: diesel_inventory.php?msg=upload_failed");
        exit();
    }

    // --- SCENARIO 2: STANDARD SAVE/UPDATE ---
    $id = $_POST['id'] ?? '';
    $activity = $_POST['activity']; 
    $rdate = $_POST['rdate'];
    $qty = $_POST['qty'];
    $deposited_to = $_POST['deposited_to'];
    
    $attachment_path = $_POST['existing_attachment'] ?? null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $newUrl = uploadToSupabase($_FILES['attachment'], $sb_project_ref, $sb_api_key, $bucket);
        if ($newUrl) { $attachment_path = $newUrl; }
    }

    $received_from = ($activity === 'INFLOW') ? $_POST['received_from'] : '---';
    $rr_no = ($activity === 'INFLOW') ? $_POST['rr_no'] : '---';
    $withdrawn_from = (in_array($activity, ['OUTFLOW', 'TRANSFERRED'])) ? $_POST['from_tank_no'] : '---';
    $ws_no = (in_array($activity, ['OUTFLOW', 'TRANSFERRED'])) ? $_POST['ws_no'] : '---';

    $sql = !empty($id) 
        ? "UPDATE diesel_inventory SET activity=:activity, rdate=:rdate, received_from=:rec, rr_no=:rr, ws_no=:ws, withdrawn_from=:withdrawn, deposited_to=:dep, qty=:qty, attachment_path=:path WHERE id=:id"
        : "INSERT INTO diesel_inventory (activity, rdate, received_from, rr_no, ws_no, withdrawn_from, deposited_to, qty, attachment_path) VALUES (:activity, :rdate, :rec, :rr, :ws, :withdrawn, :dep, :qty, :path)";
    
    $stmt = $conn->prepare($sql);
    $params = [
        ':activity' => $activity, ':rdate' => $rdate, ':rec' => $received_from,
        ':rr' => $rr_no, ':ws' => $ws_no, ':withdrawn' => $withdrawn_from,
        ':dep' => $deposited_to, ':qty' => $qty, ':path' => $attachment_path
    ];
    if (!empty($id)) $params[':id'] = $id;

    $stmt->execute($params);
    header("Location: diesel_inventory.php?msg=success");
    exit();
}
