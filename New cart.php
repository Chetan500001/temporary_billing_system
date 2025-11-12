<?php
session_start();
if (!isset($_SESSION['customer_name']) || !isset($_GET['business_id'])) {
    header("Location: find_businesses.php");
    exit();
}

$business_id = $_GET['business_id'];

$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch business name
$bizStmt = $conn->prepare("SELECT business_name FROM businesses WHERE id = ?");
$bizStmt->bind_param("i", $business_id);
$bizStmt->execute();
$bizResult = $bizStmt->get_result();
$business = $bizResult->fetch_assoc();
$bizStmt->close();

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_quantities'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            if ($quantity < 1) {
                // Remove item if quantity is 0
                $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ? AND business_id = ? AND product_id = ?");
                $stmt->bind_param("iii", $_SESSION['customer_id'], $business_id, $product_id);
            } else {
                // Update quantity
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE customer_id = ? AND business_id = ? AND product_id = ?");
                $stmt->bind_param("iiii", $quantity, $_SESSION['customer_id'], $business_id, $product_id);
            }
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Fetch cart items with product details
$cartStmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.product_name, p.product_description, p.offered_price 
    FROM cart c 
    JOIN product p ON c.product_id = p.id 
    WHERE c.customer_id = ? AND c.business_id = ?
");
$cartStmt->bind_param("ii", $_SESSION['customer_id'], $business_id);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();

$total = 0;
$cartItems = [];
while ($row = $cartResult->fetch_assoc()) {
    $row['subtotal'] = $row['offered_price'] * $row['quantity'];
    $total += $row['subtotal'];
    $cartItems[] = $row;
}

$cartStmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cart - <?php echo htmlspecialchars($business['business_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; padding: 20px; }
        .header {
            background: linear-gradient(90deg, #205781, #4F959D);
            color: white;
            padding: 15px;
            font-size: 22px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 25px;
            position: relative;
        }
        .back-btn {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: #fff; color: #205781;
            padding: 10px 15px; border-radius: 20px;
            text-decoration: none; font-weight: bold;
            left: 20px;
        }
        .cart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .total-section {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-continue {
            background: #6c757d;
            color: white;
        }
        .btn-update {
            background: #17a2b8;
            color: white;
        }
        .btn-checkout {
            background: #28a745;
            color: white;
        }
        .btn-print {
            background: #007bff;
            color: white;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: white;
                padding: 0;
            }
            .cart-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="view_business_products.php?business_id=<?php echo $business_id; ?>" class="back-btn">⬅ Back</a>
        Your Cart - <?php echo htmlspecialchars($business['business_name']); ?>
    </div>

    <div class="cart-container">
        <form method="POST" action="cart.php?business_id=<?php echo $business_id; ?>">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cartItems)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Your cart is empty</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($item['product_description']); ?></small>
                                </td>
                                <td>₹<?php echo number_format($item['offered_price'], 2); ?></td>
                                <td>
                                    <input type="number" name="quantities[<?php echo $item['product_id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input">
                                </td>
                                <td>₹<?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="total-section">
                Total: ₹<?php echo number_format($total, 2); ?>
            </div>

            <div class="action-buttons no-print">
                <a href="view_business_products.php?business_id=<?php echo $business_id; ?>" class="btn btn-continue">Continue Shopping</a>
                <button type="submit" name="update_quantities" class="btn btn-update">Update Cart</button>
                <button type="button" onclick="window.print()" class="btn btn-print">Print Bill</button>
                <a href="checkout.php?business_id=<?php echo $business_id; ?>" class="btn btn-checkout">Proceed to Checkout</a>
            </div>
        </form>
    </div>
</body>
</html>