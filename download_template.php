<?php
// download_template.php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inventory_inflow_template.csv');

$output = fopen('php://output', 'w');

// The headers must match the order in your fgetcsv() logic exactly
fputcsv($output, [
    'Date (YYYY-MM-DD)', 
    'RR Number', 
    'Supplier', 
    'Item Name', 
    'Specification', 
    'UM', 
    'Department', 
    'Price', 
    'Qty Received', 
    'Purpose/Remarks'
]);

// Add one example row so the user knows the format
fputcsv($output, [
    date('Y-m-d'), 
    'RR-101', 
    'Sample Supplier Inc.', 
    'Pioneer Cement', 
    '40kg Bag', 
    'bags', 
    'Engineering', 
    '250.00', 
    '100', 
    'For Phase 1 Foundation'
]);

fclose($output);
exit;
