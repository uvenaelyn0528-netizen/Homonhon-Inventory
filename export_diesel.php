<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Get Filters
$search = $_GET['search'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$sql = "SELECT * FROM diesel_inventory WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (rr_no LIKE :search OR ws_no LIKE :search OR deposited_to LIKE :search OR received_from LIKE :search OR withdrawn_from LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND rdate BETWEEN :from_date AND :to_date";
    $params[':from_date'] = $from_date;
    $params[':to_date'] = $to_date;
}
$sql .= " ORDER BY rdate DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

// 2. Calculate Total Balance for Header
$bal_stmt = $conn->query("SELECT (SUM(CASE WHEN activity = 'INFLOW' THEN qty ELSE 0 END) - SUM(CASE WHEN activity = 'OUTFLOW' THEN qty ELSE 0 END)) as balance FROM diesel_inventory");
$total_balance = $bal_stmt->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0;

// 3. Get Tank Breakdown
$tank_query = $conn->query("
    SELECT unit_name, SUM(amount) as unit_balance FROM (
        SELECT deposited_to as unit_name, qty as amount FROM diesel_inventory WHERE (deposited_to LIKE 'TANK%' OR deposited_to LIKE 'FT%') AND (activity = 'INFLOW' OR activity = 'TRANSFERRED')
        UNION ALL
        SELECT withdrawn_from as unit_name, -qty as amount FROM diesel_inventory WHERE (withdrawn_from LIKE 'TANK%' OR withdrawn_from LIKE 'FT%') AND (activity = 'OUTFLOW' OR activity = 'TRANSFERRED')
    ) AS combined GROUP BY unit_name HAVING unit_name IS NOT NULL AND unit_name NOT IN ('', '---', 'Direct to Unit') ORDER BY unit_name ASC
");
$tanks = $tank_query->fetchAll(PDO::FETCH_ASSOC);

// 4. Set Excel Headers
$filename = "Diesel_Report_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
?>

<table border="1">
    <tr>
        <th colspan="8" style="background-color: #112941; color: white; font-size: 16px;">DIESEL INVENTORY REPORT - GOLDRICH CONSTRUCTION</th>
    </tr>
    <tr>
        <th colspan="4" align="left">TOTAL SYSTEM STOCK:</th>
        <th colspan="4" align="right" style="color: #8B0000; font-size: 14px;"><?= number_format($total_balance, 2) ?> L</th>
    </tr>
    <tr><td colspan="8"></td></tr> <tr style="background-color: #f1c40f;">
        <th colspan="8">CURRENT TANK VOLUMES</th>
    </tr>
    <?php 
    // Chunk tanks into rows of 4 for better Excel readability
    $chunks = array_chunk($tanks, 4);
    foreach($chunks as $chunk): ?>
        <tr>
            <?php foreach($chunk as $t): ?>
                <td colspan="1" style="background-color: #eee;"><b><?= $t['unit_name'] ?></b></td>
                <td colspan="1" align="right"><?= number_format($t['unit_balance'], 0) ?> L</td>
            <?php endforeach; ?>
            <?php 
                // Fill empty cells if chunk is less than 4
                for($i=count($chunk); $i<4; $i++) echo "<td colspan='2'></td>"; 
            ?>
        </tr>
    <?php endforeach; ?>
    
    <tr><td colspan="8"></td></tr> <thead>
        <tr style="background-color: #112941; color: white;">
            <th>DATE</th>
            <th>ACTIVITY</th>
            <th>SUPPLIER / SOURCE</th>
            <th>RR NO.</th>
            <th>WS NO.</th>
            <th>FROM</th>
            <th>TO</th>
            <th>QTY (L)</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
        <tr>
            <td><?= date('M d, Y', strtotime($row['rdate'])) ?></td>
            <td><?= $row['activity'] ?></td>
            <td><?= $row['received_from'] ?></td>
            <td><?= $row['rr_no'] ?></td>
            <td><?= $row['ws_no'] ?></td>
            <td><?= $row['withdrawn_from'] ?></td>
            <td><?= $row['deposited_to'] ?></td>
            <td align="right"><?= number_format($row['qty'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
