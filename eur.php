<?php
/**
 * Equipment Utilization Report (EUR) Management
 */
session_start();
$username = $_SESSION['username'] ?? 'User';
$role     = $_SESSION['role'] ?? 'Staff';

// 1. Supabase Database Configuration
$host     = getenv('DB_HOST')     ?: 'aws-1-ap-northeast-1.pooler.supabase.com';
$port     = getenv('DB_PORT')     ?: '6543'; 
$dbname   = getenv('DB_NAME')     ?: 'postgres';
$user     = getenv('DB_USER')     ?: 'postgres.otrkginfndevnotgkajc';
$password = getenv('DB_PASSWORD') ?: 'Wh01302016!2025';

// 2. Establish Database Connection
$pdo = null;
$errorMessage = null;

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    $errorMessage = "Database Connection Error: " . $e->getMessage();
}

// Determine current user role from session
$userRole = $_SESSION['role'] ?? '';
$isViewer = (strcasecmp($userRole, 'Viewer') === 0);

// 3. Handle Form Submissions (Add, Edit, Delete EUR Records)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $pdo) {
    if ($isViewer) {
        $errorMessage = "Access Denied: Viewer accounts do not have permission to add, edit, or delete entries.";
    } else {
        // --- ADD ENTRY ---
        if ($_POST['action'] === 'add_eur') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO eur_records (date, eur_no, name, equip_type, equip_id, shift, time_start, time_end, no_of_hrs, monitoring_name, activities, area)
                    VALUES (:date, :eur_no, :name, :equip_type, :equip_id, :shift, :time_start, :time_end, :no_of_hrs, :monitoring_name, :activities, :area)
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
                    ':activities'      => $_POST['activities'],
                    ':area'            => $_POST['area'],
                ]);

                header("Location: eur.php?success=1");
                exit;
            } catch (PDOException $e) {
                $errorMessage = "Failed to save EUR entry: " . $e->getMessage();
            }
        }

        // --- EDIT ENTRY ---
        if ($_POST['action'] === 'edit_eur' && !empty($_POST['id'])) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE eur_records 
                    SET date = :date, eur_no = :eur_no, name = :name, equip_type = :equip_type, 
                        equip_id = :equip_id, shift = :shift, time_start = :time_start, 
                        time_end = :time_end, no_of_hrs = :no_of_hrs, monitoring_name = :monitoring_name,
                        activities = :activities, area = :area
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    ':id'              => $_POST['id'],
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
                    ':activities'      => $_POST['activities'],
                    ':area'            => $_POST['area'],
                ]);

                header("Location: eur.php?updated=1");
                exit;
            } catch (PDOException $e) {
                $errorMessage = "Failed to update EUR entry: " . $e->getMessage();
            }
        }

        // --- DELETE ENTRY ---
        if ($_POST['action'] === 'delete_eur' && !empty($_POST['id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM eur_records WHERE id = :id");
                $stmt->execute([':id' => $_POST['id']]);

                header("Location: eur.php?deleted=1");
                exit;
            } catch (PDOException $e) {
                $errorMessage = "Failed to delete EUR entry: " . $e->getMessage();
            }
        }
    }
}

// 4. Summary Filter Parameters
$sumGroup    = $_GET['sum_group']    ?? 'equipment'; 
$periodType  = $_GET['period_type']  ?? '1st_15th';   
$selectedMth = $_GET['selected_mth'] ?? date('Y-m');
$startDate   = $_GET['start_date']   ?? date('Y-m-01');
$endDate     = $_GET['end_date']     ?? date('Y-m-t');
$openModal   = isset($_GET['show_summary']) && $_GET['show_summary'] === '1';

if ($periodType === '1st_15th') {
    $filterStart = $selectedMth . '-01';
    $filterEnd   = $selectedMth . '-15';
} elseif ($periodType === '16th_end') {
    $filterStart = $selectedMth . '-16';
    $filterEnd   = date('Y-m-t', strtotime($selectedMth . '-01'));
} elseif ($periodType === 'full_month') {
    $filterStart = $selectedMth . '-01';
    $filterEnd   = date('Y-m-t', strtotime($selectedMth . '-01'));
} else {
    $filterStart = $startDate;
    $filterEnd   = $endDate;
}

// 5. Fetch EUR Records & Summary
$eurRecords  = [];
$summaryData = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM eur_records ORDER BY date DESC, id DESC");
        $eurRecords = $stmt->fetchAll();

        if ($sumGroup === 'operator') {
            $sumSql = "
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

        $sumStmt = $pdo->prepare($sumSql);
        $sumStmt->execute([
            ':start_date' => $filterStart,
            ':end_date'   => $filterEnd
        ]);
        $summaryData = $sumStmt->fetchAll();

    } catch (PDOException $e) {
        $errorMessage = "Notice: Query failed. " . $e->getMessage();
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
        .container { max-width: 1350px; margin: 0 auto; background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        
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

        .btn-edit { background-color: #f59e0b; color: #fff; padding: 5px 10px; font-size: 12px; border-radius: 4px; margin-right: 4px; }
        .btn-edit:hover { background-color: #d97706; }
        .btn-delete { background-color: #ef4444; color: #fff; padding: 5px 10px; font-size: 12px; border-radius: 4px; }
        .btn-delete:hover { background-color: #dc2626; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }

        .table-responsive { overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f1f5f9; }

        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; pointer-events: none; }
        .modal.active { display: flex; pointer-events: auto; }
        .modal-content { background: #fff; padding: 25px; border-radius: 10px; width: 100%; max-width: 750px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; font-size: 20px; color: #1e293b; }
        .close-btn { font-size: 22px; cursor: pointer; color: #94a3b8; }
        .close-btn:hover { color: #000; }

        .summary-filters { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.full-width { grid-column: span 2; }
        label { font-size: 12px; font-weight: 600; color: #475569; }
        input, select, textarea { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background-color: #fff; }
        input:focus, select:focus, textarea:focus { border-color: #2563eb; }
        .form-actions { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }
    </style>
</head>
<body>

<div class="container">
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
                <button type="button" class="btn btn-add" onclick="openAddModal()">+ Add New Entry</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">EUR Record successfully saved!</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">EUR Record updated successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">EUR Record deleted successfully!</div>
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
                    <th>Activities</th>
                    <th>Area</th>
                    <th>Shift</th>
                    <th>Time Start</th>
                    <th>Time End</th>
                    <th>No. Of Hrs</th>
                    <th>Monitoring Name</th>
                    <?php if (!$isViewer): ?>
                        <th style="text-align: center;">Actions</th>
                    <?php endif; ?>
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
                            <td><?= htmlspecialchars($row['activities'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['area'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['shift'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['time_start'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['time_end'] ?? ''); ?></td>
                            <td><strong><?= htmlspecialchars($row['no_of_hrs'] ?? '0'); ?></strong></td>
                            <td><?= htmlspecialchars($row['monitoring_name'] ?? ''); ?></td>
                            <?php if (!$isViewer): ?>
                                <td style="text-align: center;">
                                    <button type="button" class="btn btn-edit" onclick='openEditModal(<?= json_encode($row); ?>)'>Edit</button>
                                    <form method="POST" action="eur.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete EUR record <?= htmlspecialchars($row['eur_no']); ?>?');">
                                        <input type="hidden" name="action" value="delete_eur">
                                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                        <button type="submit" class="btn btn-delete">Delete</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $isViewer ? 12 : 13; ?>" style="text-align: center; color: #64748b; padding: 25px;">
                            No EUR entries found.
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
<!-- Add/Edit EUR Modal -->
<div id="eurModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add EUR Record</h2>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" action="eur.php" id="eurForm">
            <input type="hidden" name="action" id="formAction" value="add_eur">
            <input type="hidden" name="id" id="recordId" value="">
            
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
                    <select id="equip_type" name="equip_type" onchange="updateDynamicDropdowns()" required>
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
                        <option value="">Select Equip. Type First</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="shift">Shift</label>
                    <select id="shift" name="shift" required>
                        <option value="Day">Day Shift</option>
                        <option value="Night">Night Shift</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="activities">Activities</label>
                    <select id="activities" name="activities" required>
                        <option value="">Select Equip. Type First</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="area">Area</label>
                    <select id="area" name="area" required>
                        <option value="">Select Area</option>
                        <option value="MA1">MA1</option>
                        <option value="MA2">MA2</option>
                        <option value="MA3">MA3</option>
                        <option value="MA4">MA4</option>
                        <option value="MA5">MA5</option>
                        <option value="MA6">MA6</option>
                        <option value="MA8">MA8</option>
                        <option value="MA9">MA9</option>
                        <option value="MA10">MA10</option>
                        <option value="MA11">MA11</option>
                        <option value="MA12">MA12</option>
                        <option value="MA14">MA14</option>
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
                <button type="submit" class="btn btn-add" id="submitBtn">Save Entry</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    // Equipment ID mapping by Equipment Type
    const equipIDMap = {
        'Backhoe': ['TX-01','TX-02','TX-03','TX-04','TX-05','TX-06','TX-07','TX-08','TX-09','TX-10'],
        'Wheel Loader': ['WL-01','WL-02','WL-03','WL-04'],
        'Bulldozer': ['BD-01','BD-02','BD-03','BD-04'],
        'Road Grader': ['RG-01','RG-02','RG-03','RG-04'],
        'Road Roller': ['RR-01','RR-02','RR-03','RR-04','RR-05','RR-06','RR-07','RR-08','RR-09','RR-10'],
        'Dumptruck': ['DT-01','DT-02','DT-03','DT-04','DT-05','DT-06','DT-07','DT-08','DT-09','DT-10'],
        'Service Vehicle': ['SV-01','SV-02','SV-03','SV-04'],
        'Water Truck': ['WT-01','WT-02','WT-03','WT-04']
    };

    // Activities mapping by Equipment Type
    const activitiesMap = {
        'Dumptruck': ['DT', 'Hauling of Ore', 'Hauling of Waste', 'Hauling of Boulders', 'Hauling of Item 200'],
        'Backhoe': ['Loading of Ore', 'Loading of Waste', 'Loading of Boulders', 'Loading of Item 200', 'Receiving of Ore', 'Receiving of Waste', 'Receiving of Boulders', 'Receiving of Item 200'],
        'Road Grader': ['Road Maintenance', 'Road Piloting'],
        'Road Roller': ['Road Maintenance', 'Road Piloting'],
        'Bulldozer': ['Road Maintenance', 'Road Piloting'],
        'Wheel Loader': ['Road Maintenance', 'Road Piloting'],
        'Water Truck': ['Watering'],
        'Service Vehicle': ['Road Maintenance', 'Road Piloting']
    };

    function updateDynamicDropdowns(selectedID = '', selectedActivity = '') {
        const typeSelect = document.getElementById('equip_type').value;
        
        // Update Equip IDs
        const idSelect = document.getElementById('equip_id');
        idSelect.innerHTML = '<option value="">Select Equipment ID</option>';
        if (typeSelect && equipIDMap[typeSelect]) {
            equipIDMap[typeSelect].forEach(id => {
                const opt = document.createElement('option');
                opt.value = id;
                opt.textContent = id;
                if (id === selectedID) opt.selected = true;
                idSelect.appendChild(opt);
            });
        }

        // Update Activities
        const actSelect = document.getElementById('activities');
        actSelect.innerHTML = '<option value="">Select Activity</option>';
        if (typeSelect && activitiesMap[typeSelect]) {
            activitiesMap[typeSelect].forEach(act => {
                const opt = document.createElement('option');
                opt.value = act;
                opt.textContent = act;
                if (act === selectedActivity) opt.selected = true;
                actSelect.appendChild(opt);
            });
        }
    }

    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add EUR Record';
        document.getElementById('formAction').value = 'add_eur';
        document.getElementById('recordId').value = '';
        document.getElementById('eurForm').reset();
        updateDynamicDropdowns();
        document.getElementById('eurModal').classList.add('active');
    }

    function openEditModal(record) {
        document.getElementById('modalTitle').textContent = 'Edit EUR Record';
        document.getElementById('formAction').value = 'edit_eur';
        document.getElementById('recordId').value = record.id;

        document.getElementById('date').value = record.date;
        document.getElementById('eur_no').value = record.eur_no;
        document.getElementById('name').value = record.name;
        document.getElementById('equip_type').value = record.equip_type;
        
        updateDynamicDropdowns(record.equip_id, record.activities);

        document.getElementById('area').value = record.area || '';
        document.getElementById('shift').value = record.shift;
        document.getElementById('time_start').value = record.time_start;
        document.getElementById('time_end').value = record.time_end;
        document.getElementById('no_of_hrs').value = record.no_of_hrs;
        document.getElementById('monitoring_name').value = record.monitoring_name;

        document.getElementById('eurModal').classList.add('active');
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
