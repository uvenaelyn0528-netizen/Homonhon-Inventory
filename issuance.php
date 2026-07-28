<?php 
include 'db.php'; 

if (session_status() === PHP_SESSION_NONE) { session_start(); } 

// Authorization Check
$isAuthorized = isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'staff']);

// --- HANDLE CSV IMPORT PROCESS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    
    // Increase memory and runtime limits for large imports
    @ini_set('memory_limit', '256M');
    @set_time_limit(120);

    $file = $_FILES['excel_file']['tmp_name'];
    
    if (!empty($file) && ($handle = fopen($file, "r")) !== FALSE) {
        $row_count = 0;
        
        // Skip header row
        fgetcsv($handle, 1000, ",");
        
        try {
            // Check if a transaction is already active before starting one
            if (!$conn->inTransaction()) {
                $conn->beginTransaction();
            }

            $insert_stmt = $conn->prepare("
                INSERT INTO diesel_history 
                (activity, tank_source, rdate, shift, rtime, is_no, ws_no, equipment_type, equipment_id, code, odometer, name, qty) 
                VALUES ('OUTFLOW', :tank_source, :rdate, :shift, :rtime, :is_no, :ws_no, :equipment_type, :equipment_id, :code, :odometer, :name, :qty)
            ");

            while (($raw_data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Ignore completely empty rows
                if (empty($raw_data) || (empty($raw_data[0]) && empty($raw_data[1]))) {
                    continue;
                }

                // Convert encoding to UTF-8 to prevent invalid byte sequence errors
                $data = array_map(function($field) {
                    $val = $field ?? '';
                    return mb_convert_encoding($val, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
                }, $raw_data);

                // Safe parsing of Date & Time
                $rdate = !empty($data[1]) ? date('Y-m-d', strtotime($data[1])) : date('Y-m-d');
                
                $raw_time = trim($data[9] ?? '');
                $rtime = !empty($raw_time) ? date('H:i:s', strtotime($raw_time)) : '00:00:00';

                // Safe parsing of Numeric values
                $raw_qty = !empty($data[11]) ? $data[11] : ($data[2] ?? '0');
                $qty_val = (float) preg_replace('/[^0-9.]/', '', $raw_qty);
                $odometer_val = (float) preg_replace('/[^0-9.]/', '', $data[8] ?? '0');

                $insert_stmt->execute([
                    ':tank_source'    => trim($data[0] ?? 'TANK 001'),
                    ':rdate'          => $rdate,
                    ':ws_no'          => trim($data[3] ?? ''),
                    ':name'           => trim($data[4] ?? ''),
                    ':equipment_type' => trim($data[5] ?? ''),
                    ':equipment_id'   => trim($data[6] ?? ''),
                    ':code'           => trim($data[7] ?? ''),
                    ':odometer'       => $odometer_val,
                    ':rtime'          => $rtime,
                    ':is_no'          => trim($data[10] ?? ''),
                    ':qty'            => $qty_val,
                    ':shift'          => trim($data[12] ?? 'D')
                ]);

                $row_count++;
            }

            // Commit transaction
            if ($conn->inTransaction()) {
                $conn->commit();
            }
            
            fclose($handle);

            header("Location: " . $_SERVER['PHP_SELF'] . "?import_success=" . $row_count);
            exit();

        } catch (Throwable $e) {
            // Catch both Exception and Error objects
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            if ($handle) {
                fclose($handle);
            }
            
            // Output explicit message to avoid blank 502 page
            echo "<div style='font-family:sans-serif; padding:20px; background:#fff3f3; color:#900; border:1px solid #f00;'>";
            echo "<h2>Import Error Encountered</h2>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . " on line " . $e->getLine() . "</p>";
            echo "</div>";
            exit();
        }
    }
}

            // Commit all records in a single execution
            $conn->commit();
            fclose($handle);

            header("Location: " . $_SERVER['PHP_SELF'] . "?import_success=" . $row_count);
            exit();

        } catch (Exception $e) {
            // Roll back changes if an error happens
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            fclose($handle);
            die("<div style='padding:20px; color:red; font-family:sans-serif;'><h3>Import Failed:</h3>" . htmlspecialchars($e->getMessage()) . "</div>");
        }
    }
}

// Fetch distinct options for filter dropdowns
$tank_options = $conn->query("SELECT DISTINCT tank_source FROM diesel_history WHERE activity = 'OUTFLOW' AND tank_source IS NOT NULL AND tank_source != ''")->fetchAll(PDO::FETCH_COLUMN);
$type_options = $conn->query("SELECT DISTINCT equipment_type FROM diesel_history WHERE activity = 'OUTFLOW' AND equipment_type IS NOT NULL AND equipment_type != ''")->fetchAll(PDO::FETCH_COLUMN);

// Filter Input Values
$filter_from = $_GET['from_date'] ?? '';
$filter_to = $_GET['to_date'] ?? '';
$filter_tank = $_GET['tank_source'] ?? '';
$filter_type = $_GET['equipment_type'] ?? '';
$filter_search = trim($_GET['search'] ?? '');

// Build Dynamic SQL Query with Prepared Statements
$sql = "SELECT * FROM diesel_history WHERE activity = 'OUTFLOW'";
$params = [];

if (!empty($filter_from)) {
    $sql .= " AND rdate >= :from_date";
    $params[':from_date'] = $filter_from;
}
if (!empty($filter_to)) {
    $sql .= " AND rdate <= :to_date";
    $params[':to_date'] = $filter_to;
}
if (!empty($filter_tank)) {
    $sql .= " AND tank_source = :tank_source";
    $params[':tank_source'] = $filter_tank;
}
if (!empty($filter_type)) {
    $sql .= " AND equipment_type = :equipment_type";
    $params[':equipment_type'] = $filter_type;
}
if (!empty($filter_search)) {
    $sql .= " AND (ws_no ILIKE :search OR is_no ILIKE :search OR equipment_id ILIKE :search OR name ILIKE :search)";
    $params[':search'] = "%{$filter_search}%";
}

$sql .= " ORDER BY rdate DESC, rtime DESC";

// Execute Prepared Query
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables for totals
$total_year = 0;
$total_month = 0;
$total_day = 0;
$unique_days = []; 
$summary_type = [];
$summary_unit = [];

$current_year = date('Y');
$current_month = date('m');
$current_day = date('Y-m-d');

foreach ($rows as $row) {
    $qty = (float)($row['qty'] ?? 0);
    $row_date = (string)($row['rdate'] ?? '');
    
    if (!empty($row_date)) {
        $unique_days[$row_date] = true; 
        
        $type = !empty($row['equipment_type']) ? $row['equipment_type'] : 'Unknown';
        $unit = !empty($row['equipment_id']) ? $row['equipment_id'] : 'Unknown';

        // Time-based totals
        if (strpos($row_date, $current_year) === 0) {
            $total_year += $qty;
            if (substr($row_date, 5, 2) === $current_month) {
                $total_month += $qty;
            }
        }
        if ($row_date === $current_day) {
            $total_day += $qty;
        }

        // Category-based totals
        $summary_type[$type] = ($summary_type[$type] ?? 0) + $qty;
        $summary_unit[$unit] = ($summary_unit[$unit] ?? 0) + $qty;
    }
}

// Calculate Daily Average based on active days in the filtered results
$day_count = count($unique_days);
$daily_average = ($day_count > 0) ? ($total_year / $day_count) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Issuance Record | Goldrich Construction</title>
    <style>
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
        .btn-filter { background: var(--navy); color: white; }
        .btn-edit { color: #3498db; background: none; border: 1px solid #3498db; padding: 4px 6px; border-radius: 4px; cursor: pointer; }
        .btn-delete { color: #e74c3c; background: none; border: 1px solid #e74c3c; padding: 4px 6px; border-radius: 4px; cursor: pointer; }
        
        /* Filter Bar Styles */
        .filter-bar { background: white; padding: 10px 20px; border-bottom: 1px solid #ddd; display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; flex-shrink: 0; }
        .filter-group { display: flex; flex-direction: column; gap: 3px; }
        .filter-group label { font-size: 10px; font-weight: bold; color: var(--navy); }
        .filter-group input, .filter-group select { padding: 5px 8px; font-size: 11px; border: 1px solid #ccc; border-radius: 4px; height: 28px; box-sizing: border-box; }

        .dashboard-container { padding: 15px 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; flex-shrink: 0; }
        .stat-card { background: white; padding: 12px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-list { font-size: 11px; margin-top: 5px; max-height: 70px; overflow-y: auto; }
        .stat-item { display:flex; justify-content:space-between; border-bottom: 1px solid #eee; padding: 2px 0; }

        .table-container { flex: 1; overflow: auto; padding: 0 20px 20px 20px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; background: white; font-size: 11px; min-width: 1600px; }
        thead th { position: sticky; top: 0; background: var(--navy); color: white; padding: 12px; text-align: left; border-bottom: 2px solid var(--gold); z-index: 50; }
        td { padding: 10px; border-bottom: 1px solid #ddd; white-space: nowrap; }
        
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center; }
        .modal-content { background:white; padding:25px; border-radius:10px; width:650px; max-height: 90vh; overflow-y: auto; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-grid label { font-size: 11px; font-weight: bold; color: var(--navy); display: block; margin-bottom: 5px; }
        .form-grid input, .form-grid select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
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
            <a href="export_issuance.php" class="btn" style="background: #27ae60; color: white; border: 1px solid #219150;">📊 EXPORT TO EXCEL</a>
            <button class="btn btn-add" onclick="openAddModal()">➕ ADD NEW ISSUANCE</button>
            <button class="btn btn-import" onclick="openImportModal()">📥 IMPORT EXCEL</button>
        </div>
    </header>

    <!-- FILTER BAR -->
    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label>From Date</label>
            <input type="date" name="from_date" value="<?= htmlspecialchars($filter_from) ?>">
        </div>
        <div class="filter-group">
            <label>To Date</label>
            <input type="date" name="to_date" value="<?= htmlspecialchars($filter_to) ?>">
        </div>
        <div class="filter-group">
            <label>Tank Source</label>
            <select name="tank_source">
                <option value="">All Tanks</option>
                <?php foreach($tank_options as $tank): ?>
                    <option value="<?= htmlspecialchars($tank) ?>" <?= $filter_tank === $tank ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tank) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Equipment Type</label>
            <select name="equipment_type">
                <option value="">All Types</option>
                <?php foreach($type_options as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= $filter_type === $type ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Search Keyword</label>
            <input type="text" name="search" placeholder="WS#, IS#, Unit, Operator..." value="<?= htmlspecialchars($filter_search) ?>">
        </div>
        <div class="filter-group" style="flex-direction: row; gap: 5px;">
            <button type="submit" class="btn btn-filter">🔍 Filter</button>
        </div>
    </form>

    <div class="dashboard-container">
        <div class="stat-card" style="border-left: 5px solid var(--dark-red);">
            <h4 style="margin:0; color: #666; font-size: 12px;">CONSUMPTION METRICS (L)</h4>
            <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 12px;">
                <span><strong>Daily Avg:</strong> <?= number_format($daily_average, 2) ?></span>
                <span><strong>Month:</strong> <?= number_format($total_month, 2) ?></span>
                <span><strong>Year:</strong> <?= number_format($total_year, 2) ?></span>
            </div>
        </div>

        <div class="stat-card" style="border-left: 5px solid var(--gold);">
            <h4 style="margin:0; color: #666; font-size: 12px;">BY EQUIPMENT TYPE</h4>
            <div class="stat-list">
                <?php foreach($summary_type as $type => $q): ?>
                    <div class="stat-item">
                        <span><?= htmlspecialchars((string)$type) ?></span>
                        <strong><?= number_format($q, 2) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stat-card" style="border-left: 5px solid var(--navy);">
            <h4 style="margin:0; color: #666; font-size: 12px;">BY UNIT (EQPT ID)</h4>
            <div class="stat-list">
                <?php foreach($summary_unit as $unit => $q): ?>
                    <div class="stat-item">
                        <span><?= htmlspecialchars((string)$unit) ?></span>
                        <strong><?= number_format($q, 2) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="table-container">
        <?php if (isset($_GET['import_success'])): ?>
            <div id="statusAlert" style="background: #dcfce7; color: #166534; padding: 12px 15px; border-bottom: 1px solid #bbf7d0; font-weight: bold; font-size: 12px; margin-bottom: 10px; display: flex; justify-content: space-between; border-radius: 4px;">
                <span>✅ Imported <?= (int)$_GET['import_success'] ?> issuance records successfully!</span>
                <button onclick="this.parentElement.remove()" style="background:none; border:none; color:#166534; cursor:pointer;">×</button>
            </div>
            <script>setTimeout(() => document.getElementById('statusAlert')?.remove(), 4000);</script>
        <?php endif; ?>

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
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="14" style="text-align: center; color: #888; padding: 20px;">No records match the current filter criteria.</td>
                </tr>
                <?php else: ?>
                    <?php foreach($rows as $row): ?>
                    <tr>
                        <td style="text-align: center;">
                            <button class="btn-edit" onclick='editIssuance(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>✏️</button>
                            <button class="btn-delete" onclick="deleteIssuance(<?= (int)($row['id'] ?? 0) ?>)">🗑️</button>
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
                        <td><?= !empty($row['rtime']) ? date('h:i A', strtotime($row['rtime'])) : '---' ?></td>
                        <td><?= htmlspecialchars($row['is_no'] ?? '---') ?></td>
                        <td style="text-align:right; font-weight:bold;"><?= number_format($row['qty'] ?? 0, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- IMPORT EXCEL/CSV MODAL -->
<div id="importModal" class="modal">
    <div class="modal-content" style="width: 420px;">
        <h2 style="margin-top:0; color: var(--navy); border-bottom: 2px solid #eee; padding-bottom: 10px;">📥 Import Issuance CSV</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <p style="font-size:11px; color:#666; margin-top:0;">Select a <code>.csv</code> file containing issuance data to bulk upload records.</p>
            <div style="margin-bottom: 15px;">
                <label style="font-size: 11px; font-weight: bold; color: var(--navy); display: block; margin-bottom: 5px;">Select File (.csv)</label>
                <input type="file" name="excel_file" accept=".csv" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px; font-size: 10px; color: #475569; margin-bottom: 20px;">
                <strong>CSV Layout Order:</strong><br>
                1. Tank Source | 2. Date | 3. Shift | 4. Time | 5. IS No. | 6. WS No. | 7. Eqpt Type | 8. Eqpt ID | 9. Code | 10. Odometer | 11. Operator | 12. Qty
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-import" style="flex:1; justify-content:center; font-size: 13px;">UPLOAD & IMPORT</button>
                <button type="button" onclick="toggleImportModal(false)" class="btn btn-back" style="flex:1; justify-content:center; font-size: 13px;">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<!-- ADD / EDIT ISSUANCE MODAL -->
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
    function toggleImportModal(show) { document.getElementById('importModal').style.display = show ? 'flex' : 'none'; }
    function openImportModal() { toggleImportModal(true); }
    
    function openAddModal() {
        document.getElementById('modalTitle').innerText = "New Daily Issuance";
        document.getElementById('submitBtn').innerText = "SAVE ISSUANCE";
        document.getElementById('formId').value = "";
        document.querySelector('#issuanceModal form').reset();
        toggleModal(true);
    }
    
    function editIssuance(data) {
        document.getElementById('modalTitle').innerText = "Edit Daily Issuance";
        document.getElementById('submitBtn').innerText = "UPDATE ISSUANCE";
        document.getElementById('formId').value = data.id || '';
        document.getElementById('f_tank').value = data.tank_source || '';
        document.getElementById('f_date').value = data.rdate || '';
        document.getElementById('f_time').value = data.rtime || '';
        document.getElementById('f_shift').value = data.shift || '';
        document.getElementById('f_slip').value = data.is_no || '';
        document.getElementById('f_ws').value = data.ws_no || '';
        document.getElementById('f_type').value = data.equipment_type || '';
        document.getElementById('f_dep').value = data.equipment_id || '';
        document.getElementById('f_code').value = data.code || '';
        document.getElementById('f_odo').value = data.odometer || '';
        document.getElementById('f_rec').value = data.name || '';
        document.getElementById('f_qty').value = data.qty || '';
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
