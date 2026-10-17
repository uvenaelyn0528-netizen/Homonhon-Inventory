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
            border-radius: 10px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
        }
        .header-section { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 2px solid #e2e8f0; 
            padding-bottom: 20px; 
            margin-bottom: 30px; 
            flex-wrap: wrap; 
            gap: 15px; 
        }
        h1 { 
            margin: 0; 
            color: #1e293b; 
            font-size: 26px; 
        }
        
        /* Action Buttons Grid */
        .btn-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            gap: 15px; 
            margin-top: 10px; 
        }
        .btn { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            padding: 14px 20px; 
            font-size: 15px; 
            font-weight: 600; 
            text-decoration: none; 
            border-radius: 8px; 
            border: none; 
            cursor: pointer; 
            transition: all 0.2s ease-in-out; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
            text-align: center; 
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
        }
        .btn:active { 
            transform: translateY(0); 
        }
        
        .btn-eur { 
            background-color: #2563eb; 
            color: #ffffff; 
        }
        .btn-eur:hover { 
            background-color: #1d4ed8; 
        }
        
        .btn-ticket { 
            background-color: #059669; 
            color: #ffffff; 
        }
        .btn-ticket:hover { 
            background-color: #047857; 
        }
        
        .btn-report { 
            background-color: #d97706; 
            color: #ffffff; 
        }
        .btn-report:hover { 
            background-color: #b45309; 
        }

        .btn-materials { 
            background-color: #4f46e5; 
            color: #ffffff; 
        }
        .btn-materials:hover { 
            background-color: #4338ca; 
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <h1>Engineering Department</h1>
    </div>

    <!-- Action Buttons -->
    <div class="btn-grid">
        <a href="eur.php" class="btn btn-eur">EUR</a>
        <a href="trip_ticket.php" class="btn btn-ticket">Trip Ticket</a>
        <a href="reportorial.php" class="btn btn-report">Reportorial</a>
        <a href="materials_supplies.php" class="btn btn-materials">Materials and Supplies</a>
    </div>
</div>

</body>
</html>
