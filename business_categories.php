<?php
session_start();
if (!isset($_GET['business_id'])) {
    header("Location: find_products.php");
    exit();
}
$business_id = $_GET['business_id'];

// DB
$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}

// Get business name
$biz_stmt = $conn->prepare("SELECT business_name FROM businesses WHERE id = ?");
$biz_stmt->bind_param("i", $business_id);
$biz_stmt->execute();
$biz_result = $biz_stmt->get_result()->fetch_assoc();
$biz_name = $biz_result['business_name'] ?? 'Business';

// Get categories
$cat_stmt = $conn->prepare("SELECT DISTINCT category FROM product WHERE business_id = ?");
$cat_stmt->bind_param("i", $business_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $biz_name; ?> - Categories</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #F5F5F5;
            padding: 20px;
        }
        .header {
            background: #205781;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 20px;
        }
        .category-card {
            background: white;
            margin: 15px auto;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .category-card a {
            text-decoration: none;
            color: #4F959D;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header"><?php echo $biz_name; ?> - Categories</div>

    <?php if ($cat_result->num_rows > 0): ?>
        <?php while ($row = $cat_result->fetch_assoc()): ?>
            <div class="category-card">
                <a href="category_products.php?business_id=<?php echo $business_id; ?>&category=<?php echo urlencode($row['category']); ?>">
                    <?php echo htmlspecialchars($row['category']); ?>
                </a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center;">No categories available.</p>
    <?php endif; ?>
</body>
</html>
