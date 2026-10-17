<?php
/**
 * Equipment Utilization Report (EUR) Management
 */
session_start();
$username =$_SESSION['username'] ?? 'User';
$role     =$_SESSION['role'] ?? 'Staff';

// 1. Supabase Database Configuration
$host     = getenv('DB_HOST')     ?: 'aws-1-ap-northeast-1.pooler.supabase.com';
$port     = getenv('DB_PORT')     ?: '6543';$dbname   = getenv('DB_NAME')     ?: 'postgres';
$user     = getenv('DB_USER')     ?: 'postgres.otrkginfndevnotgkajc';$password = getenv('DB_PASSWORD') ?: 'Wh01302016!2025';

// 2. Establish Database Connection
$pdo = null;
$errorMessage = null;

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    $pdo = new PDO($dsn, $user,$password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {$errorMessage = "Database Connection Error: " . $e->getMessage();
}

// Determine current user role from session
$userRole =$_SESSION['role'] ?? '';
$isViewer = (strcasecmp($userRole, 'Viewer') === 0);

// 3. Handle Form Submission (Add New EUR Record - Restricted for Viewers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_eur' &&$pdo) {
    if ($isViewer) {$errorMessage = "Access Denied: Viewer accounts do not have permission to add or edit entries.";
    } else {
        try {
            $stmt =$pdo->prepare("
                INSERT INTO eur_records (date, eur_no, name, equip_type, equip_id, shift, time_start, time_end, no_of_hrs, monitoring_name)
                VALUES (:date, :eur_no, :name, :equip_type, :equip_id, :shift, :time_start, :time_end, :no_of_hrs, :monitoring_name)
            ");
            
            $stmt->execute([
                ':date'            => $_POST['date'],
                ':eur_no'          => $_POST['eur_no'],
                ':name'            => $_POST['name'],
                ':equip_type'      => $_POST['equip_type'],
                ':equip_id'        => $_POST['equip_id'],
                ':shift'           => $_POST['shift'],
                ':time_start'      => $_POST['time_start'],
                ':time_end'        => $_POST['time_end'],
                ':no_of_hrs'       => $_POST['no_of_hrs'],
                ':monitoring_name' => $_POST['monitoring_name'],
            ]);

            header("Location: eur.php?success=1");
            exit;
        } catch (PDOException $e) {$errorMessage = "Failed to save EUR entry: " . $e->getMessage();
        }
    }
}

// 4. Summary Filter Parameters
$sumGroup    =$_GET['sum_group']    ?? 'equipment'; // 'equipment' or 'operator'
$periodType  =$_GET['period_type']  ?? '1st_15th';   // '1st_15th', '16th_end', 'full_month', 'custom'
$selectedMth =$_GET['selected_mth'] ?? date('Y-m');
$startDate   =$_GET['start_date']   ?? date('Y-m-01');
$endDate     =$_GET['end_date']     ?? date('Y-m-t');
$openModal   = isset($_GET['show_summary']) &&$_GET['show_summary'] === '1';

// Calculate Date Range based on Period Selection
if ($periodType === '1st_15th') {
    $filterStart =$selectedMth . '-01';
    $filterEnd   =$selectedMth . '-15';
} elseif ($periodType === '16th_end') {$filterStart = $selectedMth . '-16';$filterEnd   = date('Y-m-t', strtotime($selectedMth . '-01')); } elseif ($periodType === 'full_month') {
    $filterStart =$selectedMth . '-01';
    $filterEnd   = date('Y-m-t', strtotime($selectedMth . '-01'));
} else {
    $filterStart =$startDate;
    $filterEnd   =$endDate;
}

// 5. Fetch Table Data & Summary Query
$eurRecords  = [];$summaryData = [];

if ($pdo) {
    try {
        // Fetch Main EUR Table
        $stmt =$pdo->query("SELECT * FROM eur_records ORDER BY date DESC, id DESC");
        $eurRecords =$stmt->fetchAll();

        // Dynamic Summary Query
        if ($sumGroup === 'operator') {$sumSql = "
                SELECT 
                    name as label_title,
                    'Operator' as group_type,
                    COUNT(id) as total_entries,
                    SUM(no_of_hrs) as total_hrs
                FROM eur_records
                WHERE date BETWEEN :start_date AND :end_date
                GROUP BY name
                ORDER BY total_hrs DESC
            ";
        } else {
            $sumSql = "
                SELECT 
                    equip_id as label_title,
                    MAX(equip_type) as sub_type,
                    COUNT(id) as total_entries,
                    SUM(no_of_hrs) as total_hrs
                FROM eur_records
                WHERE date BETWEEN :start_date AND :end_date
                GROUP BY equip_id
                ORDER BY equip_id ASC
            ";
        }

        $sumStmt =$pdo->prepare($sumSql);$sumStmt->execute([
            ':start_date' => $filterStart,
            ':end_date'   => $filterEnd
        ]);
        $summaryData =$sumStmt->fetchAll();

    } catch (PDOException $e) {$errorMessage = "Notice: Database query failed. " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EUR - Equipment Utilization Report</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; margin: 0; padding: 25px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        
        /* Header Banner Design */
        .header-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            padding: 22px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: inset 0 2px 4px rgba(255,255,255,0.1);
        }
        .header-title-area h1 { margin: 0 0 5px 0; font-size: 26px; font-weight: 700; letter-spacing: 0.5px; }
        .header-title-area p { margin: 0; color: #94a3b8; font-size: 13px; }
        .user-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-badge span { color: #38bdf8; font-weight: 600; }

        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .nav-actions { display: flex; gap: 10px; position: relative; z-index: 10; flex-wrap: wrap; }
        
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 18px; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; transition: all 0.2s ease; }
        .btn-back { background-color: #64748b; color: #fff; }
        .btn-back:hover { background-color: #475569; }
        .btn-add { background-color: #2563eb; color: #fff; position: relative; z-index: 11; }
        .btn-add:hover { background-color: #1d4ed8; }
        .btn-summary { background-color: #0d9488; color: #fff; }
        .btn-summary:hover { background-color: #0f766e; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }

        /* Table Styling */
        .table-responsive { overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f1f5f9; }

        /* Modal Form & Summary Styling */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; pointer-events: none; }
        .modal.active { display: flex; pointer-events: auto; }
        .modal-content { background: #fff; padding: 25px; border-radius: 10px; width: 100%; max-width: 750px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; font-size: 20px; color: #1e293b; }
        .close-btn { font-size: 22px; cursor: pointer; color: #94a3b8; }
        .close-btn:hover { color: #000; }

        /* Filter Box Inside Summary */
        .summary-filters { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        label { font-size: 12px; font-weight: 600; color: #475569; }
        input, select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background-color: #fff; }
        input:focus, select:focus { border-color: #2563eb; }
        .form-actions { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }
    </style>
</head>
<body>

<div class="container">
    <!-- Header Banner Design -->
    <div class="header-banner">
        <div class="header-title-area">
            <h1>Equipment Utilization Report (EUR)</h1>
            <p>Engineering Department &bull; Operational Tracking</p>
        </div>
        <div class="user-badge">
            👤 <?= htmlspecialchars($username); ?> &bull; <span><?= htmlspecialchars($role); ?></span>
        </div>
    </div>

    <div class="action-bar">
        <div class="nav-actions">
            <a href="Engineering.php" class="btn btn-back">← Back to Engineering</a>
            <button type="button" class="btn btn-summary" onclick="openSummaryModal()">📊 Summary of Accumulated Hours</button>
        </div>
        <div class="nav-actions">
            <?php if (!$isViewer): ?>
                <button type="button" class="btn btn-add" onclick="openModal()">+ Add New Entry</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">EUR Record successfully saved!</div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>EUR No.</th>
                    <th>Name</th>
                    <th>Equip. Type</th>
                    <th>Equip. ID</th>
                    <th>Shift</th>
                    <th>Time Start</th>
                    <th>Time End</th>
                    <th>No. Of Hrs</th>
                    <th>Monitoring Name</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($eurRecords)): ?>
                    <?php foreach ($eurRecords as$row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['date'] ?? ''); ?></td>
                            <td><strong><?= htmlspecialchars($row['eur_no'] ?? ''); ?></strong></td>
                            <td><?= htmlspecialchars($row['name'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['equip_type'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['equip_id'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['shift'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['time_start'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['time_end'] ?? ''); ?></td>
                            <td><strong><?= htmlspecialchars($row['no_of_hrs'] ?? '0'); ?></strong></td>
                            <td><?= htmlspecialchars($row['monitoring_name'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center; color: #64748b; padding: 25px;">
                            No EUR entries found. 
                            <?php if (!$isViewer): ?>
                                Click <strong>"+ Add New Entry"</strong> to record an equipment report.
                            <?php else: ?>
                                (Viewer Mode: Read-only access)
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Interactive Summary Modal -->
<div id="summaryModal" class="modal <?= $openModal ? 'active' : ''; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Accumulated Operating Hours Summary</h2>
            <span class="close-btn" onclick="closeSummaryModal()">&times;</span>
        </div>

        <!-- Filter Controls Form -->
        <form method="GET" action="eur.php" class="summary-filters">
            <input type="hidden" name="show_summary" value="1">
            <div class="filter-grid">
                <div class="form-group">
                    <label for="sum_group">Group Summary By</label>
                    <select id="sum_group" name="sum_group">
                        <option value="equipment" <?= $sumGroup === 'equipment' ? 'selected' : ''; ?>>Per Equipment ID</option>
                        <option value="operator" <?= $sumGroup === 'operator' ? 'selected' : ''; ?>>Per Operator Name</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="period_type">Cut-off / Date Range</label>
                    <select id="period_type" name="period_type" onchange="toggleDateInputs()">
                        <option value="1st_15th" <?= $periodType === '1st_15th' ? 'selected' : ''; ?>>1st to 15th Cut-off</option>
                        <option value="16th_end" <?= $periodType === '16th_end' ? 'selected' : ''; ?>>16th to End of Month</option>
                        <option value="full_month" <?= $periodType === 'full_month' ? 'selected' : ''; ?>>Full Month</option>
                        <option value="custom" <?= $periodType === 'custom' ? 'selected' : ''; ?>>Custom Date Range</option>
                    </select>
                </div>

                <div class="form-group" id="month_input_group" style="<?= $periodType === 'custom' ? 'display:none;' : ''; ?>">
                    <label for="selected_mth">Select Month</label>
                    <input type="month" id="selected_mth" name="selected_mth" value="<?= htmlspecialchars($selectedMth); ?>">
                </div>

                <div class="form-group custom-date-grp" style="<?= $periodType !== 'custom' ? 'display:none;' : ''; ?>">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate); ?>">
                </div>

                <div class="form-group custom-date-grp" style="<?= $periodType !== 'custom' ? 'display:none;' : ''; ?>">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate); ?>">
                </div>
            </div>
            <div style="margin-top: 12px; text-align: right;">
                <button type="submit" class="btn btn-summary" style="padding: 8px 16px; font-size: 13px;">Apply Filters</button>
            </div>
        </form>

        <p style="font-size: 12px; color: #64748b; margin-bottom: 12px;">
            Showing results for period: <strong><?= date('M d, Y', strtotime($filterStart)); ?></strong> to <strong><?= date('M d, Y', strtotime($filterEnd)); ?></strong>
        </p>

        <!-- Summary Data Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?= $sumGroup === 'operator' ? 'Operator Name' : 'Equipment ID'; ?></th>
                        <?php if ($sumGroup === 'equipment'): ?>
                            <th>Equipment Type</th>
                        <?php endif; ?>
                        <th>Total Entries</th>
                        <th>Accumulated Operating Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($summaryData)): ?>
                        <?php foreach ($summaryData as$row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['label_title']); ?></strong></td>
                                <?php if ($sumGroup === 'equipment'): ?>
                                    <td><?= htmlspecialchars($row['sub_type'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($row['total_entries']); ?></td>
                                <td><strong style="color: #2563eb;"><?= number_format($row['total_hrs'], 1); ?> hrs</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">
                                No records found for the selected cut-off period.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-actions" style="margin-top: 20px;">
            <button type="button" class="btn btn-back" onclick="closeSummaryModal()">Close</button>
        </div>
    </div>
</div>

<?php if (!$isViewer): ?>
<!-- Modal Form for Adding EUR (Hidden for Viewers) -->
<div id="eurModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add EUR Record</h2>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" action="eur.php">
            <input type="hidden" name="action" value="add_eur">
            <div class="form-grid">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required value="<?= date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="eur_no">EUR No.</label>
                    <input type="text" id="eur_no" name="eur_no" placeholder="EUR-0001" required>
                </div>
                
                <div class="form-group">
                    <label for="name">Operator Name</label>
                    <select id="name" name="name" required>
                        <option value="">Select Operator</option>
                        <option value="Laconsay, Kevin">Laconsay, Kevin</option>
                        <option value="Uvena, Mario">Uvena, Mario</option>
                        <option value="Benitez, Kyle">Benitez, Kyle</option>
                        <option value="Salve, Levi">Salve, Levi</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="equip_type">Equip. Type</label>
                    <select id="equip_type" name="equip_type" required>
                        <option value="">Select Equipment Type</option>
                        <option value="Backhoe">Backhoe</option>
                        <option value="Wheel Loader">Wheel Loader</option>
                        <option value="Bulldozer">Bulldozer</option>
                        <option value="Road Grader">Road Grader</option>
                        <option value="Road Roller">Road Roller</option>
                        <option value="Dumptruck">Dumptruck</option>
                        <option value="Service Vehicle">Service Vehicle</option>
                        <option value="Water Truck">Water Truck</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="equip_id">Equip. ID</label>
                    <select id="equip_id" name="equip_id" required>
                        <option value="">Select Equipment ID</option>
                        <option value="TX-01">TX-01</option>
                        <option value="TX-02">TX-02</option>
                        <option value="TX-03">TX-03</option>
                        <option value="TX-04">TX-04</option>
                        <option value="TX-05">TX-05</option>
                        <option value="TX-06">TX-06</option>
                        <option value="TX-07">TX-07</option>
                        <option value="TX-08">TX-08</option>
                        <option value="TX-09">TX-09</option>
                        <option value="TX-10">TX-10</option>
                        <option value="DT-01">DT-01</option>
                        <option value="DT-02">DT-02</option>
                        <option value="DT-03">DT-03</option>
                        <option value="DT-04">DT-04</option>
                        <option value="DT-05">DT-05</option>
                        <option value="DT-06">DT-06</option>
                        <option value="DT-07">DT-07</option>
                        <option value="DT-08">DT-08</option>
                        <option value="DT-09">DT-09</option>
                        <option value="DT-10">DT-10</option>
                        <option value="RR-01">RR-01</option>
                        <option value="RR-02">RR-02</option>
                        <option value="RR-03">RR-03</option>
                        <option value="RR-04">RR-04</option>
                        <option value="RR-05">RR-05</option>
                        <option value="RR-06">RR-06</option>
                        <option value="RR-07">RR-07</option>
                        <option value="RR-08">RR-08</option>
                        <option value="RR-09">RR-09</option>
                        <option value="RR-10">RR-10</option>
                        <option value="WL-01">WL-01</option>
                        <option value="WL-02">WL-02</option>
                        <option value="WL-03">WL-03</option>
                        <option value="WL-04">WL-04</option>
                        <option value="RG-01">RG-01</option>
                        <option value="RG-02">RG-02</option>
                        <option value="RG-03">RG-03</option>
                        <option value="RG-04">RG-04</option>
                        <option value="WT-01">WT-01</option>
                        <option value="WT-02">WT-02</option>
                        <option value="WT-03">WT-03</option>
                        <option value="WT-04">WT-04</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="shift">Shift</label>
                    <select id="shift" name="shift" required>
                        <option value="Day">Day Shift</option>
                        <option value="Night">Night Shift</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="time_start">Time Start</label>
                    <input type="time" id="time_start" name="time_start" onchange="calculateHours()" required>
                </div>
                <div class="form-group">
                    <label for="time_end">Time End</label>
                    <input type="time" id="time_end" name="time_end" onchange="calculateHours()" required>
                </div>
                <div class="form-group">
                    <label for="no_of_hrs">No. Of Hrs</label>
                    <input type="number" step="0.1" id="no_of_hrs" name="no_of_hrs" placeholder="0.0" required>
                </div>
                <div class="form-group">
                    <label for="monitoring_name">Monitoring Name</label>
                    <input type="text" id="monitoring_name" name="monitoring_name" placeholder="Supervisor/Inspector" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-back" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-add">Save Entry</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    function openModal() {
        const modal = document.getElementById('eurModal');
        if (modal) modal.classList.add('active');
    }
    function closeModal() {
        const modal = document.getElementById('eurModal');
        if (modal) modal.classList.remove('active');
    }

    function openSummaryModal() {
        const modal = document.getElementById('summaryModal');
        if (modal) modal.classList.add('active');
    }
    function closeSummaryModal() {
        const modal = document.getElementById('summaryModal');
        if (modal) modal.classList.remove('active');
    }

    function toggleDateInputs() {
        const type = document.getElementById('period_type').value;
        const mthGroup = document.getElementById('month_input_group');
        const customGrps = document.querySelectorAll('.custom-date-grp');

        if (type === 'custom') {
            mthGroup.style.display = 'none';
            customGrps.forEach(grp => grp.style.display = 'flex');
        } else {
            mthGroup.style.display = 'flex';
            customGrps.forEach(grp => grp.style.display = 'none');
        }
    }
    
    // Auto-calculate hours between time_start and time_end
    function calculateHours() {
        const start = document.getElementById('time_start').value;
        const end = document.getElementById('time_end').value;
        if (start && end) {
            const startDate = new Date(`1970-01-01T${start}:00`);
            let endDate = new Date(`1970-01-01T${end}:00`);
            if (endDate < startDate) {
                endDate.setDate(endDate.getDate() + 1);
            }
            const diffHours = (endDate - startDate) / (1000 * 60 * 60);
            document.getElementById('no_of_hrs').value = diffHours.toFixed(1);
        }
    }

    window.onclick = function(event) {
        const modalAdd = document.getElementById('eurModal');
        const modalSum = document.getElementById('summaryModal');
        if (modalAdd && event.target == modalAdd) closeModal();
        if (modalSum && event.target == modalSum) closeSummaryModal();
    }
</script>

</body>
</html>
