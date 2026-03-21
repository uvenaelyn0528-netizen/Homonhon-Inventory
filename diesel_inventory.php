<?php 
include 'db.php'; 

// 1. Handle Filters - Updated for PDO
$search = $_GET['search'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$query = "SELECT * FROM diesel_inventory WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (rr_no LIKE :search OR ws_no LIKE :search OR deposited_to LIKE :search OR received_from LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($from_date) && !empty($to_date)) {
    $query .= " AND rdate BETWEEN :from_date AND :to_date";
    $params[':from_date'] = $from_date;
    $params[':to_date'] = $to_date;
}

$query .= " ORDER BY rdate DESC, rtime DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);

// 2. Calculate Current Balance - Updated for PDO
$bal_stmt = $conn->query("SELECT SUM(CASE WHEN activity = 'INFLOW' THEN qty ELSE -qty END) as balance FROM diesel_inventory");
$balance_row = $bal_stmt->fetch(PDO::FETCH_ASSOC);
$balance = $balance_row['balance'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diesel Ledger | Goldrich Construction</title>
    <style>
        :root {
            --navy: #112941;
            --gold: #f1c40f;
            --dark-red: #8B0000;
            --light-bg: #f4f7f6;
        }

        html, body { 
            height: 100%; margin: 0; padding: 0; 
            overflow: hidden; font-family: 'Segoe UI', sans-serif;
            background: var(--light-bg);
        }

        .page-wrapper { display: flex; flex-direction: column; height: 100vh; }

        .main-header {
            background: #fff; padding: 10px 30px;
            border-bottom: 3px solid var(--dark-red);
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 100;
        }

        .header-left { flex: 1; }
        .header-center { 
            flex: 2; display: flex; align-items: center; justify-content: center; 
            gap: 15px; text-align: center; 
        }
        .header-right { flex: 1; display: flex; justify-content: flex-end; align-items: center; gap: 15px; }

        .logo-img { width: 55px; height: auto; }
        .company-name { color: var(--dark-red); margin: 0; font-size: 17px; font-family: Broadway, sans-serif; line-height: 1; }
        .page-title { margin: 2px 0 0 0; font-size: 19px; color: var(--navy); font-weight: 800; }

        .balance-card {
            background: var(--navy); color: var(--gold);
            padding: 8px 15px; border-radius: 8px; text-align: center;
            border: 1px solid rgba(241, 196, 15, 0.4); min-width: 130px;
        }

        .controls-bar {
            background: var(--navy); padding: 12px 30px;
            display: flex; justify-content: space-between; align-items: center;
            color: white; z-index: 90;
        }

        .table-container { flex: 1; overflow: auto; padding: 0 20px 20px 20px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; background: white; min-width: 1200px; }
        
        thead th {
            position: sticky; top: 0; 
            background: #f8f9fa; color: var(--navy);
            padding: 15px 25px; font-size: 11px; text-transform: uppercase;
            border-bottom: 3px solid var(--dark-red); z-index: 50;
            white-space: nowrap;
        }

        td { padding: 10px 25px; border-bottom: 1px solid #eee; font-size: 12px; white-space: nowrap; }

        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; font-size: 11px; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-issuance { background: var(--dark-red); color: white; border: 1px solid var(--gold); }
        .btn-print { background: #3498db; color: white; }
        .btn-import { background: #27ae60; color: white; }
        
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center; }
        .modal-content { background:white; padding:25px; border-radius:10px; width:450px; }
        .modal-content input, .modal-content select { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }

        @media print {
            .main-header, .controls-bar, .btn, .col-action { display: none !important; }
            .table-container { overflow: visible !important; }
            thead th { position: static; background: #eee !important; color: black !important; border: 1px solid #000; }
            td { border: 1px solid #000; }
        }
    </style>
</head>
<body>

<div class="page-wrapper">
    <header class="main-header">
        <div class="header-left">
            <a href="index.php" class="btn" style="background:#eee; color:#333;">⬅ DASHBOARD</a>
        </div>
        
        <div class="header-center">
            <img src="images/logo.png" alt="Logo" class="logo-img">
            <div>
                <h2 class="company-name">GOLDRICH CONSTRUCTION AND TRADING</h2>
                <h3 class="page-title">⛽ Diesel Inventory Ledger</h3>
            </div>
        </div>

        <div class="header-right">
            <div class="balance-card">
                <div style="font-size: 8px; opacity: 0.8;">CURRENT STOCK</div>
                <div style="font-size: 18px; font-weight: bold;"><?= number_format($balance, 2) ?> L</div>
            </div>
            <button class="btn" onclick="openFuelModal()" style="background:var(--gold); color:var(--navy);">+ NEW ENTRY</button>
        </div>
    </header>

    <nav class="controls-bar">
        <form method="GET" style="display: flex; gap: 15px; align-items: center;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search RR/WS..." style="padding: 6px; border-radius: 4px; border:none;">
            <input type="date" name="from_date" value="<?= $from_date ?>" style="padding: 5px; border-radius: 4px;">
            <input type="date" name="to_date" value="<?= $to_date ?>" style="padding: 5px; border-radius: 4px;">
            <button type="submit" class="btn" style="background: var(--gold); color: var(--navy);">FILTER</button>
        </form>

        <div class="controls-right" style="display:flex; gap:10px;">
            <a href="issuance.php" class="btn btn-issuance" style="background:white; color:var(--navy);">📋 DAILY ISSUANCE RECORD</a>
            <button class="btn btn-import" onclick="document.getElementById('importFile').click()">📥 IMPORT</button>
            <button class="btn btn-print" onclick="window.print()">🖨️ PRINT REPORT</button>
            
            <form id="importForm" action="import_process.php" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="file" name="excel_file" id="importFile" accept=".csv" onchange="document.getElementById('importForm').submit()">
            </form>
        </div>
    </nav>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>DATE / TIME</th>
                    <th>ACTIVITY</th>
                    <th>RECEIVED FROM</th>
                    <th>RR NO.</th>
                    <th>WS NO.</th>
                    <th>DEPOSITED TO</th>
                    <th>QTY (L)</th>
                     <th>Withdrawn From</th>
                    <th class="col-action">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($row['rdate'])) ?> <small><?= $row['rtime'] ?></small></td>
                    <td style="font-weight:bold; color:<?= $row['activity']=='INFLOW'?'#27ae60':'#e67e22'?>"><?= $row['activity'] ?></td>
                    <td><?= htmlspecialchars($row['received_from'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['rr_no'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['ws_no'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['deposited_to']) ?></td>
                    <td style="font-weight:bold;"><?= number_format($row['qty'], 2) ?></td>
                    <td class="col-action">
                        <button class="btn-edit" onclick='editRecord(<?= json_encode($row) ?>)'>Edit</button>
                        <button class="btn-delete" onclick="deleteRecord(<?= $row['id'] ?>)">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="fuelModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin-top:0; color: var(--navy);">Fuel Entry</h2>
        <form action="diesel_process.php" method="POST">
            <input type="hidden" name="id" id="formId">
            <label>Transaction Type</label>
            <select name="activity" id="activityType" onchange="toggleFields()" required>
                <option value="INFLOW">📥 STOCK INFLOW</option>
                <option value="OUTFLOW">📤 STOCK OUTFLOW</option>
            </select>
            <label>Date</label><input type="date" name="rdate" id="formDate" required>
            <label>Time</label><input type="time" name="rtime" id="formTime" required>
            
            <div id="inflowFields">
                <label>Received From</label><input type="text" name="received_from" id="in_rec">
                <label>RR No.</label><input type="text" name="rr_no" id="in_rr">
            </div>
            <div id="outflowFields" style="display:none;">
                <label>WS No.</label><input type="text" name="ws_no" id="out_ws">
            </div>
            
            <label>Deposited To</label><input type="text" name="deposited_to" id="formDep" required>
            <label>Quantity</label><input type="number" step="0.01" name="qty" id="formQty" required>
            
            <div style="margin-top:15px; display: flex; gap: 10px;">
                <button type="submit" class="btn" style="flex:1; background:var(--navy); color:white;">SAVE</button>
                <button type="button" onclick="closeFuelModal()" class="btn" style="flex:1; background:#ccc;">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<script>
function openFuelModal() {
    document.getElementById('fuelModal').style.display = 'flex';
    document.getElementById('formId').value = '';
    toggleFields();
}
function closeFuelModal() { document.getElementById('fuelModal').style.display = 'none'; }
function toggleFields() {
    const type = document.getElementById('activityType').value;
    document.getElementById('inflowFields').style.display = type === 'INFLOW' ? 'block' : 'none';
    document.getElementById('outflowFields').style.display = type === 'OUTFLOW' ? 'block' : 'none';
}
function editRecord(data) {
    openFuelModal();
    document.getElementById('formId').value = data.id;
    document.getElementById('activityType').value = data.activity;
    document.getElementById('formDate').value = data.rdate;
    document.getElementById('formTime').value = data.rtime;
    document.getElementById('in_rec').value = data.received_from;
    document.getElementById('in_rr').value = data.rr_no;
    document.getElementById('out_ws').value = data.ws_no;
    document.getElementById('formDep').value = data.deposited_to;
    document.getElementById('formQty').value = data.qty;
    toggleFields();
}
function deleteRecord(id) {
    if(confirm("Delete this record?")) window.location.href = "delete_fuel.php?id=" + id;
}
</script>
</body>
</html>
