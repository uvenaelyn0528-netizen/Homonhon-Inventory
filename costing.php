<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departmental Costing - Goldrich</title>
    <style>
        :root {
            --primary: #2c3e50;
            --dark-red: #8b0000;
            --gold: #f1c40f;
            --bg-gray: #f4f7f6;
        }

        /* Prevent body from scrolling so the internal wrapper takes over */
        html, body { 
            height: 100%; 
            margin: 0; 
            padding: 0; 
            overflow: hidden; 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: var(--bg-gray); 
        }

        /* Main Container setup as a Flexbox Column */
        .report-container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            height: 100vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        /* --- FROZEN TOP SECTION --- */
        .frozen-top {
            flex-shrink: 0; /* Prevents this section from shrinking */
            background: white;
            z-index: 1100;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid var(--dark-red);
            padding: 15px 30px;
        }

        .header-left, .header-right { flex: 1; }
        .header-right { display: flex; justify-content: flex-end; }

        .header-center {
            flex: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            text-align: center;
        }

        .header-center img { width: 70px; height: auto; }

        .header-text-group h2 { 
            color: var(--dark-red); 
            margin: 0; 
            font-size: 20px; 
            font-family: Broadway, sans-serif; 
        }

        /* Search Bar Area */
        .table-controls {
            background: #112941; 
            padding: 10px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            flex-shrink: 0;
        }

        /* --- SCROLLABLE TABLE AREA --- */
        .table-wrapper {
            flex-grow: 1; /* Takes up remaining vertical space */
            overflow-y: auto; /* This enables the scrollbar */
            padding: 0;
            background: white;
        }

        /* Sticky Department Headers within the scroll area */
        .dept-header { 
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #2c3e50; 
            color: white; 
            padding: 12px 25px; 
            font-size: 15px; 
            font-weight: bold; 
        }
        
        thead th { 
            position: sticky;
            top: 43px; /* Sits exactly under Dept Header */
            z-index: 900;
            background: #f8f9fa;
            text-align: left; 
            color: #7f8c8d; 
            font-size: 11px; 
            text-transform: uppercase; 
            padding: 12px 10px; 
            border-bottom: 2px solid #eee; 
        }

        /* Content Styling */
        .project-section { padding: 0 25px 20px 25px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        td { padding: 12px 10px; font-size: 13px; border-bottom: 1px solid #f0f0f0; }
        
        .purpose-label { 
            background: #fff9e6; color: #d35400; font-weight: bold; 
            padding: 10px 15px; display: block; border-left: 4px solid #f39c12; margin-top: 20px;
        }

        .subtotal-row { background: #f1f8ff; font-weight: bold; color: #2980b9; }

        /* --- FROZEN FOOTER --- */
        .grand-total-bar { 
            flex-shrink: 0;
            background: var(--dark-red); 
            color: white; 
            padding: 15px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }

        .btn { 
            text-decoration: none; padding: 8px 15px; border-radius: 6px; 
            font-weight: bold; font-size: 12px; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 5px;
        }

        @media print {
            html, body { overflow: visible; height: auto; }
            .no-print { display: none !important; }
            .table-wrapper { overflow: visible; }
            .report-container { height: auto; box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="report-container">
    
    <div class="frozen-top no-print">
        <div class="header-section">
            <div class="header-left">
                <a href="index.php" class="btn" style="background: #34495e; color: white;">⬅ Dashboard</a>
            </div>

            <div class="header-center">
                <img src="images/logo.png" alt="Logo">
                <div class="header-text-group">
                    <h2 style="color: darkred; margin: 0; font-size: 24px; font-family: Broadway, 'Arial Black', sans-serif;">
                    GOLDRICH CONSTRUCTION AND TRADING
                </h2>
                    <p style="color: #7f8c8d; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin: 3px 0;">
                    Homonhon Nickel Project • Logistics & Warehouse
                </p>
                <h2 style="color: #2c3e50; margin: 0; font-size: 20px; font-weight: 800; line-height: 1.2;font-family:sans-serif;">
                   DEPARTMENTAL COSTING REPORT 
                </h2>
                </div>
            </div>

            <div class="header-right">
                <button onclick="window.print()" class="btn" style="background: #27ae60; color: white;">🖨️ Print Report</button>
            </div>
        </div>

        <div class="table-controls">
            <div class="search-box">
                <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search item, department..." 
                       style="width: 350px; padding: 8px; border-radius: 5px; border: none; outline: none;">
            </div>
            <div class="action-btns" style="display: flex; gap: 10px;">
                 <form method="POST" action="delete_log.php" onsubmit="return confirm('Clear all records?');" style="margin: 0;">
                    <button type="submit" class="btn" style="background: #e74c3c; color: white;">🗑️ Clear History</button>
                </form>
            </div>
        </div>
    </div>

 <div class="table-wrapper">
    <?php
    $grand_total = 0;
    
    // Updated SQL to:
    // 1. Exclude departments that are purely numeric (using Regex)
    // 2. Exclude 'GENERAL OPERATIONS' purpose
    $sql = "SELECT department, purpose, 
                   SUM(qty) as total_qty, 
                   SUM(qty * price) as project_total 
            FROM inventory 
            WHERE department IS NOT NULL AND department != ''
              AND department ~ '[a-zA-Z]' 
              AND UPPER(purpose) != 'GENERAL OPERATIONS'
            GROUP BY department, purpose
            ORDER BY department ASC, project_total DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $current_dept = "";

    if (count($rows) > 0):
        foreach($rows as $row):
            if ($current_dept != $row['department']):
                if ($current_dept != "") echo "</tbody></table></div>"; 
                
                $current_dept = $row['department'];
                echo "<div class='dept-header'>" . strtoupper($current_dept) . "</div>";
                echo "<div class='project-section'>";
                echo "<table>
                        <thead>
                            <tr>
                                <th>Project / Purpose</th>
                                <th style='text-align:center; width: 150px;'>Total Items (Qty)</th>
                                <th style='text-align:right; width: 200px;'>Total Costing</th>
                            </tr>
                        </thead>
                        <tbody>";
            endif;

            $project_cost = (float)$row['project_total'];
            $grand_total += $project_cost;
    ?>
            <tr class="item-row">
                <td style="font-weight: 600; color: #2c3e50;">
                    📌 <?php echo htmlspecialchars($row['purpose'] ?: 'SPECIFIED PROJECT'); ?>
                </td>
                <td style="text-align:center;">
                    <?php echo number_format((float)$row['total_qty'], 2); ?>
                </td>
                <td style="text-align:right; font-weight: bold; color: #2980b9;">
                    ₱<?php echo number_format($project_cost, 2); ?>
                </td>
            </tr>
    <?php 
        endforeach; 
        echo "</tbody></table></div>"; 
    else: 
    ?>
        <div style="text-align: center; padding: 100px; color: #7f8c8d;">
            <h3>No valid departmental data found.</h3>
        </div>
    <?php endif; ?>
</div>

    <div class="grand-total-bar">
        <div>
            <span style="font-size: 10px; display: block; opacity: 0.8; font-weight: bold;">TOTAL VALUATION</span>
            <span style="font-size: 24px; font-weight: bold; color: var(--gold);">₱<?php echo number_format($grand_total, 2); ?></span>
        </div>
        <div style="text-align: right; font-size: 11px;">
            Report Date: <strong><?php echo date('F d, Y'); ?></strong>
        </div>
    </div>
</div>

<script>
function searchTable() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll(".item-row");
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}
</script>

</body>
</html>
