<?php
session_start();
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['customer_name']) || !isset($_SESSION['city']) || !isset($_SESSION['state']) || !isset($_GET['business_id'])) {
    header("Location: find_businesses.php");
    exit();
}

$business_id = $_GET['business_id'];

$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch business details
$bizStmt = $conn->prepare("SELECT business_name, category, city, state FROM businesses WHERE id = ?");
$bizStmt->bind_param("i", $business_id);
$bizStmt->execute();
$bizResult = $bizStmt->get_result();
$business = $bizResult->fetch_assoc();
$bizStmt->close();

// Get search parameters with sanitization
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_safe = "%$search%";
$start_amt = isset($_GET['start_amt']) && is_numeric($_GET['start_amt']) ? max(0, (int)$_GET['start_amt']) : 0;
$end_amt = isset($_GET['end_amt']) && is_numeric($_GET['end_amt']) ? max($start_amt, (int)$_GET['end_amt']) : 1000000;
$sort = isset($_GET['sort']) && in_array($_GET['sort'], ['ASC', 'DESC']) ? $_GET['sort'] : 'ASC';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Get distinct categories for dropdown
$categoryStmt = $conn->prepare("SELECT DISTINCT category FROM product WHERE business_id = ? AND category != '' ORDER BY category");
$categoryStmt->bind_param("i", $business_id);
$categoryStmt->execute();
$categoryResult = $categoryStmt->get_result();
$categories = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row['category'];
}
$categoryStmt->close();

// Build product query
$query = "SELECT id, product_name, product_description, actual_price, offered_price, category 
          FROM product 
          WHERE business_id = ? 
          AND (product_name LIKE ? OR product_description LIKE ? OR category LIKE ?) 
          AND offered_price BETWEEN ? AND ?";

$params = [$business_id, $search_safe, $search_safe, $search_safe, $start_amt, $end_amt];
$types = "isssii";

// Add category filter if selected
if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$query .= " ORDER BY offered_price $sort";

$productStmt = $conn->prepare($query);
$productStmt->bind_param($types, ...$params);
$productStmt->execute();
$productResult = $productStmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Products by <?php echo htmlspecialchars($business['business_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #205781;
            --secondary-color: #4F959D;
            --accent-color: #FF7E5F;
            --light-bg: #f0f4f8;
            --dark-text: #333;
            --light-text: #777;
            --white: #fff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --border-radius: 12px;
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: var(--light-bg); 
            padding: 0;
            margin: 0;
            color: var(--dark-text);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Business Header */
        .business-header {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        
        .business-name {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0 0 10px 0;
        }
        
        .business-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            color: var(--light-text);
            font-size: 14px;
        }
        
        .business-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .business-meta i {
            color: var(--secondary-color);
        }
        
        /* Search Filters */
        .search-container {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .search-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }
        
        .search-title i {
            color: var(--secondary-color);
        }
        
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-color);
            font-size: 14px;
        }
        
        .search-input, .category-select {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: var(--transition);
            width: 100%;
        }
        
        .search-input:focus, 
        .category-select:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 149, 157, 0.2);
        }
        
        .price-range {
            grid-column: span 2;
        }
        
        .range-slider {
            width: 100%;
            position: relative;
            margin-top: 10px;
        }
        
        .range-slider .slider {
            width: 100%;
            height: 6px;
            -webkit-appearance: none;
            appearance: none;
            background: #ddd;
            border-radius: 3px;
            outline: none;
            margin: 15px 0;
        }
        
        .range-slider .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            background: var(--primary-color);
            border-radius: 50%;
            cursor: pointer;
        }
        
        .range-values {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: var(--light-text);
        }
        
        .filter-buttons {
            grid-column: span 2;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .apply-filters, .reset-filters {
            padding: 12px 25px;
            border-radius: 8px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .apply-filters {
            background: var(--primary-color); 
            color: var(--white);
        }
        
        .apply-filters:hover {
            background: #164266;
        }
        
        .reset-filters {
            background: var(--white);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .reset-filters:hover {
            background: #f0f4f8;
        }
        
        /* Sorting Options */
        .sort-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .results-count {
            font-weight: 500;
            color: var(--light-text);
            font-size: 14px;
        }
        
        .sort-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .sort-option {
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid #ddd;
            background: var(--white);
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .sort-option.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        .sort-option i {
            font-size: 12px;
        }
        
        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .product-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-category {
            font-size: 12px;
            color: var(--secondary-color);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .product-name { 
            font-size: 18px; 
            font-weight: 600; 
            color: var(--dark-text); 
            margin-bottom: 10px;
            flex-grow: 1;
        }
        
        .product-description { 
            margin-bottom: 15px; 
            font-size: 14px; 
            color: var(--light-text);
            line-height: 1.5;
        }
        
        .price-container { 
            margin-bottom: 15px;
        }
        
        .actual-price { 
            text-decoration: line-through; 
            color: var(--light-text); 
            margin-right: 8px;
            font-size: 14px;
        }
        
        .offered-price { 
            color: var(--secondary-color); 
            font-weight: 700;
            font-size: 18px;
        }
        
        .discount-badge {
            background: #FFE8E4;
            color: var(--accent-color);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .add-cart-btn, .remove-cart-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .add-cart-btn {
            background: var(--primary-color); 
            color: var(--white);
        }
        
        .add-cart-btn:hover {
            background: #164266;
        }
        
        .remove-cart-btn {
            background: #FFE8E4;
            color: var(--accent-color);
        }
        
        .remove-cart-btn:hover {
            background: #FFD5CD;
        }
        
        .in-cart-label {
            font-size: 12px;
            color: var(--secondary-color);
            text-align: center;
            margin-top: 8px;
            font-weight: 500;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--border-radius);
            margin: 30px 0;
            box-shadow: var(--shadow);
            grid-column: 1 / -1;
        }
        
        .empty-state-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 22px;
        }
        
        .empty-state p {
            color: var(--light-text);
            margin-bottom: 25px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .suggestions {
            text-align: left;
            display: inline-block;
            margin-bottom: 25px;
            padding-left: 20px;
        }
        
        .suggestions li {
            margin-bottom: 8px;
            color: var(--light-text);
        }
        
        /* Cart Button */
        .cart-btn {
            display: inline-block;
            background: var(--primary-color); 
            color: var(--white);
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: var(--transition);
        }
        
        .cart-btn:hover {
            background: #164266;
            transform: translateY(-2px);
        }
        
        .cart-btn i {
            margin-right: 8px;
        }
        
        .badge {
            background: var(--accent-color);
            color: var(--white);
            border-radius: 50%;
            padding: 3px 8px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .price-range {
                grid-column: span 1;
            }
            
            .filter-buttons {
                grid-column: span 1;
            }
            
            .sort-section {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        /* Business Header */
.business-header {
    background: var(--white);
    border-radius: var(--border-radius);
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: var(--shadow);
    text-align: center;
}

.header-actions {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.back-btn, .cart-btn {
    padding: 8px 15px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
}

.back-btn {
    background: var(--white);
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.back-btn:hover {
    background: #f0f4f8;
}

.cart-btn {
    background: var(--primary-color); 
    color: var(--white);
}

.cart-btn:hover {
    background: #164266;
}

.badge {
    background: var(--accent-color);
    color: var(--white);
    border-radius: 50%;
    padding: 3px 8px;
    font-size: 12px;
}
    </style>
</head>
<body>
    <div class="container">
        <!-- Business Header -->
         <!-- Business Header -->
<div class="business-header">
    <div class="header-actions">
        <a href="find_businesses.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="./cart.php?business_id=<?php echo $business_id; ?>" class="cart-btn">
            <i class="fas fa-shopping-cart"></i> Cart <span class="badge" id="cart-count">0</span>
        </a>
    </div>
    
    <h1 class="business-name"><?php echo htmlspecialchars($business['business_name']); ?></h1>
    
    <div class="business-meta">
        <?php if (!empty($business['city']) || !empty($business['state'])): ?>
            <span class="location">
                <i class="fas fa-map-marker-alt"></i>
                <?php 
                    echo htmlspecialchars($business['city']);
                    if (!empty($business['city']) && !empty($business['state'])) {
                        echo ', ';
                    }
                    echo htmlspecialchars($business['state']);
                ?>
            </span>
        <?php endif; ?>
        <?php if (!empty($business['category'])): ?>
            <span class="category">
                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($business['category']); ?>
            </span>
        <?php endif; ?>
    </div>
</div>
       
        
        <!-- Search Filters -->
        <div class="search-container">
            <h2 class="search-title">
                <i class="fas fa-sliders-h"></i> Filter Products
            </h2>
            
            <form class="search-form" method="GET" action="view_business_products.php">
                <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
                
                <div class="filter-group">
                    <label for="search">Search Products</label>
                    <input type="text" id="search" name="search" class="search-input" 
                           placeholder="Name, description or category..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="category-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" 
                                <?php if ($category === $cat) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group price-range">
                    <label>Price Range</label>
                    <div class="range-slider">
                        <input type="range" id="price-slider" class="slider" min="0" max="10000" step="100"
                               value="<?php echo min($end_amt, 10000); ?>">
                        <div class="range-values">
                            <span class="min-value">₹<?php echo number_format($start_amt); ?></span>
                            <span class="max-value">₹<?php echo $end_amt == 1000000 ? '10,000+' : number_format($end_amt); ?></span>
                        </div>
                    </div>
                    <input type="hidden" id="start_amt" name="start_amt" value="<?php echo $start_amt; ?>">
                    <input type="hidden" id="end_amt" name="end_amt" value="<?php echo $end_amt; ?>">
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort By</label>
                    <select id="sort" name="sort" class="category-select">
                        <option value="ASC" <?php if ($sort == 'ASC') echo 'selected'; ?>>Price: Low to High</option>
                        <option value="DESC" <?php if ($sort == 'DESC') echo 'selected'; ?>>Price: High to Low</option>
                    </select>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="apply-filters">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="view_business_products.php?business_id=<?php echo $business_id; ?>" class="reset-filters">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Sorting Section -->
        <div class="sort-section">
            <div class="results-count">
                <?php 
                    $count = $productResult->num_rows;
                    echo $count . " product" . ($count != 1 ? 's' : '') . " found";
                ?>
            </div>
            <div class="sort-options">
                <button class="sort-option <?php echo $sort == 'ASC' ? 'active' : ''; ?>" onclick="setSort('ASC')">
                    <i class="fas fa-rupee-sign"></i> Price (Low to High)
                </button>
                <button class="sort-option <?php echo $sort == 'DESC' ? 'active' : ''; ?>" onclick="setSort('DESC')">
                    <i class="fas fa-rupee-sign"></i> Price (High to Low)
                </button>
            </div>
        </div>

        <div class="product-grid">
            <?php if ($productResult->num_rows == 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>No products match your search</h3>
                    <p>We couldn't find any products matching your criteria. Try these suggestions:</p>
                    <ul class="suggestions">
                        <li>Check your spelling</li>
                        <li>Try different keywords</li>
                        <li>Remove some filters</li>
                    </ul>
                    <a href="view_business_products.php?business_id=<?php echo $business_id; ?>" class="apply-filters" style="display: inline-flex; width: auto;">
                        <i class="fas fa-undo"></i> Reset All Filters
                    </a>
                </div>
            <?php else: ?>
                <?php while ($row = $productResult->fetch_assoc()): ?>
                    <?php
                    $productId = $row['id'];
                    $productInCart = false;
                    $discount = 0;
                    
                    if ($row['actual_price'] > 0) {
                        $discount = round(100 - ($row['offered_price'] / $row['actual_price'] * 100));
                    }

                    // Check if the product is already in the cart
                    $cartStmt = $conn->prepare("SELECT * FROM cart WHERE customer_id = ? AND business_id = ? AND product_id = ?");
                    $cartStmt->bind_param("iii", $_SESSION['customer_id'], $business_id, $productId);
                    $cartStmt->execute();
                    $cartResult = $cartStmt->get_result();

                    if ($cartResult->num_rows > 0) {
                        $productInCart = true;
                    }

                    $cartStmt->close();
                    ?>
                    
                    <div class="product-card">
                        <?php if (!empty($row['category'])): ?>
                            <div class="product-category"><?php echo htmlspecialchars($row['category']); ?></div>
                        <?php endif; ?>
                        
                        <div class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                        <div class="product-description"><?php echo htmlspecialchars($row['product_description']); ?></div>
                        
                        <div class="price-container">
                            <?php if ($row['actual_price'] > $row['offered_price']): ?>
                                <span class="actual-price">₹<?php echo number_format($row['actual_price']); ?></span>
                            <?php endif; ?>
                            <span class="offered-price">₹<?php echo number_format($row['offered_price']); ?></span>
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge"><?php echo $discount; ?>% OFF</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($productInCart): ?>
                            <button class="remove-cart-btn" onclick="removeFromCart(<?php echo $row['id']; ?>)">
                                <i class="fas fa-minus-circle"></i> Remove from Cart
                            </button>
                            <div class="in-cart-label"><i class="fas fa-check-circle"></i> Added to cart</div>
                        <?php else: ?>
                            <button class="add-cart-btn" onclick="addToCart(<?php echo $row['id']; ?>)">
                                <i class="fas fa-plus-circle"></i> Add to Cart
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Initialize price slider
        initPriceSlider();
        
        // Update cart count
        updateCartCount();
    });
    
    function initPriceSlider() {
        const slider = $('#price-slider');
        const minValue = $('.min-value');
        const maxValue = $('.max-value');
        const startAmtInput = $('#start_amt');
        const endAmtInput = $('#end_amt');
        
        // Initialize slider value
        const currentMax = <?php echo min($end_amt, 10000); ?>;
        slider.val(currentMax);
        
        // Update display when slider changes
        slider.on('input', function() {
            const value = parseInt($(this).val());
            endAmtInput.val(value);
            
            if(value >= 10000) {
                maxValue.text('₹10,000+');
                endAmtInput.val(1000000); // Reset to our high value
            } else {
                maxValue.text('₹' + value.toLocaleString('en-IN'));
                endAmtInput.val(value);
            }
        });
    }
    
    function addToCart(productId) {
        $.ajax({
            url: 'add_to_cart.php',
            method: 'POST',
            data: {
                product_id: productId,
                business_id: <?php echo $business_id; ?>
            },
            success: function (response) {
                updateCartCount();
                showToast('Item added to cart!', 'success');
                // Reload after a short delay to show the toast
                setTimeout(() => location.reload(), 1000);
            },
            error: function() {
                showToast('Failed to add item to cart', 'error');
            }
        });
    }

    function removeFromCart(productId) {
        $.ajax({
            url: 'remove_from_cart.php',
            method: 'POST',
            data: {
                product_id: productId,
                business_id: <?php echo $business_id; ?>
            },
            success: function (response) {
                updateCartCount();
                showToast('Item removed from cart', 'info');
                // Reload after a short delay to show the toast
                setTimeout(() => location.reload(), 1000);
            },
            error: function() {
                showToast('Failed to remove item from cart', 'error');
            }
        });
    }

    function updateCartCount() {
        $.ajax({
            url: 'cart_count.php',
            method: 'GET',
            data: { business_id: <?php echo $business_id; ?> },
            success: function (count) {
                $('#cart-count').text(count);
            }
        });
    }

    function setSort(order) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', order);
        window.location.href = url.toString();
    }

    function showToast(message, type) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                ${type === 'success' ? '<i class="fas fa-check-circle"></i>' : 
                 type === 'error' ? '<i class="fas fa-exclamation-circle"></i>' : 
                 '<i class="fas fa-info-circle"></i>'}
            </div>
            <div class="toast-message">${message}</div>
        `;
        
        // Add to body
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Hide after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
    </script>
</body>
</html>
<?php
$productStmt->close();
$conn->close();
?>