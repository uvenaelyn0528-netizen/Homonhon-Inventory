<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal History Summary</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* 1. Global Page Setup */
        body {
            background-color: #f4f7f6;
            padding: 20px;
            height: 50vh;
            overflow: hidden;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* 2. Main Card Container */
        .history-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 25px;
            max-width: 1400px;
            margin: auto;
            width: 100%;
            max-height: 95vh;
            display: flex;
            flex-direction: column;
        }

        /* 3. Header Section (Flexbox) */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 15px;
            margin-bottom: 15px;
            width: 100%;
        }

        .header-left {
            flex: 1;
            display: flex;
            justify-content: flex-start;
        }

        .header-center {
            flex: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
        }

        .header-right {
            flex: 1;
        }

        .header-center img {
            width: 90px;
            height: auto;
            display: block;
        }

        .header-text-group {
            text-align: left;
        }

        /* 4. Table and Scrollbar */
        .table-wrapper {
            overflow-y: auto;
            flex-grow: 1;
            margin-top: 10px;
            border: 1px solid #f1f1f1;
            border-radius: 10px;
        }

        .table-wrapper::-webkit-scrollbar { width: 8px; }
        .table-wrapper::-webkit-scrollbar-track { background: #f1f1f1; }
        .table-wrapper::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

        /* 5. Table Design */
        #withdrawalTable {
            width: 100%;
            border-collapse: collapse;
        }

        #withdrawalTable thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #112941 !important; /* Orange for Withdrawal/Outflow */
            color: white;
            padding: 15px;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
        }

        #withdrawalTable td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f1f1;
            font-size: 13px;
            color: #2c3e50;
        }

        #withdrawalTable tbody tr:hover { background-color: #fffaf5; }

        /* 6. UI Components */
        .badge-count {
            background: #fef5e7;
            color: #e67e22;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
        }

        .delete-btn-log {
            background: #ffeeee;
            color: #e74c3c;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 11px;
            font-weight: bold;
            transition: 0.2s;
        }
        .delete-btn-log:hover { background: #e74c3c; color: white; }

        @media print {
            .header-left, .table-controls, .action-col, .delete-btn-log { display: none !important; }
            .history-card { box-shadow: none; padding: 0; border: none; }
            body { background: white; overflow: visible; padding: 0; }
            .table-wrapper { overflow: visible; border: none; }
            #printFooter { display: block !important; margin-top: 50px; }
        }
    </style>
</head>
<body>

<div class="history-card">
    <div class="header-section">
        <div class="header-left">
            <a href="index.php" style="background: #34495e; padding: 10px 18px; text-decoration: none; border-radius: 8px; color: white; font-size: 12px; display: flex; align-items: center; gap: 8px; font-weight: bold;">
                ⬅ Back to Dashboard
            </a>
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
                <h2 style="color: #2c3e50; margin: 0; font-size: 20px; font-weight: 800; line-height: 1.2;">
                    INVENTORY OUTFLOW REPORT 
                    <span class="badge-count">WITHDRAWAL LOGS</span>
                </h2>
            </div>
        </div>

        <div class="header-right"></div>
    </div>

    <div class="table-controls" style="background: #112941; padding: 5px; border-radius: 10px; border: 1px solid #edf2f7; display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px;">
        <div class="left-side">
            <label style="display: block; font-size: 10px; font-weight: bold; color: #7f8c8d; margin-bottom: 5px; text-transform: uppercase;">Quick Search History</label>
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search items, names, or departments..." style="width: 350px; padding: 5px; border-radius: 8px; border: 1px solid #ddd; outline: none;">
        </div>
        
        <div class="right-side" style="display: flex; gap: 10px; align-items: center;">
            <form method="POST" action="delete_log.php" onsubmit="return confirm('PERMANENTLY DELETE ALL WITHDRAWAL RECORDS?');" style="margin: 0;">
                <input type="hidden" name="clear_type" value="withdrawal">
                <button type="submit" style="height: 30px; padding: 0 15px; background: #e74c3c; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">🗑️ Clear History</button>
            </form>
            <button onclick="window.print()" style="height: 30px; padding: 0 15px; background: #2980b9; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">Generate Report 🖨️</button>
        </div>
    </div>

    <div class="table-wrapper">
        <table id="withdrawalTable">
            <thead>
                <tr>
                    <th style="text-align: center;">Date</th>
                    <th>Item Description</th>
                    <th>Specification</th>
                    <th style="text-align: center;">Qty Out</th>
                    <th>Department</th>
                    <th>Purpose</th>
                    <th>Withdrawn By</th>
                    <th class="action-col" style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
    <?php
    // PDO equivalent of mysqli_query
    // Note: Added fallback for lowercase 'wdate' just in case
    $query = "SELECT * FROM withdrawals ORDER BY wdate DESC"; 
    $stmt = $conn->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows && count($rows) > 0) {
        foreach ($rows as $row) {
            // Handle potential casing differences between MySQL and PostgreSQL
            $db_date = $row['wdate'] ?? $row['Wdate'] ?? '';
            $formattedDate = ($db_date) ? date('M d, Y', strtotime($db_date)) : '---';
            
            $item_name = htmlspecialchars($row['item_name'] ?? $row['Item_Name'] ?? '');
            $spec = htmlspecialchars($row['specification'] ?? $row['Specification'] ?? '');
            $qty = number_format($row['qty'] ?? $row['QTY'] ?? 0);
            $dept = htmlspecialchars($row['department'] ?? $row['Department'] ?? '');
            $purpose = htmlspecialchars($row['purpose'] ?? $row['Purpose'] ?? '');
            $name = htmlspecialchars($row['name'] ?? $row['Name'] ?? 'N/A');
            $id = $row['id'] ?? 0;

            echo "<tr>
                <td style='font-weight: 600; color: #34495e; text-align: center;'>$formattedDate</td>
                <td style='font-weight: 600;'>$item_name</td>
                <td style='color: #7f8c8d;'>$spec</td>
                <td style='color:#e67e22; font-weight: bold; text-align: center;'>- $qty</td>
                <td><span style='background: #f1f2f6; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;'>$dept</span></td>
                <td style='font-style: italic; color: #7f8c8d;'>$purpose</td>
                <td style='font-weight: 500;'>$name</td>
                <td class='action-col' style='text-align: center;'>
                    <a href='delete_log.php?id=$id&type=withdrawal' class='delete-btn-log' onclick=\"return confirm('Delete record?')\">🗑️ Delete</a>
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='8' style='text-align:center; padding: 40px; color: #95a5a6;'>No withdrawal records found.</td></tr>";
    }
    ?>
</tbody>
        </table>
    </div>

    <div id="printFooter" style="display: none; margin-top: 50px;">
        <div style="display: flex; justify-content: space-between;">
            <div style="text-align: center;">
                <p style="margin-bottom: 50px;">Prepared By:</p>
                <p style="border-top: 1px solid #000; width: 200px; padding-top: 5px; font-weight: bold;">Warehouse In-Charge</p>
            </div>
            <div style="text-align: center;">
                <p style="margin-bottom: 50px;">Noted By:</p>
                <p style="border-top: 1px solid #000; width: 200px; padding-top: 5px; font-weight: bold;">Project Manager</p>
            </div>
        </div>
    </div>
</div>

<script>
function searchTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var tr = document.getElementById("withdrawalTable").getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var found = false;
        var td = tr[i].getElementsByTagName("td");
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].innerText.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        tr[i].style.display = found ? "" : "none";
    }
}
</script>

</body>
</html>
