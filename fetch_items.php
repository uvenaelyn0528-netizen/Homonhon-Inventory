<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? 'Viewer';

try {
    $search = $_GET['search'] ?? '';

    $sql = "SELECT i.*, 
            (SELECT SUM(qty) FROM received_history rh WHERE rh.item_name = i.item_name) as total_received
            FROM inventory i 
            WHERE (i.item_name ILIKE :search OR i.department ILIKE :search)
            AND i.is_deleted = FALSE 
            ORDER BY i.item_name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($items) > 0) {
        foreach ($items as $row) {
            $id    = $row['id'];
            $name  = htmlspecialchars($row['item_name'] ?? '');
            $spec  = htmlspecialchars($row['specification'] ?? '');
            
            $received  = $row['total_received'] ?? 0;
            $withdrawn = $row['total_withdrawn'] ?? 0;
            $stock     = $received - $withdrawn;

            $um    = htmlspecialchars($row['um'] ?? 'pcs');
            
            // --- UPDATED DEPARTMENT LOGIC ---
            // Pull the raw value from the database
            $dept_raw = $row['department'] ?? ''; 

            // If the value is accidentally numeric, it flags it as an error
            // Otherwise, it forces it to Uppercase for consistent reporting
            if (is_numeric($dept_raw)) {
                $dept = "<span style='color:red; font-weight:bold;'>Error: Numeric ($dept_raw)</span>";
            } else {
                $dept = !empty($dept_raw) ? strtoupper(htmlspecialchars($dept_raw)) : "<span style='color:gray;'>UNASSIGNED</span>";
            }
            // --------------------------------
            
            $purp  = htmlspecialchars($row['purpose'] ?? '');
            $price = $row['price'] ?? 0;
            $min   = $row['min_stock'] ?? 0;
            $max   = $row['max_stock'] ?? 0;

            // --- MIN-MAX WARNING LOGIC ---
            $stockStyle = "";
            $warningLabel = "";

            if ($stock <= $min && $min > 0) {
                $stockStyle = "background: #ffcccc; color: #8B0000; padding: 2px 6px; border-radius: 4px; border: 1px solid #8B0000;";
                $warningLabel = " <small style='display:block; font-size:9px;'>⚠️ LOW STOCK</small>";
            } elseif ($stock > $max && $max > 0) {
                $stockStyle = "background: #e1f5fe; color: #01579b; padding: 2px 6px; border-radius: 4px;";
                $warningLabel = " <small style='display:block; font-size:9px;'>ℹ️ OVERSTOCK</small>";
            }

            echo "<tr>
                <td style='padding:12px;'><strong>$name</strong></td>
                <td>$spec</td>
                <td>$um</td>
                <td style='color:green;'>$received</td>
                <td style='color:orange;'>$withdrawn</td>
                <td>
                    <span style='$stockStyle'><strong>$stock</strong></span>
                    $warningLabel
                </td>
                <td>$dept</td> <td>$purp</td>
                <td>₱" . number_format($price, 2) . "</td>
                <td>₱" . number_format($stock * $price, 2) . "</td>";

            // Sticky Action Column
            echo "<td class='action-cell' style='position: sticky; right: 0; background: white; border-left: 1px solid #ddd; z-index: 5; padding: 10px; white-space: nowrap; text-align: center;'>";

            if ($role == 'Admin' || $role == 'Staff') {
                echo "<button title='Withdraw' onclick='openWithdrawModal($id, \"" . addslashes($name) . "\", $stock)' style='background:#e67e22; color:white; border:none; padding:5px 8px; border-radius:4px; cursor:pointer; margin-right: 4px;'>📤</button>";
            }

            if ($role == 'Admin') {
    // We add addslashes($dept) so the JavaScript function receives the department name correctly
    echo "<button title='Edit' onclick='openEditModal($id, \"" . addslashes($name) . "\", \"" . addslashes($spec) . "\", $min, $max, \"" . addslashes($dept) . "\")' style='background:#3498db; color:white; border:none; padding:5px 8px; border-radius:4px; cursor:pointer; margin-right: 4px;'>✏️</button>";
    
    echo "<a href='delete_log.php?type=inventory&id=$id' 
         onclick='return confirm(\"Move this item to Trash? It will be hidden from the inventory.\")' 
         style='background:#e74c3c; color:white; padding:6px 10px; border-radius:4px; text-decoration:none; font-size:14px; display: inline-block; vertical-align: middle;'>🗑️</a>";
}

            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='11' style='text-align:center; padding:20px;'>No items found in inventory.</td></tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='11' style='color:red; text-align:center;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>
