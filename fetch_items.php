<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php'; // Ensure db.php uses PDO as shown in previous steps

// Capture the user role
$role = $_SESSION['role'] ?? 'Viewer';

$search = $_GET['search'] ?? '';
$date = $_GET['date'] ?? '';

try {
    // 1. Base Query with Alphabetical Ordering
    // PostgreSQL uses ILIKE for case-insensitive search
    $query_str = "SELECT * FROM inventory WHERE 1=1";
    $params = [];

    if ($search != '') { 
        $query_str .= " AND (item_name ILIKE :search OR \"Department\" ILIKE :search OR \"Purpose\" ILIKE :search OR \"Specification\" ILIKE :search)"; 
        $params['search'] = "%$search%";
    }
    if ($date != '') { 
        $query_str .= " AND \"RDATE\"::text = :date"; 
        $params['date'] = $date;
    }
    $query_str .= " ORDER BY item_name ASC"; 

    $stmt = $conn->prepare($query_str);
    $stmt->execute($params);
    $rows = "";

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];
        $raw_name = $row['item_name'];
        $raw_spec = $row['Specification'] ?? '';
        
        // JS SAFE VARIABLES
        $js_name = addslashes($raw_name);
        $js_spec = addslashes($raw_spec);
        
        $price = $row['price'] ?? 0;
        $purpose = $row['Purpose'] ?: '---';
        
        $min = $row['min_stock'] ?? 5;
        $max = $row['max_stock'] ?? 20;

        // 3. Calculation Logic (Sub-queries updated for PDO/Postgres)
        // Note: Ensure table names 'received_history' and 'withdrawals' exist in Supabase
        $rec_stmt = $conn->prepare("SELECT SUM(\"Qty\") as total FROM received_history WHERE item_name = ? AND \"Specification\" = ?");
        $rec_stmt->execute([$raw_name, $raw_spec]);
        $total_received = $rec_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        $wit_stmt = $conn->prepare("SELECT SUM(\"QTY\") as total FROM withdrawals WHERE item_name = ?");
        $wit_stmt->execute([$raw_name]);
        $total_withdrawn = $wit_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

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
            <td style='text-align: center; font-size: 12px;'>".htmlspecialchars($row['UM'] ?? 'pcs')."</td>
            <td style='text-align: center; color: #27ae60; font-weight: bold;'>$total_received</td>
            <td style='text-align: center; color: #e67e22; font-weight: bold;'>$total_withdrawn</td>
            <td style='text-align: center; padding: 4px;'>
                <div style='$stock_box_style'>
                    <span style='$number_style'>$current_stock</span>
                    $status_label
                </div>
            </td>
            <td style='font-size: 11px;'>".htmlspecialchars($row['Department'])."</td>
            <td style='font-size: 10px; color: #7f8c8d; max-width: 130px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;' title='".htmlspecialchars($purpose)."'>
                ".htmlspecialchars($purpose)."
            </td>
            <td style='text-align: right; font-size: 11px; font-family: monospace;'>₱" . number_format($price, 2) . "</td>
            <td style='text-align: right; font-weight: bold; font-size: 12px; font-family: monospace;'>₱" . number_format($total_value, 2) . "</td>";
            
            $rows .= "<td style='position: sticky; right: 0; background: white; border-left: 1px solid #eee; z-index: 5;'>
                        <div style='display: flex; gap: 4px; justify-content: center; padding: 2px;'>";
            
            if ($role === 'Admin' || $role === 'Staff') {
                $rows .= "<button type='button' onclick='openWithdrawModal($id, \"$js_name\", $current_stock)' 
                            style='background: #e67e22; color: white; padding: 6px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;' 
                            title='Withdraw Item'>📤</button>";
                
                if ($role === 'Admin') {
                    $rows .= "<button type='button' onclick='openEditModal($id, \"$js_name\", \"$js_spec\", $min, $max)' 
                                style='background: #3498db; color: white; padding: 6px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;' 
                                title='Edit Item'>✏️</button>
                              <a href='delete_item.php?id=$id' onclick='return confirm(\"Are you sure?\")' 
                                style='background: #e74c3c; color: white; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 12px;'>🗑️</a>";
                }
            } else {
                $rows .= "<span style='color: #bdc3c7; font-size: 10px;'>View Only</span>";
            }
            $rows .= "</div></td></tr>";
    }

    if (empty($rows)) {
        $rows = "<tr><td colspan='11' style='text-align:center; padding:20px;'>No items found matching your search.</td></tr>";
    }

} catch (PDOException $e) {
    $rows = "<tr><td colspan='11' style='text-align:center; color:red; padding:20px;'>Error: " . $e->getMessage() . "</td></tr>";
}

// 6. AJAX SUPPORT
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $date_options = "<option value=''>-- All Dates --</option>";
    $date_query = $conn->query("SELECT DISTINCT \"RDATE\" FROM inventory WHERE \"RDATE\" IS NOT NULL ORDER BY \"RDATE\" DESC");
    while($d = $date_query->fetch(PDO::FETCH_ASSOC)) {
        $date_val = $d['RDATE'];
        $date_options .= "<option value='$date_val'>$date_val</option>";
    }
    echo json_encode(['table' => $rows, 'dates' => $date_options]);
} else {
    echo $rows;
}
?>
