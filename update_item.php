<?php
include 'db.php';
session_start();

if (isset($_POST['update_item'])) {
    // Security Check
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
        die("Unauthorized access. Admins only.");
    }

    $id   = $_POST['item_id'];
    $name = $_POST['item_name'];
    $spec = $_POST['specification'];
    $min  = (int)$_POST['min_stock'];
    $max  = (int)$_POST['max_stock'];

    try {
        // Use PDO prepared statements for security
        $sql = "UPDATE inventory SET 
                item_name = :name, 
                specification = :spec, 
                min_stock = :min, 
                max_stock = :max 
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $name,
            ':spec' => $spec,
            ':min'  => $min,
            ':max'  => $max,
            ':id'   => $id
        ]);

        header("Location: index.php?msg=updated");
        exit();
    } catch (PDOException $e) {
        echo "Error updating record: " . $e->getMessage();
    }
}
?>
