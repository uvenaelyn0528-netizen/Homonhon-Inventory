<?php
include 'db.php';

// --- CONFIGURATION ---
$supabaseUrl = 'YOUR_SUPABASE_URL'; 
$supabaseKey = 'YOUR_SUPABASE_SERVICE_ROLE_KEY'; 
$bucketName  = 'scan_copy';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $upload_only = isset($_POST['upload_only']);
    $remove_attachment = isset($_POST['remove_attachment']) && $_POST['remove_attachment'] == '1';
    $publicUrl = null;

    // --- 1. HANDLE FILE UPLOAD (SUPABASE) ---
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        $fileName = time() . '_' . uniqid() . '_' . basename($file['name']);
        $filePath = $file['tmp_name'];
        
        // Supabase requires PUT for uploads to a specific path
        $url = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$fileName}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filePath));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$supabaseKey}",
            "apikey: {$supabaseKey}",
            "Content-Type: " . mime_content_type($filePath)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            $publicUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucketName}/{$fileName}";
        } else {
            die("Upload Failed. HTTP Code: $httpCode | Response: $response");
        }
    }

    // --- 2. DATABASE LOGIC ---
    if ($upload_only) {
        // Only updating the scan from the 📤 button
        if ($id && $publicUrl) {
            $stmt = $conn->prepare("UPDATE diesel_inventory SET attachment_path = ? WHERE id = ?");
            $stmt->execute([$publicUrl, $id]);
        }
        header("Location: diesel_inventory.php?upload=success");
        exit();

    } else {
        // Full Form Save (New Entry or Edit Modal)
        $activity = $_POST['activity'] ?? null;
        $rdate = $_POST['rdate'] ?? null;
        $qty = $_POST['qty'] ?? 0;
        $deposited_to = $_POST['deposited_to'] ?? '';

        if (!$activity || !$rdate) {
            header("Location: diesel_inventory.php?msg=error_missing_fields");
            exit();
        }
        
        $received_from = ($activity === 'INFLOW') ? ($_POST['received_from'] ?? '---') : '---';
        $rr_no = ($activity === 'INFLOW') ? ($_POST['rr_no'] ?? '---') : '---';
        $from_tank = $_POST['from_tank_no'] ?? '---';
        $ws_no = $_POST['ws_no'] ?? '---';

        // Determine final attachment path
        if ($remove_attachment) {
            $attachment_path = null;
        } elseif ($publicUrl) {
            $attachment_path = $publicUrl;
        } else {
            $attachment_path = $_POST['existing_attachment'] ?? null;
        }

        if (!empty($id)) {
            $sql = "UPDATE diesel_inventory SET activity=?, rdate=?, received_from=?, rr_no=?, ws_no=?, withdrawn_from=?, deposited_to=?, qty=?, attachment_path=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$activity, $rdate, $received_from, $rr_no, $ws_no, $from_tank, $deposited_to, $qty, $attachment_path, $id]);
        } else {
            $sql = "INSERT INTO diesel_inventory (activity, rdate, received_from, rr_no, ws_no, withdrawn_from, deposited_to, qty, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$activity, $rdate, $received_from, $rr_no, $ws_no, $from_tank, $deposited_to, $qty, $attachment_path]);
        }
        
        header("Location: diesel_inventory.php?msg=success");
        exit();
    }
}
