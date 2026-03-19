<?php
include 'db.php';

try {
    // PostgreSQL requires double quotes for capitalized table/column names
    $stmt = $conn->query("SELECT * FROM inventory ORDER BY item_name ASC");
    $items = $stmt->fetchAll();

    if (!$items) {
        echo "<tr><td colspan='11' style='text-align:center; padding:20px;'>No items found in database.</td></tr>";
    } else {
        foreach ($items as $row) {
            echo "<tr>";
            echo "<td style='padding:12px;'>" . htmlspecialchars($row['item_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Specification'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['UM'] ?? 'pcs') . "</td>";
            echo "<td style='color:green;'>" . ($row['received'] ?? 0) . "</td>";
            echo "<td style='color:orange;'>" . ($row['withdrawn'] ?? 0) . "</td>";
            echo "<td>" . (($row['received'] ?? 0) - ($row['withdrawn'] ?? 0)) . "</td>";
            echo "<td>" . htmlspecialchars($row['Department'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['Purpose'] ?? '') . "</td>";
            echo "<td>₱" . number_format($row['price'] ?? 0, 2) . "</td>";
            echo "<td>₱" . number_format((($row['received'] ?? 0) - ($row['withdrawn'] ?? 0)) * ($row['price'] ?? 0), 2) . "</td>";
            echo "<td><button style='background:#e67e22; color:white; border:none; padding:5px; border-radius:4px;'>📤</button></td>";
            echo "</tr>";
        }
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='11' style='color:red; text-align:center;'>SQL Error: " . $e->getMessage() . "</td></tr>";
}
?>
