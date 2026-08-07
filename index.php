<?php
// Force all errors to show
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$role = $_SESSION['role'] ?? 'guest';

try {
    if (!file_exists('db.php')) {
        throw new Exception("File 'db.php' is missing from the server.");
    }
    require 'db.php'; 
} catch (Throwable $e) {
    die("<div style='background:red; color:white; padding:20px; font-family:sans-serif;'>
            <h2>Database Connection Error</h2>
            <p>" . $e->getMessage() . "</p>
            <p>File: " . $e->getFile() . " on line " . $e->getLine() . "</p>
         </div>");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRCT Homonhon Project - Departments Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #2c3e50;
            --warning: #f39c12;
            --success: #27ae60;
            --dark-blue: #112941;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
        }

        /* Top Navigation Bar */
        .top-navbar {
            background-color: var(--dark-blue);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .top-navbar .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .top-navbar .brand img {
            width: 45px;
            height: auto;
        }

        .top-navbar .brand h1 {
            font-size: 16px;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .user-panel {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 14px;
        }

        .logout-btn {
            background-color: #e74c3c;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        /* Main Dashboard Container */
        .dashboard-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .dashboard-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            margin-bottom: 30px;
            border-left: 5px solid #3498db;
        }

        .dashboard-header h2 {
            margin: 0 0 5px 0;
            color: var(--dark-blue);
            font-size: 24px;
        }

        .dashboard-header p {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Grid Layout matching reference image */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .dept-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid #e1e8ed;
            border-top: 4px solid var(--dark-blue);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .dept-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.08);
            border-top-color: #3498db;
        }

        .dept-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
        }

        .dept-icon {
            font-size: 28px;
            background: #f8f9fa;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dept-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--dark-blue);
            margin: 0;
        }

        .dept-desc {
            font-size: 13px;
            color: #7f8c8d;
            margin: 0;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <!-- Top Navigation Header -->
    <div class="top-navbar">
        <div class="brand">
            <img src="images/logo.png" alt="Logo">
            <h1>GOLDRICH CONSTRUCTION AND TRADING</h1>
        </div>
        <div class="user-panel">
            <span>Hello, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong></span>
            <a href="logout.php" class="logout-btn" onclick="return confirm('Confirm Logout?')">Logout</a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="dashboard-container">
        
        <div class="dashboard-header">
            <h2>Homonhon Project Departments & Divisions</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>! (Role: <strong><?php echo htmlspecialchars($role); ?></strong>) - Select a department below to view records and inventory workflows.</p>
        </div>

        <!-- Cards Grid for Departments & Divisions -->
        <div class="cards-grid">
            
            <a href="Warehouse.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">📦</div>
                    <div>
                        <h3 class="dept-title">Warehouse</h3>
                        <p class="dept-desc">General storage, stocks, items, and inventory processing.</p>
                    </div>
                </div>
            </a>

            <a href="Admin.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">⚙️</div>
                    <div>
                        <h3 class="dept-title">Admin</h3>
                        <p class="dept-desc">Administrative controls, policies, and personnel operations.</p>
                    </div>
                </div>
            </a>

            <a href="mechanical.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">🔧</div>
                    <div>
                        <h3 class="dept-title">Mechanical</h3>
                        <p class="dept-desc">Equipment maintenance, heavy machinery parts, and servicing.</p>
                    </div>
                </div>
            </a>

            <a href="Safety.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">🦺</div>
                    <div>
                        <h3 class="dept-title">Safety</h3>
                        <p class="dept-desc">COSH/BOSH compliance, PPE, and site safety logs.</p>
                    </div>
                </div>
            </a>

            <a href="TSG.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">🛠️</div>
                    <div>
                        <h3 class="dept-title">TSG</h3>
                        <p class="dept-desc">Technical Services Group operations and project support.</p>
                    </div>
                </div>
            </a>

            <a href="Assay.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">🧪</div>
                    <div>
                        <h3 class="dept-title">Assay</h3>
                        <p class="dept-desc">Laboratory testing, mineral sample logs, and chemical supplies.</p>
                    </div>
                </div>
            </a>

            <a href="Mine_operation.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">⛏️</div>
                    <div>
                        <h3 class="dept-title">Mine Operation</h3>
                        <p class="dept-desc">Extraction tracking, pit equipment, and mining logs.</p>
                    </div>
                </div>
            </a>

            <a href="Port_Operation.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">⚓</div>
                    <div>
                        <h3 class="dept-title">Port Operation</h3>
                        <p class="dept-desc">Shipping docks, cargo handling, and logistics tracking.</p>
                    </div>
                </div>
            </a>

            <a href="Comrel.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">🤝</div>
                    <div>
                        <h3 class="dept-title">Comrel</h3>
                        <p class="dept-desc">Community relations, local engagement, and outreach projects.</p>
                    </div>
                </div>
            </a>

            <a href="Envi.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">🌱</div>
                    <div>
                        <h3 class="dept-title">Envi (Environment)</h3>
                        <p class="dept-desc">Environmental monitoring, compliance, and rehabilitation.</p>
                    </div>
                </div>
            </a>

            <a href="Nursery.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">🌳</div>
                    <div>
                        <h3 class="dept-title">Nursery</h3>
                        <p class="dept-desc">Tree planting, reforestation initiatives, and seedling tracking.</p>
                    </div>
                </div>
            </a>

            <a href="Engineering.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">📐</div>
                    <div>
                        <h3 class="dept-title">Engineering</h3>
                        <p class="dept-desc">Infrastructure blueprints, civil works, and technical plans.</p>
                    </div>
                </div>
            </a>

        </div>
    </div>

</body>
</html>
