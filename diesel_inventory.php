<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); } 

// Authorization Check
$isAuthorized = isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'staff']);

// 1. Filters & Search Logic
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

// 2. Statistics & Tanks (Simplified for display)
$bal_stmt = $conn->query("SELECT (SUM(CASE WHEN activity = 'INFLOW' THEN qty ELSE 0 END) - SUM(CASE WHEN activity = 'OUTFLOW' THEN qty ELSE 0 END)) as balance FROM diesel_inventory");
$balance = $bal_stmt->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0;

$tanks_ft = ["TANK 001", "TANK 002", "TANK 003", "TANK 004", "TANK 005", "TANK 006", "TANK 007", "TANK 008", "TANK 009", "FT-03", "FT-04", "FD-01", "FD-02", "FT-02", "MT LARRY", "MT PHITE", "MT GIEDI"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Diesel Ledger | Goldrich Construction</title>
    <style>
        :root { --navy: #112941; --gold: #f1c40f; --dark-red: #8B0000; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; }
        .main-header { background: #fff; padding: 10px 30px; border-bottom: 4px solid var(--gold); display: flex; align-items: center; justify-content: space-between; }
        .table-container { background: white; margin: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: var(--navy); color: white; padding: 12px; text-align: left; font-size: 11px; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 12px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 11px; text-decoration: none; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; justify-content:center; align-items:center; }
        .modal-content { background:white; border-radius:8px; width:400px; padding: 20px; }
        .attachment-link { color: var(--navy); font-weight: bold; text-decoration: none; background: #e2e8f0; padding: 3px 8px; border-radius: 4px; font-size: 10px; }
    </style>
</head>
<body>

    <header class="main-header">
        <h2 style="margin:0; font-size:18px;">DIESEL INVENTORY LEDGER</h2>
        <div style="text-align:right;">
            <div style="font-size: 20px; font-weight: 900; color: var(--navy);"><?= number_format($balance, 2) ?> L</div>
        </div>
    </header>

    <div class="table-container">
        <?php if (isset($_GET['upload']) || isset($_GET['msg'])): ?>
            <div id="statusAlert" style="background: #dcfce7; color: #166534; padding: 15px; border-bottom: 1px solid #bbf7d0; font-weight: bold;">
                <?= isset($_GET['upload']) ? "✅ File uploaded to Supabase successfully!" : "✅ Record updated successfully!" ?>
            </div>
            <script>setTimeout(() => document.getElementById('statusAlert')?.remove(), 4000);</script>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>DATE</th>
                    <th>ACTIVITY</th>
                    <th>SOURCE</th>
                    <th>RR NO.</th>
                    <th>QTY (L)</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($row['rdate'])) ?></td>
                    <td><?= $row['activity'] ?></td>
                    <td><?= htmlspecialchars($row['received_from'] ?: '---') ?></td>
                    <td>
                        <?= htmlspecialchars($row['rr_no'] ?: '---') ?>
                        <?php if (!empty($row['attachment_path'])): ?>
                            <br><a href="<?= $row['attachment_path'] ?>" target="_blank" class="attachment-link">📎 View Scan</a>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:bold; color:var(--dark-red);"><?= number_format($row['qty'], 2) ?></td>
                    <td>
                        <?php if ($isAuthorized): ?>
                            <button onclick='editRecord(<?= json_encode($row) ?>)'>✏️</button>
                            <?php if (strtoupper($row['activity']) === 'INFLOW'): ?>
                                <button type="button" onclick="openUploadModal(<?= $row['id'] ?>)">📤</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <h3>Upload Scan</h3>
            <form action="diesel_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="upload_only" value="1">
                <input type="hidden" name="id" id="uploadId">
                <input type="file" name="attachment" required style="margin-bottom: 20px; display:block;">
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn" style="background:var(--navy); color:white; flex:1;">UPLOAD</button>
                    <button type="button" onclick="closeUploadModal()" class="btn" style="background:#eee; flex:1;">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <div id="fuelModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Fuel Entry</h3>
            <form action="diesel_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="formId">
                <select name="activity" id="activityType" required style="width:100%; padding:8px; margin-bottom:10px;">
                    <option value="INFLOW">INFLOW</option>
                    <option value="OUTFLOW">OUTFLOW</option>
                </select>
                <input type="date" name="rdate" id="formDate" required style="width:100%; padding:8px; margin-bottom:10px;">
                <input type="number" step="0.01" name="qty" id="formQty" placeholder="Quantity" required style="width:100%; padding:8px; margin-bottom:10px;">
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn" style="background:var(--navy); color:white; flex:1;">SAVE</button>
                    <button type="button" onclick="closeFuelModal()" class="btn" style="background:#eee; flex:1;">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUploadModal(id) {
            document.getElementById('uploadModal').style.display = 'flex';
            document.getElementById('uploadId').value = id;
        }
        function closeUploadModal() { document.getElementById('uploadModal').style.display = 'none'; }
        
        function editRecord(data) {
            document.getElementById('fuelModal').style.display = 'flex';
            document.getElementById('formId').value = data.id;
            document.getElementById('activityType').value = data.activity;
            document.getElementById('formDate').value = data.rdate;
            document.getElementById('formQty').value = data.qty;
        }
        function closeFuelModal() { document.getElementById('fuelModal').style.display = 'none'; }
    </script>
</body>
</html>
