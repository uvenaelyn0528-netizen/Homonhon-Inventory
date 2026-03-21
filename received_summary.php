<?php 
include 'db.php'; 

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Get the user role (default to Viewer if not set)
$raw_role = $_SESSION['role'] ?? 'Viewer'; 
$role = strtolower(trim($raw_role)); 

// FETCH DATA
$query = "SELECT * FROM received_history ORDER BY received_date DESC, id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CALCULATIONS
$totalValue = 0; $totalItems = 0; $thisMonthValue = 0;
$currentMonth = date('Y-m');

if ($rows) {
    foreach ($rows as $row) {
        $rowPrice = floatval($row['price'] ?? 0);
        $rowQty   = floatval($row['qty'] ?? 0);
        $rowAmount = floatval($row['amount'] ?? ($rowPrice * $rowQty));
        $totalValue += $rowAmount;
        $totalItems += $rowQty;
        if (strpos($row['received_date'], $currentMonth) === 0) { $thisMonthValue += $rowAmount; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Received Items Summary</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f4f7f6; padding: 10px; height: 100vh; overflow: hidden; display: flex; flex-direction: column; font-family: 'Segoe UI', sans-serif; }
        .history-card { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 15px; width: 100%; max-height: 98vh; display: flex; flex-direction: column; margin: auto; }
        .summary-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 10px; }
        .stat-card { background: #fff; padding: 10px; border-radius: 12px; border: 1px solid #edf2f7; }
        .stat-label { font-size: 11px; color: #7f8c8d; text-transform: uppercase; font-weight: bold; }
        .stat-value { font-size: 20px; font-weight: 800; color: #2c3e50; }
        .header-section { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f8f9fa; padding-bottom: 5px; }
        .header-center { flex: 3; display: flex; align-items: center; justify-content: center; gap: 20px; }
        .header-center img { width: 70px; }
        .table-wrapper { overflow: auto; flex-grow: 1; margin-top: 10px; border: 1px solid #f1f1f1; border-radius: 10px; }
        #inflowTable { width: 100%; border-collapse: collapse; min-width: 1400px; }
        #inflowTable thead th { position: sticky; top: 0; background: #112941; color: white; padding: 12px; font-size: 11px; text-align: left; z-index: 10; }
        #inflowTable td { padding: 10px; border-bottom: 1px solid #f1f1f1; font-size: 13px; }
        .action-col { position: sticky; right: 0; background: white; text-align: center; border-left: 1px solid #eee; }
        .btn-control { height: 35px; padding: 0 15px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 12px; color: white; text-decoration: none; }
    </style>
</head>
<body>

<div class="history-card">
    <div class="header-section">
        <div class="header-left"><a href="index.php" class="btn-control" style="background: #34495e;">⬅ Dashboard</a></div>
        <div class="header-center">
            <img src="images/logo.png">
            <div style="text-align:center;">
                <h2 style="color: darkred; margin: 0; font-size: 20px; font-family: Broadway;">GOLDRICH CONSTRUCTION AND TRADING</h2>
                <h3 style="margin: 0; color: #2c3e50;">INVENTORY INFLOW REPORT</h3>
            </div>
        </div>
        <div class="header-right"></div>
    </div>

    <div class="summary-container">
        <div class="stat-card" style="border-left: 5px solid #2980b9;"><div class="stat-label">Total Value</div><div class="stat-value">₱<?= number_format($totalValue, 2); ?></div></div>
        <div class="stat-card" style="border-left: 5px solid #27ae60;"><div class="stat-label">Spent This Month</div><div class="stat-value">₱<?= number_format($thisMonthValue, 2); ?></div></div>
        <div class="stat-card" style="border-left: 5px solid #f39c12;"><div class="stat-label">Total Items</div><div class="stat-value"><?= number_format($totalItems, 0); ?> pcs</div></div>
    </div>

    <div style="background: #112941; padding: 10px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
        <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search RR#, items, suppliers..." style="padding: 8px; border-radius: 5px; width: 350px; border:none;">
        <div style="display: flex; gap: 10px;">
            
            <?php if ($role === 'admin'): ?>
                <form action="delete_log.php" method="POST" onsubmit="return confirm('DELETE ALL RECORDS PERMANENTLY?');" style="margin:0;">
                    <input type="hidden" name="clear_type" value="received">
                    <button type="submit" class="btn-control" style="background: #e74c3c;">🗑️ Clear History</button>
                </form>
                <button type="button" class="btn-control" style="background: #8e44ad;" onclick="document.getElementById('importFile').click()">📥 Import Excel</button>
            <?php else: ?>
                <button onclick="alert('Admin Access Required')" class="btn-control" style="background: #e74c3c; opacity: 0.5;">🗑️ Clear History</button>
                <button onclick="alert('Admin Access Required')" class="btn-control" style="background: #8e44ad; opacity: 0.5;">📥 Import Excel</button>
            <?php endif; ?>

            <button onclick="window.print()" class="btn-control" style="background: #2980b9;">Print Report 🖨️</button>
        </div>
    </div>

    <div class="table-wrapper">
        <table id="inflowTable">
            <thead>
                <tr>
                    <th>DATE</th><th>RR NUMBER</th><th>SUPPLIER</th><th>ITEM DESCRIPTION</th><th>SPECIFICATION</th><th>QTY</th><th>PRICE</th><th>AMOUNT</th><th>DEPT</th><th>PURPOSE</th><th class="action-col">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows): foreach ($rows as $row): 
                    $p = floatval($row['price'] ?? 0); $q = floatval($row['qty'] ?? 0);
                ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($row['received_date'])) ?></td>
                    <td style="font-weight:bold; color:#2980b9;"><?= htmlspecialchars($row['rr_number'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($row['supplier'] ?? '---') ?></td>
                    <td><strong><?= htmlspecialchars($row['item_name'] ?? '') ?></strong><br><small><?= htmlspecialchars($row['specification'] ?? '') ?></small></td>
                    <td style="color:green; font-weight:bold;">+ <?= number_format($q, 2) ?></td>
                    <td>₱<?= number_format($p, 2) ?></td>
                    <td>₱<?= number_format($p * $q, 2) ?></td>
                    <td><?= htmlspecialchars($row['department'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['purpose'] ?? '') ?></td>
                    <td class="action-col">
                        <?php if ($role === 'admin'): ?>
                            <a href='delete_received_row.php?id=<?= $row['id'] ?>' onclick="return confirm('Delete this row?')" style="text-decoration:none;">🗑️</a>
                        <?php else: ?>
                            🔒
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" style="text-align:center; padding:20px;">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<form id="importForm" action="import_received.php" method="POST" enctype="multipart/form-data" style="display:none;">
    <input type="file" name="excel_file" id="importFile" accept=".csv" onchange="submitImport()">
    <input type="submit" name="import_btn" id="hiddenSubmitBtn">
</form>

<script>
function submitImport() {
    if (document.getElementById('importFile').files.length > 0) {
        document.getElementById('hiddenSubmitBtn').click();
    }
}
function searchTable() {
    var filter = document.getElementById("searchInput").value.toUpperCase();
    var tr = document.getElementById("inflowTable").getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        tr[i].style.display = tr[i].innerText.toUpperCase().includes(filter) ? "" : "none";
    }
}
</script>
</body>
</html>
