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
            height: 100vh; /* Changed from 50vh to 100vh for better visibility */
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
            padding: 15px;
            max-width: 1550px;
            margin: auto;
            width: 100%;
            max-height: 95vh;
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

        #inflowTable { width: 100%; border-collapse: collapse; min-width: 1400px; }
        #inflowTable thead th {
            position: sticky; top: 0; z-index: 10;
            background: #112941 !important; color: white;
            padding: 15px; text-transform: uppercase; font-size: 11px;
            letter-spacing: 1px; text-align: left; white-space: nowrap; 
        }

        #inflowTable td { padding: 12px 15px; border-bottom: 1px solid #f1f1f1; font-size: 13px; color: #2c3e50; }
        #inflowTable tbody tr:hover { background-color: #f9fbf9; }

        #inflowTable th.action-col, #inflowTable td.action-col {
            position: sticky; right: 0; box-shadow: -3px 0 5px rgba(0,0,0,0.05);
        }
        #inflowTable th.action-col { z-index: 12; background: #112941 !important; }
        #inflowTable td.action-col { z-index: 2; background-color: white; }

        .badge-count { background: #e8f5e9; color: #27ae60; padding: 3px 10px; border-radius: 15px; font-size: 11px; font-weight: bold; margin-left: 8px; }
        
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
            .header-left, .table-controls, .action-col { display: none !important; }
            .history-card { box-shadow: none; padding: 0; }
            body { background: white; overflow: visible; }
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
                <a href="import_received.php" style="height: 35px; padding: 0 15px; background: #8e44ad; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 12px; display: flex; align-items: center;">📥 Import Excel</a>
            <?php else: ?>
                <button onclick="restricted('Admin')" style="height: 35px; padding: 0 15px; background: #e74c3c; opacity: 0.6; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">🗑️ Clear History</button>
                <button onclick="restricted('Admin')" style="height: 35px; padding: 0 15px; background: #8e44ad; opacity: 0.6; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">📥 Import Excel</button>
            <?php endif; ?>

            <button onclick="window.print()" style="height: 35px; padding: 0 15px; background: #2980b9; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Generate Report 🖨️</button>
        </div>
    </div>

   <div class="table-wrapper">
    <table id="inflowTable">
        <thead>
            <tr>
                <th>DATE</th>
                <th>RR NUMBER</th>
                <th>SUPPLIER</th>
                <th style="text-align: left;">ITEM DESCRIPTION & SPECS</th>
                <th>QTY</th>
                <th>PRICE</th>
                <th>AMOUNT</th>
                <th>DEPT</th>
                <th>PURPOSE / REMARKS</th>
                <th class="action-col">ACTION</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // FETCH DIRECTLY from received_history now that you have a price column
        $query = "SELECT * FROM received_history ORDER BY received_date DESC, id DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grand_total = 0;

        if ($rows) {
            foreach ($rows as $row) {
                $price = floatval($row['price'] ?? 0);
                $qty   = floatval($row['qty'] ?? 0);
                $amount = floatval($row['amount'] ?? ($price * $qty)); 
                $grand_total += $amount;

                // For the Edit Modal JS Function
                $js_params = sprintf("'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'", 
                    $row['id'], 
                    addslashes($row['item_name']), 
                    $row['received_date'], 
                    $qty, 
                    addslashes($row['department']), 
                    addslashes($row['purpose']), 
                    addslashes($row['rr_number']), 
                    addslashes($row['supplier']), 
                    $price
                );

                echo "<tr>";
                echo "<td>" . date('M d, Y', strtotime($row['received_date'])) . "</td>";
                echo "<td style='font-weight: bold; color: #2980b9;'>" . htmlspecialchars($row['rr_number'] ?? '---') . "</td>";
                echo "<td>" . htmlspecialchars($row['supplier'] ?? '---') . "</td>";
                echo "<td style='text-align: left;'>
                        <strong>" . htmlspecialchars($row['item_name'] ?? '') . "</strong><br>
                        <small style='color: #666; font-style: italic;'>" . htmlspecialchars($row['specification'] ?? '') . "</small>
                      </td>";
                echo "<td style='color: green; font-weight: bold;'>+ " . number_format($qty, 2) . "</td>";
                echo "<td>₱" . number_format($price, 2) . "</td>";
                echo "<td style='font-weight: bold;'>₱" . number_format($amount, 2) . "</td>";
                echo "<td>" . htmlspecialchars($row['department'] ?? '') . "</td>";
                echo "<td style='font-size: 11px;'>" . htmlspecialchars($row['purpose'] ?? '') . "</td>";
                echo "<td class='action-col'>
                        <button onclick=\"openEditSummaryModal($js_params)\" style='background:none; border:none; cursor:pointer;'>📝</button>
                        <a href='delete_received_row.php?id=".$row['id']."' onclick=\"return confirm('Delete this log?')\" style='text-decoration:none;'>🗑️</a>
                      </td>";
                echo "</tr>";
            }
            
            echo "<tr style='background: #f8f9fa; font-weight: bold; border-top: 2px solid #112941;'>
                    <td colspan='6' style='text-align: right; padding: 15px;'>GRAND TOTAL:</td>
                    <td colspan='4' style='text-align: left; color: #112941; font-size: 16px;'>₱" . number_format($grand_total, 2) . "</td>
                  </tr>";
        } else {
            echo "<tr><td colspan='10' style='text-align:center; padding: 20px;'>No received logs found.</td></tr>";
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
    var tr = document.getElementById("inflowTable").getElementsByTagName("tr");
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
