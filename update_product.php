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
$business_id = $_SESSION['business_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_category = $_GET['category'] ?? '';
$filter_brand = $_GET['brand'] ?? '';
$products = [];

// Get distinct categories
$category_query = $conn->prepare("SELECT DISTINCT category FROM product WHERE business_id = ? AND category IS NOT NULL AND category != ''");
$category_query->bind_param("i", $business_id);
$category_query->execute();
$category_result = $category_query->get_result();
$categories = [];
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row['category'];
}
$category_query->close();

// Get distinct brands
$brand_query = $conn->prepare("SELECT DISTINCT brand FROM product WHERE business_id = ? AND brand IS NOT NULL AND brand != ''");
$brand_query->bind_param("i", $business_id);
$brand_query->execute();
$brand_result = $brand_query->get_result();
$brands = [];
while ($row = $brand_result->fetch_assoc()) {
    $brands[] = $row['brand'];
}
$brand_query->close();

// Fetch filtered products
$sql = "SELECT id, product_name, product_description, actual_price, offered_price, category, brand 
        FROM product 
        WHERE business_id = ?";
$param_types = "i";
$params = [$business_id];

if (!empty($filter_category)) {
    $sql .= " AND category = ?";
    $param_types .= "s";
    $params[] = $filter_category;
}

if (!empty($filter_brand)) {
    $sql .= " AND brand = ?";
    $param_types .= "s";
    $params[] = $filter_brand;
}

if (!empty($search)) {
    $sql .= " AND (product_name LIKE ? OR brand LIKE ?)";
    $param_types .= "ss";
    $like_term = "%$search%";
    $params[] = $like_term;
    $params[] = $like_term;
}

$sql .= " ORDER BY product_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Products</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #205781;
            --primary-light: rgba(32, 87, 129, 0.1);
            --secondary: #4F959D;
            --danger: #e74c3c;
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
        
        .filters {
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
        
        .filters input, 
        .filters select {
            padding: 12px 15px;
            border-radius: var(--border-radius);
            border: 1px solid var(--light-gray);
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: white;
        }
        
        .filters input {
            flex: 1;
            min-width: 250px;
        }
        
        .filters input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(32, 87, 129, 0.2);
        }
        
        .filters select {
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
        
        .product-item h4 {
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
            color: var(--success);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .product-price small {
            color: var(--gray);
            text-decoration: line-through;
            font-size: 0.85rem;
            margin-left: 5px;
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
        
        .update-form {
            margin-top: 15px;
            display: none;
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--light-gray);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .update-form input, 
        .update-form textarea,
        .update-form select {
            width: 100%;
            padding: 12px 15px;
            margin: 5px 0;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .update-form input:focus, 
        .update-form textarea:focus,
        .update-form select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(32, 87, 129, 0.2);
        }
        
        .update-form textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .options-group {
            margin-bottom: 25px;
        }
        
        .options-title {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .radio-group {
            display: flex;
            gap: 25px;
            margin-bottom: 15px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .radio-option input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .radio-option label {
            font-weight: 400;
            margin-bottom: 0;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
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
        
        .highlight {
            color: var(--primary);
            font-weight: 500;
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
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filters input,
            .filters select {
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
            
            .radio-group {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="usericon.png" alt="User Icon">
        <h3><?= htmlspecialchars($_SESSION['business_name']) ?></h3>
        <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <span><i class="fas fa-box-open"></i> Manage Products</span>
            <a href="business_dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>

        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="🔍 Search products or brands..." value="<?= htmlspecialchars($search) ?>">
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($filter_category === $cat) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="brand">
                <option value="">All Brands</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= htmlspecialchars($brand) ?>" <?= ($filter_brand === $brand) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($brand) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>
            <?php if (!empty($search) || !empty($filter_category) || !empty($filter_brand)): ?>
                <a href="update_product.php" class="btn btn-outline"><i class="fas fa-sync-alt"></i> Reset</a>
            <?php endif; ?>
        </form>

        <div class="product-list">
            <?php if (empty($products)): ?>
                <div class="no-products">
                    <p>No products found<?php 
                        echo !empty($search) ? ' matching <span class="highlight">"' . htmlspecialchars($search) . '"</span>' : '';
                        echo !empty($filter_category) ? ' in category <span class="highlight">"' . htmlspecialchars($filter_category) . '"</span>' : '';
                        echo !empty($filter_brand) ? ' from brand <span class="highlight">"' . htmlspecialchars($filter_brand) . '"</span>' : '';
                    ?>.</p>
                    <a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-item" id="product-<?= $product['id'] ?>">
                        <div class="product-info">
                            <h4><?= htmlspecialchars($product['product_name']) ?></h4>
                            <p><?= htmlspecialchars($product['product_description']) ?></p>
                            <div class="product-meta">
                                <span class="product-price">
                                    ₹<?= number_format($product['offered_price'], 2) ?>
                                    <small>₹<?= number_format($product['actual_price'], 2) ?></small>
                                </span>
                                <?php if (!empty($product['category'])): ?>
                                    <span class="product-category"><?= htmlspecialchars($product['category']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($product['brand'])): ?>
                                    <span class="product-brand"><?= htmlspecialchars($product['brand']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn btn-primary" onclick="toggleForm(<?= $product['id'] ?>)">
                                <i class="fas fa-edit"></i> Update
                            </button>
                        </div>
                    </div>

                    <div class="update-form" id="form-<?= $product['id'] ?>">
                        <form onsubmit="handleFormSubmit(event, <?= $product['id'] ?>)">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            
                            <div class="form-group">
                                <label>Product Name</label>
                                <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="product_description" required><?= htmlspecialchars($product['product_description']) ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Actual Price (₹)</label>
                                <input type="number" step="0.01" name="actual_price" value="<?= $product['actual_price'] ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Offered Price (₹)</label>
                                <input type="number" step="0.01" name="offered_price" value="<?= $product['offered_price'] ?>" required>
                            </div>
                            
                            <div class="options-group">
                                <h3 class="options-title">Category Options</h3>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="existing-category-<?= $product['id'] ?>" name="category_option" value="existing" checked onclick="toggleCategoryInput(<?= $product['id'] ?>)">
                                        <label for="existing-category-<?= $product['id'] ?>">Existing Category</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="new-category-<?= $product['id'] ?>" name="category_option" value="new" onclick="toggleCategoryInput(<?= $product['id'] ?>)">
                                        <label for="new-category-<?= $product['id'] ?>">New Category</label>
                                    </div>
                                </div>
                                
                                <div class="form-group" id="existingCategoryGroup-<?= $product['id'] ?>">
                                    <label for="existing_category-<?= $product['id'] ?>">Select Category</label>
                                    <select id="existing_category-<?= $product['id'] ?>" name="existing_category" class="form-control">
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($product['category'] === $cat) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group" id="newCategoryGroup-<?= $product['id'] ?>" style="display: none;">
                                    <label for="new_category-<?= $product['id'] ?>">New Category Name</label>
                                    <input type="text" id="new_category-<?= $product['id'] ?>" name="new_category" class="form-control" placeholder="Enter new category name" value="<?= (!in_array($product['category'], $categories) && !empty($product['category'])) ? htmlspecialchars($product['category']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="options-group">
                                <h3 class="options-title">Brand Options</h3>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="existing-brand-<?= $product['id'] ?>" name="brand_option" value="existing" <?= (!empty($product['brand']) && in_array($product['brand'], $brands)) ? 'checked' : '' ?> onclick="toggleBrandInput(<?= $product['id'] ?>)">
                                        <label for="existing-brand-<?= $product['id'] ?>">Existing Brand</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="new-brand-<?= $product['id'] ?>" name="brand_option" value="new" <?= (!empty($product['brand']) && !in_array($product['brand'], $brands)) ? 'checked' : '' ?> onclick="toggleBrandInput(<?= $product['id'] ?>)">
                                        <label for="new-brand-<?= $product['id'] ?>">New Brand</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="no-brand-<?= $product['id'] ?>" name="brand_option" value="none" <?= empty($product['brand']) ? 'checked' : '' ?> onclick="toggleBrandInput(<?= $product['id'] ?>)">
                                        <label for="no-brand-<?= $product['id'] ?>">No Brand</label>
                                    </div>
                                </div>
                                
                                <div class="form-group" id="existingBrandGroup-<?= $product['id'] ?>" style="<?= (!empty($product['brand']) && in_array($product['brand'], $brands)) ? '' : 'display: none;' ?>">
                                    <label for="existing_brand-<?= $product['id'] ?>">Select Brand</label>
                                    <select id="existing_brand-<?= $product['id'] ?>" name="existing_brand" class="form-control">
                                        <option value="">-- Select Brand --</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= htmlspecialchars($brand) ?>" <?= ($product['brand'] === $brand) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($brand) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group" id="newBrandGroup-<?= $product['id'] ?>" style="<?= (!empty($product['brand']) && !in_array($product['brand'], $brands)) ? '' : 'display: none;' ?>">
                                    <label for="new_brand-<?= $product['id'] ?>">New Brand Name</label>
                                    <input type="text" id="new_brand-<?= $product['id'] ?>" name="new_brand" class="form-control" placeholder="Enter new brand name" value="<?= (!empty($product['brand']) && !in_array($product['brand'], $brands)) ? htmlspecialchars($product['brand']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                                <button type="button" class="btn btn-outline" onclick="toggleForm(<?= $product['id'] ?>)"><i class="fas fa-times"></i> Cancel</button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleForm(id) {
            const form = document.getElementById('form-' + id);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
            
            if (form.style.display === 'block') {
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function toggleCategoryInput(id) {
            const selected = document.querySelector(`input[name="category_option"]:checked`);
            if (!selected) return;
            
            const value = selected.value;
            document.getElementById(`existingCategoryGroup-${id}`).style.display = value === 'existing' ? 'block' : 'none';
            document.getElementById(`newCategoryGroup-${id}`).style.display = value === 'new' ? 'block' : 'none';

            document.getElementById(`existing_category-${id}`).required = value === 'existing';
            document.getElementById(`new_category-${id}`).required = value === 'new';
        }

        function toggleBrandInput(id) {
            const selected = document.querySelector(`input[name="brand_option"]:checked`);
            if (!selected) return;
            
            const value = selected.value;
            document.getElementById(`existingBrandGroup-${id}`).style.display = value === 'existing' ? 'block' : 'none';
            document.getElementById(`newBrandGroup-${id}`).style.display = value === 'new' ? 'block' : 'none';

            document.getElementById(`existing_brand-${id}`).required = value === 'existing';
            document.getElementById(`new_brand-${id}`).required = value === 'new';
        }

        async function handleFormSubmit(event, productId) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            
            try {
                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
                
                const response = await fetch('update_product_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success message
                    alert('Product updated successfully!');
                    
                    // Update the displayed product info
                    const productItem = document.getElementById(`product-${productId}`);
                    if (productItem) {
                        // Update name
                        const nameElement = productItem.querySelector('h4');
                        if (nameElement) {
                            nameElement.textContent = formData.get('product_name');
                        }
                        
                        // Update description
                        const descElement = productItem.querySelector('p');
                        if (descElement) {
                            descElement.textContent = formData.get('product_description');
                        }
                        
                        // Update prices
                        const actualPrice = parseFloat(formData.get('actual_price')).toFixed(2);
                        const offeredPrice = parseFloat(formData.get('offered_price')).toFixed(2);
                        
                        const priceElement = productItem.querySelector('.product-price');
                        if (priceElement) {
                            priceElement.innerHTML = `₹${offeredPrice} <small>₹${actualPrice}</small>`;
                        }
                        
                        // Update category
                        let category = '';
                        if (formData.get('category_option') === 'existing') {
                            category = formData.get('existing_category');
                        } else if (formData.get('category_option') === 'new') {
                            category = formData.get('new_category');
                        }
                        
                        const categoryElement = productItem.querySelector('.product-category');
                        if (categoryElement) {
                            categoryElement.textContent = category;
                        } else if (category) {
                            const metaElement = productItem.querySelector('.product-meta');
                            if (metaElement) {
                                const newCategoryElement = document.createElement('span');
                                newCategoryElement.className = 'product-category';
                                newCategoryElement.textContent = category;
                                metaElement.appendChild(newCategoryElement);
                            }
                        }
                        
                        // Update brand
                        let brand = '';
                        if (formData.get('brand_option') === 'existing') {
                            brand = formData.get('existing_brand');
                        } else if (formData.get('brand_option') === 'new') {
                            brand = formData.get('new_brand');
                        }
                        
                        const brandElement = productItem.querySelector('.product-brand');
                        if (brandElement) {
                            if (brand) {
                                brandElement.textContent = brand;
                            } else {
                                brandElement.remove();
                            }
                        } else if (brand) {
                            const metaElement = productItem.querySelector('.product-meta');
                            if (metaElement) {
                                const newBrandElement = document.createElement('span');
                                newBrandElement.className = 'product-brand';
                                newBrandElement.textContent = brand;
                                metaElement.appendChild(newBrandElement);
                            }
                        }
                    }
                    
                    // Hide the form
                    toggleForm(productId);
                    
                    // Reload the page to refresh filters if needed
                    if (window.location.search.includes('search=') || 
                        window.location.search.includes('category=') || 
                        window.location.search.includes('brand=')) {
                        window.location.reload();
                    }
                } else if (data.errors) {
                    alert('Error: ' + data.errors.join('\n'));
                } else if (data.error) {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating the product.');
            } finally {
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                submitBtn.disabled = false;
            }
        }

        // Initialize form toggles for each product
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($products as $product): ?>
                toggleCategoryInput(<?= $product['id'] ?>);
                toggleBrandInput(<?= $product['id'] ?>);
            <?php endforeach; ?>
        });
    </script>
</body>
</html>