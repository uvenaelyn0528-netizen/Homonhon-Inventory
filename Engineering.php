<?php
/**
 * Engineering Inventory Management
 */

// 1. Supabase Database Configuration
// Set these values or pass them via environment variables in Render
$host     = getenv('DB_HOST')     ?: 'aws-1-ap-northeast-1.pooler.supabase.com';
$port     = getenv('DB_PORT')     ?: '6543'; // Transaction pooler port
$dbname   = getenv('DB_NAME')     ?: 'postgres.otrkginfndevnotgkajc';
$user     = getenv('DB_USER')     ?: 'postgres.otrkginfndevnotgkajc';
$password = getenv('DB_PASSWORD') ?: 'Wh01302016!2025';

// 2. Establish PDO Database Connection
try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Graceful error message display for debugging/development
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}

// 3. Fetch Items from Database
$inventoryItems = [];
$errorMessage   = null;

try {
    // Replace 'engineering_inventory' with your actual Supabase table name
    $stmt = $pdo->query("SELECT id, item_name, category, quantity, status, updated_at FROM engineering_inventory ORDER BY id ASC");
    $inventoryItems = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = "Could not fetch inventory records: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineering Department - Inventory</title>
    <style>
        * { box-sizing: border-box; font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
        .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; }
        tr:hover { background-color: #f1f5f9; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-available { background-color: #dcfce7; color: #166534; }
        .badge-low { background-color: #fef3c7; color: #92400e; }
        .badge-out { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<div class="container">
    <h1>Engineering Department Inventory</h1>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Item Name</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($inventoryItems)): ?>
                <?php foreach ($inventoryItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['id']); ?></td>
                        <td><strong><?= htmlspecialchars($item['item_name']); ?></strong></td>
                        <td><?= htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                        <td><?= htmlspecialchars($item['quantity']); ?></td>
                        <td>
                            <?php 
                                $statusClass = 'badge-available';
                                if ($item['quantity'] == 0) {
                                    $statusClass = 'badge-out';
                                } elseif ($item['quantity'] < 5) {
                                    $statusClass = 'badge-low';
                                }
                            ?>
                            <span class="badge <?= $statusClass; ?>">
                                <?= htmlspecialchars($item['status'] ?? ($item['quantity'] > 0 ? 'In Stock' : 'Out of Stock')); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($item['updated_at'] ?? '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">
                        No engineering inventory records found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
