<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Capture the user role
$role = $_SESSION['role'] ?? 'Viewer';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

// 1. Base Query with Alphabetical Ordering
$sql = "SELECT * FROM inventory WHERE 1";
if ($search != '') { 
    $sql .= " AND (item_name LIKE '%$search%' OR Department LIKE '%$search%' OR Purpose LIKE '%$search%' OR Specification LIKE '%$search%')"; 
}
if ($date != '') { 
    $sql .= " AND RDATE = '$date'"; 
}
$sql .= " ORDER BY item_name ASC"; 

$result = $conn->query($sql);
$rows = "";

if ($result) {
    while($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $raw_name = $row['item_name'];
        $raw_spec = $row['Specification'];
        
        // JS SAFE VARIABLES: Prevents errors if names have quotes like: 12" Bolt or O'Reilly
        $js_name = addslashes($raw_name);
        $js_spec = addslashes($raw_spec);
        
        $price = $row['price'] ?? 0;
        $purpose = $row['Purpose'] ?: '---';
        
        // 2. DYNAMIC MIN-MAX LOGIC
        $min = $row['min_stock'] ?? 5;
        $max = $row['max_stock'] ?? 20;

        // 3. Calculation Logic (Received vs Withdrawn)
        $rec_q = $conn->query("SELECT SUM(Qty) as total FROM received_history WHERE item_name = '$js_name' AND Specification = '$js_spec'");
        $total_received = $rec_q->fetch_assoc()['total'] ?? 0;

        $wit_q = $conn->query("SELECT SUM(QTY) as total FROM withdrawals WHERE item_name = '$js_name'");
        $total_withdrawn = $wit_q->fetch_assoc()['total'] ?? 0;

        $current_stock = $total_received - $total_withdrawn;

        // 4. STYLING LOGIC
        $stock_box_style = "display: flex; flex-direction: column; align-items: center; justify-content: center; line-height: 1.1;";
        $number_style = "font-weight: 800; font-size: 15px; padding: 2px 8px; border-radius: 4px; min-width: 35px; display: inline-block;";
        $badge_style = "font-size: 9px; font-weight: 800; margin-top: 2px; letter-spacing: -0.2px; text-transform: uppercase;";
        
        $status_label = "";
        $row_bg = "";

        if ($current_stock < $min) {
            $number_style .= " background: #e74c3c; color: #fff;";
            $status_label = "<span style='$badge_style color: #e74c3c;'>🚨 CRITICAL</span>";
            $row_bg = "background-color: #fff9f9;"; 
        } elseif ($current_stock > $max) {
            $number_style .= " background: #2980b9; color: #fff;";
            $status_label = "<span style='$badge_style color: #2980b9;'>📦 FULL</span>";
            $row_bg = "background-color: #f5faff;";
        } else {
            $number_style .= " color: #27ae60; border: 1px solid #d4efdf;";
            $status_label = "<span style='$badge_style color: #27ae60;'>STABLE</span>";
            $row_bg = "";
        }

        $total_value = $current_stock * $price;

        // 5. TABLE ROW OUTPUT
        $rows .= "<tr style='border-bottom: 1px solid #eee; $row_bg'>
            <td style='padding: 8px 10px; text-align: left;'><strong>".htmlspecialchars($raw_name)."</strong></td>
            <td style='font-size: 11px; color: #7f8c8d;'>".htmlspecialchars($raw_spec)."</td>
            <td style='text-align: center; font-size: 12px;'>{$row['UM']}</td>
            <td style='text-align: center; color: #27ae60; font-weight: bold;'>$total_received</td>
            <td style='text-align: center; color: #e67e22; font-weight: bold;'>$total_withdrawn</td>
            
            <td style='text-align: center; padding: 4px;'>
                <div style='$stock_box_style'>
                    <span style='$number_style'>$current_stock</span>
                    $status_label
                </div>
            </td>

            <td style='font-size: 11px;'>{$row['Department']}</td>
            <td style='font-size: 10px; color: #7f8c8d; max-width: 130px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;' title='".htmlspecialchars($purpose)."'>
                ".htmlspecialchars($purpose)."
            </td>

            <td style='text-align: right; font-size: 11px; font-family: monospace;'>₱" . number_format($price, 2) . "</td>
            <td style='text-align: right; font-weight: bold; font-size: 12px; font-family: monospace;'>₱" . number_format($total_value, 2) . "</td>";
            
            // ACTION COLUMN
            $rows .= "<td style='position: sticky; right: 0; background: white; border-left: 1px solid #eee; z-index: 5;'>
                        <div style='display: flex; gap: 4px; justify-content: center; padding: 2px;'>";
            
            if ($role === 'Admin' || $role === 'Staff') {
                // Withdraw Button - Using the $js_name to handle quotes safely
                $rows .= "<button type='button' onclick='openWithdrawModal($id, \"$js_name\", $current_stock)' 
                            style='background: #e67e22; color: white; padding: 6px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;' 
                            title='Withdraw Item'>📤</button>";
                
                if ($role === 'Admin') {
                    // Edit Button - Passing all required params for the edit modal
                    $rows .= "<button type='button' onclick='openEditModal($id, \"$js_name\", \"$js_spec\", $min, $max)' 
                                style='background: #3498db; color: white; padding: 6px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;' 
                                title='Edit Item'>✏️</button>";
                    
                    // Delete Button
                    $rows .= "<a href='delete_item.php?id=$id' onclick='return confirm(\"Are you sure you want to delete this item permanently?\")' 
                                style='background: #e74c3c; color: white; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 12px;' 
                                title='Delete Item'>🗑️</a>";
                }
            } else {
                $rows .= "<span style='color: #bdc3c7; font-size: 10px;'>View Only</span>";
            }

            $rows .= "</div></td></tr>";
    }
} else {
    $rows = "<tr><td colspan='11' style='text-align:center; padding:20px;'>No items found matching your search.</td></tr>";
}

// 6. AJAX SUPPORT
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    // Prepare dates for the dropdown if needed
    $date_options = "<option value=''>-- All Dates --</option>";
    $date_query = $conn->query("SELECT DISTINCT RDATE FROM inventory WHERE RDATE IS NOT NULL ORDER BY RDATE DESC");
    while($d = $date_query->fetch_assoc()) {
        $date_options .= "<option value='{$d['RDATE']}'>{$d['RDATE']}</option>";
    }
    
    echo json_encode(['table' => $rows, 'dates' => $date_options]);
} else {
    echo $rows;
}
?>