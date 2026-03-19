<?php
// This file is included in index.php, so $conn is already available
try {
    $search = $_GET['search'] ?? '';
    
    // PostgreSQL uses ILIKE for case-insensitive search
    $sql = "SELECT * FROM inventory WHERE item_name ILIKE :search OR \"Department\" ILIKE :search ORDER BY item_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($items) > 0) {
        foreach ($items as $row) {
            // Calculate stock (assuming you have these columns or separate logic)
            $received = $row['received'] ?? 0;
            $withdrawn = $row['withdrawn'] ?? 0;
            $stock = $received - $withdrawn;
            $price = $row['price'] ?? 0;

            echo "<tr>
                <td style='padding:12px;'><strong>" . htmlspecialchars($row['item_name']) . "</strong></td>
                <td>" . htmlspecialchars($row['Specification'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['UM'] ?? 'pcs') . "</td>
                <td style='color:green;'>$received</td>
                <td style='color:orange;'>$withdrawn</td>
                <td><strong>$stock</strong></td>
                <td>" . htmlspecialchars($row['Department'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['Purpose'] ?? '') . "</td>
                <td>₱" . number_format($price, 2) . "</td>
                <td>₱" . number_format($stock * $price, 2) . "</td>";
            
            // Only show Action button for Admin/Staff
            if ($role == 'Admin' || $role == 'Staff') {
                echo "<td style='position: sticky; right: 0; background: white;'>
                    <button onclick='openWithdrawModal({$row['id']}, \"" . addslashes($row['item_name']) . "\", $stock)' style='background:#e67e22; color:white; border:none; padding:5px; border-radius:4px; cursor:pointer;'>📤</button>
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
