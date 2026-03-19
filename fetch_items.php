<?php
include 'db.php';

try {
    $search = $_GET['search'] ?? '';
    
    // 1. We changed "Department" to "department" (lowercase) 
    // 2. We removed the double quotes unless your column specifically has spaces
    $sql = "SELECT * FROM inventory WHERE item_name ILIKE :search OR department ILIKE :search ORDER BY item_name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($items) > 0) {
        foreach ($items as $row) {
            // Check your column names here too! 
            // If the table shows empty cells, make sure these match your Supabase columns
            $name = htmlspecialchars($row['item_name'] ?? '');
            $spec = htmlspecialchars($row['specification'] ?? $row['Specification'] ?? '');
            $um   = htmlspecialchars($row['um'] ?? $row['UM'] ?? 'pcs');
            $dept = htmlspecialchars($row['department'] ?? $row['Department'] ?? '');
            $purp = htmlspecialchars($row['purpose'] ?? $row['Purpose'] ?? '');
            
            $received = $row['received'] ?? 0;
            $withdrawn = $row['withdrawn'] ?? 0;
            $stock = $received - $withdrawn;
            $price = $row['price'] ?? 0;

            echo "<tr>
                <td style='padding:12px;'><strong>$name</strong></td>
                <td>$spec</td>
                <td>$um</td>
                <td style='color:green;'>$received</td>
                <td style='color:orange;'>$withdrawn</td>
                <td><strong>$stock</strong></td>
                <td>$dept</td>
                <td>$purp</td>
                <td>₱" . number_format($price, 2) . "</td>
                <td>₱" . number_format($stock * $price, 2) . "</td>";
            
            if ($role == 'Admin' || $role == 'Staff') {
                echo "<td>
                    <button onclick='openWithdrawModal({$row['id']}, \"" . addslashes($name) . "\", $stock)' style='background:#e67e22; color:white; border:none; padding:5px; border-radius:4px; cursor:pointer;'>📤</button>
                </td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='11' style='text-align:center; padding:20px;'>No items found in inventory.</td></tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='11' style='color:red; text-align:center;'>Query Error: " . $e->getMessage() . "</td></tr>";
}
?>
