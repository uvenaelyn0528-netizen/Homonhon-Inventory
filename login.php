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
        --navy-dark: #0f172a;
        --navy-primary: #1e293b;
        --goldrich-red: #991b1b;
        --accent-gold: #d97706;
        --text-gray: #64748b;
        --light-bg: #f8fafc;
        --light-border: #e2e8f0;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--navy-dark);
        overflow: hidden;
    }

    /* Split Screen Layout Container */
    .login-wrapper {
        display: flex;
        width: 100vw;
        height: 100vh;
    }

    /* Left Side - Image Container */
    .left-image-section {
        flex: 1.2;
        background-image: url('images/BG.jpeg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
    }
    
    /* Overlay gradient over background image for subtle warmth */
    .left-image-section::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.2) 0%, rgba(153, 27, 27, 0.25) 100%);
    }

    /* Right Side - Login Section */
    .right-login-section {
        flex: 0.8;
        background: var(--light-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
        box-shadow: -12px 0 35px rgba(0, 0, 0, 0.25);
        z-index: 2;
    }

    /* Styled Login Card Modal */
    .login-card { 
        width: 100%;
        max-width: 400px; 
        background: #ffffff;
        border-radius: 16px;
        padding: 35px 30px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
        border: 1px solid var(--light-border);
        border-top: 5px solid var(--goldrich-red);
        position: relative;
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
        width: 55px; 
        height: auto; 
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
    }

    .header-text-group h1 { 
        color: var(--goldrich-red); 
        margin: 0; 
        font-size: 15px; 
        font-family: Broadway, 'Arial Black', sans-serif;
        line-height: 1.2;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .header-text-group p {
        color: var(--text-gray);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 4px 0 0 0;
        font-weight: 700;
    }

    .system-title-container {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 25px;
        text-align: center;
        color: #ffffff;
        box-shadow: inset 0 1px 2px rgba(255,255,255,0.1);
    }

    .system-title-container h2 {
        color: #f8fafc;
        font-size: 15px;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        font-weight: 700;
    }
    .system-title-container p {
        margin: 3px 0 0 0;
        color: #38bdf8;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Input Field Container with Icons */
    .input-group {
        position: relative;
        margin-bottom: 18px;
    }

    .input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 15px;
        color: #94a3b8;
    }

    input { 
        width: 100%; 
        padding: 12px 15px 12px 42px; 
        border: 1.5px solid #cbd5e1; 
        border-radius: 10px; 
        outline: none; 
        transition: all 0.3s ease; 
        font-size: 14px;
        color: #1e293b;
        background-color: #f8fafc;
    }

    input:focus { 
        border-color: #2563eb; 
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        background-color: #ffffff;
    }

    button { 
        width: 100%; 
        padding: 13px; 
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); 
        color: white; 
        border: none; 
        border-radius: 10px; 
        cursor: pointer; 
        font-weight: 700; 
        font-size: 14px; 
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s ease; 
        margin-top: 5px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
    }

    button:hover { 
        background: linear-gradient(135deg, #8b0000 0%, #991b1b 100%); 
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(153, 27, 27, 0.3);
    }

    .error { 
        color: #991b1b; 
        background: #fee2e2; 
        padding: 10px 14px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        font-size: 13px; 
        border: 1px solid #f87171;
        font-weight: 600;
        text-align: center;
    }

    .footer-support {
        margin-top: 25px; 
        padding-top: 18px; 
        border-top: 1px solid #f1f5f9;
        text-align: center;
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
        font-weight: 600;
    }

    .footer-support a {
        display: block; 
        margin-top: 6px; 
        font-size: 12px; 
        color: var(--goldrich-red); 
        text-decoration: none; 
        font-weight: 700;
        transition: opacity 0.2s;
    }

    .footer-support a:hover {
        opacity: 0.8;
        text-decoration: underline;
    }

    /* Responsive Design for Mobile Devices */
    @media (max-width: 768px) {
        .login-wrapper {
            flex-direction: column;
        }
        .left-image-section {
            display: none;
        }
        .right-login-section {
            flex: 1;
            width: 100%;
            padding: 20px;
        }
    }
</style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side: Full Screen Background Image -->
        <div class="left-image-section"></div>

        <!-- Right Side: Clean Login Form Modal -->
        <div class="right-login-section">
            <div class="login-card">
                <div class="brand-header">
                    <img src="images/logo.png" class="logo-small" alt="Logo">
                    <div class="header-text-group">
                        <h1>Goldrich Construction <br> and Trading</h1>
                        <p>Homonhon Nickel Project</p>
                    </div>
                </div>

                <div class="system-title-container">
                    <h2>GRCT HOMONHON</h2>
                    <p>Data Management System</p>
                </div>
                
                <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
                
                <form method="POST">
                    <div class="input-group">
                        <span class="input-icon">👤</span>
                        <input type="text" name="username" placeholder="Username" required autofocus>
                    </div>

                    <div class="input-group">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>

                    <button type="submit" name="login">Sign In</button>
                </form>

                <div class="footer-support">
                    <p><span>🔑</span> Access Issue? Contact Admin</p>
                    <a href="mailto:support.warehouse@goldrich.com">
                        support.warehouse@goldrich.com
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
