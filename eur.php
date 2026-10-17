<?php
/**
 * Equipment Utilization Report (EUR) Management
 */

// 1. Supabase Database Configuration
$host     = getenv('DB_HOST')     ?: 'aws-1-ap-northeast-1.pooler.supabase.com';
$port     = getenv('DB_PORT')     ?: '6543';$dbname   = getenv('DB_NAME')     ?: 'postgres';
$user     = getenv('DB_USER')     ?: 'postgres.otrkginfndevnotgkajc';
$password = getenv('DB_PASSWORD') ?: 'Wh01302016!2025'; // Ensure your password is set here or in Render environment variables

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

// 3. Handle Form Submission (Add New EUR Record)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_eur' &&$pdo) {
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

// 4. Fetch EUR Records
$eurRecords = [];
if ($pdo) {
    try {
        $stmt =$pdo->query("SELECT * FROM eur_records ORDER BY date DESC, id DESC");
        $eurRecords =$stmt->fetchAll();
    } catch (PDOException $e) {$errorMessage = "Notice: 'eur_records' table missing or query failed. " . $e->getMessage();
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
        
        .header-section { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        h1 { margin: 0; color: #1e293b; font-size: 24px; font-weight: 700; }
        
        .nav-actions { display: flex; gap: 10px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 18px; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; transition: all 0.2s ease; }
        .btn-back { background-color: #64748b; color: #fff; }
        .btn-back:hover { background-color: #475569; }
        .btn-add { background-color: #2563eb; color: #fff; }
        .btn-add:hover { background-color: #1d4ed8; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }

        /* Table Styling */
        .table-responsive { overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f1f5f9; }

        /* Modal Form Styling */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 25px; border-radius: 10px; width: 100%; max-width: 600px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; font-size: 20px; color: #1e293b; }
        .close-btn { font-size: 22px; cursor: pointer; color: #94a3b8; }
        .close-btn:hover { color: #000; }
        
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.full-width { grid-column: span 2; }
        label { font-size: 13px; font-weight: 600; color: #475569; }
        input, select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; background-color: #fff; }
        input:focus, select:focus { border-color: #2563eb; }
        .form-actions { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <h1>Equipment Utilization Report (EUR)</h1>
        <div class="nav-actions">
            <a href="Engineering.php" class="btn btn-back">← Back to Engineering</a>
            <button class="btn btn-add" onclick="openModal()">+ Add New Entry</button>
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
                            No EUR entries found. Click <strong>"+ Add New Entry"</strong> to record an equipment report.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form for Adding EUR -->
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
                
                <!-- Operator Name Dropdown -->
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

                <!-- Equipment Type Dropdown -->
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

                <!-- Equipment ID Dropdown -->
                <div class="form-group">
                    <label for="equip_id">Equip. ID</label>
                    <select id="equip_id" name="equip_id" required>
                        <option value="">Select Equipment ID</option>
                        <!-- TX-01 to 10 -->
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
                        <!-- DT-01 to 10 -->
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
                        <!-- RR-01 to 10 -->
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
                        <!-- WL-01 to 04 -->
                        <option value="WL-01">WL-01</option>
                        <option value="WL-02">WL-02</option>
                        <option value="WL-03">WL-03</option>
                        <option value="WL-04">WL-04</option>
                        <!-- RG-01 to 04 -->
                        <option value="RG-01">RG-01</option>
                        <option value="RG-02">RG-02</option>
                        <option value="RG-03">RG-03</option>
                        <option value="RG-04">RG-04</option>
                        <!-- WT-01 to 04 -->
                        <option value="WT-01">WT-01</option>
                        <option value="WT-02">WT-02</option>
                        <option value="WT-03">WT-03</option>
                        <option value="WT-04">WT-04</option>
