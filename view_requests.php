<?php 
include 'db.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$role = $_SESSION['role'] ?? 'Viewer'; 

// PM Approval Logic - Now accepts Qty AND PM Remarks
if ($role == 'Project Manager' && isset($_POST['pm_approve']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $qty = intval($_POST['qty']);
    $pm_note = $_POST['pm_remarks'] ?? ''; 
    
    $stmt = $conn->prepare("UPDATE item_requests SET status = 'PM Approved', qty = :qty, purpose = CONCAT(purpose, ' | PM: ', :note) WHERE request_id = :id");
    $stmt->execute(['qty' => $qty, 'note' => $pm_note, 'id' => $id]);
    header("Location: view_requests.php?msg=Approved");
    exit();
}

// Head Office Purchasing Logic - Now accepts Purchase Type AND HO Remarks
if ($role == 'Head Office Purchasing' && isset($_POST['ho_process']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $type = $_POST['set_type']; 
    $qty = intval($_POST['qty']);
    $ho_note = $_POST['ho_remarks'] ?? ''; 
    
    $stmt = $conn->prepare("UPDATE item_requests SET remarks = :type, qty = :qty, status = 'Processed', purpose = CONCAT(purpose, ' | HO: ', :note) WHERE request_id = :id");
    $stmt->execute(['type' => $type, 'qty' => $qty, 'note' => $ho_note, 'id' => $id]);
    header("Location: view_requests.php?msg=Processed");
    exit();
}

// Received Status Logic
if (isset($_GET['received_action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $new_status = ($_GET['received_action'] == 'yes') ? 'Received' : 'Pending';
    $stmt = $conn->prepare("UPDATE item_requests SET received_status = :status WHERE request_id = :id");
    $stmt->execute(['status' => $new_status, 'id' => $id]);
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
            height: 100vh; /* Changed from 50vh to 100vh for better visibility */
            overflow: hidden;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
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

        /* 3. Header Section */
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
        .header-center { flex: 3; display: flex; align-items: center; justify-content: center; gap: 25px; }
        .header-center img { width: 90px; height: auto; display: block; }
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
        #inventoryTable { width: 100%; border-collapse: collapse; }
        #inventoryTable thead th {
            position: sticky; top: 0; z-index: 10;
            background: #112941 !important;
            color: white; padding: 15px;
            text-transform: uppercase; font-size: 11px; letter-spacing: 1px;
        }
        #inventoryTable td { padding: 12px 15px; border-bottom: 1px solid #f1f1f1; font-size: 13px; color: #112941; }
        #inventoryTable tbody tr:hover { background-color: #f9fbf9; }

        /* Status Colors */
        .status-pending { color: #f39c12; font-weight: bold; background: #fef5e7; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .status-approved { color: #27ae60; font-weight: bold; background: #eafaf1; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .status-processed { color: #2980b9; font-weight: bold; background: #ebf5fb; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .received-yes { color: #27ae60; font-weight: bold; background: #eafaf1; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .received-no { color: #af1c11; font-weight: bold; background: #f4f7f6; padding: 4px 8px; border-radius: 4px; font-size: 11px; }

        /* Action Buttons */
        .action-btns { display: flex; gap: 5px; justify-content: center; flex-direction: column; }
        .action-btns a, .action-btns button {
            padding: 5px 8px; text-decoration: none; color: white; border-radius: 4px;
            font-size: 10px; font-weight: bold; border: none; cursor: pointer;
        }
        .edit-btn { background: #3498db; }
        .approve-btn { background: #27ae60; }
        .delete-btn-req { background: #e74c3c; text-align: center; }

        /* New Upload Buttons */
        .btn-upload-po { background: #34495e !important; }
        .btn-upload-rr { background: #27ae60 !important; }
        .view-scan-link { font-size: 9px; color: #2980b9; text-decoration: underline; display: block; margin-top: 2px; }

        /* Modal Styles */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        .modal-content {
            background-color: #fff; border-radius: 15px; width: 450px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2); overflow: hidden;
        }

        @media print {
            .header-left, .table-controls, .action-col { display: none !important; }
            body { background: white; overflow: visible; }
        }
        
        .btn-yes { background: #2ecc71; color: white; border-radius: 4px; padding: 2px 8px; text-decoration: none; font-size: 10px; }
        .btn-no { background: #95a5a6; color: white; border-radius: 4px; padding: 2px 8px; text-decoration: none; font-size: 10px; }
    </style>
</head>
<body>

<div class="history-card">
    <div class="header-section">
        <div class="header-left">
            <a href="index.php" style="background: #34495e; padding: 10px 18px; text-decoration: none; border-radius: 8px; color: white; font-size: 12px; display: flex; align-items: center; gap: 8px; width: fit-content; font-weight: bold;">
                ⬅ Back to Dashboard
            </a>
        </div>
        <div class="header-center">
            <img src="images/logo.png" alt="Logo">
            <div class="header-text-group">
                <h2 style="color: darkred; margin: 0; font-size: 24px; font-family: Broadway, 'Arial Black', sans-serif;">GOLDRICH CONSTRUCTION AND TRADING</h2>
                <p style="color: #7f8c8d; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin: 3px 0;">Homonhon Nickel Project • Logistics & Warehouse</p>
                <h2 style="color: #2c3e50; margin: 0; font-size: 20px; font-weight: 800;">INVENTORY REQUEST REPORT</h2>
            </div>
        </div>
        <div class="header-right"></div>
    </div>

    <div class="table-controls" style="background: #112941; padding: 10px; border-radius: 10px; display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px;">
        <div class="left-side">
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search item, person, RF#..." style="width: 350px; padding: 8px; border-radius: 8px; border: none;">
        </div>
        <div class="right-side">
            <button onclick="window.print()" style="height: 35px; padding: 0 20px; background: #2980b9; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Generate Report 🖨️</button>
        </div>
    </div>

    <div class="table-wrapper">
        <table id="inventoryTable">
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
                    <th>Purchased</th> 
                    <th class="action-col">Action</th>
                </tr>
            </thead>
            <tbody>
<?php
$stmt = $conn->prepare("SELECT * FROM item_requests ORDER BY request_date DESC");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($rows && count($rows) > 0) {
    foreach ($rows as $row) {
        $request_id = $row['request_id'] ?? $row['id'];
        $status = $row['status'] ?? 'Pending';
        $status_class = "status-" . strtolower(str_replace(' ', '', $status));
        $remark_val = $row['remarks'] ?? '';
        $rf_display = $row['rf_number'] ?? $row['RF_Number'] ?? '---';
        $rec_status = $row['received_status'] ?? 'Pending';
        $rec_class = ($rec_status == 'Received') ? 'received-yes' : 'received-no';
        
        echo "<tr>";
            echo "<td>" . date('M d, Y', strtotime($row['request_date'])) . "</td>";
            echo "<td><strong>".htmlspecialchars($row['item_name'])."</strong><br><small>".htmlspecialchars($row['specification'])."</small></td>";
            echo "<td style='text-align: center; font-weight: bold;'>".$row['qty']."</td>";
            echo "<td style='text-align: center;'><span style='background:".($remark_val=='PO'?'#3498db':'#9b59b6')."; color:white; padding:2px 6px; border-radius:10px; font-size:9px;'>$remark_val</span></td>";
            echo "<td>".$row['department']."</td>";
            echo "<td style='font-size: 11px; max-width:150px;'>".htmlspecialchars($row['purpose'])."</td>";
            echo "<td>".$row['requested_by']."</td>";
            echo "<td style='text-align: center; font-weight: bold; color: #2980b9;'>$rf_display</td>";
            echo "<td style='text-align: center;'><span class='$status_class'>$status</span></td>";
            
            // Received Status Column + Scan Links
            echo "<td style='text-align: center;'>
                    <span class='$rec_class'>$rec_status</span>
                    <div style='margin-top:5px;'>";
                        if(!empty($row['po_scan_path'])) echo "<a href='".$row['po_scan_path']."' target='_blank' class='view-scan-link'>👁️ View PO</a>";
                        if(!empty($row['rr_scan_path'])) echo "<a href='".$row['rr_scan_path']."' target='_blank' class='view-scan-link'>👁️ View RR</a>";
            echo "  </div>
                  </td>";

            echo "<td class='action-col'>
                    <div class='action-btns'>";
                        
                        // STAFF / ADMIN: RR UPLOAD + EDIT
                        if ($role == 'Admin' || $role == 'Staff') {
                            echo "<button onclick='openUploadModal($request_id, \"RR\")' class='btn-upload-rr' style='padding:4px; font-size:9px;'>📷 UPLOAD RR</button>";
                            echo "<a href='#' class='edit-btn' onclick='openEditModal(".htmlspecialchars(json_encode($row)).")'>Edit</a>";
                            echo "<div style='display:flex; gap:3px;'>
                                    <a href='view_requests.php?received_action=yes&id=$request_id' class='btn-yes'>Yes</a>
                                    <a href='view_requests.php?received_action=no&id=$request_id' class='btn-no'>No</a>
                                  </div>";
                        }

                        // PROJECT MANAGER
                        if ($role == 'Project Manager' && $status == 'Pending') {
                            echo "<form method='POST' style='background:#f4f7f6; padding:5px; border:1px solid #ddd; border-radius:5px;'>
                                    <input type='number' name='qty' value='".$row['qty']."' style='width:100%; margin-bottom:3px;'>
                                    <input type='text' name='pm_remarks' placeholder='PM Note' style='width:100%; font-size:10px;'>
                                    <input type='hidden' name='id' value='$request_id'>
                                    <button type='submit' name='pm_approve' class='approve-btn' style='width:100%; margin-top:3px;'>✅ Approve</button>
                                  </form>";
                        }

                        // HO PURCHASING: PO UPLOAD + PROCESS
                        if ($role == 'Head Office Purchasing') {
                            echo "<form method='POST' style='background:#ebf5fb; padding:5px; border:1px solid #ddd; border-radius:5px;'>
                                    <div style='display:flex; gap:2px;'>
                                        <input type='number' name='qty' value='".$row['qty']."' style='width:40px;'>
                                        <select name='set_type' style='font-size:10px;'><option value='Local'>Local</option><option value='PO' ".($remark_val=='PO'?'selected':'').">PO</option></select>
                                    </div>
                                    <input type='text' name='ho_remarks' placeholder='HO Note' style='width:100%; font-size:10px; margin-top:3px;'>
                                    <input type='hidden' name='id' value='$request_id'>
                                    <button type='submit' name='ho_process' style='background:#2980b9; color:white; border:none; width:100%; padding:4px; margin-top:3px; font-weight:bold;'>💾 Process</button>
                                  </form>";
                            echo "<button onclick='openUploadModal($request_id, \"PO\")' class='btn-upload-po' style='margin-top:3px; padding:4px; font-size:9px;'>📄 UPLOAD PO</button>";
                        }

                        if ($role == 'Admin') {
                            echo "<a href='view_requests.php?delete_id=$request_id' class='delete-btn-req' onclick='return confirm(\"Delete?\")'>Delete</a>";
                        }
            echo "  </div>
                  </td>";
        echo "</tr>";
    }
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

<div id="uploadModal" class="modal">
    <div class="modal-content" style="width: 350px;">
        <div style="background: #112941; padding: 15px; color: white; display: flex; justify-content: space-between;">
            <h3 id="uploadTitle" style="margin: 0;">Upload Scan</h3>
            <span onclick="closeUploadModal()" style="cursor: pointer; font-size: 24px;">&times;</span>
        </div>
        <form action="upload_scan_process.php" method="POST" enctype="multipart/form-data" style="padding: 20px;">
            <input type="hidden" name="request_id" id="upload_id">
            <input type="hidden" name="upload_type" id="upload_type">
            <label style="font-size: 11px; font-weight: bold; color: #7f8c8d; display: block; margin-bottom: 5px;">SELECT IMAGE OR PDF:</label>
            <input type="file" name="scan_file" accept="image/*,.pdf" required style="width: 100%; margin-bottom: 20px;">
            <button type="submit" style="width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">📤 START UPLOAD</button>
        </form>
    </div>
</div>

<script>
function restricted(allowedRole) { alert("⛔ ACCESS RESTRICTED\n\nOnly " + allowedRole + " can use this."); }

function openUploadModal(id, type) {
    document.getElementById('uploadModal').style.display = 'flex';
    document.getElementById('upload_id').value = id;
    document.getElementById('upload_type').value = type;
    document.getElementById('uploadTitle').innerText = "Upload " + type + " Scan";
}
function closeUploadModal() { document.getElementById('uploadModal').style.display = 'none'; }

function openEditModal(data) {
    document.getElementById('editRequestModal').style.display = 'flex';
    document.getElementById('edit_request_id').value = data.request_id || data.id;
    document.getElementById('edit_item_name').value = data.item_name;
    document.getElementById('edit_qty').value = data.qty;
    document.getElementById('edit_rf').value = data.RF_Number || data.rf_number || '';
    document.getElementById('edit_dept').value = data.department;
    document.getElementById('edit_purpose').value = data.purpose;
}
function closeEditModal() { document.getElementById('editRequestModal').style.display = 'none'; }

function searchTable() {
    var filter = document.getElementById("searchInput").value.toUpperCase();
    var tr = document.getElementById("inventoryTable").getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        tr[i].style.display = tr[i].innerText.toUpperCase().indexOf(filter) > -1 ? "" : "none";
    }
}
window.onclick = function(e) { 
    if(e.target == document.getElementById('editRequestModal')) closeEditModal();
    if(e.target == document.getElementById('uploadModal')) closeUploadModal();
}
</script>
</body>
</html>
