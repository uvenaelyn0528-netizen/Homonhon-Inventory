<?php
include 'db.php';
$message = ""; $message_type = "";

if (isset($_POST['register'])) {
    // 1. Capture and trim inputs
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; 

    // 2. Validation
    if (empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
        $message_type = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $message_type = "error";
    } else {
        try {
            // 3. Check if username exists using PDO Prepared Statement
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            
            if ($stmt->fetch()) {
                $message = "Username already taken!";
                $message_type = "error";
            } else {
                // 4. Hash password and Insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)";
                $insert_stmt = $conn->prepare($sql);
                
                $result = $insert_stmt->execute([
                    ':username' => $username,
                    ':password' => $hashed_password,
                    ':role'     => $role
                ]);

                if ($result) {
                    $message = "Account created as $role! Redirecting...";
                    $message_type = "success";
                    header("refresh:2;url=login.php");
                }
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>
