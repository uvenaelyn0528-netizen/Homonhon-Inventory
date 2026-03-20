<?php
include 'db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

// Fetch only soft-deleted items
$sql = "SELECT * FROM inventory WHERE is_deleted = TRUE ORDER BY item_name ASC";
$stmt = $conn->query($sql);
$trash_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory Trash Bin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .trash-container { padding: 40px; font-family: sans-serif; }
        .restore-btn { background: #27ae60; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; }
        .perm-delete-btn { background: #c0392b; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; margin-left: 5px; }
        .back-btn { background: #34495e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body style="background: #f4f7f6;">
    <div class="trash-container">
        <a href="index.php" class="back-btn">⬅️ Back to Inventory</a>
        <h2 style="color: #2c3e50;">🗑️ Deleted Items (Trash Bin)</h2>
        <p style="color: #7f8c8d; font-size: 14px;">Items here are hidden from the main inventory but can be restored.</p>

        <table style="width: 100%; background: white; border-collapse: collapse; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background: #2c3e50; color: white; text-align: left;">
                    <th style="padding: 12px;">Item Name</th>
                    <th>Specification</th>
                    <th>Department</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($trash_items) > 0): ?>
                    <?php foreach ($trash_items as $row): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;"><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                            <td><?= htmlspecialchars($row['specification']) ?></td>
                            <td><?= htmlspecialchars($row['department']) ?></td>
                            <td style="text-align: center; padding: 10px;">
                                <a href="restore_item.php?id=<?= $row['id'] ?>" class="restore-btn" onclick="return confirm('Restore this item to inventory?')">🔄 Restore</a>
                                <a href="delete_log.php?type=perm_delete&id=<?= $row['id'] ?>" class="perm-delete-btn" onclick="return confirm('❗ DANGER: This will permanently delete this item and its history. Proceed?')">🔥 Permanent Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; padding: 40px; color: #999;">Trash bin is empty.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
