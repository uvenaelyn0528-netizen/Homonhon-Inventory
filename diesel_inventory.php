<?php 
include 'db.php'; 

if (session_status() === PHP_SESSION_NONE) { session_start(); } 

// Authorization Check
$isAuthorized = isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'staff']);

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

// 2. Total System Stock
$bal_stmt = $conn->query("
    SELECT (
        SUM(CASE WHEN activity = 'INFLOW' THEN qty ELSE 0 END) - 
        SUM(CASE WHEN activity = 'OUTFLOW' THEN qty ELSE 0 END)
    ) as balance 
    FROM diesel_inventory
");
$balance = $bal_stmt->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0;

// Calculate "As of" Date
$date_stmt = $conn->query("SELECT MAX(rdate) as latest_date FROM diesel_inventory");
$latest_raw = $date_stmt->fetch(PDO::FETCH_ASSOC)['latest_date'];
$as_of_date = $latest_raw ? date('F d, Y', strtotime($latest_raw . ' +1 day')) : date('F d, Y', strtotime('+1 day'));

// 3. Tank Breakdown Strip
$tank_query = $conn->query("
    SELECT unit_name, SUM(amount) as unit_balance
    FROM (
        SELECT deposited_to as unit_name, qty as amount 
        FROM diesel_inventory 
        WHERE (deposited_to LIKE 'TANK%' OR deposited_to LIKE 'Tank%' OR deposited_to LIKE 'FT%')
        AND (activity = 'INFLOW' OR activity = 'TRANSFERRED')
        UNION ALL
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
            --print-blue: #3498db;
            --success-green: #2ecc71;
        }

        html, body { 
            height: 100%; margin: 0; padding: 0; 
            overflow: hidden; font-family: 'Segoe UI', sans-serif;
            background: var(--navy) url('images/background.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .page-wrapper { display: flex; flex-direction: column; height: 100vh; position: relative; z-index: 1; }

        .main-header {
            background: #fff; padding: 8px 30px;
            border-bottom: 4px solid var(--gold);
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); z-index: 100;
        }

        .header-center { flex: 1; display: flex; align-items: center; justify-content: center; gap: 15px; }
        .logo-img { width: 50px; height: auto; }
        .company-name { color: var(--dark-red); margin: 0; font-size: 16px; font-family: Broadway, sans-serif; }
        .page-title { margin: 0; font-size: 22px; color: var(--navy); font-weight: 900; }

        .total-stock-position {
            background: var(--navy); color: var(--gold);
            padding: 10px 25px; border-radius: 6px; text-align: center;
            border: 1px solid var(--gold);
        }

        .tank-strip {
            display: flex; gap: 8px; padding: 8px 20px;
            background: rgba(17, 41, 65, 0.8);
            overflow-x: auto; white-space: nowrap;
        }
        .tank-mini-card {
            background: rgba(255,255,255,0.9);
            padding: 4px 12px; border-radius: 4px;
            min-width: 100px; border-left: 3px solid var(--gold);
        }
        .mini-name { font-size: 9px; font-weight: 800; color: #64748b; }
        .mini-vol { font-size: 13px; font-weight: 800; color: var(--navy); }

        .controls-bar {
            background: #fff; padding: 8px 30px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #ddd;
        }

        .table-container { 
            flex: 1; overflow: auto; 
            background: rgba(255, 255, 255, 0.98); 
            margin: 0 15px 15px 15px;
            border-radius: 4px; box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }

        table { width: 100%; border-collapse: collapse; min-width: 1100px; }
        thead th {
            position: sticky; top: 0; background: var(--navy); color: white;
            padding: 12px 15px; font-size: 11px; text-transform: uppercase;
            z-index: 50; text-align: left;
        }
        td { padding: 10px 15px; border-bottom: 1px solid #eee; font-size: 12px; }

        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 11px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: 0.2s; }
        
        /* Modal styling */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center; }
        .modal-content { background:white; border-radius:8px; width:450px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .modal-header { background: var(--navy); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px 20px; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <header class="main-header">
        <div class="header-left"><a href="index.php" class="btn" style="background:#eee; color:#333;">⬅ DASHBOARD</a></div>
        <div class="header-center">
            <img src="images/logo.png" alt="Logo" class="logo-img">
            <div>
                <h2 class="company-name">GOLDRICH CONSTRUCTION AND TRADING</h2>
                <h3 class="page-title">DIESEL INVENTORY LEDGER</h3>
            </div>
        </div>
        <div class="header-right">
            <div class="total-stock-position">
                <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #fff;">AS OF: <?= $as_of_date ?></div>
                <div style="font-size: 24px; font-weight: 900;"><?= number_format($balance, 2) ?> L</div>
            </div>
        </div>
    </header>

    <div class="tank-strip">
        <?php foreach($unit_breakdown as $unit): ?>
        <div class="tank-mini-card">
            <div class="mini-name"><?= htmlspecialchars($unit['unit_name']) ?></div>
            <div class="mini-vol"><?= number_format($unit['unit_balance'], 0) ?> L</div>
        </div>
        <?php endforeach; ?>
    </div>

    <nav class="controls-bar">
        <form method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search records..." style="padding: 6px; border: 1px solid #ccc; border-radius: 4px; width: 220px;">
            <input type="date" name="from_date" value="<?= $from_date ?>" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
            <input type="date" name="to_date" value="<?= $to_date ?>" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" class="btn" style="background: #112941; color: white;">FILTER</button>
        </form>
        <div style="display:flex; gap:12px;">
            <button class="btn" onclick="openFuelModal()" style="background: #112941; color: white;">+ NEW ENTRY</button>
            <button class="btn" onclick="clearInventory()" style="background: #112941; color: white;">🗑️ WIPE</button>
            <button class="btn" onclick="window.print()" style="background: #112941; color: white;">🖨️ PRINT</button>
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
                    <td style="font-weight: bold;"><?= date('M d, Y', strtotime($row['rdate'])) ?></td>
                    <td>
                        <span style="padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; background: <?= strtoupper($row['activity'])=='INFLOW'?'#dcfce7':(strtoupper($row['activity'])=='TRANSFERRED'?'#dbeafe':'#ffedd5') ?>; color: <?= strtoupper($row['activity'])=='INFLOW'?'#166534':(strtoupper($row['activity'])=='TRANSFERRED'?'#1e40af':'#9a3412') ?>;">
                            <?= htmlspecialchars($row['activity']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['received_from'] ?: '---') ?></td>
                    <td>
                        <?= htmlspecialchars($row['rr_no'] ?: '---') ?>
                        <?php if (!empty($row['attachment_path'])): ?>
                            <a href="<?= htmlspecialchars($row['attachment_path']) ?>" download class="attachment-link" title="Download File">📎 attached File</a>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['ws_no'] ?: '---') ?></td>
                    <td><?= htmlspecialchars($row['withdrawn_from'] ?: '---') ?></td> 
                    <td style="font-weight: bold;"><?= htmlspecialchars($row['deposited_to']) ?></td>
                    <td style="font-weight: 900; color: var(--dark-red);"><?= number_format($row['qty'], 2) ?></td>
                    <td>
                        <button onclick='editRecord(<?= json_encode($row) ?>)' style="border:none; background:none; cursor:pointer;">✏️</button>
                        
                        <?php if (strtoupper($row['activity']) === 'INFLOW'): ?>
                            <button onclick="openUploadModal(<?= $row['id'] ?>)" style="border:none; background:none; cursor:pointer;" title="Upload Scan">📤</button>
                        <?php endif; ?>

                        <button onclick="deleteRecord(<?= $row['id'] ?>)" style="border:none; background:none; cursor:pointer;">🗑️</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Upload RR Scan</h3>
            <span style="cursor:pointer; font-size:24px;" onclick="closeUploadModal()">&times;</span>
        </div>
        <form action="diesel_process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="upload_record_id">
            <input type="hidden" name="upload_only" value="1">
            <div class="modal-body">
                <input type="file" name="attachment" accept="image/*,.pdf" required style="width:100%;">
                <button type="submit" class="btn" style="background:var(--success-green); color:white; width:100%; margin-top:20px; padding:15px;">START UPLOAD</button>
            </div>
        </form>
    </div>
</div>

<div id="fuelModal" class="modal">
    <div class="modal-content" style="padding:20px;">
        <h3 style="margin-top:0; color: var(--navy);">Fuel Entry</h3>
        <form action="diesel_process.php" method="POST">
            <input type="hidden" name="id" id="formId">
            <label style="font-size:11px; font-weight:bold;">Activity</label>
            <select name="activity" id="activityType" onchange="toggleFields()" required style="width:100%; padding:8px; margin-bottom:10px;">
                <option value="INFLOW">INFLOW</option>
                <option value="OUTFLOW">OUTFLOW</option>
                <option value="TRANSFERRED">TRANSFER</option>
            </select>
            
            <div style="margin-bottom:10px;">
                <label style="font-size:11px; font-weight:bold;">Date</label>
                <input type="date" name="rdate" id="formDate" required style="width:100%; padding:8px;">
            </div>
            
            <div id="inflowFields">
                <input type="text" name="received_from" id="in_rec" placeholder="Supplier" style="width:100%; padding:8px; margin-top:10px;">
                <input type="text" name="rr_no" id="in_rr" placeholder="RR No." style="width:100%; padding:8px; margin-top:10px;">
            </div>

            <div id="outflowFields" style="display:none;">
                <select name="from_tank_no" id="out_tank" style="width:100%; padding:8px; margin-top:10px;">
                    <option value="">-- From --</option>
                    <?php foreach($tanks_ft as $t) echo "<option value='$t'>$t</option>"; ?>
                </select>
                <input type="text" name="ws_no" id="out_ws" placeholder="WS No." style="width:100%; padding:8px; margin-top:10px;">
            </div>

            <label style="font-size:11px; font-weight:bold; display:block; margin-top:10px;">Deposited To</label>
            <select name="deposited_to" id="formDep" required style="width:100%; padding:8px;">
                <option value="">-- Select --</option>
                <?php foreach($tanks_ft as $t) echo "<option value='$t'>$t</option>"; ?>
                <option value="Direct to Unit">Direct to Unit</option>
            </select>
            <input type="number" step="0.01" name="qty" id="formQty" placeholder="Quantity (L)" required style="width:100%; padding:8px; margin-top:10px;">
            
            <div style="margin-top:15px; display: flex; gap: 10px;">
                <button type="submit" class="btn" style="flex:1; background:var(--navy); color: white; justify-content:center;">SAVE</button>
                <button type="button" onclick="closeFuelModal()" class="btn" style="flex:1; background:#ccc; justify-content:center;">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUploadModal(id) {
    document.getElementById('upload_record_id').value = id;
    document.getElementById('uploadModal').style.display = 'flex';
}
function closeUploadModal() { document.getElementById('uploadModal').style.display = 'none'; }

function openFuelModal() {
    document.getElementById('fuelModal').style.display = 'flex';
    document.getElementById('formId').value = '';
    document.getElementById('formDate').value = "<?= date('Y-m-d') ?>";
    toggleFields();
}
function closeFuelModal() { document.getElementById('fuelModal').style.display = 'none'; }

function toggleFields() {
    const type = document.getElementById('activityType').value;
    document.getElementById('inflowFields').style.display = type === 'INFLOW' ? 'block' : 'none';
    document.getElementById('outflowFields').style.display = (type === 'OUTFLOW' || type === 'TRANSFERRED') ? 'block' : 'none';
}

function editRecord(data) {
    openFuelModal();
    document.getElementById('formId').value = data.id;
    document.getElementById('activityType').value = data.activity;
    document.getElementById('formDate').value = data.rdate;
    document.getElementById('in_rec').value = data.received_from || '';
    document.getElementById('in_rr').value = data.rr_no || '';
    document.getElementById('out_ws').value = data.ws_no || '';
    document.getElementById('out_tank').value = data.withdrawn_from || ''; 
    document.getElementById('formDep').value = data.deposited_to || '';
    document.getElementById('formQty').value = data.qty;
    toggleFields();
}

function deleteRecord(id) { if(confirm("Delete this record?")) window.location.href = "delete_fuel.php?id=" + id; }
function clearInventory() { if(confirm("Wipe all data permanently?")) window.location.href = "clear_inventory.php"; }

window.onclick = function(event) {
    if (event.target == document.getElementById('uploadModal')) closeUploadModal();
    if (event.target == document.getElementById('fuelModal')) closeFuelModal();
}
</script>
</body>
</html>
