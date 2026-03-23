<?php
session_start();
$role = $_SESSION['role'] ?? 'Viewer';
include 'db.php'; 

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(); 

        if ($user) {
            if (password_verify($password, $user['password'])) {
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warehouse Inventory</title>
    <style>
        :root {
            --navy: #112941;
            --goldrich-red: #8b0000;
            --text-gray: #64748b;
            --light-border: #e2e8f0;
        }

        body {
            /* Subtle gradient background to make the white login card pop */
            background: radial-gradient(circle at center, #ffffff 0%, #f1f5f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .login-card { 
            background: white; 
            padding: 40px; 
            border-radius: 16px; 
            /* Softer, more professional shadow for depth */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); 
            width: 100%;
            max-width: 400px; 
            text-align: center; 
            border: 1px solid var(--light-border);
        }

        .brand-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
            text-align: left;
        }

        .logo-small { 
            width: 60px; 
            height: auto; 
            /* Subtle glow behind the logo */
            filter: drop-shadow(0 4px 8px rgba(139, 0, 0, 0.1));
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
            letter-spacing: 0.8px;
            margin: 4px 0 0 0;
            line-height: 1.3;
            font-weight: 600;
        }

        .system-title-container {
            border-top: 1px solid #f1f5f9;
            margin-top: 15px;
            padding-top: 20px;
            margin-bottom: 30px;
        }

        .system-title-container h2 {
            color: var(--navy);
            font-size: 20px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 800;
        }

        /* Improved Input Styling */
        input { 
            width: 100%; 
            padding: 12px 15px; 
            margin-bottom: 18px; 
            border: 1.5px solid #cbd5e1; 
            border-radius: 10px; 
            box-sizing: border-box; 
            outline: none; 
            transition: all 0.3s ease; 
            font-size: 14px;
            color: #1e293b;
        }

        /* Active state transitions to Navy */
        input:focus { 
            border-color: var(--navy); 
            box-shadow: 0 0 0 4px rgba(17, 41, 65, 0.08);
            background-color: #fff;
        }

        button { 
            width: 100%; 
            padding: 14px; 
            background: var(--navy); 
            color: white; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 700; 
            font-size: 15px; 
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease; 
            margin-top: 10px;
        }

        button:hover { 
            background: #1a3a5a; 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(17, 41, 65, 0.2);
        }

        button:active {
            transform: translateY(0);
        }

        .error { 
            color: #b91c1c; 
            background: #fef2f2; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 13px; 
            border: 1px solid #fee2e2;
            font-weight: 500;
        }

        .footer-support {
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 1px solid #f1f5f9;
        }

        .footer-support p {
            font-size: 11px;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .footer-support a {
            display: block; 
            margin-top: 8px; 
            font-size: 13px; 
            color: var(--goldrich-red); 
            text-decoration: none; 
            font-weight: 700;
            transition: opacity 0.2s;
        }

        .footer-support a:hover {
            opacity: 0.8;
            text-decoration: underline;
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

        <div class="footer-support">
            <p><span>🔑</span> Access Issue? Contact Admin</p>
            <a href="mailto:support.warehouse@goldrich.com">
                support.warehouse@goldrich.com
            </a>
        </div>
    </div>
</body>
</html>
