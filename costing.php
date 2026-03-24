<?php 
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 

// Function to fetch data while excluding numeric departments and "GENERAL OPERATIONS"
function getCostingData($conn, $table, $type) {
    $dateField = ($type === 'received') ? 'received_date' : 'date'; // Adjust based on your schema
    $sql = "SELECT department, purpose, 
                   SUM(qty) as total_qty, 
                   SUM(qty * price) as project_total 
            FROM $table 
            WHERE department IS NOT NULL AND department != ''
              AND department ~ '[a-zA-Z]' 
              AND UPPER(purpose) != 'GENERAL OPERATIONS'
            GROUP BY department, purpose
            ORDER BY department ASC, project_total DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$received_rows = getCostingData($conn, 'received_history', 'received');
$withdrawn_rows = getCostingData($conn, 'inventory', 'withdrawn'); // Assuming 'inventory' tracks withdrawals in your setup
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Departmental Costing - Goldrich</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root { --primary: #2c3e50; --dark-red: #8b0000; --gold: #f1c40f; --bg-gray: #f4f7f6; }
        html, body { height: 100%; margin: 0; overflow: hidden; font-family: 'Segoe UI', sans-serif; background: var(--bg-gray); }
        .report-container { max-width: 1400px; margin: 0 auto; background: white; height: 100vh; display: flex; flex-direction: column; }
        
        /* Layout for side-by-side display */
        .split-view { display: flex; flex: 1; overflow: hidden; gap: 2px; background: #ddd; }
        .costing-column { flex: 1; display: flex; flex-direction: column; background: white; overflow-y: auto; }
        
        .col-header { background: #112941; color: white; padding: 15px; text-align: center; position: sticky; top: 0; z-index: 1100; font-weight: bold; }
        .dept-header { position: sticky; top: 45px; z-index: 1000; background: #2c3e50; color: white; padding: 8px 20px; font-size: 13px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 10px; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #eee; text-align: left; position: sticky; top: 78px; z-index: 900; }
        td { padding: 10px; font-size: 12px; border-bottom: 1px solid #f0f0f0; }

        .grand-total-bar { flex-shrink: 0; background: var(--dark-red); color: white; padding: 10px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-section { border-bottom: 4px solid var(--dark-red); padding: 10px 30px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

<div class="report-container">
    <div class="header-section">
        <a href="index.php" style="text-decoration:none; color: #34495e; font-weight:bold;">⬅ Dashboard</a>
        <div style="text-align:center;">
            <h2 style="color: darkred; margin: 0; font-family: Broadway;">GOLDRICH CONSTRUCTION AND TRADING</h2>
            <h3 style="margin:0; font-size: 16px;">DEPARTMENTAL COSTING ANALYSIS</h3>
        </div>
        <button onclick="window.print()" style="background:#27ae60; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;">Print Report 🖨️</button>
    </div>

    <div class="split-view">
        <div class="costing-column">
            <div class="col-header" style="background: #1e3a5a;">📥 RECEIVED (INFLOW) COSTING</div>
            <?php renderTable($received_rows); ?>
        </div>

        <div class="costing-column">
            <div class="col-header" style="background: #2c3e50;">📤 WITHDRAWN (OUTFLOW) COSTING</div>
            <?php renderTable($withdrawn_rows); ?>
        </div>
    </div>

    <div class="grand-total-bar">
        <span>Report Generated: <strong><?= date('F d, Y') ?></strong></span>
        <span style="color: var(--gold); font-weight:bold;">Homonhon Nickel Project • Logistics & Warehouse</span>
    </div>
</div>

<?php
function renderTable($rows) {
    if (empty($rows)) {
        echo "<div style='padding:50px; text-align:center; color:#95a5a6;'>No data available.</div>";
        return;
    }

    $current_dept = "";
    foreach ($rows as $row) {
        if ($current_dept != $row['department']) {
            if ($current_dept != "") echo "</tbody></table>";
            $current_dept = $row['department'];
            echo "<div class='dept-header'>" . strtoupper($current_dept) . "</div>";
            echo "<table><thead><tr><th>Purpose</th><th style='text-align:right;'>Costing</th></tr></thead><tbody>";
        }
        echo "<tr>
                <td style='font-weight:600;'>📌 " . htmlspecialchars($row['purpose']) . "</td>
                <td style='text-align:right; color:#2980b9; font-weight:bold;'>₱" . number_format($row['project_total'], 2) . "</td>
              </tr>";
    }
    echo "</tbody></table>";
}
?>

</body>
</html>
