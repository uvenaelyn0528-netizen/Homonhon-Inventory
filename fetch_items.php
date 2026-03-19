<?php
include 'db.php';

try {
    // Check if your table is "inventory" (lowercase) or "Inventory" (Capital)
    // If "Inventory", change the query to: SELECT * FROM "Inventory"
    $query = "SELECT * FROM inventory ORDER BY item_name ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($items) > 0) {
        foreach ($items as $row) {
            echo "<tr>
                <td style='padding:10px;'><strong>" . htmlspecialchars($row['item_name']) . "</strong></td>
                <td>" . htmlspecialchars($row['Specification'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['UM'] ?? 'pcs') . "</td>
                <td style='color:green;'>" . ($row['received'] ?? 0) . "</td>
                <td style='color:orange;'>" . ($row['withdrawn'] ?? 0) . "</td>
                <td><strong>" . (($row['received'] ?? 0) - ($row['withdrawn'] ?? 0)) . "</strong></td>
                <td>" . htmlspecialchars($row['Department'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['Purpose'] ?? '') . "</td>
                <td>₱" . number_format($row['price'] ?? 0, 2) . "</td>
                <td>₱" . number_format((($row['received'] ?? 0) - ($row['withdrawn'] ?? 0)) * ($row['price'] ?? 0), 2) . "</td>
                <td><button class='btn-action'>📤</button></td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='11' style='text-align:center; padding:20px;'>No items found in database.</td></tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='11' style='color:red; text-align:center;'>SQL Error: " . $e->getMessage() . "</td></tr>";
}
?>
