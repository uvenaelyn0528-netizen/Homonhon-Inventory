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
    
    // 1. INSERT THE CAPTURE HERE
    // This captures the text-based department value (e.g., "MECHANICAL")
    $dept = $_POST['department']; 

    try {
        // 2. UPDATE THE SQL STRING
        // Add the department field to the SET clause
        $sql = "UPDATE inventory SET 
                item_name = :name, 
                specification = :spec, 
                department = :dept, 
                min_stock = :min, 
                max_stock = :max 
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        
        // 3. UPDATE THE EXECUTION ARRAY
        // Include the ':dept' mapping in the array
        $stmt->execute([
            ':name' => $name,
            ':spec' => $spec,
            ':dept' => $dept,
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
