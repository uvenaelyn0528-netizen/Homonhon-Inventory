<?php
include 'db.php';

// --- CONFIGURATION ---
// ⚠️ REPLACE THESE WITH YOUR REAL SUPABASE DETAILS ⚠️
$supabaseUrl = trim('https://your-project-id.supabase.co'); 
$supabaseKey = trim('your-long-service-role-key-here'); 
$bucketName  = trim('scan_copy');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $upload_only = isset($_POST['upload_only']);
    $remove_attachment = isset($_POST['remove_attachment']) && $_POST['remove_attachment'] == '1';
    $publicUrl = null;

    // --- 1. HANDLE FILE UPLOAD (SUPABASE) ---
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        // Clean filename: replace spaces/special chars with underscores
        $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9.]/', '_', basename($file['name']));
        $filePath = $file['tmp_name'];
        
        $url = rtrim($supabaseUrl, '/') . "/storage/v1/object/{$bucketName}/{$fileName}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filePath));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$supabaseKey}",
            "apikey: {$supabaseKey}",
            "Content-Type: " . mime_content_type($filePath)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            $publicUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/public/{$bucketName}/{$fileName}";
        } else {
            die("Upload Failed. HTTP Code: $httpCode | cURL Error: $curlError | Response: $response");
        }
    }

    // --- 2. DATABASE LOGIC ---
    if ($upload_only) {
        if ($id && $publicUrl) {
            $stmt = $conn->prepare("UPDATE diesel_inventory SET attachment_path = ? WHERE id = ?");
            $stmt->execute([$publicUrl, $id]);
        }
        header("Location: diesel_inventory.php?upload=success");
        exit();
    } else {
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
