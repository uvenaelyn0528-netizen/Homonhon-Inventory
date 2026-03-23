<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? 'Viewer';

try {
    $search = $_GET['search'] ?? '';

    // Query remains the same to keep data available for other logic
    $sql = "SELECT i.*, 
            (SELECT COALESCE(SUM(qty), 0) FROM received_history rh WHERE rh.item_name = i.item_name AND rh.specification = i.specification) as total_received,
            (SELECT COALESCE(SUM(qty_withdrawn), 0) FROM withdrawals w WHERE w.item_name = i.item_name AND w.specification = i.specification) as total_withdrawn
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
            
            // Logic kept here so the variables can still be passed to the Edit Modal
            $dept_raw = $row['department'] ?? ''; 
            $purp  = htmlspecialchars($row['purpose'] ?? '');
            $price = $row['price'] ?? 0;
            $min   = $row['min_stock'] ?? 0;
            $max   = $row['max_stock'] ?? 0;

            // Stock Warning Colors
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
                <td style='color:green; font-weight:bold;'>$received</td>
                <td style='color:orange; font-weight:bold;'>$withdrawn</td>
                <td>
                    <span style='$stockStyle'><strong>$stock</strong></span>
                    $warningLabel
                </td>";
            
            /* DEPT and PURPOSE <td> tags removed from here */

            echo "<td>₱" . number_format($price, 2) . "</td>
                <td>₱" . number_format($stock * $price, 2) . "</td>";

            // Action Buttons
            echo "<td class='action-cell' style='position: sticky; right: 0; background: white; border-left: 1px solid #ddd; z-index: 5; padding: 10px; white-space: nowrap; text-align: center;'>";

            if ($role == 'Admin' || $role == 'Staff') {
                echo "<button title='Withdraw' onclick='openWithdrawModal($id, \"" . addslashes($name) . "\", $stock)' style='background:#e67e22; color:white; border:none; padding:5px 8px; border-radius:4px; cursor:pointer; margin-right: 4px;'>📤</button>";
            }

            if ($role == 'Admin') {
                echo "<button onclick=\"openEditModal('$id', '" . addslashes($name) . "', '" . addslashes($spec) . "', '$min', '$max', '" . addslashes($dept_raw) . "')\" style='background:#3498db; color:white; border:none; padding:5px 8px; border-radius:4px; cursor:pointer; margin-right: 4px;'>✏️</button>";
                echo "<button onclick=\"confirmDelete('$id', '" . addslashes($name) . "')\" style='background:#e74c3c; color:white; border:none; padding:5px 8px; border-radius:4px; cursor:pointer;'>🗑️</button>";
            }

            echo "</td></tr>";
        }
    } else {
        // Colspan adjusted from 11 to 9
        echo "<tr><td colspan='9' style='text-align:center; padding:20px;'>No items found.</td></tr>";
    }
} catch (PDOException $e) {
    // Colspan adjusted from 11 to 9
    echo "<tr><td colspan='9' style='color:red; text-align:center;'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>
