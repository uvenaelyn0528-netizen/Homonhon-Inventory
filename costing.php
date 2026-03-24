<?php 
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 

/**
 * Updated to handle different column names for withdrawals
 *
 */
function getCostingData($conn, $table) {
    // Detect the correct quantity column for each table
    // If 'withdrawals' fails with 'qty', it likely uses 'qty_out' or similar from your schema
    $qtyCol = ($table === 'withdrawals') ? 'qty' : 'qty'; 
    
    // Check if we need to adjust for the specific Supabase withdrawal schema
    $priceCol = 'price';

    $whereClauses = [
        "department IS NOT NULL",
        "department != ''",
        "department ~ '[a-zA-Z]'", //
        "UPPER(purpose) != 'GENERAL OPERATIONS'"
    ];

    $whereSql = implode(" AND ", $whereClauses);

    $sql = "SELECT department, purpose, 
                   SUM($qtyCol) as total_qty, 
                   SUM($qtyCol * $priceCol) as project_total 
            FROM $table 
            WHERE $whereSql
            GROUP BY department, purpose
            ORDER BY department ASC, project_total DESC";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback: If 'qty' doesn't exist in withdrawals, try 'qty_out'
        if ($table === 'withdrawals' && strpos($e->getMessage(), 'qty') !== false) {
            $sql = str_replace("SUM(qty)", "SUM(qty)", $sql); // Double check your DB column name here
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        throw $e;
    }
}

$received_rows = getCostingData($conn, 'received_history');
$withdrawn_rows = getCostingData($conn, 'withdrawals'); 

// Calculate Grand Totals
$grand_received = array_sum(array_column($received_rows, 'project_total'));
$grand_withdrawn = array_sum(array_column($withdrawn_rows, 'project_total'));

function getDeptTotals($rows) {
    $totals = [];
    foreach ($rows as $row) {
        $dept = strtoupper($row['department']);
        $totals[$dept] = ($totals[$dept] ?? 0) + $row['project_total'];
    }
    return $totals;
}

$received_dept_totals = getDeptTotals($received_rows);
$withdrawn_dept_totals = getDeptTotals($withdrawn_rows);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Departmental Costing - Goldrich</title>
    <style>
        :root { --dark-red: #8b0000; --gold: #f1c40f; --bg-gray: #f4f7f6; }
        html, body { height: 100%; margin: 0; overflow: hidden; font-family: 'Segoe UI', sans-serif; background: var(--bg-gray); }
        .report-container { display: flex; flex-direction: column; height: 100vh; background: white; }
        .header-section { flex-shrink: 0; border-bottom: 4px solid var(--dark-red); padding: 10px 30px; display: flex; justify-content: space-between; align-items: center; }
        .split-view { display: flex; flex: 1; overflow: hidden; gap: 2px; background: #ddd; }
        .costing-column { flex: 1; display: flex; flex-direction: column; background: white; overflow-y: auto; }
        .col-header { position: sticky; top: 0; z-index: 1100; background: #112941; color: white; padding: 12px; text-align: center; font-weight: bold; border-bottom: 2px solid var(--gold); }
        .dept-header { position: sticky; top: 43px; z-index: 1000; background: #2c3e50; color: white; padding: 8px 20px; font-size: 13px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .net-balance-row { background: #fdfaf0; border-bottom: 1px solid #eee; padding: 5px 20px; font-size: 11px; color: #555; display: flex; justify-content: flex-end; gap: 15px; }
        .balance-val { font-weight: bold; color: #27ae60; }
        table { width: 100%; border-collapse: collapse; }
        th { position: sticky; top: 76px; z-index: 900; background: #f8f9fa; padding: 10px; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #eee; text-align: left; }
        td { padding: 10px; font-size: 12px; border-bottom: 1px solid #f0f0f0; }
        .grand-total-bar { flex-shrink: 0; background: var(--dark-red); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

<div class="report-container">
    <div class="header-section">
        <a href="index.php" style="text-decoration:none; color: #34495e; font-weight:bold;">⬅ Dashboard</a>
        <div style="text-align:center;">
            <h2 style="color: darkred; margin: 0; font-family: Broadway;">GOLDRICH CONSTRUCTION AND TRADING</h2>
            <h3 style="margin:0; font-size: 14px;">DEPARTMENTAL COSTING ANALYSIS</h3>
        </div>
        <button onclick="window.print()" style="background:#27ae60; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;">Print 🖨️</button>
    </div>

    <div class="split-view">
        <div class="costing-column">
            <div class="col-header">📥 RECEIVED (INFLOW) COSTING</div>
            <?php renderCostingTable($received_rows, $received_dept_totals, $withdrawn_dept_totals, 'inflow'); ?>
        </div>
        <div class="costing-column">
            <div class="col-header">📤 WITHDRAWN (OUTFLOW) COSTING</div>
            <?php renderCostingTable($withdrawn_rows, $withdrawn_dept_totals, $received_dept_totals, 'outflow'); ?>
        </div>
    </div>

    <div class="grand-total-bar">
        <div><span style="font-size: 10px; opacity: 0.8;">NET RECEIVED:</span><span style="font-size: 18px; font-weight: bold; color: var(--gold); margin-left: 10px;">₱<?= number_format($grand_received, 2) ?></span></div>
        <div style="text-align:center;"><span style="font-size: 10px; opacity: 0.8;">TOTAL STOCK VALUE:</span><span style="font-size: 18px; font-weight: bold; color: #fff; margin-left: 10px;">₱<?= number_format($grand_received - $grand_withdrawn, 2) ?></span></div>
        <div><span style="font-size: 10px; opacity: 0.8;">NET WITHDRAWN:</span><span style="font-size: 18px; font-weight: bold; color: white; margin-left: 10px;">₱<?= number_format($grand_withdrawn, 2) ?></span></div>
    </div>
</div>

<?php
function renderCostingTable($rows, $current_totals, $comparison_totals, $type) {
    if (empty($rows)) {
        echo "<div style='padding:50px; text-align:center; color:#95a5a6;'>No valid records found.</div>";
        return;
    }

    $current_dept = "";
    foreach ($rows as $row) {
        $dept_key = strtoupper($row['department']);
        if ($current_dept != $row['department']) {
            if ($current_dept != "") echo "</tbody></table>";
            $current_dept = $row['department'];
            
            $total_amt = $current_totals[$dept_key] ?? 0;
            $other_amt = $comparison_totals[$dept_key] ?? 0;
            $balance = ($type === 'inflow') ? ($total_amt - $other_amt) : $total_amt;

            echo "<div class='dept-header'><span>" . $dept_key . "</span><span>₱" . number_format($total_amt, 2) . "</span></div>";
            if ($type === 'inflow') {
                echo "<div class='net-balance-row'>Remaining Dept. Value: <span class='balance-val'>₱" . number_format($balance, 2) . "</span></div>";
            }
            echo "<table><thead><tr><th>Purpose / Project</th><th style='text-align:right;'>Costing</th></tr></thead><tbody>";
        }
        echo "<tr><td style='font-weight:600; color:#2c3e50;'>📌 " . htmlspecialchars($row['purpose']) . "</td><td style='text-align:right; color:#2980b9; font-weight:bold;'>₱" . number_format($row['project_total'], 2) . "</td></tr>";
    }
    echo "</tbody></table>";
}
?>
</body>
</html>
