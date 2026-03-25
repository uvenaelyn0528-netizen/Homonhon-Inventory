<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Get Filters (Matching your inventory page logic)
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
$sql .= " ORDER BY rdate DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

// 2. Set Headers for Excel Download
$filename = "Diesel_Ledger_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// 3. Output the Table
?>
<table border="1">
    <thead>
        <tr style="background-color: #112941; color: white;">
            <th>DATE</th>
            <th>ACTIVITY</th>
            <th>SUPPLIER / SOURCE</th>
            <th>RR NO.</th>
            <th>WS NO.</th>
            <th>WITHDRAWN FROM</th>
            <th>DEPOSITED TO</th>
            <th>QTY (L)</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
        <tr>
            <td><?php echo date('M d, Y', strtotime($row['rdate'])); ?></td>
            <td><?php echo $row['activity']; ?></td>
            <td><?php echo $row['received_from']; ?></td>
            <td><?php echo $row['rr_no']; ?></td>
            <td><?php echo $row['ws_no']; ?></td>
            <td><?php echo $row['withdrawn_from']; ?></td>
            <td><?php echo $row['deposited_to']; ?></td>
            <td><?php echo number_format($row['qty'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
