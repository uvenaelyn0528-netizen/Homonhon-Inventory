<?php
include 'db.php';
session_start();

// Ensure $role is available for the sticky action buttons
$role = $_SESSION['role'] ?? 'Viewer';

try {
    // 1. Get the search term from the URL
    $search = $_GET['search'] ?? '';

    // 2. Prepare and execute the PDO query
    $sql = "SELECT * FROM inventory WHERE item_name ILIKE :search OR department ILIKE :search ORDER BY item_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
    
    // 3. Define the $items variable that was missing
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($items) > 0) {
        foreach ($items as $row) {
            $id    = $row['id'];
            $name  = htmlspecialchars($row['item_name'] ?? '');
            $spec  = htmlspecialchars($row['specification'] ?? $row['Specification'] ?? '');
            $um    = htmlspecialchars($row['um'] ?? $row['UM'] ?? 'pcs');
            $dept  = htmlspecialchars($row['department'] ?? $row['Department'] ?? '');
            $purp  = htmlspecialchars($row['purpose'] ?? $row['Purpose'] ?? '');
            
            $received  = $row['received'] ?? 0;
            $withdrawn = $row['withdrawn'] ?? 0;
            $stock     = $received - $withdrawn;
            $price     = $row['price'] ?? 0;
            $min       = $row['min_stock'] ?? 0;
            $max       = $row['max_stock'] ?? 0;

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

            // Sticky Action Column
            echo "<td style='position: sticky; right: 0; background: white; border-left: 1px solid #ddd; display: flex; gap: 5px; z-index: 5; padding: 10px;'>";

            if ($role == 'Admin' || $role == 'Staff') {
                echo "<button title='Withdraw' onclick='openWithdrawModal($id, \"" . addslashes($name) . "\", $stock)' style='background:#e67e22; color:white; border:none; padding:5px 8px; border-radius:4px; cursor:pointer;'>📤</button>";
            }

            if ($role == 'Admin') {
                echo "<button title='Edit' onclick='openEditModal($id, \"" . addslashes($name) . "\", \"" . addslashes($spec) . "\", $min, $max)' style='background:#3498db; color:white; border:none; padding:5px 8px; border-radius:4px; cursor:pointer;'>✏️</button>";
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
