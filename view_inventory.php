<?php
session_start();
if (!isset($_SESSION['business_id'])) {
    header("Location: index.html");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'billing_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch inventory for this business
$business_id = $_SESSION['business_id'];
$inventory = [];
$stmt = $conn->prepare("SELECT product_name, product_description, actual_price, offered_price FROM product WHERE business_id = ? ORDER BY product_name");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $inventory[] = $row;
}
$stmt->close();

// Calculate total inventory value
$total_value = 0;
foreach ($inventory as $item) {
    $total_value += $item['offered_price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory View</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2565AE;
            --primary-light: rgba(37, 101, 174, 0.1);
            --primary-dark: #1A4B8C;
            --secondary: #4A90E2;
            --danger: #FF4757;
            --success: #2ED573;
            --warning: #FFA502;
            --light: #F8F9FF;
            --dark: #2F3542;
            --gray: #747D8C;
            --light-gray: #F1F2F6;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 18px 25px;
            font-size: 1.4rem;
            font-weight: 600;
            text-align: center;
            position: relative;
            box-shadow: var(--box-shadow);
        }
        
        .action-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.15);
            color: white;
            border: none;
            padding: 10px 18px;
            font-size: 0.9rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-50%) scale(1.03);
        }
        
        .print-btn {
            right: 20px;
        }
        
        .back-btn {
            left: 20px;
        }
        
        .inventory-container {
            padding: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stat-item {
            flex: 1;
            min-width: 200px;
            background: var(--light-gray);
            border-radius: var(--border-radius);
            padding: 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin: 5px 0;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .inventory-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .inventory-table th {
            background-color: var(--primary);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }
        
        .inventory-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-gray);
            transition: var(--transition);
        }
        
        .inventory-table tr:last-child td {
            border-bottom: none;
        }
        
        .inventory-table tr:hover td {
            background-color: var(--primary-light);
        }
        
        .price-cell {
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }
        
        .total-row {
            font-weight: bold;
            background-color: var(--light-gray);
        }
        
        .total-row td {
            padding: 15px;
        }
        
        .total-value {
            font-size: 1.1rem;
            color: var(--primary-dark);
        }
        
        @media (max-width: 768px) {
            .header {
                font-size: 1.2rem;
                padding: 15px 60px;
            }
            
            .action-btn {
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            
            .inventory-container {
                padding: 15px;
            }
            
            .inventory-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media print {
            .header, .action-btn {
                display: none;
            }
            
            body {
                background: white;
                font-size: 11pt;
                padding: 0;
            }
            
            .inventory-container {
                padding: 0;
                max-width: 100%;
            }
            
            .inventory-table {
                box-shadow: none;
                border-radius: 0;
            }
            
            .stats-card {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="business_dashboard.php">
            <button class="action-btn back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </a>
        Inventory List - <?php echo htmlspecialchars($_SESSION['business_name']); ?>
        <button class="action-btn print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>

    <div class="inventory-container">
        <div class="stats-card">
            <div class="stat-item">
                <div class="stat-label">Total Products</div>
                <div class="stat-value"><?php echo count($inventory); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Inventory Value</div>
                <div class="stat-value">₹<?php echo number_format($total_value, 2); ?></div>
            </div>
        </div>
        
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Description</th>
                    <th>Actual Price</th>
                    <th>Offered Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inventory as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['product_description']); ?></td>
                        <td class="price-cell">₹<?php echo number_format($item['actual_price'], 2); ?></td>
                        <td class="price-cell">₹<?php echo number_format($item['offered_price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3">Total Inventory Value</td>
                    <td class="total-value">₹<?php echo number_format($total_value, 2); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php $conn->close(); ?>