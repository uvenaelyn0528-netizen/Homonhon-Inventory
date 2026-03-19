<?php
include 'db.php';
session_start();

if (isset($_POST['update_summary'])) {
    // Ensure only Admin/Staff can perform this action if needed
    if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
        die("Unauthorized access.");
    }

    $id    = $_POST['id'];
    $rr    = $_POST['rr_number'];
    $rdate = $_POST['RDATE']; 
    $qty   = $_POST['Qty'];

    try {
        // Use PDO Prepared Statements (No need for mysqli_real_escape_string)
        $sql = "UPDATE received_history SET 
                rr_number = :rr, 
                received_date = :rdate, 
                \"Qty\" = :qty 
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':rr'    => $rr,
            ':rdate' => $rdate,
            ':qty'   => $qty,
            ':id'    => $id
        ]);

        header("Location: received_summary.php?msg=success");
        exit();
    } catch (PDOException $e) {
        // Detailed error reporting for debugging
        echo "Database Error: " . $e->getMessage();
    }
}
?>
