<?php 
include 'db.php'; 

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Convert role to lowercase and trim for accurate matching
$raw_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Viewer'; 
$role = strtolower(trim($raw_role)); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Received Items Summary</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- RETAINED ORIGINAL DESIGN --- */
        body {
            background-color: #f4f7f6;
            padding: 10px;
            height: 50vh; 
            overflow: hidden; 
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .history-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 10px;
            max-width: 1450px;
            margin: auto;
            width: 100%;
            max-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 5px;
            margin-bottom: 5px;
            width: 100%;
        }

        .header-left { flex: 1; display: flex; justify-content: flex-start; }
        .header-center {
            flex: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
        }
        .header-right { flex: 1; }
        .header-center img { width: 90px; height: auto; display: block; }

        .table-wrapper {
            overflow: auto; 
            flex-grow: 1;
            margin-top: 10px;
            border: 1px solid #f1f1f1;
            border-radius: 10px;
            position: relative; 
        }

        .table-wrapper::-webkit-scrollbar { width: 8px; height: 10px; }
        .table-wrapper::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .table-wrapper::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

        #receivedTable { width: 100%; border-collapse: collapse; min-width: 1350px; }
        #receivedTable thead th {
            position: sticky; top: 0; z-index: 10;
            background: #112941 !important; color: white;
            padding: 15px; text-transform: uppercase; font-size: 11px;
            letter-spacing: 1px; text-align: left; white-space: nowrap; 
        }

        #receivedTable td { padding: 12px 15px; border-bottom: 1px solid #f1f1f1; font-size: 13px; color: #2c3e50; }
        #receivedTable tbody tr:hover { background-color: #f9fbf9; }

        #receivedTable th.action-col, #receivedTable td.action-col {
            position: sticky; right: 0; box-shadow: -3px 0 5px rgba(0,0,0,0.05);
        }
        #receivedTable th.action-col { z-index: 12; background: #112941 !important; }
        #receivedTable td.action-col { z-index: 2; background-color: white; }

        .badge-count { background: #e8f5e9; color: #27ae60; padding: 3px 10px; border-radius: 15px; font-size: 11px; font-weight: bold; margin-left: 8px; }
        .delete-btn-log { background: #ffeeee; color: #e74c3c; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 11px; font-weight: bold; }

        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        .modal-content { background-color: #fff; border-radius: 15px; width: 450px; box-shadow: 0 15px 40px rgba(0,0,0,0.2); overflow: hidden; }
        .modal-header { background: #112941; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; }
        .modal-body label { display: block; font-size: 11px; font-weight: 700; color: #34495e; margin-bottom: 6px; text-transform: uppercase; }
        .modal-body input { width: 100%; padding: 12px; border: 1px solid #dcdde1; border-radius: 6px; font-size: 14px; box-sizing: border-box; margin-bottom: 15px; }
        .submit-btn { width: 100%; padding: 14px; background: #27ae60; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }

        @media print {
            .header-left, .table-controls, .action-col, .delete-btn-log { display: none !important; }
            .history-card { box-shadow: none; padding: 0; }
            body { background: white; overflow: visible; }
        }
    </style>
</head>
<body>

<div class="history-card">
    <div class="header-section">
        <div class="header-left">
            <a href="index.php" class="back-btn" style="background: #34495e; padding: 10px 18px; text-decoration: none; border-radius: 8px; color: white; font-size: 12px; display: flex; align-items: center; gap: 8px; font-weight: bold;">
                ⬅ Back to Dashboard
            </a>
        </div>

        <div class="header-center">
            <img src="images/logo.png" alt="Logo">
            <div class="header-text-group">
                <h2 style="color: darkred; margin: 0; font-size: 24px; font-family: Broadway, 'Arial Black', sans-serif;">GOLDRICH CONSTRUCTION AND TRADING</h2>
                <p style="color: #7f8c8d; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin: 3px 0;">Homonhon Nickel Project • Logistics & Warehouse</p>
                <h2 style="color: #2c3e50; margin: 0; font-size: 20px; font-weight: 800; line-height: 1.2;">
                    INVENTORY INFLOW REPORT <span class="badge-count">RECEIVED LOGS</span>
                </h2>
            </div>
        </div>
        <div class="header-right"></div>
    </div>

    <div class="table-controls" style="background: #112941; padding: 10px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <div class="left-side">
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search RR#, items, suppliers..." style="width: 350px; padding: 8px; border-radius: 8px; border: none; outline: none;">
        </div>
        
        <div class="right-side" style="display: flex; gap: 10px;">
            <?php if($role === 'admin'): ?>
                <form method="POST" action="delete_log.php" onsubmit="return confirm('PERMANENTLY DELETE ALL RECORDS?');" style="margin: 0;">
                    <input type="hidden" name="clear_type" value="received">
                    <button type="submit" style="height: 35px; padding: 0 15px; background: #e74c3c; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">🗑️ Clear History</button>
                </form>
            <?php else: ?>
                <button onclick="restricted('Admin')" style="height: 35px; padding: 0 15px; background: #e74c3c; opacity: 0.6; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">🗑️ Clear History</button>
            <?php endif; ?>

            <?php if($role === 'admin'): ?>
                <a href="import_received.php" style="height: 35px; padding: 0 15px; background: #8e44ad; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 12px; display: flex; align-items: center;">📥 Import Excel</a>
            <?php else: ?>
                <button onclick="restricted('Admin')" style="height: 35px; padding: 0 15px; background: #8e44ad; opacity: 0.6; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">📥 Import Excel</button>
            <?php endif; ?>

            <button onclick="window.print()" style="height: 35px; padding: 0 15px; background: #2980b9; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Generate Report 🖨️</button>
        </div>
    </div>

    <div class="table-wrapper">
        <table id="receivedTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>RR Number</th>
                    <th>Supplier</th>
                    <th style="min-width: 250px;">Item Description & Specs</th> 
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right; min-width: 100px;">Price</th>
                    <th style="text-align: right; min-width: 120px;">Amount</th>
                    <th>Dept</th>
                    <th style="min-width: 180px;">Purpose / Remarks</th>
                    <th class="action-col" style="text-align: center; min-width: 100px;">Action</th>
                </tr>
            </thead>
           <tbody>
   <?php
$stmt = $conn->prepare("SELECT * FROM received_history ORDER BY received_date DESC, log_timestamp DESC");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($rows && count($rows) > 0) {
    foreach ($rows as $row) {
        // Use 'received_date' from your Supabase screenshot
        $db_date = $row['received_date'] ?? ''; 
        $formattedDate = ($db_date) ? date('M d, Y', strtotime($db_date)) : '---';
        
        // Use lowercase column names as seen in your Supabase dashboard
        $item_name = htmlspecialchars($row['item_name'] ?? '');
        $spec      = htmlspecialchars($row['specification'] ?? '');
        $qty       = number_format($row['qty'] ?? 0);
        $rr_no     = htmlspecialchars($row['rr_number'] ?? ''); // From screenshot
        $supplier  = htmlspecialchars($row['supplier'] ?? '');  // From screenshot
        $dept      = htmlspecialchars($row['department'] ?? '');
        $id        = $row['id'] ?? 0;

        echo "<tr>
            <td style='text-align: center;'>$formattedDate</td>
            <td style='font-weight: bold;'>$item_name</td>
            <td>$spec</td>
            <td style='text-align: center; color: green; font-weight: bold;'>+ $qty</td>
            <td>$dept</td>
            <td style='text-align: center; font-family: monospace;'>$rr_no</td>
            <td>$supplier</td>
            <td class='action-col'>
                <a href='delete_log.php?id=$id&type=received' class='delete-btn-log' onclick=\"return confirm('Delete this record?')\">🗑️</a>
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='8' style='text-align:center; padding: 30px;'>No received records found in database.</td></tr>";
}
?>
</tbody>
        </table>
    </div>
</div>

<div id="editSummaryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin:0; font-size: 18px;">📝 Edit Record</h3>
            <span style="cursor:pointer; font-size:24px;" onclick="closeEditSummaryModal()">&times;</span>
        </div>
        <form action="update_summary_process.php" method="POST" class="modal-body">
            <input type="hidden" name="id" id="summary_id">
            <label>RR Number</label>
            <input type="text" name="rr_number" id="summary_rr" required>
            <label>Received Date</label>
            <input type="date" name="RDATE" id="summary_date" required>
            <label>Qty</label>
            <input type="number" name="Qty" id="summary_qty" required>
            <label>Unit Price</label>
            <input type="number" step="0.01" name="Price" id="summary_price" required>
            <label>Purpose</label>
            <input type="text" name="Purpose" id="summary_purpose">
            <button type="submit" name="update_summary" class="submit-btn">💾 Save Changes</button>
        </form>
    </div>
</div>

<script>
function restricted(allowedRole) {
    alert("⛔ ACCESS RESTRICTED\n\nOnly " + allowedRole + " can use this.");
}

function searchTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var tr = document.getElementById("receivedTable").getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var found = false;
        var td = tr[i].getElementsByTagName("td");
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].innerText.toUpperCase().indexOf(filter) > -1) {
                found = true; break;
            }
        }
        tr[i].style.display = found ? "" : "none";
    }
}

function openEditSummaryModal(id, name, date, qty, dept, purpose, rr, supplier, price) {
    document.getElementById('editSummaryModal').style.display = 'flex';
    document.getElementById('summary_id').value = id;
    document.getElementById('summary_rr').value = rr;
    document.getElementById('summary_date').value = date;
    document.getElementById('summary_qty').value = qty;
    document.getElementById('summary_price').value = price;
    document.getElementById('summary_purpose').value = purpose;
}

function closeEditSummaryModal() { document.getElementById('editSummaryModal').style.display = 'none'; }
</script>

</body>
</html>
