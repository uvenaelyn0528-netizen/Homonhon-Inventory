<?php
/**
 * Engineering Inventory Management
 */

// 1. Supabase Database Configuration
// Set these values or pass them via environment variables in Render
$host     = getenv('DB_HOST')     ?: 'aws-1-ap-northeast-1.pooler.supabase.com';
$port     = getenv('DB_PORT')     ?: '6543'; // Transaction pooler port
$dbname   = getenv('DB_NAME')     ?: 'postgres';
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
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}

// 3. Fetch Items from Database
$inventoryItems = [];
$errorMessage   = null;

try {
    // Querying the 'inventory' table
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY id ASC");
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
    <title>Engineering Department</title>
    <style>
        * { box-sizing: border-box; font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .header-section { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        h1 { margin: 0; color: #1e293b; font-size: 24px; }
        
        /* Action Buttons Navigation */
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; transition: background-color 0.2s ease, transform 0.1s ease; }
        .btn:active { transform: scale(0.98); }
        
        .btn-eur { background-color: #2563eb; color: #ffffff; }
        .btn-eur:hover { background-color: #1d4ed8; }
        
        .btn-ticket { background-color: #059669; color: #ffffff; }
        .btn-ticket:hover { background-color: #047857; }
        
        .btn-report { background-color: #d97706; color: #ffffff; }
        .btn-report:hover { background-color: #b45309; }

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
    <div class="header-section">
        <h1>Engineering Department</h1>
        
        <!-- Action Buttons -->
        <div class="btn-group">
            <a href="eur.php" class="btn btn-eur">EUR</a>
            <a href="trip_ticket.php" class="btn btn-ticket">Trip Ticket</a>
            <a href="reportorial.php" class="btn btn-report">Reportorial</a>
        </div>
    </div>

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
                        <td><?= htmlspecialchars($item['id'] ?? '—'); ?></td>
                        <td><strong><?= htmlspecialchars($item['item_name'] ?? $item['name'] ?? 'N/A'); ?></strong></td>
                        <td><?= htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                        <td><?= htmlspecialchars($item['quantity'] ?? '0'); ?></td>
                        <td>
                            <?php 
                                $qty = $item['quantity'] ?? 0;
                                $statusClass = 'badge-available';
                                if ($qty == 0) {
                                    $statusClass = 'badge-out';
                                } elseif ($qty < 5) {
                                    $statusClass = 'badge-low';
                                }
                            ?>
                            <span class="badge <?= $statusClass; ?>">
                                <?= htmlspecialchars($item['status'] ?? ($qty > 0 ? 'In Stock' : 'Out of Stock')); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($item['updated_at'] ?? '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">
                        No inventory records found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
