<?php 
include 'db.php'; 

if (session_status() === PHP_SESSION_NONE) { session_start(); } 

// 1. Handle Filters
$search = $_GET['search'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$sql = "SELECT * FROM diesel_inventory WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (rr_no LIKE :search OR ws_no LIKE :search OR deposited_to LIKE :search OR received_from LIKE :search OR withdrawn_from LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND rdate BETWEEN :from_date AND :to_date";
    $params[':from_date'] = $from_date;
    $params[':to_date'] = $to_date;
}

$sql .= " ORDER BY rdate DESC, recorded_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

// 2. Total System Stock (Inflow - Outflow only)
$bal_stmt = $conn->query("
    SELECT (
        SUM(CASE WHEN activity = 'INFLOW' THEN qty ELSE 0 END) - 
        SUM(CASE WHEN activity = 'OUTFLOW' THEN qty ELSE 0 END)
    ) as balance 
    FROM diesel_inventory
");
$balance = $bal_stmt->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0;

// 3. ENHANCED PER TANK BREAKDOWN (Inflow + TransIn - Outflow - TransOut)
$tank_query = $conn->query("
    SELECT unit_name, SUM(amount) as unit_balance
    FROM (
        -- ADDITIONS (Inflow to Tank OR Transfer Received by Tank)
        SELECT deposited_to as unit_name, qty as amount 
        FROM diesel_inventory 
        WHERE (deposited_to LIKE 'TANK%' OR deposited_to LIKE 'Tank%' OR deposited_to LIKE 'FT%')
        AND (activity = 'INFLOW' OR activity = 'TRANSFERRED')
        
        UNION ALL
        
        -- SUBTRACTIONS (Outflow from Tank OR Transfer Withdrawn from Tank)
        SELECT withdrawn_from as unit_name, -qty as amount 
        FROM diesel_inventory 
        WHERE (withdrawn_from LIKE 'TANK%' OR withdrawn_from LIKE 'Tank%' OR withdrawn_from LIKE 'FT%')
        AND (activity = 'OUTFLOW' OR activity = 'TRANSFERRED')
    ) AS combined_inventory
    GROUP BY unit_name
    HAVING unit_name IS NOT NULL AND unit_name NOT IN ('', '---', 'Direct to Unit')
    ORDER BY unit_name ASC
");
$unit_breakdown = $tank_query->fetchAll(PDO::FETCH_ASSOC);

$tanks_ft = ["Tank 1", "Tank 2", "Tank 3", "Tank 4", "Tank 5", "Tank 6", "Tank 7", "Tank 8", "Tank 9", "FT 3", "FT 4"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Diesel Ledger | Goldrich Construction</title>
    <style>
        :root {
            --navy: #112941;
            --gold: #f1c40f;
            --dark-red: #8B0000;
            --slate-bg: #f1f5f9;
            --text-gray: #64748b;
        }

        html, body { 
            height: 100%; margin: 0; padding: 0; 
            overflow: hidden; font-family: 'Segoe UI', sans-serif;
            background-image: url('images/background.jpg'); 
            background-size: cover;
            background-position: center;
            background-color: var(--navy);
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            backdrop-filter: blur(2px);
            z-index: -1;
        }

        .page-wrapper { display: flex; flex-direction: column; height: 100vh; position: relative; z-index: 1; }

        /* HEADER */
        .main-header {
            background: #fff; padding: 10px 30px;
            border-bottom: 3px solid var(--dark-red);
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 100;
        }

        .header-center { flex: 2; display: flex; align-items: center; justify-content: center; gap: 15px; }
        .logo-img { width: 55px; height: auto; }
        .company-name { color: var(--dark-red); margin: 0; font-size: 17px; font-family: Broadway, sans-serif; }
        .page-title { margin: 2px 0 0 0; font-size: 19px; color: var(--navy); font-weight: 800; }

        .balance-card {
            background: var(--navy); color: var(--gold);
            padding: 8px 15px; border-radius: 8px; text-align: center;
            border: 1px solid rgba(241, 196, 15, 0.4); min-width: 140px;
        }

        /* TANK DASHBOARD */
        .unit-summary-container {
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 12px;
            max-height: 180px;
            overflow-y: auto;
        }

        .tank-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-left: 5px solid var(--navy);
        }

        .tank-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .tank-name { font-size: 11px; font-weight: 800; color: var(--text-gray); text-transform: uppercase; }
        .tank-volume { font-size: 20px; font-weight: 900; color: var(--navy); font-family: 'Courier New', monospace; margin: 2px 0; }
        .tank-unit { font-size: 10px; font-weight: bold; color: var(--dark-red); }

        /* CONTROLS & TABLE */
        .controls-bar {
            background: var(--navy); padding: 10px 30px;
            display: flex; justify-content: space-between; align-items: center;
            color: white;
        }

        .table-container { 
            flex: 1; overflow: auto; padding: 0; 
            background: rgba(255, 255, 255, 0.95); 
            margin: 15px 20px 20px 20px;
            border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1200px; }
        thead th {
            position: sticky; top: 0; background: var(--navy); color: white;
            padding: 15px 20px; font-size: 11px; text-transform: uppercase;
            border-bottom: 2px solid var(--gold); z-index: 50;
        }
        td { padding: 12px 20px; border-bottom: 1px solid #eee; font-size: 12px; }

        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 11px; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; text-decoration: none; }
        
        /* MODAL */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center; }
        .modal-content { background:white; padding:25px; border-radius:10px; width:450px; }
        .modal-content label { display: block; font-size: 11px; font-weight: bold; margin-bottom: 5px; color: var(--navy); }
        .modal-content input, .modal-content select { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
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

        <div class="header-right" style="display:flex; gap:15px; align-items:center;">
            <div class="balance-card">
                <div style="font-size: 8px; opacity: 0.8;">TOTAL SYSTEM STOCK</div>
                <div style="font-size: 18px; font-weight: bold;"><?= number_format($balance, 2) ?> L</div>
            </div>
            <button class="btn" onclick="openFuelModal()" style="background:var(--gold); color:var(--navy);">+ NEW ENTRY</button>
        </div>
    </header>

    <div class="unit-summary-container">
        <div style="margin-bottom: 10px; color: white; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">
            📍 Tank Stock Positions
        </div>
        <div class="summary-grid">
            <?php foreach($unit_breakdown as $unit): ?>
            <div class="tank-card">
                <div class="tank-header">
                    <span class="tank-name"><?= htmlspecialchars($unit['unit_name']) ?></span>
                    <span style="font-size:14px;">⛽</span>
                </div>
                <div class="tank-volume"><?= number_format($unit['unit_balance'], 0) ?></div>
                <div class="tank-unit">LITERS</div>
                <div style="height:4px; width:100%; background:#eee; margin-top:8px; border-radius:2px; overflow:hidden;">
                    <div style="width:100%; height:100%; background:<?= $unit['unit_balance'] < 5000 ? '#e74c3c' : '#27ae60' ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <nav class="controls-bar">
        <form method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search RR/WS/Tank..." style="padding: 6px; border-radius: 4px; border:none; width: 200px;">
            <input type="date" name="from_date" value="<?= $from_date ?>" style="padding: 5px; border-radius: 4px; border:none;">
            <input type="date" name="to_date" value="<?= $to_date ?>" style="padding: 5px; border-radius: 4px; border:none;">
            <button type="submit" class="btn" style="background: var(--gold); color: var(--navy);">FILTER</button>
        </form>

        <div class="controls-right" style="display:flex; gap:10px;">
            <?php if (strtolower(trim($_SESSION['role'] ?? '')) === 'admin'): ?>
                <button type="button" class="btn" onclick="clearInventory()" style="background: #c0392b; color: white;">🗑️ CLEAR</button>
                <button type="button" class="btn" onclick="document.getElementById('dieselFile').click()" style="background: #8e44ad; color: white;">📥 IMPORT</button>
                <form id="dieselImportForm" action="import_diesel.php" method="POST" enctype="multipart/form-data" style="display:none;">
                    <input type="file" name="diesel_file" id="dieselFile" accept=".csv" onchange="this.form.submit()">
                </form>
            <?php endif; ?>
            <a href="issuance.php" class="btn" style="background:white; color:var(--navy);">📋 ISSUANCE</a>
            <button class="btn" onclick="window.print()" style="background:#3498db; color:white;">🖨️ PRINT</button>
        </div>
    </nav>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>DATE</th>
                    <th>ACTIVITY</th>
                    <th>SUPPLIER / SOURCE</th>
                    <th>RR NO.</th>
                    <th>WS NO.</th>
                    <th>FROM</th>
                    <th>TO</th>
                    <th>QTY (L)</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($row['rdate'])) ?></td>
                    <td style="font-weight:bold; color:<?= $row['activity']=='INFLOW'?'#27ae60':($row['activity']=='TRANSFERRED'?'#3498db':'#e67e22') ?>"><?= $row['activity'] ?></td>
                    <td><?= htmlspecialchars($row['received_from'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['rr_no'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['ws_no'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['withdrawn_from'] ?: '---') ?></td> 
                    <td style="font-weight: bold;"><?= htmlspecialchars($row['deposited_to']) ?></td>
                    <td style="font-weight:bold;"><?= number_format($row['qty'], 2) ?></td>
                    <td>
                        <button class="btn-edit" onclick='editRecord(<?= json_encode($row) ?>)' style="border:1px solid #3498db; color:#3498db; background:none; padding:3px 7px; border-radius:4px; cursor:pointer;">✏️</button>
                        <button class="btn-delete" onclick="deleteRecord(<?= $row['id'] ?>)" style="border:1px solid #e74c3c; color:#e74c3c; background:none; padding:3px 7px; border-radius:4px; cursor:pointer;">🗑️</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="fuelModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-top:0; color: var(--navy);">Fuel Entry</h2>
        <form action="diesel_process.php" method="POST">
            <input type="hidden" name="id" id="formId">
            <label>Activity</label>
            <select name="activity" id="activityType" onchange="toggleFields()" required>
                <option value="INFLOW">📥 INFLOW (Delivery)</option>
                <option value="OUTFLOW">📤 OUTFLOW (Issuance)</option>
                <option value="TRANSFERRED">🔄 TRANSFER (Tank to Tank)</option>
            </select>
            <div style="display:flex; gap:10px;">
                <div style="flex:1;"><label>Date</label><input type="date" name="rdate" id="formDate" required></div>
                <div style="flex:1;"><label>Time</label><input type="time" name="rtime" id="formTime" required></div>
            </div>
            <div id="inflowFields">
                <label>Received From</label><input type="text" name="received_from" id="in_rec">
                <label>RR No.</label><input type="text" name="rr_no" id="in_rr">
            </div>
            <div id="outflowFields" style="display:none;">
                <label id="sourceLabel">From</label>
                <select name="from_tank_no" id="out_tank">
                    <option value="">-- Select --</option>
                    <?php foreach($tanks_ft as $t) echo "<option value='$t'>$t</option>"; ?>
                </select>
                <label id="wsLabel">WS No.</label><input type="text" name="ws_no" id="out_ws">
            </div>
            <label>Deposited To</label>
            <select name="deposited_to" id="formDep" required>
                <option value="">-- Select --</option>
                <?php foreach($tanks_ft as $t) echo "<option value='$t'>$t</option>"; ?>
                <option value="Direct to Unit">Direct to Unit</option>
            </select>
            <label>Quantity (L)</label>
            <input type="number" step="0.01" name="qty" id="formQty" required>
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
    document.querySelector('#fuelModal form').reset();
    document.getElementById('formDate').value = "<?= date('Y-m-d') ?>";
    document.getElementById('formTime').value = "<?= date('H:i') ?>";
    toggleFields();
}
function closeFuelModal() { document.getElementById('fuelModal').style.display = 'none'; }

function toggleFields() {
    const type = document.getElementById('activityType').value;
    document.getElementById('inflowFields').style.display = type === 'INFLOW' ? 'block' : 'none';
    document.getElementById('outflowFields').style.display = (type === 'OUTFLOW' || type === 'TRANSFERRED') ? 'block' : 'none';
    document.getElementById('sourceLabel').innerText = (type === 'TRANSFERRED') ? "Transfer From" : "Withdrawn From";
}

function editRecord(data) {
    openFuelModal();
    document.getElementById('formId').value = data.id;
    document.getElementById('activityType').value = data.activity;
    document.getElementById('formDate').value = data.rdate;
    document.getElementById('formTime').value = data.rtime;
    document.getElementById('in_rec').value = data.received_from || '';
    document.getElementById('in_rr').value = data.rr_no || '';
    document.getElementById('out_ws').value = data.ws_no || '';
    document.getElementById('out_tank').value = data.withdrawn_from || ''; 
    document.getElementById('formDep').value = data.deposited_to || '';
    document.getElementById('formQty').value = data.qty;
    toggleFields();
}

function deleteRecord(id) { if(confirm("Delete this record?")) window.location.href = "delete_fuel.php?id=" + id; }
function clearInventory() { if(confirm("WIPE ALL DATA?")) window.location.href = "clear_inventory.php"; }
</script>
</body>
</html>
