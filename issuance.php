<?php 
include 'db.php'; 

/**
 * FIXED: Query now uses correct column names from your Supabase 'diesel_history' table.
 * Added 'rtime' to ORDER BY after we added it to your DB.
 */
$query = "SELECT * FROM diesel_history WHERE activity = 'OUTFLOW' ORDER BY rdate DESC, rtime DESC";
$res = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Issuance Record | Goldrich Construction</title>
    <style>
        /* Your existing styles remain the same */
        :root { --navy: #112941; --gold: #f1c40f; --dark-red: #8B0000; --light-bg: #f4f7f6; --green: #27ae60; }
        html, body { height: 100%; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; background: var(--light-bg); overflow: hidden; }
        .page-wrapper { display: flex; flex-direction: column; height: 100vh; }
        .header-strip { background: white; padding: 10px 30px; border-bottom: 3px solid var(--dark-red); display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 100; }
        .header-center { flex: 2; display: flex; align-items: center; justify-content: center; gap: 15px; text-align: center; }
        .logo-img { width: 50px; height: auto; }
        .company-name { color: var(--dark-red); margin: 0; font-size: 16px; font-family: Broadway, sans-serif; line-height: 1; }
        .page-title { margin: 2px 0 0 0; font-size: 18px; color: var(--navy); font-weight: 800; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; font-size: 11px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-back { background: #eee; color: #333; border: 1px solid #ccc; }
        .btn-add { background: var(--dark-red); color: white; border: 1px solid var(--gold); }
        .btn-import { background: var(--green); color: white; border: 1px solid #219150; }
        .btn-edit { color: #3498db; background: none; border: 1px solid #3498db; padding: 4px 6px; border-radius: 4px; cursor: pointer; }
        .btn-delete { color: #e74c3c; background: none; border: 1px solid #e74c3c; padding: 4px 6px; border-radius: 4px; cursor: pointer; }
        .table-container { flex: 1; overflow: auto; padding: 20px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; background: white; font-size: 11px; min-width: 1600px; }
        thead th { position: sticky; top: 0; background: var(--navy); color: white; padding: 12px; text-align: left; border-bottom: 2px solid var(--gold); z-index: 50; }
        td { padding: 10px; border-bottom: 1px solid #ddd; white-space: nowrap; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center; }
        .modal-content { background:white; padding:25px; border-radius:10px; width:650px; max-height: 90vh; overflow-y: auto; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        label { font-size: 11px; font-weight: bold; color: var(--navy); display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <header class="header-strip">
        <div class="header-left">
            <a href="diesel_inventory.php" class="btn btn-back">⬅ BACK TO INVENTORY</a>
        </div>
        
        <div class="header-center">
            <img src="images/logo.png" alt="Logo" class="logo-img">
            <div>
                <h2 class="company-name">GOLDRICH CONSTRUCTION AND TRADING</h2>
                <h3 class="page-title">📋 DAILY DIESEL ISSUANCE LOG</h3>
            </div>
        </div>

        <div class="header-right">
            <button class="btn btn-add" onclick="openAddModal()">➕ ADD NEW ISSUANCE</button>
            <button class="btn btn-import" onclick="document.getElementById('importFile').click()">📥 IMPORT EXCEL</button>

            <form id="importForm" action="import_process.php" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="file" name="excel_file" id="importFile" accept=".csv" onchange="document.getElementById('importForm').submit()">
            </form>
        </div>
    </header>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Actions</th>
                    <th>Tank Source</th>
                    <th>Date</th>
                    <th>Shift</th>
                    <th>Deposited To (Unit)</th>
                    <th>WS No.</th>
                    <th>Operator Name</th>
                    <th>Type of Eqpt.</th>
                    <th>Eqpt. ID</th>
                    <th>Code</th>
                    <th>Odometer/Hrs</th>
                    <th>Time</th>
                    <th>Issuance Slip No.</th>
                    <th style="text-align:right;">Qty (L)</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td style="text-align: center;">
                        <button class="btn-edit" onclick='editIssuance(<?= json_encode($row) ?>)'>✏️</button>
                        <button class="btn-delete" onclick="deleteIssuance(<?= $row['id'] ?>)">🗑️</button>
                    </td>
                    <td><?= htmlspecialchars($row['tank_source'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($row['rdate'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['shift'] ?? '---') ?></td>
                    <td><strong><?= htmlspecialchars($row['equipment_id'] ?? '') ?></strong></td>
                    <td><?= htmlspecialchars($row['ws_no'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['equipment_type'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($row['equipment_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['code'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($row['odometer'] ?? '0') ?></td>
                    <td><?= $row['rtime'] ? date('h:i A', strtotime($row['rtime'])) : '---' ?></td>
                    <td><?= htmlspecialchars($row['is_no'] ?? '---') ?></td>
                    <td style="text-align:right; font-weight:bold;"><?= number_format($row['qty'] ?? 0, 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="issuanceModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin-top:0; color: var(--dark-red); border-bottom: 2px solid #eee; padding-bottom: 10px;">New Daily Issuance</h2>
        <form action="diesel_process.php" method="POST">
            <input type="hidden" name="activity" value="OUTFLOW">
            <input type="hidden" name="id" id="formId">
            
            <div class="form-grid">
                <div>
                    <label>Tank Source</label>
                    <input type="text" name="tank_source" id="f_tank" placeholder="e.g. TANK 004" required>
                </div>
                <div>
                    <label>Date</label>
                    <input type="date" name="rdate" id="f_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label>Shift</label>
                    <input type="text" name="shift" id="f_shift" placeholder="D or N">
                </div>
                <div>
                    <label>Time</label>
                    <input type="time" name="rtime" id="f_time" value="<?= date('H:i') ?>" required>
                </div>
                <div>
                    <label>Issuance Slip (IS No.)</label>
                    <input type="text" name="is_no" id="f_slip" placeholder="Enter IS #">
                </div>
                <div>
                    <label>WS No. (Withdrawal Slip)</label>
                    <input type="text" name="ws_no" id="f_ws" placeholder="Enter WS #" required>
                </div>
                <div>
                    <label>Type of Eqpt.</label>
                    <input type="text" name="equipment_type" id="f_type" placeholder="e.g. Dump Truck">
                </div>
                <div>
                    <label>Eqpt. ID / Plate No.</label>
                    <input type="text" name="equipment_id" id="f_dep" placeholder="e.g. DT-01" required>
                </div>
                <div>
                    <label>Code</label>
                    <input type="text" name="code" id="f_code" placeholder="Project Code">
                </div>
                <div>
                    <label>Odometer / Hours</label>
                    <input type="number" step="0.1" name="odometer" id="f_odo" placeholder="Current Reading">
                </div>
                <div style="grid-column: span 2;">
                    <label>Operator / Recipient Name</label>
                    <input type="text" name="name" id="f_rec" placeholder="Full Name">
                </div>
                <div style="grid-column: span 2;">
                    <label style="color: var(--dark-red); font-size: 14px;">QUANTITY ISSUED (LITERS)</label>
                    <input type="number" step="0.01" name="qty" id="f_qty" required style="font-size: 20px; font-weight: bold; border: 2px solid var(--navy);">
                </div>
            </div>

            <div style="margin-top:20px; display: flex; gap: 10px;">
                <button type="submit" id="submitBtn" class="btn btn-add" style="flex:1; justify-content:center; font-size: 14px;">SAVE ISSUANCE</button>
                <button type="button" onclick="toggleModal(false)" class="btn btn-back" style="flex:1; justify-content:center; font-size: 14px;">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(show) { document.getElementById('issuanceModal').style.display = show ? 'flex' : 'none'; }
    function openAddModal() {
        document.getElementById('modalTitle').innerText = "New Daily Issuance";
        document.getElementById('submitBtn').innerText = "SAVE ISSUANCE";
        document.getElementById('formId').value = "";
        document.querySelector('form').reset();
        toggleModal(true);
    }
    function editIssuance(data) {
        document.getElementById('modalTitle').innerText = "Edit Daily Issuance";
        document.getElementById('submitBtn').innerText = "UPDATE ISSUANCE";
        document.getElementById('formId').value = data.id;
        document.getElementById('f_tank').value = data.tank_source || '';
        document.getElementById('f_date').value = data.rdate;
        document.getElementById('f_time').value = data.rtime;
        document.getElementById('f_shift').value = data.shift || '';
        document.getElementById('f_slip').value = data.is_no || '';
        document.getElementById('f_ws').value = data.ws_no;
        document.getElementById('f_type').value = data.equipment_type || '';
        document.getElementById('f_dep').value = data.equipment_id;
        document.getElementById('f_code').value = data.code || '';
        document.getElementById('f_odo').value = data.odometer || '';
        document.getElementById('f_rec').value = data.name || '';
        document.getElementById('f_qty').value = data.qty;
        toggleModal(true);
    }
    function deleteIssuance(id) {
        if (confirm("Are you sure you want to delete this issuance record?")) {
            window.location.href = "delete_fuel.php?id=" + id;
        }
    }
</script>
</body>
</html>
