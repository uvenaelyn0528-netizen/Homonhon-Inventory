<?php
/**
 * Engineering Department Dashboard
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineering Department</title>
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
            max-width: 900px; 
            margin: 0 auto; 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
            overflow: hidden; 
        }
        .header-section { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding-bottom: 20px; 
            margin-bottom: 20px; 
            flex-wrap: wrap; 
            gap: 15px; 
        }
        h1 { 
            margin: 0; 
            color: #1e293b; 
            font-size: 26px; 
            font-weight: 700;
        }
        
        /* Navigation Buttons */
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
            padding: 12px 24px; 
            font-size: 15px; 
            font-weight: 600; 
            text-decoration: none; 
            border-radius: 8px; 
            border: none; 
            cursor: pointer; 
            transition: transform 0.1s ease; 
            text-align: center; 
        }
        .btn:active { 
            transform: scale(0.98); 
        }
        
        .btn-eur { 
            background-color: #3b82f6; 
            color: #ffffff; 
        }
        
        .btn-ticket { 
            background-color: #10b981; 
            color: #ffffff; 
        }
        
        .btn-report { 
            background-color: #f97316; 
            color: #ffffff; 
        }

        .btn-materials { 
            background-color: #8b5cf6; 
            color: #ffffff; 
        }

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
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <h1>Engineering Department</h1>
    </div>

    <!-- Navigation Buttons -->
    <div class="btn-grid">
        <a href="eur.php" class="btn btn-eur">EUR</a>
        <a href="trip_ticket.php" class="btn btn-ticket">Trip Ticket</a>
        <a href="reportorial.php" class="btn btn-report">Reportorial</a>
        <a href="materials_supplies.php" class="btn btn-materials">Materials and Supplies</a>
    </div>

    <!-- Mining Engineering Feature Banner -->
    <div class="banner-container">
        <img src="https://images.unsplash.com/photo-1578328819058-b69f3a3b0f6b?auto=format&fit=crop&w=1200&q=80" alt="Mining Equipment and Operations" class="banner-img">
    </div>
</div>

</body>
</html>
