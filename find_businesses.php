<?php 
session_start();

// Redirect if session not set
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['customer_name']) || !isset($_SESSION['city']) || !isset($_SESSION['state'])) {
    header("Location: customer_dashboard.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle search
$search = $_GET['search'] ?? '';
$search_safe = "%$search%";

// Query with search
if (!empty($search)) {
    $stmt = $conn->prepare("
        SELECT DISTINCT b.id AS business_id, b.business_name, b.category, b.description,
        (SELECT MIN(p.offered_price) 
         FROM product p 
         WHERE p.business_id = b.id AND (p.product_name LIKE ? OR p.brand LIKE ?)) AS min_price
        FROM businesses b
        LEFT JOIN product p ON p.business_id = b.id
        WHERE b.city = ? AND (b.business_name LIKE ? OR p.product_name LIKE ? OR p.brand LIKE ? OR b.category LIKE ?)
    ");
    $stmt->bind_param("sssssss", $search_safe, $search_safe, $_SESSION['city'], $search_safe, $search_safe, $search_safe, $search_safe);
    $stmt->execute();
    $results = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT id AS business_id, business_name, category, description FROM businesses WHERE city = ?");
    $stmt->bind_param("s", $_SESSION['city']);
    $stmt->execute();
    $results = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Find Businesses</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2565AE;
            --secondary: #2565AE;
            --light: #F5F5F5;
            --dark: #333;
            --gray: #666;
            --white: #fff;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light);
            padding: 0;
            margin: 0;
            color: var(--dark);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .back-button {
            background-color: var(--white);
            color: var(--primary);
            font-weight: 600;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .search-section {
            background: var(--white);
            padding: 30px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .search-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            width: 100%;
        }
        
        .search-input {
            flex: 1;
            padding: 14px 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 149, 157, 0.2);
        }
        
        .search-button {
            padding: 14px 25px;
            font-size: 16px;
            font-weight: 600;
            background: var(--secondary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-button:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }
        
        .filter-tag {
            display: inline-block;
            margin-top: 10px;
            font-size: 14px;
            color: var(--gray);
        }
        
        .businesses-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }
        
        .business-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .business-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        
        .business-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .business-name {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .business-category {
            display: inline-block;
            margin-top: 5px;
            font-size: 14px;
            color: var(--secondary);
            font-weight: 500;
        }
        
        .business-body {
            padding: 20px;
        }
        
        .business-description {
            color: var(--gray);
            margin: 0 0 15px 0;
            font-size: 15px;
            line-height: 1.5;
        }
        
        .business-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f9f9f9;
        }
        
        .price-badge {
            background: var(--secondary);
            color: var(--white);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .view-button {
            padding: 8px 20px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .view-button:hover {
            background: #164466;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 20px;
            grid-column: 1 / -1;
        }
        
        .no-results-icon {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .reset-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #f0f0f0;
            color: var(--dark);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .reset-button:hover {
            background: #e0e0e0;
        }
        
        @media (max-width: 768px) {
            .businesses-container {
                grid-template-columns: 1fr;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <a href="customer_dashboard.php?back=true" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <div class="user-info">
            <span class="business-location">
                <?php echo htmlspecialchars($_SESSION['city']) . ', ' . htmlspecialchars($_SESSION['state']); ?>
            </span>
        </div>
    </div>
</div>

<div class="search-section">
    <div class="search-container">
        <form method="GET" action="find_businesses.php" class="search-form">
            <input type="text" name="search" class="search-input" 
                   placeholder="Search business, product, brand or category..." 
                   value="<?php echo htmlspecialchars($search); ?>" required autofocus />
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
        <?php if (!empty($search)): ?>
            <p class="filter-tag">
                Showing results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                <a href="find_businesses.php" class="reset-button">
                    <i class="fas fa-times"></i> Clear search
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="businesses-container">
        <?php if (!empty($search) && $results->num_rows == 0): ?>
            <div class="no-results">
                <div class="no-results-icon">
                    <i class="far fa-frown"></i>
                </div>
                <h3>No results found for "<?php echo htmlspecialchars($search); ?>"</h3>
                <p>Try searching for something else or check your spelling</p>
                <a href="find_businesses.php" class="view-button">
                    <i class="fas fa-sync-alt"></i> Show all businesses
                </a>
            </div>
        <?php endif; ?>

        <?php if ($results->num_rows > 0): ?>
            <?php while ($row = $results->fetch_assoc()): ?>
                <div class="business-card">
                    <div class="business-header">
                        <h3 class="business-name"><?php echo htmlspecialchars($row['business_name']); ?></h3>
                        <span class="business-category"><?php echo htmlspecialchars($row['category']); ?></span>
                    </div>
                    <div class="business-body">
                        <?php if (!empty($row['description'])): ?>
                            <p class="business-description"><?php echo htmlspecialchars($row['description']); ?></p>
                        <?php else: ?>
                            <p class="business-description">No description available</p>
                        <?php endif; ?>
                    </div>
                    <div class="business-footer">
                        <?php if (isset($row['min_price']) && $row['min_price'] !== null): ?>
                            <span class="price-badge">
                                <i class="fas fa-tag"></i> From ₹<?php echo number_format($row['min_price'], 2); ?>
                            </span>
                        <?php endif; ?>
                        <form action="view_business_products.php" method="GET" style="margin: 0;">
                            <input type="hidden" name="business_id" value="<?php echo $row['business_id']; ?>">
                            <button type="submit" class="view-button">
                                <i class="fas fa-store"></i> View Products
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php elseif (empty($search)): ?>
            <div class="no-results">
                <div class="no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Find businesses in your city</h3>
                <p>Search for businesses, products, brands or categories to get started</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>     