<?php
// Force all errors to show
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ADD THIS LINE HERE TO FIX THE WARNINGS
$role = $_SESSION['role'] ?? 'Viewer'; 
// Check if a role is set in the session, otherwise default to 'guest'
$role = $_SESSION['role'] ?? 'guest';

try {
    if (!file_exists('db.php')) {
        throw new Exception("File 'db.php' is missing from the server.");
    }
    require 'db.php'; 
} catch (Throwable $e) {
    // ... rest of your error handling
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
    <title>Warehouse Inventory System - Goldrich</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #2c3e50;
            --warning: #f39c12;
            --success: #27ae60;
            --dark-blue: #112941;
        }

        /* Keep your existing Sidebar and Header CSS */
        .header-section { background: white; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f8f9fa; padding: 15px 25px; width: 100%; box-sizing: border-box; }
        .header-center { flex: 3; display: flex; align-items: center; justify-content: center; gap: 25px; }
        .header-center img { width: 80px; height: auto; }
        .controls-bar { background: var(--dark-blue); padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; color: white; }
        .search-box { display: flex; align-items: center; background: white; padding: 5px 15px; border-radius: 8px; gap: 10px; }
        .search-box input { border: none; outline: none; padding: 5px; width: 250px; }

        /* Enhanced Modal Design */
        .modal {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.7);
            z-index: 9999 !important; justify-content: center; align-items: center;
        }
        .modal-content {
            background: white; border-radius: 12px; width: 500px;
            padding: 0; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .modal-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; }
        .modal-body label { display: block; font-size: 11px; font-weight: bold; margin-bottom: 5px; color: #555; }
        .modal-body input, .modal-body select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .submit-btn { width: 100%; padding: 12px; border: none; border-radius: 6px; color: white; font-weight: bold; cursor: pointer; }
        .close-modal { cursor: pointer; font-size: 20px; color: #666; }

        /* 1. Allow the page to handle the layout correctly */
html, body { 
    height: 100%; 
    margin: 0; 
    padding: 0; 
    overflow: hidden; /* Keeps the sidebar fixed while the main area scrolls */
}

      /* 2. Fix the layout of the main content */
.main-content {
    margin-left: 250px;
    background: #f4f7f6;
    height: 100vh; /* Full viewport height */
    display: flex;
    flex-direction: column;
    transition: margin-left 0.5s;
}
CSS
/* 1. Allow the page to handle the layout correctly */
html, body { 
    height: 100%; 
    margin: 0; 
    padding: 0; 
    overflow: hidden; /* Keeps the sidebar fixed while the main area scrolls */
}

/* 2. Fix the layout of the main content */
.main-content {
    margin-left: 250px;
    background: #f4f7f6;
    height: 100vh; /* Full viewport height */
    display: flex;
    flex-direction: column;
    transition: margin-left 0.5s;
}

/* 3. The magic for Scrollbars */
.table-container {
    flex: 1;                /* Takes up remaining height */
    overflow-x: auto;       /* ENABLE SIDE-TO-SIDE SCROLL */
    overflow-y: auto;       /* ENABLE UP-AND-DOWN SCROLL */
    padding: 20px;
    background: #f4f7f6;
}
/* 4. Ensure the table doesn't shrink, forcing the scrollbar */
#inventoryTable {
    min-width: 1200px;      /* Force side-scroll on smaller screens */
    width: 100%;
    background: white;
    border-collapse: collapse;
}

/* 5. Keep the Header at the top while scrolling down */
#inventoryTable thead th { 
    position: sticky; 
    top: 0; 
    z-index: 10; 
    background: var(--dark-blue); 
    color: white; 
    padding: 12px;
}

/* 6. Fix the 'Action' column to the right side so it's always visible (Optional) */
.action-cell {
    position: sticky;
    right: 0;
    background: white;
    box-shadow: -2px 0 5px rgba(0,0,0,0.05);
}
    </style>
</head>
<body>

<div id="mySidebar" class="sidebar">
    <div class="sidebar-user" style="padding: 20px 15px; background: rgba(0, 0, 0, 0.1); border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div class="user-details" style="display: flex; align-items: center; gap: 15px;">
            <div class="user-avatar" style="min-width: 45px; height: 45px; background: linear-gradient(135deg, #34495e, #2c3e50); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #3498db; color: white; font-size: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                👤
            </div>
            <div class="user-info-text">
                <span class="username" style="font-size:10px; color:#3498db; text-transform: uppercase; letter-spacing: 1px; display: block; line-height: 1.4;">
                    <span style="color: #a695a2; font-size: 11px; font-weight: bold;">USER: </span>
                    <?php echo strtoupper($_SESSION['username'] ?? 'User'); ?>
                </span>
                <span class="user-role" style="font-size:10px; color:#3498db; text-transform: uppercase; letter-spacing: 1px; display: block; margin-top: 2px;">
                    <span style="color: #a695a2; font-size: 11px; font-weight: bold;">TYPE: </span>    
                    <?php echo $_SESSION['role'] ?? 'Staff'; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="sidebar-actions" style="padding: 10px 15px;">
        <a href="logout.php" class="logout-btn-stacked" onclick="return confirm('Confirm Logout?')">🚪 LOGOUT ACCOUNT</a>
    </div>

    <hr style="border: 0.5px solid #3e4f5f; margin: 15px 0;">
    
    <div class="sidebar-section">
        <label style="color:#7f8c8d; font-size:10px; margin-left:15px;">ADMINISTRATION</label>
        <?php if ($role == 'Admin'): ?>
            <a href="register.php" style="display:block; padding:10px 15px; color:#3498db; text-decoration:none; font-weight: bold;">👤 Create Account</a>
            <a href="manage_users.php" style="display:block; padding:10px 15px; color:#bdc3c7; text-decoration:none;">⚙️ Manage Users</a>
        <?php else: ?>
            <p style="color:#555; font-size:9px; margin-left:15px; font-style: italic;">User management restricted.</p>
        <?php endif; ?>
    </div>

    <div class="sidebar-section">
        <label style="color:#7f8c8d; font-size:10px; margin-left:15px;">MAIN ACTIONS</label>
        <?php if ($role == 'Admin' || $role == 'Staff'): ?>
            <a onclick="openAddModal()" style="cursor:pointer; display:block; padding:10px 15px; color:#bdc3c7;">➕ Add Item</a>
        <?php else: ?>
            <a onclick="restricted('Admin or Staff')" style="cursor:pointer; display:block; padding:10px 15px; color:#555;">➕ Add Item 🔒</a>
        <?php endif; ?>

        <?php if ($role == 'Admin' || $role == 'Staff'): ?>
            <a onclick="openRequestModal()" style="cursor:pointer; display:block; padding:10px 15px; color:#bdc3c7;">📝 Request Item</a>
        <?php else: ?>
            <a onclick="restricted('Admin or Staff')" style="cursor:pointer; display:block; padding:10px 15px; color:#555;">📝 Request Item 🔒</a>
        <?php endif; ?>
    </div>

    <div class="sidebar-section">
        <label style="color:#7f8c8d; font-size:10px; margin-left:15px;">FUEL MANAGEMENT</label>
        <a href="diesel_inventory.php" style="display:block; padding:10px 15px; color:#f1c40f; text-decoration:none; font-weight: bold;">⛽ Diesel Inventory</a>
    </div>

    <div class="sidebar-section">
        <label style="color:#7f8c8d; font-size:10px; margin-left:15px;">FINANCIALS AND ANALYTICS</label>
        <a href="costing.php" style="display:block; padding:10px 15px; color:#bdc3c7; text-decoration:none;">📊 Department Costing</a>
    </div>

    <div class="sidebar-section">
    <label style="color:#7f8c8d; font-size:10px; margin-left:15px;">RECORDS & HISTORY</label>
    <a href="view_requests.php">📋 Request History</a>
    <a href="received_summary.php">📥 Received History</a>
    <a href="history.php">📤 Withdrawal History</a>
    
    <?php if ($role == 'Admin'): ?>
        <a href="trash_bin.php" style="color: #e74c3c; font-weight: bold; margin-top: 10px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
            🗑️ Inventory Trash Bin
        </a>
    <?php endif; ?>
</div>

    <div id="costingSummary" style="display:none; background: #1a252f; padding: 10px 15px; font-size: 11px; border-radius: 4px; margin: 0 10px;">
    <?php
    // Updated for PostgreSQL and PDO
    $cost_query = "SELECT \"department\", SUM(qty * price) as total_cost FROM inventory GROUP BY \"department\"";
    $stmt = $conn->query($cost_query);
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<div style="display: flex; justify-content: space-between; color: white;">';
        echo '<span>' . htmlspecialchars($row['department']) . ':</span>';
        echo '<span style="color: #27ae60;">₱' . number_format($row['total_cost'], 2) . '</span>';
        echo '</div>';
    }
    ?>
</div>
</div>

<div id="mainContent" class="main-content" style="margin-left: 250px;">
    <div class="header-section">
        <div class="header-left"><button onclick="toggleNav()" style="padding:10px; background:#34495e; color:white; border-radius:8px; cursor:pointer; border:none;">☰ Menu</button></div>
        <div class="header-center">
            <img src="images/logo.png" alt="Logo">
            <div class="header-text-group">
                <h2 style="color: darkred; margin: 0; font-size: 24px; font-family: Broadway, sans-serif;">GOLDRICH CONSTRUCTION AND TRADING</h2>
                <p style="color: #7f8c8d; font-size: 11px; text-transform: uppercase; margin: 3px 0;">Homonhon Nickel Project • Logistics & Warehouse</p>
                <h2 style="color: #2c3e50; margin: 0; font-size: 20px;">WAREHOUSE INVENTORY SYSTEM</h2>
            </div>
        </div>
        <div class="header-right"></div>
    </div>

    <div class="controls-bar">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div class="search-box">
                🔍 <input type="text" id="live_search" placeholder="Search item...">
                <select id="date_filter"><option value="">All Dates</option></select>
            </div>
            <a href="index.php" style="color: white; text-decoration: none; font-weight: bold; font-size: 12px; opacity: 0.8;">🔄 Reset Filter</a>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <?php if ($role == 'Admin'): ?>
                <a href="import_inventory.php" style="background: var(--success); color: white; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 12px; font-weight: bold;">📥 Import Files</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container" style="padding:20px;">
        <table id="inventoryTable" style="width:100%; background:white; border-collapse: collapse; border-radius:10px; overflow:hidden;">
            <thead>
                <tr>
                    <th style="padding:12px; text-align: left;">Item Name</th>
                    <th>Specification</th>
                    <th>U/M</th>
                    <th>Received</th>
                    <th>Withdrawn</th>
                    <th>Stock</th>
                    <th>Dept.</th>
                    <th>Purpose</th>
                    <th>Price</th>
                    <th>Total Amount</th>
                    <?php if ($role == 'Admin' || $role == 'Staff'): ?>
                      <th style="position: sticky; right: 0; background: #112941; z-index: 10;">Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="table_data">
                <?php include 'fetch_items.php'; ?> 
            </tbody>
        </table>
    </div>
</div>

<div id="addItemModal" class="modal">
    <div class="modal-content" style="max-width: 550px; width: 95%;">
         <div class="modal-header" style="display: block; padding: 15px 20px; background: #f8f9fa;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="images/logo.png" alt="Logo" style="width: 45px; height: auto;">
                    <div>
                        <h2 style="color: darkred; margin: 0; font-size: 15px; font-family: Broadway, sans-serif; line-height: 1;">
                            GOLDRICH CONSTRUCTION AND TRADING
                        </h2>
                        <p style="color: #7f8c8d; font-size: 8px; text-transform: uppercase; letter-spacing: 1px; margin: 2px 0 0 0;">
                            Homonhon Nickel Project • Logistics & Warehouse
                        </p>
                        <h3 style="margin: 5px 0 0 0; font-size: 16px; color: #2c3e50;">📦 Stock Inflow Entry</h3>
                    </div>
                </div>
                <span class="close-modal" onclick="closeAllModals()">&times;</span>
            </div>
        </div>
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            <form action="add_item_process.php" method="POST">
                <div style="display:flex; gap:15px; margin-bottom: 12px;">
                    <div style="flex:1;">
                        <label style="font-size: 11px; font-weight: bold; color: #555;">Received Date</label>
                        <input type="date" name="RDATE" value="<?=date('Y-m-d')?>" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size: 11px; font-weight: bold; color: #555;">RR Number</label>
                        <input type="text" name="rr_number" placeholder="e.g. RR-1024" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                </div>

                
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 11px; font-weight: bold; color: #555;">Item Description</label>
                    <input type="text" name="item_name" placeholder="Name of the item" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-size: 11px; font-weight: bold; color: #555;">Technical Specifications</label>
                    <input type="text" name="Specification" placeholder="Brand, Size, or Model" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;">
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 11px; font-weight: bold; color: #555;">Supplier</label>
                    <input type="text" name="supplier" placeholder="Company Name" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;">
                </div>


                <div style="display:flex; gap:15px; margin-bottom: 12px;">
                    <div style="flex:1;">
                        <label style="font-size: 11px; font-weight: bold; color: #555;">Department</label>
                        <select name="Department" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;">
                            <option value="" disabled selected>Select</option>
                            <option value="Warehouse">Warehouse</option>
                            <option value="Admin">Admin</option>
                            <option value="Mechanical">Mechanical</option>
                            <option value="Safety">Safety</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label style="font-size: 11px; font-weight: bold; color: #555;">Unit Price (₱)</label>
                        <input type="number" step="0.01" name="price" placeholder="0.00" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                </div>

                <div style="display:flex; gap:15px; margin-bottom: 12px; background: #f0f7ff; padding: 10px; border-radius: 8px; border: 1px solid #d0e3ff;">
                    <div style="flex:1;">
                        <label style="font-size: 11px; font-weight: bold; color: #e74c3c;">⚠️ Min Stock (Alert)</label>
                        <input type="number" name="min_stock" min="1" value="5" required style="width:100%; padding:8px; border:2px solid #e74c3c; border-radius:5px;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size: 11px; font-weight: bold; color: #3498db;">📦 Max Stock (Limit)</label>
                        <input type="number" name="max_stock" min="1" value="20" required style="width:100%; padding:8px; border:2px solid #3498db; border-radius:5px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: bold; color: #555;">Purpose / Remarks</label>
                    <input type="text" name="Purpose" placeholder="Usage details" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;">
                </div>
                <button type="submit" name="submit" class="submit-btn" style="background: #27ae60;">✅ Post to Inventory</button>
            </form>
        </div>
    </div>
</div>

<div id="requestItemModal" class="modal">
    <div class="modal-content" style="max-width: 550px; width: 95%;">
        <div class="modal-header" style="display: block; padding: 15px 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="images/logo.png" alt="Logo" style="width: 45px; height: auto;">
                    <div>
                        <h2 style="color: darkred; margin: 0; font-size: 15px; font-family: Broadway, sans-serif; line-height: 1;">
                            GOLDRICH CONSTRUCTION AND TRADING
                        </h2>
                        <p style="color: #7f8c8d; font-size: 8px; text-transform: uppercase; letter-spacing: 1px; margin: 2px 0 0 0;">
                            Homonhon Nickel Project • Logistics & Warehouse
                        </p>
                    </div>
                </div>
                <span class="close-modal" onclick="closeAllModals()">&times;</span>
            </div>
            <div style="margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px;">
                <h3 style="margin: 0; font-size: 16px; color: var(--primary);">📝 Item Request Form (RF)</h3>
            </div>
        </div>
         
        <div class="modal-body modal-body-scroll" style="max-height: 70vh; overflow-y: auto;">
            <form action="save_request.php" method="POST">
                
                <div style="display:flex; gap:15px; margin-bottom: 16px;">
                    <div style="flex:1;">
                        <label>RF Number</label>
                        <input type="text" name="RF" placeholder="e.g. RF-1001" required>
                    </div>
                    <div style="flex:1;">
                        <label>Priority Type</label>
                        <select name="remarks" required>
                            <option value="Urgent">🔴 Urgent</option>
                            <option value="Stock">🔵 Stock</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Item Description</label>
                    <input type="text" name="item_name" placeholder="Name of the item requested" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Technical Specifications</label>
                    <input type="text" name="specification" placeholder="Size, Model, or Brand Preference">
                </div>

                <div style="display:flex; gap:15px; margin-bottom: 16px;">
                    <div style="flex:1;">
                        <label>Quantity Required</label>
                        <input type="number" name="qty" placeholder="0" required>
                    </div>
                    <div style="flex:1;">
                        <label>Requesting Department</label>
                        <select name="department" required>
                            <option value="" disabled selected>Select Department</option>
                            <option value="Warehouse">Warehouse</option>
                            <option value="Admin">Admin</option>
                            <option value="Mechanical">Mechanical</option>
                            <option value="Safety">Safety</option>
                            <option value="Engineering">Engineering</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Purpose / Remarks</label>
                    <input type="text" name="purpose" placeholder="Reason for request" required>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Requested By</label>
                    <input type="text" name="requested_by" placeholder="Name" style="background: #f8faf9; color: #7f8c8d; font-weight: bold; border: 1px dashed #ddd; width: 100%; padding: 10px; border-radius: 6px; box-sizing: border-box;" required>
                </div>

                <button type="submit" name="submit_request" class="submit-btn" style="background: var(--warning); color:white; width:100%; font-size: 16px;">
                    🚀 Save Request
                </button>
            </form>
        </div>
    </div>
</div>

<div id="withdrawModal" class="modal">
    <div class="modal-content" style="width: 400px;">
        <div class="modal-header">
            <h3 style="margin: 0; color: #e67e22;">📤 Withdraw Stock</h3>
            <span class="close-modal" onclick="closeAllModals()">&times;</span>
        </div>
        <form action="withdraw_process.php" method="POST" class="modal-body">
            <input type="hidden" name="item_id" id="withdraw_item_id">
            <p id="withdraw_item_display" style="font-weight:bold; color:var(--dark-blue);"></p>
            <p style="font-size: 12px;">Available Stock: <span id="withdraw_stock_display" style="color:red; font-weight:bold;"></span></p>
            
            <label>Quantity to Withdraw</label>
            <input type="number" name="withdraw_qty" required min="1">
            
            <label>Received By</label>
            <input type="text" name="withdrawn_by" required>

            <label>Purpose / Remarks</label>
            <select name="purpose" required>
                <option value="">-- Select Purpose --</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Operation">Operation</option>
                <option value="Repair">Repair</option>
                <option value="Replacement">Replacement</option>
                <option value="Others">Others</option>
            </select>
            
            <button type="submit" class="submit-btn" style="background: #e67e22; margin-top: 10px;">Confirm Withdrawal</button>
        </form>
    </div>
</div>

<div id="editItemModal" class="modal">
    <div class="modal-content" style="max-width: 500px; width: 95%;">
        <div class="modal-header">
            <h3 style="margin: 0; color: #2c3e50;">✏️ Edit Item Details</h3>
            <span class="close-modal" onclick="closeAllModals()">&times;</span>
        </div>
        <div class="modal-body">
            <form action="update_item.php" method="POST">
                <input type="hidden" name="item_id" id="edit_item_id">
                <label>Item Description</label>
                <input type="text" name="item_name" id="edit_item_name" required>
                <label>Technical Specifications</label>
                <input type="text" name="specification" id="edit_specification" required>
                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label style="color: #e74c3c;">Min Stock</label>
                        <input type="number" name="min_stock" id="edit_min_stock" required>
                    </div>
                    <div style="flex:1;">
                        <label style="color: #3498db;">Max Stock</label>
                        <input type="number" name="max_stock" id="edit_max_stock" required>
                    </div>
                </div>
                <button type="submit" name="update_item" class="submit-btn" style="background: #2980b9; margin-top: 10px;">💾 Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleNav() {
        var s = document.getElementById("mySidebar");
        var m = document.getElementById("mainContent");
        if (s.style.width === "250px") {
            s.style.width = "0"; m.style.marginLeft = "0";
        } else {
            s.style.width = "250px"; m.style.marginLeft = "250px";
        }
    }

    function closeAllModals() {
        document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
    }

    function openAddModal() { closeAllModals(); document.getElementById('addItemModal').style.display = 'flex'; }
    function openRequestModal() { closeAllModals(); document.getElementById('requestItemModal').style.display = 'flex'; }

    // FIXED WITHDRAW MODAL TRIGGER
    function openWithdrawModal(id, name, stock) {
        closeAllModals();
        document.getElementById('withdrawModal').style.display = 'flex';
        document.getElementById('withdraw_item_id').value = id;
        document.getElementById('withdraw_item_display').innerText = name;
        document.getElementById('withdraw_stock_display').innerText = stock;
    }

    // FIXED EDIT MODAL TRIGGER
    function openEditModal(id, name, spec, min, max) {
        closeAllModals();
        document.getElementById('editItemModal').style.display = 'flex';
        document.getElementById('edit_item_id').value = id;
        document.getElementById('edit_item_name').value = name;
        document.getElementById('edit_specification').value = spec;
        document.getElementById('edit_min_stock').value = min;
        document.getElementById('edit_max_stock').value = max;
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) closeAllModals();
    }

    // Live Search
    document.getElementById('live_search').addEventListener('input', function() {
        fetch(`fetch_items.php?search=${this.value}`)
            .then(res => res.text())
            .then(data => { document.getElementById('table_data').innerHTML = data; });
    });

    function restricted(role) { alert("⛔ Restricted to " + role); }
    function confirmDelete(id, name) {
    if (confirm("❗ Move '" + name + "' to Trash Bin?\n\nYou can restore it later if needed.")) {
        // Change this line to include the 'type'
        window.location.href = "delete_log.php?type=inventory&id=" + id;
    }
}
</script>
</body>
</html>
