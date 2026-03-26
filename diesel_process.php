<?php
include 'db.php';

// --- CONFIGURATION ---
// Replace these with your actual Supabase Project Settings
$supabaseUrl = 'YOUR_SUPABASE_URL'; 
$supabaseKey = 'YOUR_SUPABASE_SERVICE_ROLE_KEY'; // Use Service Role Key for storage
$bucketName  = 'scan_copy';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $activity = $_POST['activity'];
    $rdate = $_POST['rdate'];
    $qty = $_POST['qty'];
    $deposited_to = $_POST['deposited_to'];
    
    // Conditional logic for fields based on activity type
    $received_from = ($activity === 'INFLOW') ? $_POST['received_from'] : '---';
    $rr_no = ($activity === 'INFLOW') ? $_POST['rr_no'] : '---';
    $withdrawn_from = (in_array($activity, ['OUTFLOW', 'TRANSFERRED'])) ? $_POST['from_tank_no'] : '---';
    $ws_no = (in_array($activity, ['OUTFLOW', 'TRANSFERRED'])) ? $_POST['ws_no'] : '---';

    // Keep the existing path if editing and no new file is selected
    $attachment_path = $_POST['existing_attachment'] ?? null;

    // --- INTEGRATED SUPABASE UPLOAD ---
    // This section acts as your "Upload Button" logic
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        $fileName = 'RR_' . time() . '_' . uniqid() . '_' . basename($file['name']);
        $filePath = $file['tmp_name'];
        
        $upload_url = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$fileName}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $upload_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // More stable for Supabase
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filePath));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$supabaseKey}",
            "apikey: {$supabaseKey}",
            "Content-Type: " . mime_content_type($filePath)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // If upload is successful (200 or 201), set the public URL for the DB
        if ($httpCode === 200 || $httpCode === 201) {
            $attachment_path = "{$supabaseUrl}/storage/v1/object/public/{$bucketName}/{$fileName}";
        } else {
            // Optional: Uncomment for debugging if uploads fail
            // die("Supabase Error: " . $response);
        }
    }

    // --- DATABASE EXECUTION ---
    if (!empty($id)) {
        // SCENARIO: EDITING EXISTING RECORD
        $sql = "UPDATE diesel_inventory SET 
                activity=?, rdate=?, received_from=?, rr_no=?, ws_no=?, 
                withdrawn_from=?, deposited_to=?, qty=?, attachment_path=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $activity, $rdate, $received_from, $rr_no, $ws_no, 
            $withdrawn_from, $deposited_to, $qty, $attachment_path, $id
        ]);
    } else {
        // SCENARIO: NEW ENTRY
        $sql = "INSERT INTO diesel_inventory (
                    activity, rdate, received_from, rr_no, ws_no, 
                    withdrawn_from, deposited_to, qty, attachment_path
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $activity, $rdate, $received_from, $rr_no, $ws_no, 
            $withdrawn_from, $deposited_to, $qty, $attachment_path
        ]);
    }

    // Redirect back with a success message
    header("Location: diesel_inventory.php?msg=success");
    exit();
}
?>
