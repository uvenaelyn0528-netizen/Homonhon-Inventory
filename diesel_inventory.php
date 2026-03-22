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

// 2. UPDATED: Total Balance (Only Tanks and FTs)
$bal_stmt = $conn->query("
    SELECT SUM(amount) as balance 
    FROM (
        SELECT qty as amount FROM diesel_inventory 
        WHERE activity = 'INFLOW' 
        AND deposited_to NOT LIKE 'FD%' 
        AND deposited_to NOT LIKE 'PH%'
        AND deposited_to != 'Direct to Unit'

        UNION ALL

        SELECT -qty as amount FROM diesel_inventory 
        WHERE activity = 'OUTFLOW' 
        AND withdrawn_from NOT LIKE 'FD%' 
        AND withdrawn_from NOT LIKE 'PH%'
    ) as net_balance
");
$balance_row = $bal_stmt->fetch(PDO::FETCH_ASSOC);
$balance = $balance_row['balance'] ?? 0;

// 3. UPDATED: Unit Breakdown (Only Tanks and FTs)
$breakdown_stmt = $conn->query("
    SELECT unit_name, SUM(amount) as unit_balance
    FROM (
        SELECT deposited_to as unit_name, qty as amount 
        FROM diesel_inventory 
        WHERE deposited_to NOT LIKE 'FD%' 
        AND deposited_to NOT LIKE 'PH%'
        AND deposited_to NOT IN ('', '---', 'Direct to Unit')
        
        UNION ALL
        
        SELECT withdrawn_from as unit_name, -qty as amount 
        FROM diesel_inventory 
        WHERE withdrawn_from NOT LIKE 'FD%' 
        AND withdrawn_from NOT LIKE 'PH%'
        AND withdrawn_from NOT IN ('', '---')
    ) AS combined_inventory
    GROUP BY unit_name
    ORDER BY unit_name ASC
");
$unit_breakdown = $breakdown_stmt->fetchAll(PDO::FETCH_ASSOC);
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

        .header-center { 
            flex: 2; display: flex; align-items: center; justify-content: center; 
            gap: 15px; text-align: center; 
        }

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

        /* Unit Breakdown Bar Styling */
        .unit-summary-bar {
            display: flex; gap: 10px; padding: 10px 30px; 
            overflow-x: auto; background: #e9ecef; border-bottom: 1px solid #ddd;
        }
        .unit-card {
            background: white; padding: 8px 12px; border-radius: 4px; 
            border-left: 4px solid var(--navy); min-width: 110px; 
            box-shadow: 2px 2px 5px rgba(0,0,0,0.05); flex-shrink: 0;
        }
        .unit-card-label { font-size: 9px; font-weight: bold; color: #666; text-transform: uppercase; }
        .unit-card-val { font-size: 13px; color: var(--dark-red); font-weight: 800; }

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
        
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center; }
        .modal-content { background:white; padding:25px; border-radius:10px; width:450px; max-height: 90vh; overflow-y: auto; }
        .modal-content label { display: block; font-size: 11px; font-weight: bold; margin-bottom: 5px; color: var(--navy); }
        .modal-content input, .modal-content select { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }

        .btn-edit { color: #3498db; background: none; border: 1px solid #3498db; padding: 4px 8px; border-radius: 4px; cursor: pointer; }
        .btn-delete { color: #e74c3c; background: none; border: 1px solid #e74c3c; padding: 4px 8px; border-radius: 4px; cursor: pointer; }
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
                <div style="font-size: 8px; opacity: 0.8;">TOTAL CURRENT STOCK</div>
                <div style="font-size: 18px; font-weight: bold;"><?= number_format($balance, 2) ?> L</div>
            </div>
            <button class="btn" onclick="openFuelModal()" style="background:var(--gold); color:var(--navy);">+ NEW ENTRY</button>
        </div>
    </header>

    <nav class="controls-bar">
        <form method="GET" style="display: flex; gap: 15px; align-items: center;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search RR/WS/Tank..." style="padding: 6px; border-radius: 4px; border:none;">
            <input type="date" name="from_date" value="<?= $from_date ?>" style="padding: 5px; border-radius: 4px;">
            <input type="date" name="to_date" value="<?= $to_date ?>" style="padding: 5px; border-radius: 4px;">
            <button type="submit" class="btn" style="background: var(--gold); color: var(--navy);">FILTER</button>
        </form>

        <div class="controls-right" style="display:flex; gap:10px; align-items: center;">
            <?php if (strtolower(trim($_SESSION['role'] ?? '')) === 'admin'): ?>
                <button type="button" class="btn" onclick="document.getElementById('dieselFile').click()" style="background: #8e44ad; color: white;">
                    📥 IMPORT DIESEL EXCEL
                </button>
                <form id="dieselImportForm" action="import_diesel.php" method="POST" enctype="multipart/form-data" style="display:none;">
                    <input type="file" name="diesel_file" id="dieselFile" accept=".csv" onchange="this.form.submit()">
                </form>
            <?php endif; ?>
            
            <a href="issuance.php" class="btn btn-issuance" style="background:white; color:var(--navy);">📋 DAILY ISSUANCE RECORD</a>
            <button class="btn" onclick="window.print()" style="background:#3498db; color:white;">🖨️ PRINT REPORT</button>
        </div>
    </nav>

    <div class="unit-summary-bar">
        <?php foreach($unit_breakdown as $unit): ?>
        <div class="unit-card">
            <div class="unit-card-label"><?= htmlspecialchars($unit['unit_name']) ?></div>
            <div class="unit-card-val"><?= number_format($unit['unit_balance'], 2) ?> L</div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>DATE / TIME</th>
                    <th>ACTIVITY</th>
                    <th>RECEIVED FROM / OPERATOR</th>
                    <th>RR NO.</th>
                    <th>WS NO.</th>
                    <th>WITHDRAWN FROM</th>
                    <th>DEPOSITED TO</th>
                    <th>QTY (L)</th>
                    <th class="col-action">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($row['rdate'])) ?></td>
                    <td style="font-weight:bold; color:<?= $row['activity']=='INFLOW'?'#27ae60':'#e67e22'?>"><?= $row['activity'] ?></td>
                    <td><?= htmlspecialchars($row['received_from'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['rr_no'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['ws_no'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['withdrawn_from'] ?: '---') ?></td> 
                    <td style="font-weight: bold;"><?= htmlspecialchars($row['deposited_to']) ?></td>
                    <td style="font-weight:bold;"><?= number_format($row['qty'], 2) ?></td>
                    <td class="col-action">
                        <button class="btn-edit" onclick='editRecord(<?= json_encode($row) ?>)'>✏️</button>
                        <button class="btn-delete" onclick="deleteRecord(<?= $row['id'] ?>)">🗑️</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="fuelModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin-top:0; color: var(--navy); border-bottom: 2px solid #eee; padding-bottom:10px;">Fuel Entry</h2>
        <form action="diesel_process.php" method="POST">
            <input type="hidden" name="id" id="formId">
            
            <label>Transaction Type</label>
            <select name="activity" id="activityType" onchange="toggleFields()" required>
                <option value="INFLOW">📥 STOCK INFLOW (Delivery)</option>
                <option value="OUTFLOW">📤 STOCK OUTFLOW (Issuance)</option>
            </select>

            <label>Date</label><input type="date" name="rdate" id="formDate" required>
            <label>Time</label><input type="time" name="rtime" id="formTime" required>

            <div id="inflowFields">
                <label>Received From (Supplier)</label>
                <input type="text" name="received_from" id="in_rec">
                <label>RR No.</label>
                <input type="text" name="rr_no" id="in_rr">
            </div>

            <div id="outflowFields" style="display:none;">
                <label>Withdrawn From</label>
                <select name="from_tank_no" id="out_tank">
                    <option value="">-- Select Source Tank --</option>
                    <?php for($i=1; $i<=9; $i++) echo "<option value='Tank $i'>Tank $i</option>"; ?>
                    <option value="FT 3">FT 3</option>
                    <option value="FT 4">FT 4</option>
                </select>
                <label>WS No.</label>
                <input type="text" name="ws_no" id="out_ws">
            </div>

            <label>Deposited To</label>
            <select name="deposited_to" id="formDep" required>
                <option value="">-- Select Destination --</option>
                <?php for($i=1; $i<=9; $i++) echo "<option value='Tank $i'>Tank $i</option>"; ?>
                <option value="FT 3">FT 3</option>
                <option value="FT 4">FT 4</option>
                <option value="Direct to Unit">Direct to Unit (Equipment)</option>
            </select>

            <label>Quantity (Liters)</label>
            <input type="number" step="0.01" name="qty" id="formQty" required>

            <div style="margin-top:20px; display: flex; gap: 10px;">
                <button type="submit" class="btn" style="flex:1; background:var(--navy); color:white;">SAVE ENTRY</button>
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
    document.getElementById('outflowFields').style.display = type === 'OUTFLOW' ? 'block' : 'none';
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
    // FIXED: Correct mapping for withdrawn_from in edit mode
    document.getElementById('out_tank').value = data.withdrawn_from || ''; 
    document.getElementById('formDep').value = data.deposited_to || '';
    document.getElementById('formQty').value = data.qty;
    toggleFields();
}

function deleteRecord(id) {
    if(confirm("Are you sure you want to delete this fuel record?")) {
        window.location.href = "delete_fuel.php?id=" + id;
    }
}
</script>
</body>
</html>
