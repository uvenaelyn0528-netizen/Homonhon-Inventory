<?php
/**
 * Engineering Department Dashboard
 */
session_start();
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineering Department Dashboard</title>
    <style>
        * { 
            box-sizing: border-box; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        body { 
            background-color: #f4f6f9; 
            margin: 0; 
            padding: 40px 20px; 
            color: #333; 
        }
        .container { 
            max-width: 950px; 
            margin: 0 auto; 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 16px rgba(0,0,0,0.08); 
            overflow: hidden; 
        }

        /* Enhanced Header Design */
        .header-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            padding: 25px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: inset 0 2px 4px rgba(255,255,255,0.1);
        }
        .header-title-area h1 {
            margin: 0 0 5px 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .header-title-area p {
            margin: 0;
            color: #94a3b8;
            font-size: 14px;
        }
        .user-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-badge span {
            color: #38bdf8;
            font-weight: 600;
        }
        
        /* Navigation Buttons Grid */
        .btn-grid { 
            display: flex;
            gap: 15px;
            margin-bottom: 30px; 
            flex-wrap: wrap;
        }
        .btn { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 12px 22px; 
            font-size: 14px; 
            font-weight: 600; 
            text-decoration: none; 
            border-radius: 8px; 
            border: none; 
            cursor: pointer; 
            transition: all 0.2s ease; 
            text-align: center; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn:active { 
            transform: scale(0.98); 
        }
        
        .btn-eur { background-color: #3b82f6; color: #ffffff; }
        .btn-ticket { background-color: #10b981; color: #ffffff; }
        .btn-report { background-color: #f97316; color: #ffffff; }
        .btn-materials { background-color: #8b5cf6; color: #ffffff; }
        .btn-budget { background-color: #4f46e5; color: #ffffff; }

        /* Banner Container */
        .banner-container {
            width: calc(100% + 60px); 
            margin-left: -30px; 
            margin-bottom: -30px; 
            position: relative;
        }
        .banner-img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            max-height: 400px;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Styled Header Section -->
    <div class="header-banner">
        <div class="header-title-area">
            <h1>Engineering Department</h1>
            <p>Mining Operations & Equipment Management Portal</p>
        </div>
        <div class="user-badge">
            👤 <?= htmlspecialchars($username); ?> &bull; <span><?= htmlspecialchars($role); ?></span>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="btn-grid">
        <a href="eur.php" class="btn btn-eur">EUR</a>
        <a href="trip_ticket.php" class="btn btn-ticket">Trip Ticket</a>
        <a href="reportorial.php" class="btn btn-report">Reportorial</a>
        <a href="materials_supplies.php" class="btn btn-materials">Materials and Supplies</a>
        <a href="projected_budget_2027.php" class="btn btn-budget">Projected Budget for 2027</a>
    </div>

    <!-- Local Engineering Mining Banner -->
    <div class="banner-container">
        <img src="images/Engineering.jpg" alt="Mining Equipment and Operations" class="banner-img">
    </div>
</div>

</body>
</html>
