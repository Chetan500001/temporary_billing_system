<?php
session_start();

// Database Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "billing_system";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if bill ID is provided
if (!isset($_GET['bill_id']) || !is_numeric($_GET['bill_id'])) {
    die("Invalid bill ID");
}

// Verify user owns this bill
$bill_id = intval($_GET['bill_id']);
$customer_id = isset($_SESSION['customer_id']) ? intval($_SESSION['customer_id']) : 0;

$sql = "SELECT * FROM saved_bills WHERE id = ? AND customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bill_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Bill not found or you don't have permission to view it");
}

$bill = $result->fetch_assoc();
$items = json_decode($bill['items'], true);

// Validate items array structure
if (!is_array($items)) {
    die("Invalid items data format");
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporary Bill</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .receipt-container { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; position: relative; }
        .header { text-align: center; margin-bottom: 20px; }
        .business-name { font-size: 24px; font-weight: bold; }
        .bill-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background-color: #f2f2f2; }
        .total { text-align: right; font-weight: bold; font-size: 18px; }
        .footer { margin-top: 30px; text-align: center; font-style: italic; color: #666; }
        .disclaimer {
            text-align: center;
            color: red;
            font-weight: bold;
            margin: 15px 0;
            padding: 10px;
            border: 1px dashed red;
            background-color: #fff8f8;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .button-container button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .button-container button:hover {
            background-color: #45a049;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .disclaimer { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Disclaimer Notice -->
        <div class="disclaimer">
            THIS IS NOT AN ORIGINAL BILL - FOR REFERENCE PURPOSE ONLY
        </div>
        
        <div class="header">
            <div class="business-name"><?= htmlspecialchars($bill['business_name']) ?></div>
            <div>Receipt</div>
        </div>
        
        <div class="bill-info">
            <div>
                <div><strong>Bill Name:</strong> <?= htmlspecialchars($bill['bill_name']) ?></div>
                <div><strong>Date:</strong> <?= date('F j, Y, g:i a', strtotime($bill['date'])) ?></div>
            </div>
            <div>
                <div><strong>Bill #:</strong> <?= $bill['id'] ?></div>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): 
                    // Set default values if keys don't exist
                    $product = isset($item['product_name']) ? $item['product_name'] : 'Unknown Product';
                    $quantity = isset($item['quantity']) ? $item['quantity'] : 0;
                    $price = isset($item['offered_price']) ? floatval($item['offered_price']) : 0.00;
                    $item_total = $quantity * $price;
                ?>
                <tr>
                    <td><?= htmlspecialchars($product) ?></td>
                    <td><?= htmlspecialchars($quantity) ?></td>
                    <td>₹<?= number_format($price, 2) ?></td>
                    <td>₹<?= number_format($item_total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="total">
            Total: ₹<?= number_format($bill['total'], 2) ?>
        </div>
        
        <div class="footer">
            Thank you for your business!
        </div>
        
        <div class="button-container no-print">
            <button onclick="window.close()">Close</button>
            <button onclick="window.print()">Print Receipt</button>
        </div>
    </div>
</body>
</html>
