<?php 
include 'db.php'; 

// 1. Handle Filters - Updated for PDO with Prepared Statements
$search = $_GET['search'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$query = "SELECT * FROM diesel_inventory WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (rr_no LIKE :search OR ws_no LIKE :search OR deposited_to LIKE :search OR received_from LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($from_date) && !empty($to_date)) {
    $query .= " AND rdate BETWEEN :from_date AND :to_date";
    $params[':from_date'] = $from_date;
    $params[':to_date'] = $to_date;
}

$query .= " ORDER BY rdate DESC, rtime DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);

// 2. Calculate Current Balance - Updated for PDO
$bal_stmt = $conn->query("SELECT SUM(CASE WHEN activity = 'INFLOW' THEN qty ELSE -qty END) as balance FROM diesel_inventory");
$balance_row = $bal_stmt->fetch(PDO::FETCH_ASSOC);
$balance = $balance_row['balance'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    </head>
<body>

<div class="page-wrapper">
    <header class="main-header">
        <div class="header-right">
            <div class="balance-card">
                <div style="font-size: 8px; opacity: 0.8;">CURRENT STOCK</div>
                <span style="font-size: 18px; font-weight: bold;"><?= number_format($balance, 2) ?> L</span>
            </div>
            <button class="btn btn-issuance" onclick="openFuelModal()">➕ NEW FUEL ENTRY</button>
        </div>
    </header>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Activity</th>
                    <th>Received From</th>
                    <th>Receiving Report No.</th>
                    <th>Deposited To</th>
                    <th>Withdrawal Slip No.</th>
                    <th>Tank No.</th>
                    <th style="text-align: right;">QTY (L)</th>
                    <th class="col-action" style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['rdate']) ?></td>
                    <td style="font-weight:bold; color:<?= $row['activity']=='INFLOW'?'#27ae60':'#e67e22'?>"><?= $row['activity'] ?></td>
                    <td><?= htmlspecialchars($row['received_from'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['rr_no'] ?: '---') ?></td>
                    <td><strong><?= htmlspecialchars($row['deposited_to']) ?></strong></td>
                    <td><?= htmlspecialchars($row['ws_no'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['from_tank_no'] ?: '---') ?></td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($row['qty'], 2) ?></td>
                    <td class="col-action" style="text-align: center;">
                        <button class="btn-edit" onclick="editRecord(<?= htmlspecialchars(json_encode($row)) ?>)">✏️</button>
                        <button class="btn-delete" onclick="deleteRecord(<?= $row['id'] ?>)">🗑️</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
