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

// 1. CORRECTED: Total System Stock (Physical Inventory Only)
$bal_stmt = $conn->query("
    SELECT (
        SUM(CASE WHEN activity = 'INFLOW' THEN qty ELSE 0 END) - 
        SUM(CASE WHEN activity = 'OUTFLOW' THEN qty ELSE 0 END)
    ) as balance 
    FROM diesel_inventory
");
$balance = $bal_stmt->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0;

// 2. CORRECTED: Per Tank Breakdown
$tank_query = $conn->query("
    SELECT unit_name, SUM(amount) as unit_balance
    FROM (
        SELECT deposited_to as unit_name, qty as amount 
        FROM diesel_inventory 
        WHERE (deposited_to LIKE 'TANK%' OR deposited_to LIKE 'Tank%')
        
        UNION ALL
        
        SELECT withdrawn_from as unit_name, -qty as amount 
        FROM diesel_inventory 
        WHERE (withdrawn_from LIKE 'TANK%' OR withdrawn_from LIKE 'Tank%')
    ) AS combined_inventory
    GROUP BY unit_name
    HAVING unit_name IS NOT NULL AND unit_name NOT IN ('', '---', 'Direct to Unit')
    ORDER BY unit_name ASC
");
$unit_breakdown = $tank_query->fetchAll(PDO::FETCH_ASSOC);
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
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: scroll;
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

        .unit-summary-bar {
            display: flex; gap: 10px; padding: 15px 30px; 
            overflow-x: auto; background: transparent;
        }
        
        .unit-card {
            background: white; padding: 10px 15px; border-radius: 6px; 
            border-left: 5px solid var(--navy); min-width: 120px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); flex-shrink: 0;
        }
        .unit-card-label { font-size: 10px; font-weight: bold; color: var(--text-gray); text-transform: uppercase; }
        .unit-card-val { font-size: 14px; color: var(--dark-red); font-weight: 800; }

        .table-container { 
            flex: 1; overflow: auto; padding: 0 20px 20px 20px; 
            background: rgba(255, 255, 255, 0.95); 
            margin: 0 20px 20px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            border: 1px solid #e2e8f0;
        }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0; background: transparent; min-width: 1200px; }
        
        /* UPDATED: THEAD STYLES */
        thead th {
            position: sticky; 
            top: 0; 
            background: var(--navy); 
            color: white;
            padding: 15px 25px; 
            font-size: 11px; 
            text-transform: uppercase;
            border-bottom: 2px solid var(--gold);
            z-index: 50;
        }

        td { padding: 12px 25px; border-bottom: 1px solid #eee; font-size: 12px; white-space: nowrap; }

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
                <button type="button" class="btn" onclick="clearInventory()" style="background: #c0392b; color: white;">
                    🗑️ Clear History
                </button>
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
                    <td style="font-weight:bold; color:<?= 
                        $row['activity']=='INFLOW'?'#27ae60':($row['activity']=='TRANSFERRED'?'#3498db':'#e67e22')
                    ?>"><?= $row['activity'] ?></td>
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
                <option value="TRANSFERRED">🔄 TRANSFER (Tank to Tank)</option>
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
                <label id="sourceLabel">Withdrawn From</label>
                <select name="from_tank_no" id="out_tank">
                    <option value="">-- Select Source Tank --</option>
                    <?php 
                        $tanks_ft = ["Tank 1", "Tank 2", "Tank 3", "Tank 4", "Tank 5", "Tank 6", "Tank 7", "Tank 8", "Tank 9", "FT 3", "FT 4"];
                        foreach($tanks_ft as $t) echo "<option value='$t'>$t</option>";
                    ?>
                </select>
                <label id="wsLabel">WS No.</label>
                <input type="text" name="ws_no" id="out_ws">
            </div>

            <label>Deposited To</label>
            <select name="deposited_to" id="formDep" required>
                <option value="">-- Select Destination --</option>
                <?php 
                    foreach($tanks_ft as $t) echo "<option value='$t'>$t</option>";
                ?>
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
    document.getElementById('outflowFields').style.display = (type === 'OUTFLOW' || type === 'TRANSFERRED') ? 'block' : 'none';
    
    if (type === 'TRANSFERRED') {
        document.getElementById('sourceLabel').innerText = "Transfer From (Source)";
        document.getElementById('wsLabel').innerText = "Transfer Reference No.";
    } else {
        document.getElementById('sourceLabel').innerText = "Withdrawn From";
        document.getElementById('wsLabel').innerText = "WS No.";
    }
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

function deleteRecord(id) {
    if(confirm("Are you sure you want to delete this fuel record?")) {
        window.location.href = "delete_fuel.php?id=" + id;
    }
}

function clearInventory() {
    const confirmation = confirm("WARNING: This will permanently delete ALL diesel inventory records and reset your stock to zero. This cannot be undone.\n\nAre you sure you want to proceed?");
    
    if (confirmation) {
        const finalCheck = confirm("FINAL CONFIRMATION: Click OK to completely wipe the Diesel Ledger.");
        if (finalCheck) {
            window.location.href = "clear_inventory.php";
        }
    }
}
</script>
</body>
</html>
