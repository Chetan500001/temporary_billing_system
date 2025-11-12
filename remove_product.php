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

// Initialize filters with trimmed search term
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category = $_GET['category'] ?? '';
$selected_brand = $_GET['brand'] ?? '';
$products = [];

// Fetch categories
$categories = [];
$category_stmt = $conn->prepare("SELECT DISTINCT category FROM product WHERE business_id = ? AND category IS NOT NULL AND category != ''");
$category_stmt->bind_param("i", $_SESSION['business_id']);
$category_stmt->execute();
$result = $category_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category'];
}
$category_stmt->close();

// Fetch brands
$brands = [];
$brand_stmt = $conn->prepare("SELECT DISTINCT brand FROM product WHERE business_id = ? AND brand IS NOT NULL AND brand != ''");
$brand_stmt->bind_param("i", $_SESSION['business_id']);
$brand_stmt->execute();
$result = $brand_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $brands[] = $row['brand'];
}
$brand_stmt->close();

// Fetch filtered products
$sql = "SELECT id, product_name, product_description, actual_price, offered_price, category, brand 
        FROM product WHERE business_id = ?";
$param_types = "i";
$params = [$_SESSION['business_id']];

if (!empty($selected_category)) {
    $sql .= " AND category = ?";
    $param_types .= "s";
    $params[] = $selected_category;
}

if (!empty($selected_brand)) {
    $sql .= " AND brand = ?";
    $param_types .= "s";
    $params[] = $selected_brand;
}

if (!empty($search_term)) {
    $sql .= " AND (product_name LIKE ? OR product_description LIKE ? OR brand LIKE ?)";
    $param_types .= "sss";
    $like_term = "%$search_term%";
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
}

$sql .= " ORDER BY product_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Remove Product</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #205781;
            --primary-light: rgba(32, 87, 129, 0.1);
            --secondary: #4F959D;
            --danger: #e74c3c;
            --danger-light: rgba(231, 76, 60, 0.1);
            --success: #2ecc71;
            --warning: #f39c12;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --light-gray: #ecf0f1;
            --border-radius: 10px;
            --box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: var(--primary);
            color: white;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .sidebar img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid rgba(255,255,255,0.2);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .sidebar h3 {
            font-size: 1.3rem;
            font-weight: 500;
            margin-bottom: 40px;
            text-align: center;
            color: white;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .header {
            background: white;
            color: var(--primary);
            padding: 20px 30px;
            border-radius: var(--border-radius);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--box-shadow);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
            gap: 8px;
        }
        
        .btn i {
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(32, 87, 129, 0.2);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary-light);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .filter-bar input, 
        .filter-bar select {
            padding: 12px 15px;
            border-radius: var(--border-radius);
            border: 1px solid var(--light-gray);
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: white;
        }
        
        .filter-bar input {
            flex: 1;
            min-width: 250px;
        }
        
        .filter-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(32, 87, 129, 0.2);
        }
        
        .filter-bar select {
            min-width: 200px;
        }
        
        .product-list {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .product-item {
            padding: 20px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }
        
        .product-item:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-item h3 {
            margin: 0 0 8px 0;
            color: var(--dark);
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .product-item p {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 12px;
        }
        
        .product-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .product-price {
            color: var(--dark);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .offer-price {
            color: var(--success);
            font-weight: 600;
        }
        
        .product-category, .product-brand {
            background: var(--light-gray);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--dark);
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .remove-btn {
            background: var(--danger);
            color: white;
            padding: 10px 18px;
            font-size: 0.9rem;
            border-radius: var(--border-radius);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remove-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.2);
        }
        
        .confirmation-dialog {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .confirmation-box {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            text-align: center;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .confirmation-box h3 {
            color: var(--danger);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .confirmation-box p {
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .confirmation-buttons {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .cancel-btn, .confirm-btn {
            padding: 12px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cancel-btn {
            background: var(--light-gray);
            color: var(--dark);
        }
        
        .cancel-btn:hover {
            background: #d5dbdb;
        }
        
        .confirm-btn {
            background: var(--danger);
            color: white;
        }
        
        .confirm-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.2);
        }
        
        .no-products {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }
        
        .no-products p {
            margin-bottom: 20px;
            font-size: 1rem;
        }
        
        .spinner {
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 2px solid white;
            width: 16px;
            height: 16px;
            display: inline-block;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 15px;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                height: auto;
            }
            
            .sidebar img {
                width: 50px;
                height: 50px;
                margin-bottom: 0;
                margin-right: 15px;
            }
            
            .sidebar h3 {
                margin-bottom: 0;
                font-size: 1.1rem;
            }
            
            .main-content {
                padding: 20px;
                margin-left: 0;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-bar input,
            .filter-bar select {
                width: 100%;
                min-width: auto;
            }
            
            .product-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .action-buttons {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="usericon.png" alt="User Icon">
        <h3><?php echo htmlspecialchars($_SESSION['business_name']); ?></h3>
        <a href="logout.php" class="btn btn-danger" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <span><i class="fas fa-trash-alt"></i> Remove Products</span>
            <div>
                <a href="business_dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <input type="text" name="search" placeholder="🔍 Search products..." value="<?= htmlspecialchars($search_term) ?>">
            <select name="category" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $cat == $selected_category ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="brand" onchange="this.form.submit()">
                <option value="">All Brands</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= htmlspecialchars($brand) ?>" <?= $brand == $selected_brand ? 'selected' : '' ?>>
                        <?= htmlspecialchars($brand) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($search_term || $selected_category || $selected_brand): ?>
                <a href="remove_product.php" class="btn btn-outline"><i class="fas fa-sync-alt"></i> Reset</a>
            <?php endif; ?>
        </form>

        <div class="product-list" id="productContainer">
            <?php if (empty($products)): ?>
                <div class="no-products">
                    <p>No products found matching your criteria.</p>
                    <p>Try adjusting your search filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-item" id="product-<?= $product['id'] ?>">
                        <div class="product-info">
                            <h3><?= htmlspecialchars($product['product_name']) ?></h3>
                            <p><?= htmlspecialchars($product['product_description']) ?></p>
                            <div class="product-meta">
                                <span class="product-price">
                                    ₹<?= number_format($product['actual_price'], 2) ?> 
                                    <span class="offer-price">(Offer ₹<?= number_format($product['offered_price'], 2) ?>)</span>
                                </span>
                                <?php if (!empty($product['category'])): ?>
                                    <span class="product-category"><?= htmlspecialchars($product['category']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($product['brand'])): ?>
                                    <span class="product-brand"><?= htmlspecialchars($product['brand']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button class="remove-btn" onclick="showConfirmation(<?= $product['id'] ?>, '<?= addslashes(htmlspecialchars($product['product_name'])) ?>')">
                            <i class="fas fa-trash-alt"></i> Remove
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="confirmationDialog" class="confirmation-dialog">
        <div class="confirmation-box">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Removal</h3>
            <p>Are you sure you want to remove <strong id="productName"></strong>?</p>
            <p>This action cannot be undone.</p>
            <div class="confirmation-buttons">
                <button class="cancel-btn" onclick="hideConfirmation()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form id="removeForm">
                    <input type="hidden" name="product_id" id="productId">
                    <button type="submit" class="confirm-btn">
                        <i class="fas fa-trash-alt"></i> Confirm
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showConfirmation(id, name) {
            document.getElementById("productId").value = id;
            document.getElementById("productName").innerText = name;
            document.getElementById("confirmationDialog").style.display = "flex";
            document.body.style.overflow = "hidden";
        }

        function hideConfirmation() {
            document.getElementById("confirmationDialog").style.display = "none";
            document.body.style.overflow = "auto";
        }

        document.getElementById('removeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const confirmBtn = form.querySelector('.confirm-btn');
            
            // Show loading state
            confirmBtn.innerHTML = '<span class="spinner"></span> Deleting...';
            confirmBtn.disabled = true;
            
            try {
                const response = await fetch('remove_product_process.php', {
                    method: 'POST',
                    body: new FormData(form)
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to delete product');
                }
                
                if (data.success) {
                    // Success - remove product from UI with animation
                    const productElement = document.getElementById('product-' + form.product_id.value);
                    if (productElement) {
                        productElement.style.transition = 'all 0.3s';
                        productElement.style.opacity = '0';
                        productElement.style.transform = 'translateX(-20px)';
                        setTimeout(() => productElement.remove(), 300);
                    }
                    
                    // Show success message with animation
                    const successMsg = document.createElement('div');
                    successMsg.innerHTML = `
                        <div style="position: fixed; top: 20px; right: 20px; background: var(--success); color: white; padding: 15px 25px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); z-index: 1000; animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s forwards;">
                            <i class="fas fa-check-circle"></i> ${data.message}
                        </div>
                    `;
                    document.body.appendChild(successMsg);
                    setTimeout(() => successMsg.remove(), 3000);
                    
                    // If no products left, show empty state
                    if (document.querySelectorAll('.product-item').length === 0) {
                        document.getElementById('productContainer').innerHTML = `
                            <div class="no-products">
                                <p>No products found matching your criteria.</p>
                                <p>Try adjusting your search filters.</p>
                            </div>
                        `;
                    }
                } else {
                    throw new Error(data.message || 'Failed to delete product');
                }
            } catch (error) {
                // Show error message
                const errorMsg = document.createElement('div');
                errorMsg.innerHTML = `
                    <div style="position: fixed; top: 20px; right: 20px; background: var(--danger); color: white; padding: 15px 25px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); z-index: 1000; animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s forwards;">
                        <i class="fas fa-exclamation-circle"></i> ${error.message}
                    </div>
                `;
                document.body.appendChild(errorMsg);
                setTimeout(() => errorMsg.remove(), 3000);
                
                console.error('Deletion error:', error);
            } finally {
                hideConfirmation();
                confirmBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Confirm';
                confirmBtn.disabled = false;
            }
        });

        // Close modal when clicking outside
        document.getElementById('confirmationDialog').addEventListener('click', function(e) {
            if (e.target === this) {
                hideConfirmation();
            }
        });

        // Add CSS for notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>