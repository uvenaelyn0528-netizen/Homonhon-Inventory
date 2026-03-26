<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); } 

// Authorization Check
$isAuthorized = isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'staff']);

// 1. Handle Filters & Search
$search = $_GET['search'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$filter_activity = $_GET['activity'] ?? ''; 

$sql = "SELECT * FROM diesel_inventory WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (rr_no LIKE :search OR ws_no LIKE :search OR deposited_to LIKE :search OR received_from LIKE :search OR withdrawn_from LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($filter_activity)) {
    $sql .= " AND activity = :activity";
    $params[':activity'] = $filter_activity;
}
if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND rdate BETWEEN :from_date AND :to_date";
    $params[':from_date'] = $from_date;
    $params[':to_date'] = $to_date;
}

$sql .= " ORDER BY rdate DESC, recorded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);

// 2. Total System Stock Logic
$bal_stmt = $conn->query("SELECT (SUM(CASE WHEN activity = 'INFLOW' THEN qty ELSE 0 END) - SUM(CASE WHEN activity = 'OUTFLOW' THEN qty ELSE 0 END)) as balance FROM diesel_inventory");
$balance = $bal_stmt->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0;

$date_stmt = $conn->query("SELECT MAX(rdate) as latest_date FROM diesel_inventory");
$latest_raw = $date_stmt->fetch(PDO::FETCH_ASSOC)['latest_date'];
$as_of_date = $latest_raw ? date('F d, Y', strtotime($latest_raw . ' +1 day')) : date('F d, Y', strtotime('+1 day'));

// 3. Tank Breakdown Logic
$tank_query = $conn->query("
    SELECT unit_name, SUM(amount) as unit_balance
    FROM (
        SELECT deposited_to as unit_name, qty as amount FROM diesel_inventory WHERE (deposited_to LIKE 'TANK%' OR deposited_to LIKE 'FT%') AND (activity = 'INFLOW' OR activity = 'TRANSFERRED')
        UNION ALL
        SELECT withdrawn_from as unit_name, -qty as amount FROM diesel_inventory WHERE (withdrawn_from LIKE 'TANK%' OR withdrawn_from LIKE 'FT%') AND (activity = 'OUTFLOW' OR activity = 'TRANSFERRED')
    ) AS combined GROUP BY unit_name HAVING unit_name IS NOT NULL AND unit_name NOT IN ('', '---', 'Direct to Unit') ORDER BY unit_name ASC
");
$unit_breakdown = $tank_query->fetchAll(PDO::FETCH_ASSOC);
$tanks_ft = ["TANK 001", "TANK 002", "TANK 003", "TANK 004", "TANK 005", "TANK 006", "TANK 007", "TANK 008", "TANK 009", "FT-03", "FT-04", "FD-01", "FD-02", "FT-02", "MT LARRY", "MT PHITE", "MT GIEDI"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Diesel Ledger | Goldrich Construction</title>
    <style>
        :root { --navy: #112941; --gold: #f1c40f; --dark-red: #8B0000; }
        html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; font-family: 'Segoe UI', sans-serif; background: var(--navy) url('images/background.jpg') no-repeat center center fixed; background-size: cover; }
        .page-wrapper { display: flex; flex-direction: column; height: 100vh; position: relative; z-index: 1; }
        .main-header { background: #fff; padding: 8px 30px; border-bottom: 4px solid var(--gold); display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 15px rgba(0,0,0,0.3); z-index: 100; }
        .company-name { color: var(--dark-red); margin: 0; font-size: 16px; font-family: Broadway, sans-serif; }
        .total-stock-position { background: var(--navy); color: var(--gold); padding: 10px 25px; border-radius: 6px; text-align: center; border: 1px solid var(--gold); }
        .tank-strip { display: flex; gap: 8px; padding: 8px 20px; background: rgba(17, 41, 65, 0.8); overflow-x: auto; }
        .tank-mini-card { background: rgba(255,255,255,0.9); padding: 4px 12px; border-radius: 4px; min-width: 100px; border-left: 3px solid var(--gold); }
        .table-container { flex: 1; overflow: auto; background: rgba(255, 255, 255, 0.98); margin: 0 15px 15px 15px; border-radius: 4px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        table { width: 100%; border-collapse: collapse; min-width: 1100px; }
        thead th { position: sticky; top: 0; background: var(--navy); color: white; padding: 12px 15px; font-size: 11px; text-transform: uppercase; z-index: 50; text-align: left; }
        td { padding: 10px 15px; border-bottom: 1px solid #eee; font-size: 12px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 11px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: 0.2s; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center; }
        .modal-content { background:white; border-radius:8px; width:450px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .attachment-link { color: var(--navy); font-weight: bold; text-decoration: none; display: inline-block; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 10px; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <header class="main-header">
        <div class="header-left"><a href="index.php" class="btn" style="background:#eee; color:#333;">⬅ DASHBOARD</a></div>
        <div class="header-center" style="display:flex; align-items:center; gap:15px;">
            <img src="images/logo.png" style="width:50px;">
            <div>
                <h2 class="company-name">GOLDRICH CONSTRUCTION AND TRADING</h2>
                <h3 style="margin:0; font-size:22px; color:var(--navy); font-weight:900;">DIESEL INVENTORY LEDGER</h3>
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
            <div style="font-size:9px; font-weight:800; color:#64748b;"><?= htmlspecialchars($unit['unit_name']) ?></div>
            <div style="font-size:13px; font-weight:800; color:var(--navy);"><?= number_format($unit['unit_balance'], 0) ?> L</div>
        </div>
        <?php endforeach; ?>
    </div>

    <nav style="background:#fff; padding:8px 30px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #ddd;">
        <form method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search records..." style="padding: 6px; border: 1px solid #ccc; border-radius: 4px; width: 220px;">
            <button type="submit" class="btn" style="background: var(--navy); color: white;">FILTER</button>
        </form>
        
        <div style="display:flex; gap:12px;">
            <?php if ($isAuthorized): ?>
                <button class="btn" onclick="openFuelModal('INFLOW')" style="background: var(--navy); color: white;">+ NEW ENTRY</button>
            <?php endif; ?>
            <button class="btn" onclick="window.print()" style="background: var(--navy); color: white;">🖨️ PRINT</button>
        </div>
    </nav>

    <div class="table-container">
        <?php if (isset($_GET['upload']) || isset($_GET['msg'])): ?>
            <div id="statusAlert" style="background: #dcfce7; color: #166534; padding: 10px 15px; border-bottom: 1px solid #bbf7d0; font-weight: bold; font-size: 13px;">
                <?= isset($_GET['upload']) ? "✅ File uploaded to Supabase successfully!" : "✅ Record updated successfully!" ?>
            </div>
            <script>setTimeout(() => document.getElementById('statusAlert')?.remove(), 3000);</script>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>DATE</th>
                    <th>ACTIVITY</th>
                    <th>SUPPLIER / SOURCE</th>
                    <th>RR NO.</th>
                    <th>QTY (L)</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td style="font-weight: bold;"><?= date('M d, Y', strtotime($row['rdate'])) ?></td>
                    <td><span style="padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; background: <?= $row['activity']=='INFLOW'?'#dcfce7':'#ffedd5' ?>;"><?= $row['activity'] ?></span></td>
                    <td><?= htmlspecialchars($row['received_from'] ?: '---') ?></td>
                    <td>
                        <?= htmlspecialchars($row['rr_no'] ?: '---') ?>
                        <?php if (!empty($row['attachment_path'])): ?>
                            <br><a href="<?= $row['attachment_path'] ?>" target="_blank" class="attachment-link">📎 View Scan</a>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 900; color: var(--dark-red);"><?= number_format($row['qty'], 2) ?></td>
                    <td>
                        <?php if ($isAuthorized): ?>
                            <button onclick='editRecord(<?= json_encode($row) ?>)' style="border:none; background:none; cursor:pointer;">✏️</button>
                            <?php if (strtoupper($row['activity']) === 'INFLOW'): ?>
                                <button onclick="openUploadModal(<?= $row['id'] ?>)" style="border:none; background:none; cursor:pointer;" title="Upload Scan">📤</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="uploadModal" class="modal">
    <div class="modal-content" style="width: 350px; border-top: 5px solid var(--navy); padding:20px;">
        <h3 style="margin:0 0 15px 0; color: var(--navy); font-size: 16px;">📤 Upload RR Scan</h3>
        <form action="diesel_process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="upload_only" value="1">
            <input type="hidden" name="id" id="uploadId">
            <input type="file" name="attachment" required style="font-size:12px; margin-bottom: 20px;">
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn" style="flex:1; background:var(--navy); color: white; justify-content:center;">UPLOAD</button>
                <button type="button" onclick="closeUploadModal()" class="btn" style="flex:1; background:#eee; justify-content:center;">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUploadModal(id) { document.getElementById('uploadModal').style.display = 'flex'; document.getElementById('uploadId').value = id; }
    function closeUploadModal() { document.getElementById('uploadModal').style.display = 'none'; }
    function openFuelModal() { alert("Open Fuel Modal logic here..."); }
    function editRecord(data) { alert("Edit logic for ID: " + data.id); }
    window.onclick = function(e) { if (e.target.className == 'modal') { closeUploadModal(); } }
</script>
</body>
</html>
