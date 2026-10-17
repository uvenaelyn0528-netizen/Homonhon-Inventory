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
            overflow: hidden; /* Round corners for inner content */
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
        
        /* Navigation Buttons (Exact colors from image) */
        .btn-grid { 
            display: flex;
            gap: 15px;
            margin-bottom: 30px; 
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
            background-color: #3b82f6; /* Image blue */
            color: #ffffff; 
        }
        
        .btn-ticket { 
            background-color: #10b981; /* Image teal/green */
            color: #ffffff; 
        }
        
        .btn-report { 
            background-color: #f97316; /* Image orange */
            color: #ffffff; 
        }

        .btn-materials { 
            background-color: #8b5cf6; /* Image purple */
            color: #ffffff; 
        }

        /* Mining Image Feature Banner (Image 10.png) */
        .banner-container {
            width: calc(100% + 60px); /* Fill the white card padding */
            margin-left: -30px; /* Offset the container padding */
            margin-bottom: -30px; /* Offset the container padding */
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

    <!-- Navigation Buttons (Matches Image Layout) -->
    <div class="btn-grid">
        <a href="eur.php" class="btn btn-eur">EUR</a>
        <a href="trip_ticket.php" class="btn btn-ticket">Trip Ticket</a>
        <a href="reportorial.php" class="btn btn-report">Reportorial</a>
        <a href="materials_supplies.php" class="btn btn-materials">Materials and Supplies</a>
    </div>

    <!-- Featured Mining Image Banner (Exact image from above) -->
    <div class="banner-container">
        <img src="https://i.imgur.com/vH3jH2A.png" alt="Mining Equipment, Mapping, and Inventory Control" class="banner-img">
    </div>
</div>

</body>
</html>
