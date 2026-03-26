<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

/**
 * EXPORT PROTOCOL: 
 * Professional Excel Generation with Consumption Summaries and Category Breakdowns.
 */

// 1. Fetch the data (Matching your "Daily Issuance Log" table)
$query = "SELECT * FROM diesel_history WHERE activity = 'OUTFLOW' ORDER BY rdate DESC, rtime DESC";
$res = $conn->query($query);
$rows = $res->fetchAll(PDO::FETCH_ASSOC);

// 2. Initialize variables for totals and categories
$total_year = 0;
$total_month = 0;
$unique_days = [];
$summary_type = [];
$summary_unit = [];

$current_year = date('Y');
$current_month = date('m');

foreach ($rows as $row) {
    $qty = (float)($row['qty'] ?? 0);
    $row_date = $row['rdate'];
    
    if ($row_date) {
        $unique_days[$row_date] = true;
        
        // Match the logic from your UI: Equipment Type and Equipment ID
        $type = !empty($row['equipment_type']) ? $row['equipment_type'] : 'Unknown';
        $unit = !empty($row['equipment_id']) ? $row['equipment_id'] : 'Unknown';

        // Time-based totals
        if (strpos($row_date, $current_year) === 0) {
            $total_year += $qty;
            if (substr($row_date, 5, 2) === $current_month) {
                $total_month += $qty;
            }
        }

        // Category-based totals
        $summary_type[$type] = ($summary_type[$type] ?? 0) + $qty;
        $summary_unit[$unit] = ($summary_unit[$unit] ?? 0) + $qty;
    }
}

// Sort summaries by highest consumption for the report
arsort($summary_type);
arsort($summary_unit);

$day_count = count($unique_days);
$daily_average = ($day_count > 0) ? ($total_year / $day_count) : 0;

// 3. Set Download Headers
$filename = "Diesel_Issuance_Report_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
?>

<meta charset="UTF-8">
<table border="1">
    <tr>
        <th colspan="12" style="background-color: #112941; color: #ffffff; font-size: 16px; height: 35px;">
            GOLDRICH CONSTRUCTION AND TRADING - DAILY DIESEL ISSUANCE LOG
        </th>
    </tr>

    <tr style="background-color: #f4f7f6;">
        <th colspan="4" align="left"><b>CONSUMPTION METRICS (LITERS)</b></th>
        <th colspan="3" align="left">Daily Avg: <?= number_format($daily_average, 2) ?></th>
        <th colspan="2" align="left">Month Total: <?= number_format($total_month, 2) ?></th>
        <th colspan="3" align="left">Year Total: <?= number_format($total_year, 2) ?></th>
    </tr>

    <tr><td colspan="12" style="border:none;"></td></tr>

    <tr>
        <th colspan="6" style="background-color: #f1c40f; color: #000;"><b>BY EQUIPMENT TYPE</b></th>
        <th colspan="6" style="background-color: #112941; color: #fff;"><b>BY UNIT (EQPT ID)</b></th>
    </tr>

    <tr>
        <td colspan="6" valign="top" style="padding:0;">
            <table border="1" width="100%">
                <tr style="background-color: #eee;">
                    <th>Equipment Type</th>
                    <th>Total Qty (L)</th>
                </tr>
                <?php foreach($summary_type as $type => $q): ?>
                <tr>
                    <td><?= htmlspecialchars($type) ?></td>
                    <td align="right"><?= number_format($q, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </td>
        <td colspan="6" valign="top" style="padding:0;">
            <table border="1" width="100%">
                <tr style="background-color: #eee;">
                    <th>Unit ID</th>
                    <th>Total Qty (L)</th>
                </tr>
                <?php foreach($summary_unit as $unit => $q): ?>
                <tr>
                    <td><?= htmlspecialchars($unit) ?></td>
                    <td align="right"><?= number_format($q, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>

    <tr><td colspan="12" style="border:none;"></td></tr>

    <thead>
        <tr style="background-color: #8B0000; color: #ffffff;">
            <th>TANK</th>
            <th>DATE</th>
            <th>TIME</th>
            <th>SHIFT</th>
            <th>EQPT. ID</th>
            <th>EQPT. TYPE</th>
            <th>WS NO.</th>
            <th>IS NO.</th>
            <th>OPERATOR</th>
            <th>CODE</th>
            <th>ODO/HRS</th>
            <th style="background-color: #f1c40f; color: #000;">QTY (L)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($rows as $row): ?>
        <tr>
            <td align="center"><?= htmlspecialchars($row['tank_source'] ?? '---') ?></td>
            <td align="center"><?= htmlspecialchars($row['rdate'] ?? '') ?></td>
            <td align="center"><?= $row['rtime'] ? date('h:i A', strtotime($row['rtime'])) : '---' ?></td>
            <td align="center"><?= htmlspecialchars($row['shift'] ?? '---') ?></td>
            <td><b><?= htmlspecialchars($row['equipment_id'] ?? '') ?></b></td>
            <td><?= htmlspecialchars($row['equipment_type'] ?? '---') ?></td>
            <td align="center"><?= htmlspecialchars($row['ws_no'] ?? '---') ?></td>
            <td align="center"><?= htmlspecialchars($row['is_no'] ?? '---') ?></td>
            <td><?= htmlspecialchars($row['name'] ?? '---') ?></td>
            <td align="center"><?= htmlspecialchars($row['code'] ?? '---') ?></td>
            <td align="right"><?= number_format($row['odometer'] ?? 0, 1) ?></td>
            <td align="right" style="font-weight: bold;">
                <?= number_format($row['qty'] ?? 0, 2) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<table border="0" style="margin-top: 20px;">
    <tr>
        <td colspan="4" style="font-size: 10px; color: #666;">
            Report Generated: <?= date('F d, Y h:i A') ?>
        </td>
    </tr>
</table>
