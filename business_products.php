<?php
session_start();
if (!isset($_GET['business_id'])) {
    header("Location: product_search.php");
    exit();
}

$business_id = $_GET['business_id'];

// Database connection
$conn = new mysqli('localhost', 'root', '', 'billing_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get business info
$business_stmt = $conn->prepare("SELECT business_name FROM businesses WHERE id = ?");
$business_stmt->bind_param("i", $business_id);
$business_stmt->execute();
$business_result = $business_stmt->get_result();
$business = $business_result->fetch_assoc();
$business_stmt->close();

// Get products
$products = [];
$product_stmt = $conn->prepare("SELECT * FROM product WHERE business_id = ?");
$product_stmt->bind_param("i", $business_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
while ($row = $product_result->fetch_assoc()) {
    $products[] = $row;
}
$product_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($business['business_name']); ?> - Products</title>
    <style>
        /* Same styles as product_search.php */
        .back-btn {
            padding: 8px 15px;
            background: #4F959D;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div><?php echo $_SESSION['customer_name']; ?> - <?php echo $business['business_name']; ?></div>
        <a href="product_search.php" class="back-btn">Back to Search</a>
    </div>

    <div class="product-list">
        <h2><?php echo htmlspecialchars($business['business_name']); ?> - All Products</h2>
        
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                    <div class="product-description" style="color: #666; margin-bottom: 10px;">
                        <?php echo htmlspecialchars($product['product_description']); ?>
                    </div>
                    <div class="product-price">
                        <span class="original-price">₹<?php echo number_format($product['actual_price'], 2); ?></span>
                        ₹<?php echo number_format($product['offered_price'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">No products found for this business.</div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>