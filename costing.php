<?php 
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 

function getCostingData($conn, $table) {
    // Excludes numeric departments and 'GENERAL OPERATIONS' purpose
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

$received_rows = getCostingData($conn, 'received_history');
$withdrawn_rows = getCostingData($conn, 'inventory'); 

// Calculate Grand Totals for the footer
$grand_received = array_sum(array_column($received_rows, 'project_total'));
$grand_withdrawn = array_sum(array_column($withdrawn_rows, 'project_total'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Departmental Costing - Goldrich</title>
    <style>
        :root { --dark-red: #8b0000; --gold: #f1c40f; --bg-gray: #f4f7f6; }
        
        /* Fixed height container to ensure footer visibility */
        html, body { height: 100%; margin: 0; overflow: hidden; font-family: 'Segoe UI', sans-serif; background: var(--bg-gray); }
        .report-container { display: flex; flex-direction: column; height: 100vh; max-width: 100%; background: white; }

        /* Header section */
        .header-section { flex-shrink: 0; border-bottom: 4px solid var(--dark-red); padding: 10px 30px; display: flex; justify-content: space-between; align-items: center; }
        
        /* Scrollable area for tables */
        .split-view { display: flex; flex: 1; overflow: hidden; gap: 2px; background: #ddd; }
        .costing-column { flex: 1; display: flex; flex-direction: column; background: white; overflow-y: auto; }
        
        .col-header { position: sticky; top: 0; z-index: 1100; background: #112941; color: white; padding: 12px; text-align: center; font-weight: bold; border-bottom: 2px solid var(--gold); }
        
        /* Sticky Department Header with Flexbox for right-aligned total */
        .dept-header { 
            position: sticky; top: 43px; z-index: 1000; 
            background: #2c3e50; color: white; padding: 8px 20px; 
            font-size: 13px; font-weight: bold;
            display: flex; justify-content: space-between; align-items: center;
        }
        .dept-total { color: var(--gold); font-size: 14px; }

        table { width: 100%; border-collapse: collapse; }
        th { position: sticky; top: 76px; z-index: 900; background: #f8f9fa; padding: 10px; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #eee; text-align: left; }
        td { padding: 10px; font-size: 12px; border-bottom: 1px solid #f0f0f0; }

        /* Fixed Footer at the bottom */
        .grand-total-bar { 
            flex-shrink: 0; background: var(--dark-red); color: white; 
            padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; 
            box-shadow: 0 -5px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="report-container">
    <div class="header-section">
        <a href="index.php" style="text-decoration:none; color: #34495e; font-weight:bold;">⬅ Dashboard</a>
        <div style="text-align:center;">
            <h2 style="color: darkred; margin: 0; font-family: Broadway;">GOLDRICH CONSTRUCTION AND TRADING</h2>
            <h3 style="margin:0; font-size: 14px; letter-spacing: 1px;">DEPARTMENTAL COSTING ANALYSIS</h3>
        </div>
        <button onclick="window.print()" style="background:#27ae60; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;">Print 🖨️</button>
    </div>

    <div class="split-view">
        <div class="costing-column">
            <div class="col-header">📥 RECEIVED (INFLOW) COSTING</div>
            <?php renderCostingTable($received_rows); ?>
        </div>
        <div class="costing-column">
            <div class="col-header">📤 WITHDRAWN (OUTFLOW) COSTING</div>
            <?php renderCostingTable($withdrawn_rows); ?>
        </div>
    </div>

    <div class="grand-total-bar">
        <div>
            <span style="font-size: 10px; opacity: 0.8;">NET RECEIVED:</span>
            <span style="font-size: 18px; font-weight: bold; color: var(--gold); margin-left: 10px;">₱<?= number_format($grand_received, 2) ?></span>
        </div>
        <div>
            <span style="font-size: 10px; opacity: 0.8;">NET WITHDRAWN:</span>
            <span style="font-size: 18px; font-weight: bold; color: white; margin-left: 10px;">₱<?= number_format($grand_withdrawn, 2) ?></span>
        </div>
    </div>
</div>

<?php
function renderCostingTable($rows) {
    if (empty($rows)) {
        echo "<div style='padding:50px; text-align:center; color:#95a5a6;'>No valid records found.</div>";
        return;
    }

    // Pre-calculate departmental totals for the headers
    $dept_totals = [];
    foreach ($rows as $row) {
        $dept_totals[$row['department']] = ($dept_totals[$row['department']] ?? 0) + $row['project_total'];
    }

    $current_dept = "";
    foreach ($rows as $row) {
        if ($current_dept != $row['department']) {
            if ($current_dept != "") echo "</tbody></table>";
            $current_dept = $row['department'];
            $total_for_this_dept = number_format($dept_totals[$current_dept], 2);
            
            echo "<div class='dept-header'>";
            echo "<span>" . strtoupper($current_dept) . "</span>";
            echo "<span class='dept-total'>₱$total_for_this_dept</span>"; // Added departmental total on the right
            echo "</div>";
            
            echo "<table><thead><tr><th>Purpose / Project</th><th style='text-align:right;'>Costing</th></tr></thead><tbody>";
        }
        echo "<tr>
                <td style='font-weight:600; color:#2c3e50;'>📌 " . htmlspecialchars($row['purpose']) . "</td>
                <td style='text-align:right; color:#2980b9; font-weight:bold;'>₱" . number_format($row['project_total'], 2) . "</td>
              </tr>";
    }
    echo "</tbody></table>";
}
?>

</body>
</html>
