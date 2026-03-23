<?php
include 'db.php';
$message = ""; 
$message_type = "";

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Warehouse System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .register-card { background: white; padding: 30px 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 360px; text-align: center; }
        h2 { color: #2c3e50; font-size: 18px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
        
        .input-group { position: relative; width: 100%; margin-bottom: 15px; text-align: left; }
        label { font-size: 11px; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-left: 5px; display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; outline: none; transition: 0.3s; font-size: 14px; }
        input:focus, select:focus { border-color: #27ae60; }
        
        .toggle-password { position: absolute; right: 12px; top: 35px; cursor: pointer; color: #95a5a6; }

        button { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 16px; margin-top: 10px; }
        button:hover { background: #219150; }
        
        .error { color: #e74c3c; background: #fdedec; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; border: 1px solid #fadbd8; }
        .success { color: #27ae60; background: #eafaf1; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; border: 1px solid #d4efdf; }
        .login-link { margin-top: 15px; display: block; font-size: 13px; color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class="register-card">
        <img src="images/logo.png" style="width: 140px; margin-bottom: 10px;" alt="Logo">
        <h2>Account Registration</h2>
        
        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter username" required>
            </div>

            <div class="input-group">
                <label>System Role</label>
                <select name="role" required>
                    <option value="Staff">Staff</option>
                    <option value="Admin">Admin</option>
                    <option value="Project Manager">Project Manager</option>
                    <option value="Head Office Purchasing">Head Office Purchasing</option>
                    <option value="Viewer">Viewer</option>
                </select>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" id="password" name="password" placeholder="Min. 8 characters" required>
                <i class="fa-solid fa-eye toggle-password" onclick="togglePass('password', this)"></i>
            </div>

            <div class="input-group">
                <label>Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                <i class="fa-solid fa-eye toggle-password" onclick="togglePass('confirm_password', this)"></i>
            </div>

            <button type="submit" name="register">Register Account</button>
        </form>

        <a href="login.php" class="login-link">Already have an account? Sign In</a>
    </div>

    <script>
        function togglePass(id, icon) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }
    </script>
</body>
</html>
