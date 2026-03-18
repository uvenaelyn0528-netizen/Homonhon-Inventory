<?php
session_start();
include 'db.php'; // This provides the $pdo connection

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // In PDO, we use prepare() to prevent SQL injection (no need for real_escape_string)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(); // Fetches as an associative array automatically

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // SETTING THE SESSION
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; 
                
                header("Location: index.php");
                exit();
            } else {
                $error = "Maling password!";
            }
        } else {
            $error = "User hindi mahanap!";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warehouse Inventory</title>
    <style>
        :root {
            --primary-dark: #2c3e50;
            --goldrich-red: #8b0000;
            --text-gray: #7f8c8d;
            --bg-color: #f4f7f6;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: var(--bg-color); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }

        .login-card { 
            background: white; 
            padding: 35px; 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            width: 380px; 
            text-align: center; 
        }

        .brand-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
            text-align: left;
        }

        .logo-small { 
            width: 65px; 
            height: auto; 
        }

        .header-text-group h1 { 
            color: var(--goldrich-red); 
            margin: 0; 
            font-size: 16px; 
            font-family: Broadway, 'Arial Black', sans-serif;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .header-text-group p {
            color: var(--text-gray);
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 4px 0 0 0;
            line-height: 1.3;
        }

        .system-title-container {
            border-top: 1px solid #eee;
            margin-top: 15px;
            padding-top: 15px;
            margin-bottom: 25px;
        }

        .system-title-container h2 {
            color: var(--primary-dark);
            font-size: 18px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
        }

        input { 
            width: 100%; 
            padding: 12px; 
            margin-bottom: 15px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            box-sizing: border-box; 
            outline: none; 
            transition: 0.3s; 
            font-size: 14px;
        }

        input:focus { 
            border-color: var(--goldrich-red); 
            box-shadow: 0 0 5px rgba(139, 0, 0, 0.1);
        }

        button { 
            width: 100%; 
            padding: 12px; 
            background: var(--primary-dark); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 16px; 
            transition: 0.3s; 
        }

        button:hover { 
            background: #1a252f; 
            transform: translateY(-1px);
        }

        .error { 
            color: #e74c3c; 
            background: #fdedec; 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 15px; 
            font-size: 13px; 
            border: 1px solid #fadbd8;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-header">
            <img src="images/logo.png" class="logo-small" alt="Logo">
            <div class="header-text-group">
                <h1>Goldrich Construction <br> and Trading</h1>
                <p>Homonhon Nickel Project <br> Logistics & Warehouse</p>
            </div>
        </div>

        <div class="system-title-container">
            <h2>Warehouse <br> Inventory System</h2>
        </div>
        
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Sign In</button>
        </form>

        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed #ddd;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px; color: var(--text-gray);">
                <span style="font-size: 14px;">🔑</span>
                <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                    Access Issue? Contact System Admin
                </span>
            </div>
            <a href="mailto:admin@goldrich.com" style="display: block; margin-top: 5px; font-size: 12px; color: var(--goldrich-red); text-decoration: none; font-weight: bold;">
                support.warehouse@goldrich.com
            </a>
        </div>
        </div> </body>
</html>
