<?php
// 1. Resource management for Render to prevent 502 errors
ini_set('memory_limit', '512M');
set_time_limit(0); 

include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Check if file was uploaded via the "Import CSV" button in history.php
if (isset($_FILES['withdrawal_csv']) && $_FILES['withdrawal_csv']['size'] > 0) {
    $fileName = $_FILES["withdrawal_csv"]["tmp_name"];
    $file = fopen($fileName, "r");
    
    // Skip the header row (Date, Item, Spec, Qty, Dept, Purpose, User)
    fgetcsv($file); 

    try {
        $conn->beginTransaction();
        
        // Use 'qty_withdrawn' to match your Supabase schema
        $stmt = $conn->prepare("INSERT INTO withdrawals 
                                (withdrawal_date, item_name, specification, qty_withdrawn, department, purpose, withdrawn_by) 
                                VALUES (:date, :item, :spec, :qty, :dept, :purpose, :user)");

        $import_count = 0;

        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            // Skip rows where the Item Description (column 2) is empty
            if (empty($column[1])) continue; 

            // --- DATE CLEANER ---
            $raw_date = trim($column[0] ?? '');
            if (strpos($raw_date, '-') !== false) {
                $raw_date = explode('-', $raw_date)[0]; // Take start date if it's a range
            }
            $clean_date = str_replace('/', '-', $raw_date);
            $final_date = date('Y-m-d', strtotime($clean_date));
            
            // Fallback to today if date is invalid
            if (!$final_date || $final_date == '1970-01-01') { 
                $final_date = date('Y-m-d'); 
            }

            // --- NUMERIC CLEANER (Fixes 0.00 issue) ---
            $qty = floatval(preg_replace('/[^0-9.]/', '', $column[3] ?? '0'));

            $stmt->execute([
                'date'    => $final_date,
                'item'    => trim($column[1] ?? ''),
                'spec'    => trim($column[2] ?? ''),
                'qty'     => $qty,
                'dept'    => trim($column[4] ?? ''),
                'purpose' => trim($column[5] ?? ''),
                'user'    => trim($column[6] ?? 'N/A')
            ]);

            $import_count++;

            // Commit in chunks of 1000 to save memory on Render
            if ($import_count % 1000 == 0) {
                $conn->commit();
                $conn->beginTransaction();
            }
        }

        $conn->commit();
        fclose($file);
        
        // Redirect back to history page with success message
        header("Location: history.php?import=success&count=" . $import_count);
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        die("Critical Import Error: " . $e->getMessage());
    }
} else {
    die("No file uploaded or file is empty.");
}
?>
