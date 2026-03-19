<?php 
include 'db.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$role = $_SESSION['role'] ?? 'Viewer'; 

// PM Approval Logic
if ($role == 'Project Manager' && isset($_GET['pm_approve']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE item_requests SET status = 'PM Approved' WHERE request_id = :id");
    $stmt->execute(['id' => $id]);
    header("Location: view_requests.php");
    exit();
}

// Head Office Purchasing Logic
if ($role == 'Head Office Purchasing' && isset($_GET['set_type']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $type = $_GET['set_type']; 
    $stmt = $conn->prepare("UPDATE item_requests SET remarks = :type, status = 'Processed' WHERE request_id = :id");
    $stmt->execute(['type' => $type, 'id' => $id]);
    header("Location: view_requests.php");
    exit();
}

// Admin Delete Logic
if ($role == 'Admin' && isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM item_requests WHERE request_id = :id");
    $stmt->execute(['id' => $id]);
    header("Location: view_requests.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request History Log | Goldrich</title>
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
            padding-bottom: 5px;
            margin-bottom: 5px;
            width: 100%;
        }

        .header-left { flex: 1; display: flex; justify-content: flex-start; }
        .header-right { flex: 1; }

        .header-center {
            flex: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
        }

        .header-center img {
            width: 90px;
            height: auto;
            display: block;
        }

        .header-text-group { text-align: left; }

        /* 4. Table and Scrollbar */
        .table-wrapper {
            overflow-y: auto;
            flex-grow: 1;
            margin-top: 5px;
            border: 1px solid #f1f1f1;
            border-radius: 5px;
        }

        .table-wrapper::-webkit-scrollbar { width: 8px; }
        .table-wrapper::-webkit-scrollbar-track { background: #f1f1f1; }
        .table-wrapper::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

        /* 5. Table Design */
        #requestsTable {
            width: 100%;
            border-collapse: collapse;
        }

        #requestsTable thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #112941 !important; /* Darker blue/grey for requests */
            color: white;
            padding: 15px;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
        }

        #requestsTable td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f1f1;
            font-size: 13px;
            color: #112941;
        }

        #requestsTable tbody tr:hover { background-color: #f9fbf9; }

        /* Status Colors */
        .status-pending { color: #f39c12; font-weight: bold; background: #fef5e7; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .status-approved { color: #27ae60; font-weight: bold; background: #eafaf1; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .status-declined { color: #e74c3c; font-weight: bold; background: #fdedec; padding: 4px 8px; border-radius: 4px; font-size: 11px; }

        /* 6. UI Components */
        .badge-count {
            background: #ebf5fb;
            color: #2980b9;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
        }

        .action-btns { display: flex; gap: 5px; justify-content: center; }
        .action-btns a, .action-btns button {
            padding: 5px 8px;
            text-decoration: none;
            color: white;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .edit-btn { background: #3498db; }
        .approve-btn { background: #27ae60; }
        .decline-btn { background: #f39c12; }
        .delete-btn-req { background: #e74c3c; }

        /* Modal Styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5); 
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: #fff;
            margin: 8% auto;
            padding: 25px;
            border-radius: 15px;
            width: 450px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 11px; font-weight: bold; color: #7f8c8d; margin-bottom: 5px; text-transform: uppercase; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }

        @media print {
            .header-left, .table-controls, .action-col { display: none !important; }
            .history-card { box-shadow: none; padding: 0; }
            body { background: white; overflow: visible; }
        }
        
        /* Received Status Badges */
        .received-yes { color: #27ae60; font-weight: bold; background: #eafaf1; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .received-no { color: #af1c11; font-weight: bold; background: #f4f7f6; padding: 4px 8px; border-radius: 4px; font-size: 11px; }

        .btn-yes { background: #2ecc71; color: white; border-radius: 4px; padding: 2px 8px; text-decoration: none; font-size: 10px; }
        .btn-no { background: #95a5a6; color: white; border-radius: 4px; padding: 2px 8px; text-decoration: none; font-size: 10px; }
    </style>
</head>
<body>

<div class="history-card">
    <div class="header-section">
        <div class="header-left">
            <a href="index.php" class="back-btn" style="background: #34495e; padding: 10px 18px; text-decoration: none; border-radius: 8px; color: white; font-size: 12px; display: flex; align-items: center; gap: 8px; width: fit-content; font-weight: bold;">
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
                    INVENTORY REQUEST REPORT 
                    <span class="badge-count">REQUEST LOGS</span>
                </h2>
            </div>
        </div>
        <div class="header-right"></div>
    </div>

    <div class="table-controls" style="background: #112941; padding: 5px; border-radius: 10px; border: 1px solid #edf2f7; display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px;">
        <div class="left-side">
            <label style="display: block; font-size: 10px; font-weight: bold; color: #7f8c8d; margin-bottom: 5px; text-transform: uppercase;">Search Request History</label>
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search item, person, RF#..." style="width: 350px; padding: 5px; border-radius: 8px; border: 1px solid #ddd; outline: none;">
        </div>
        
        <div class="right-side" style="display: flex; gap: 10px; align-items: center;">
            <?php if ($role == 'Admin'): ?>
                <form method="POST" action="delete_log.php" onsubmit="return confirm('PERMANENTLY CLEAR ALL REQUEST RECORDS?');" style="margin: 0;">
                    <input type="hidden" name="clear_type" value="requests">
                    <button type="submit" style="height: 30px; padding: 0 15px; background: #e74c3c; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">🗑️ Clear History</button>
                </form>
            <?php else: ?>
                <button type="button" onclick="restricted('Admin')" style="height: 30px; padding: 0 15px; background: #e74c3c; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px; opacity: 0.6;">🗑️ Clear History</button>
            <?php endif; ?>
            <button onclick="window.print()" style="height: 30px; padding: 0 15px; background: #2980b9; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">Generate Report 🖨️</button>
        </div>
    </div>

    <div class="table-wrapper">
        <table id="inventoryTable" style="width: 100%; border-collapse: collapse;">
           <thead>
    <tr>
        <th>Date Requested</th>
        <th style="text-align: left;">Item Description</th>
        <th>Qty</th>
        <th>Type</th> 
        <th>Dept</th>
        <th>Purpose</th> 
        <th>Requested By</th>
        <th>RF #</th> 
        <th>Status</th>
        <th>Purchased</th> <th class="action-col">Action</th>
    </tr>
</thead>
           <tbody>
<?php
// Execute query using PDO
$stmt = $conn->prepare("SELECT * FROM item_requests ORDER BY request_date DESC");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FIX: Use count() instead of ->num_rows
if ($rows && count($rows) > 0) {
    foreach ($rows as $row) {
        // Handle case-sensitivity for column names
        $request_id = $row['request_id'] ?? $row['id'];
        $status = $row['status'] ?? 'Pending';
        $status_class = "status-" . strtolower($status);
        
        $raw_date = $row['request_date'] ?? '';
        $formatted_date = ($raw_date && $raw_date != '0000-00-00') ? date('M d, Y', strtotime($raw_date)) : '---';
        
        $remark_val = $row['remarks'] ?? '';
        $bg_color = ($remark_val == 'PO') ? '#3498db' : '#9b59b6';
        $rf_display = $row['rf_number'] ?? $row['RF_Number'] ?? $row['RF'] ?? '---';
        
        $rec_status = $row['received_status'] ?? 'Pending';
        $rec_class = ($rec_status == 'Received') ? 'received-yes' : 'received-no';
        
        echo "<tr style='border-bottom: 1px solid #edf2f7;'>";
            echo "<td style='padding: 10px; font-size: 11px;'>$formatted_date</td>";
            echo "<td style='padding: 10px;'>
                    <strong style='font-size: 13px; display: block; color: #112941;'>" . htmlspecialchars($row['item_name'] ?? '') . "</strong>";
                    if (!empty($row['specification'])) {
                        echo "<small style='color: #7f8c8d; font-size: 11px; font-style: italic;'>" . htmlspecialchars($row['specification']) . "</small>";
                    }
            echo "</td>";

            echo "<td style='text-align: center; font-weight: bold;'>" . ($row['qty'] ?? 0) . "</td>";
            
            echo "<td style='text-align: center;'>";
                if($remark_val) {
                    echo "<span class='remark-badge' style='background: $bg_color; color: white; padding: 2px 6px; border-radius: 10px; font-size: 9px;'>$remark_val</span>";
                }
            echo "</td>";

            echo "<td style='font-size: 12px;'>" . htmlspecialchars($row['department'] ?? '') . "</td>";
            echo "<td style='font-size: 11px; color: #555; max-width: 120px;'>" . htmlspecialchars($row['purpose'] ?? '-') . "</td>";
            echo "<td style='font-size: 12px;'>" . htmlspecialchars($row['requested_by'] ?? '') . "</td>";
            echo "<td style='text-align: center; font-weight: bold; color: #2980b9;'>$rf_display</td>";
            echo "<td style='text-align: center;'><span class='$status_class' style='font-size: 10px;'>$status</span></td>";
            
            echo "<td style='text-align: center;'>
                    <div style='display: flex; flex-direction: column; gap: 4px; align-items: center;'>
                        <span class='$rec_class' style='margin-bottom: 4px;'>$rec_status</span>
                        <div style='display: flex; gap: 3px;'>";
                            if ($role == 'Admin' || $role == 'Staff') {
                                echo "<a href='view_requests.php?received_action=yes&id=$request_id' class='btn-yes' title='Mark as Received'>Yes</a>";
                                echo "<a href='view_requests.php?received_action=no&id=$request_id' class='btn-no' title='Mark as Pending'>No</a>";
                            } else {
                                echo "<a href='javascript:void(0)' onclick='restricted(\"Admin or Staff\")' class='btn-yes' style='opacity:0.5;'>Yes</a>";
                                echo "<a href='javascript:void(0)' onclick='restricted(\"Admin or Staff\")' class='btn-no' style='opacity:0.5;'>No</a>";
                            }
                        echo "</div>
                    </div>
                </td>";

            echo "<td class='action-col'>
                    <div class='action-btns'>";
                        if ($role == 'Admin' || $role == 'Staff') {
                            echo "<a href='#' class='edit-btn' onclick='openEditModal(" . htmlspecialchars(json_encode($row)) . ")'>Edit</a>";
                        }
                        if ($role == 'Project Manager' && $status == 'Pending') {
                            echo "<a href='view_requests.php?pm_approve=1&id=$request_id' class='approve-btn' style='background:#27ae60;'>PM Approve</a>";
                        }
                        if ($role == 'Head Office Purchasing') {
                            echo "<a href='view_requests.php?set_type=Local&id=$request_id' class='approve-btn' style='background:#8e44ad;'>Set Local</a>";
                            echo "<a href='view_requests.php?set_type=PO&id=$request_id' class='approve-btn' style='background:#2980b9;'>Set PO</a>";
                        }
                        if ($role == 'Admin') {
                            echo "<a href='view_requests.php?delete_id=$request_id' class='delete-btn-req' onclick='return confirm(\"Delete?\")'>Delete</a>";
                        }
            echo "  </div>
                </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='11' style='text-align:center; padding: 20px;'>No request records found.</td></tr>";
}
?>
</tbody>
        </table>
    </div>
</div>

<div id="editRequestModal" class="modal">
    <div class="modal-content">
        <div style="background: #3498db; padding: 15px; color: white; display: flex; justify-content: space-between;">
            <h3 style="margin: 0;">✏️ Edit Request</h3>
            <span onclick="closeEditModal()" style="cursor: pointer; font-size: 24px;">&times;</span>
        </div>
        <form method="POST" action="update_request.php" style="padding: 20px; display: flex; flex-direction: column; gap: 12px;">
            <input type="hidden" name="request_id" id="edit_request_id">
            <input type="text" name="item_name" id="edit_item_name" placeholder="Item Name" required style="padding: 10px; border-radius: 5px; border: 1px solid #ddd;">
            <div style="display: flex; gap: 10px;">
                <input type="number" name="qty" id="edit_qty" placeholder="Qty" required style="flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ddd;">
                <input type="text" name="RF_Number" id="edit_rf" placeholder="RF Number" required style="flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ddd;">
            </div>
            <select name="department" id="edit_dept" style="padding: 10px; border-radius: 5px; border: 1px solid #ddd;">
                <option value="Admin">Admin</option>
                <option value="Engineering">Engineering</option>
                <option value="Warehouse">Warehouse</option>
                <option value="Safety">Safety</option>
                <option value="TSG">TSG</option>
                <option value="Mechanical">Mechanical</option>
            </select>
            <input type="text" name="purpose" id="edit_purpose" placeholder="Purpose" required style="padding: 10px; border-radius: 5px; border: 1px solid #ddd;">
            <button type="submit" name="update_request" style="padding: 12px; background: #3498db; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">Save Changes</button>
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
    var table = document.getElementById("inventoryTable");
    var tr = table.getElementsByTagName("tr");

    for (var i = 1; i < tr.length; i++) {
        tr[i].style.display = "none";
        var td = tr[i].getElementsByTagName("td");
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].innerText.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
                break;
            }
        }
    }
}

function openEditModal(data) {
    document.getElementById('editRequestModal').style.display = 'flex';
    document.getElementById('edit_request_id').value = data.request_id;
    document.getElementById('edit_item_name').value = data.item_name;
    document.getElementById('edit_qty').value = data.qty;
    document.getElementById('edit_rf').value = data.RF_Number || data.rf_number || data.RF || '';
    document.getElementById('edit_dept').value = data.department;
    document.getElementById('edit_purpose').value = data.purpose;
}

function closeEditModal() { document.getElementById('editRequestModal').style.display = 'none'; }
window.onclick = function(e) { if(e.target == document.getElementById('editRequestModal')) closeEditModal(); }
</script>

</body>
</html>
