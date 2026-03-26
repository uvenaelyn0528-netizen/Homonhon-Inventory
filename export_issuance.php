<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

/**
 * EXPORT PROTOCOL: 
 * This script is READ-ONLY and available to all logged-in users (Admin, Staff, Viewer).
 */

// 1. Fetch the exact same data as your main table
$query = "SELECT * FROM diesel_history WHERE activity = 'OUTFLOW' ORDER BY rdate DESC, rtime DESC";
$res = $conn->query($query);
$rows = $res->fetchAll(PDO::FETCH_ASSOC);

// 2. Calculate Metrics for the Excel Header (matching your dashboard)
$total_year = 0;
$total_month = 0;
$unique_days = [];
$current_year = date('Y');
$current_month = date('m');

foreach ($rows as $row) {
    $qty = (float)($row['qty'] ?? 0);
    $row_date = $row['rdate'];
    
    if ($row_date) {
        $unique_days[$row_date] = true;
        if (strpos($row_date, $current_year) === 0) {
            $total_year += $qty;
            if (substr($row_date, 5, 2) === $current_month) {
                $total_month += $qty;
            }
        }
    }
}

$day_count = count($unique_days);
$daily_average = ($day_count > 0) ? ($total_year / $day_count) : 0;

// 3. Set Download Headers
$filename = "Goldrich_Diesel_Issuance_" . date('Y-m-d_Hi') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// 4. Output the Formatted Table
?>
<meta charset="UTF-8">
<table border="1">
    <tr>
        <th colspan="13" style="background-color: #112941; color: #ffffff; font-size: 16px; height: 40px; vertical-align: middle;">
            GOLDRICH CONSTRUCTION AND TRADING - DAILY DIESEL ISSUANCE LOG
        </th>
    </tr>

    <tr style="height: 30px;">
        <th colspan="3" style="background-color: #f8f9fa; border-bottom: 2px solid #8B0000;">CONSUMPTION SUMMARY:</th>
        <th colspan="3" align="left">Daily Avg: <?= number_format($daily_average, 2) ?> L</th>
        <th colspan="3" align="left">Current Month: <?= number_format($total_month, 2) ?> L</th>
        <th colspan="4" align="left">Year to Date: <?= number_format($total_year, 2) ?> L</th>
    </tr>

    <tr><td colspan="13" style="height: 10px; border: none;"></td></tr>

    <thead>
        <tr style="background-color: #8B0000; color: #ffffff;">
            <th width="150">TANK SOURCE</th>
            <th width="120">DATE</th>
            <th width="100">TIME</th>
            <th width="80">SHIFT</th>
            <th width="150">EQPT. ID / UNIT</th>
            <th width="180">TYPE OF EQPT.</th>
            <th width="120">WS NO.</th>
            <th width="120">IS NO.</th>
            <th width="200">OPERATOR NAME</th>
            <th width="100">CODE</th>
            <th width="120">ODOMETER/HRS</th>
            <th width="120" style="background-color: #f1c40f; color: #000000;">QTY (LITERS)</th>
            <th width="150">EXPORTED BY</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($rows as $row): ?>
        <tr>
            <td align="center"><?= htmlspecialchars($row['tank_source'] ?? '---') ?></td>
            <td align="center"><?= htmlspecialchars($row['rdate'] ?? '') ?></td>
            <td align="center"><?= $row['rtime'] ? date('h:i A', strtotime($row['rtime'])) : '---' ?></td>
            <td align="center"><?= htmlspecialchars($row['shift'] ?? '---') ?></td>
            <td><strong><?= htmlspecialchars($row['equipment_id'] ?? '') ?></strong></td>
            <td><?= htmlspecialchars($row['equipment_type'] ?? '---') ?></td>
            <td align="center"><?= htmlspecialchars($row['ws_no'] ?? '---') ?></td>
            <td align="center"><?= htmlspecialchars($row['is_no'] ?? '---') ?></td>
            <td><?= htmlspecialchars($row['name'] ?? '---') ?></td>
            <td align="center"><?= htmlspecialchars($row['code'] ?? '---') ?></td>
            <td align="right"><?= number_format($row['odometer'] ?? 0, 1) ?></td>
            <td align="right" style="font-weight: bold; color: #8B0000;">
                <?= number_format($row['qty'] ?? 0, 2) ?>
            </td>
            <td align="center" style="font-size: 9px; color: #666;">
                <?= $_SESSION['username'] ?? 'System User' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<table border="0" style="margin-top: 20px;">
    <tr><td colspan="13"></td></tr>
    <tr>
        <td colspan="3"><b>Prepared By:</b></td>
        <td colspan="4">_________________________</td>
        <td colspan="2"><b>Date:</b></td>
        <td colspan="4"><?= date('F d, Y h:i A') ?></td>
    </tr>
</table>
