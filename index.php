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
            --sidebar-bg: #112941;
            --sidebar-width: 230px; /* Locked width for sidebar */
            --navbar-height: 75px; /* Locked height for top bar */
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Prevents full page scroll */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
        }

        /* --- SIDEBAR (Fixed to Left) --- */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--sidebar-bg);
            color: #bdc3c7;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 1000; /* Stays on top */
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar-profile {
            padding: 15px;
            background: rgba(0,0,0,0.25);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-profile img {
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 50%;
            padding: 2px;
        }

        .sidebar-profile-info .user-name {
            color: #ffffff;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            margin: 0 0 2px 0;
            letter-spacing: 0.5px;
        }

        .sidebar-profile-info .user-role {
            color: #3498db;
            font-size: 9px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            padding: 10px 0;
            display: flex;
            flex-direction: column;
        }

        .sidebar-section-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #7f8c8d;
            padding: 12px 15px 6px 15px;
            font-weight: bold;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 15px;
            color: #ecf0f1;
            text-decoration: none;
            font-size: 12px; /* Increased slightly */
            font-weight: 600; /* Made font bolder */
            letter-spacing: 0.3px;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar-item:hover {
            background-color: rgba(255,255,255,0.08);
            color: #ffffff;
        }

        .sidebar-item span.icon {
            font-size: 14px;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }

        /* --- TOP NAVBAR (Starts AFTER Sidebar) --- */
        .top-navbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width); /* Starts exactly where sidebar ends */
            width: calc(100% - var(--sidebar-width)); /* Takes remaining width */
            height: var(--navbar-height);
            background-color: #ffffff;
            color: #112941;
            padding: 0 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            box-sizing: border-box;
            z-index: 999;
            border-bottom: 1px solid #e1e8ed;
        }

        .top-navbar .brand-container {
            display: flex;
            align-items: center;
        }

        .top-navbar .brand-text h1 {
            font-size: 16px;
            margin: 0;
            color: #8b0000;
            font-weight: 800;
            letter-spacing: 0.5px;
            font-family: 'Georgia', serif;
        }

        .top-navbar .brand-text p {
            font-size: 9px;
            margin: 0;
            color: #555;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .top-navbar .system-title {
            font-size: 15px;
            font-weight: bold;
            color: var(--dark-blue);
            letter-spacing: 1px;
            text-align: center;
            flex: 1; /* Pushes system title to center */
        }

        .user-panel {
            display: flex;
            align-items: center;
        }

        .logout-btn {
            background-color: #e74c3c;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 11px;
            transition: background 0.2s;
            text-transform: uppercase;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        /* --- MAIN DASHBOARD AREA --- */
        .dashboard-container {
            margin-left: var(--sidebar-width); /* Pushes content strictly past sidebar */
            margin-top: var(--navbar-height); /* Pushes content strictly below navbar */
            width: calc(100% - var(--sidebar-width));
            height: calc(100vh - var(--navbar-height));
            padding: 25px;
            box-sizing: border-box;
            overflow-y: auto; /* Scrollable area */
            background-color: #f4f7f6;
        }

        .dashboard-header {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            margin-bottom: 20px;
            border-left: 5px solid #3498db;
        }

        .dashboard-header h2 {
            margin: 0 0 5px 0;
            color: var(--dark-blue);
            font-size: 18px;
        }

        .dashboard-header p {
            margin: 0;
            color: #7f8c8d;
            font-size: 13px;
        }

        /* Grid Layout */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding-bottom: 30px;
        }

        .dept-card {
            background: white;
            border-radius: 10px;
            padding: 18px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 2px 5px rgba(0,0,0,0.04);
            border: 1px solid #e1e8ed;
            border-top: 4px solid var(--dark-blue);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .dept-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.08);
            border-top-color: #3498db;
        }

        .dept-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .dept-icon {
            font-size: 24px;
            background: #f8f9fa;
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dept-title {
            font-size: 15px;
            font-weight: bold;
            color: var(--dark-blue);
            margin: 0 0 4px 0;
        }

        .dept-desc {
            font-size: 12px;
            color: #7f8c8d;
            margin: 0;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-profile">
            <img src="images/logo.png" alt="Logo">
            <div class="sidebar-profile-info">
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'USER'); ?></p>
                <p class="user-role">TYPE: <?php echo strtoupper(htmlspecialchars($role)); ?></p>
            </div>
        </div>

        <div class="sidebar-menu">
            <div class="sidebar-section-title">Fuel Management</div>
            <a href="diesel_inventory.php" class="sidebar-item">
                <span class="icon">⛽</span>
                <span>Diesel Inventory</span>
            </a>

            <div class="sidebar-section-title">Records & History</div>
            <a href="Request_history.php" class="sidebar-item">
                <span class="icon">📄</span>
                <span>Request History</span>
            </a>
            <a href="Received_history.php" class="sidebar-item">
                <span class="icon">📥</span>
                <span>Received History</span>
            </a>
            <a href="Withdrawal_history.php" class="sidebar-item">
                <span class="icon">📤</span>
                <span>Withdrawal History</span>
            </a>
            <a href="Inventory_trash.php" class="sidebar-item" style="color: #e74c3c;">
                <span class="icon">🗑️</span>
                <span>Inventory Trash Bin</span>
            </a>

            <div class="sidebar-section-title">Financials and Analytics</div>
            <a href="Department_costing.php" class="sidebar-item">
                <span class="icon">📊</span>
                <span>Department Costing</span>
            </a>

            <div class="sidebar-section-title">Main Actions</div>
            <a href="Add_item.php" class="sidebar-item">
                <span class="icon">➕</span>
                <span>Add Item</span>
            </a>
            <a href="Request_item.php" class="sidebar-item">
                <span class="icon">📝</span>
                <span>Request Item</span>
            </a>

            <div class="sidebar-section-title">Administration</div>
            <a href="register.php" class="sidebar-item">
                <span class="icon">👤</span>
                <span>Create Account</span>
            </a>
            <?php if ($role === 'admin'): ?>
            <a href="manage_users.php" class="sidebar-item">
                <span class="icon">⚙️</span>
                <span>Manage Users</span>
            </a>
            <?php endif; ?>
        </div>
    </aside>

    <!-- TOP NAVBAR -->
    <div class="top-navbar">
        <div class="brand-container">
            <div class="brand-text">
                <h1>GOLDRICH CONSTRUCTION AND TRADING</h1>
                <p>HOMONHON NICKEL PROJECT • LOGISTICS & WAREHOUSE</p>
            </div>
        </div>
        
        <div class="system-title">WAREHOUSE INVENTORY SYSTEM</div>
        
        <div class="user-panel">
            <a href="logout.php" class="logout-btn" onclick="return confirm('Confirm Logout?')">LOGOUT ACCOUNT</a>
        </div>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <main class="dashboard-container">
        
        <div class="dashboard-header">
            <h2>Homonhon Project Departments & Divisions</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>! (Role: <strong><?php echo htmlspecialchars($role); ?></strong>) - Select a department below to view records and inventory workflows.</p>
        </div>

        <div class="cards-grid">
            
            <a href="Admin.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">⚙️</div>
                    <div>
                        <h3 class="dept-title">Admin</h3>
                        <p class="dept-desc">Administrative controls, policies, and personnel operations.</p>
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

            <a href="Comrel.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">🤝</div>
                    <div>
                        <h3 class="dept-title">Comrel</h3>
                        <p class="dept-desc">Community relations, local engagement, and outreach projects.</p>
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

            <a href="Envi.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">🌱</div>
                    <div>
                        <h3 class="dept-title">Envi (Environment)</h3>
                        <p class="dept-desc">Environmental monitoring, compliance, and rehabilitation.</p>
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

            <a href="Mine_operation.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">⛏️</div>
                    <div>
                        <h3 class="dept-title">Mine Operation</h3>
                        <p class="dept-desc">Extraction tracking, pit equipment, and mining logs.</p>
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

            <a href="Port_Operation.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">⚓</div>
                    <div>
                        <h3 class="dept-title">Port Operation</h3>
                        <p class="dept-desc">Shipping docks, cargo handling, and logistics tracking.</p>
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

            <a href="Warehouse.php" class="dept-card">
                <div class="dept-card-header">
                    <div class="dept-icon">📦</div>
                    <div>
                        <h3 class="dept-title">Warehouse</h3>
                        <p class="dept-desc">General storage, stocks, items, and inventory processing.</p>
                    </div>
                </div>
            </a>

        </div>
    </main>

</body>
</html>
