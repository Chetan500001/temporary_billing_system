<?php
session_start();
if (!isset($_SESSION['business_id'])) {
    header("Location: index.html");
    exit();
}

// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'billing_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch categories created by this business
$categories = [];
$stmt = $conn->prepare("SELECT DISTINCT category FROM product WHERE business_id = ?");
$stmt->bind_param("i", $_SESSION['business_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (!empty($row['category'])) {
        $categories[] = $row['category'];
    }
}
$stmt->close();

// Fetch brands created by this business
$brands = [];
$stmt = $conn->prepare("SELECT DISTINCT brand FROM product WHERE business_id = ? AND brand IS NOT NULL");
$stmt->bind_param("i", $_SESSION['business_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (!empty($row['brand'])) {
        $brands[] = $row['brand'];
    }
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #205781;
            --secondary: #4F959D;
            --danger: #d9534f;
            --light: #f8f9fa;
            --dark: #343a40;
            --white: #ffffff;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 8px;
            --box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: var(--primary);
            color: var(--white);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 10;
        }
        
        .sidebar img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid rgba(255,255,255,0.2);
        }
        
        .sidebar h3 {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 40px;
            text-align: center;
            color: var(--white);
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .header {
            background: var(--white);
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
        }
        
        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(32, 87, 129, 0.1);
        }
        
        .form-container {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-title {
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: var(--light);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(32, 87, 129, 0.2);
        }
        
        textarea.form-control {
            min-height: 120px;
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
        }
        
        .submit-btn {
            background: var(--primary);
            color: var(--white);
            padding: 14px;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(32, 87, 129, 0.2);
        }
        
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 20px;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            
            .sidebar img {
                width: 50px;
                height: 50px;
                margin-bottom: 0;
                margin-right: 15px;
            }
            
            .sidebar h3 {
                margin-bottom: 0;
                font-size: 1rem;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .header {
                padding: 15px 20px;
                font-size: 1.2rem;
            }
            
            .form-container {
                padding: 20px;
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
        <h3><?php echo htmlspecialchars($_SESSION['business_name']); ?></h3>
    </div>

    <div class="main-content">
        <div class="header">
            <span>Add New Product</span>
            <div>
                <a href="business_dashboard.php" class="btn btn-outline">Back</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="form-container">
            <h2 class="form-title">Product Information</h2>
            <form action="save_product.php" method="POST">
                <div class="form-group">
                    <label for="product_name">Product Name</label>
                    <input type="text" id="product_name" name="product_name" class="form-control" placeholder="Enter product name" required>
                </div>
                
                <div class="form-group">
                    <label for="product_description">Description</label>
                    <textarea id="product_description" name="product_description" class="form-control" placeholder="Enter product description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="actual_price">Actual Price (₹)</label>
                    <input type="number" step="0.01" id="actual_price" name="actual_price" class="form-control" placeholder="Enter actual price" required>
                </div>
                
                <div class="form-group">
                    <label for="offered_price">Offered Price (₹)</label>
                    <input type="number" step="0.01" id="offered_price" name="offered_price" class="form-control" placeholder="Enter offered price" required>
                </div>
                
                <div class="options-group">
                    <h3 class="options-title">Category Options</h3>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="existing-category" name="category_option" value="existing" checked onclick="toggleCategoryInput()">
                            <label for="existing-category">Existing Category</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="new-category" name="category_option" value="new" onclick="toggleCategoryInput()">
                            <label for="new-category">New Category</label>
                        </div>
                    </div>
                    
                    <div class="form-group" id="existingCategoryGroup">
                        <label for="existing_category">Select Category</label>
                        <select id="existing_category" name="existing_category" class="form-control">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="newCategoryGroup" style="display: none;">
                        <label for="new_category">New Category Name</label>
                        <input type="text" id="new_category" name="new_category" class="form-control" placeholder="Enter new category name">
                    </div>
                </div>
                
                <div class="options-group">
                    <h3 class="options-title">Brand Options</h3>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="existing-brand" name="brand_option" value="existing" checked onclick="toggleBrandInput()">
                            <label for="existing-brand">Existing Brand</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="new-brand" name="brand_option" value="new" onclick="toggleBrandInput()">
                            <label for="new-brand">New Brand</label>
                        </div>
                    </div>
                    
                    <div class="form-group" id="existingBrandGroup">
                        <label for="existing_brand">Select Brand</label>
                        <select id="existing_brand" name="existing_brand" class="form-control">
                            <option value="">-- Select Brand --</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= htmlspecialchars($brand) ?>"><?= htmlspecialchars($brand) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="newBrandGroup" style="display: none;">
                        <label for="new_brand">New Brand Name</label>
                        <input type="text" id="new_brand" name="new_brand" class="form-control" placeholder="Enter new brand name">
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">Add Product</button>
            </form>
        </div>
    </div>

    <script>
        function toggleCategoryInput() {
            const selected = document.querySelector('input[name="category_option"]:checked').value;
            document.getElementById('existingCategoryGroup').style.display = selected === 'existing' ? 'block' : 'none';
            document.getElementById('newCategoryGroup').style.display = selected === 'new' ? 'block' : 'none';

            document.getElementById('existing_category').required = selected === 'existing';
            document.getElementById('new_category').required = selected === 'new';
        }

        function toggleBrandInput() {
            const selected = document.querySelector('input[name="brand_option"]:checked').value;
            document.getElementById('existingBrandGroup').style.display = selected === 'existing' ? 'block' : 'none';
            document.getElementById('newBrandGroup').style.display = selected === 'new' ? 'block' : 'none';

            document.getElementById('existing_brand').required = selected === 'existing';
            document.getElementById('new_brand').required = selected === 'new';
        }

        // Add form submission handler
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('save_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show success popup
                    alert(data.message);
                    // Clear form
                    this.reset();
                    // Reset category and brand inputs
                    toggleCategoryInput();
                    toggleBrandInput();
                } else {
                    // Show error message
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });

        window.onload = function() {
            toggleCategoryInput();
            toggleBrandInput();
        };
    </script>
</body>
</html>