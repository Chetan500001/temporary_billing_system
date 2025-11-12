<?php
session_start();
if (!isset($_SESSION['business_id'])) {
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Dashboard</title>
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
            --border-radius: 10px;
            --box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
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
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 35px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        
        .sidebar img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid rgba(255,255,255,0.15);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 2;
        }
        
        .sidebar h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 40px;
            text-align: center;
            color: white;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .header {
            background: white;
            color: var(--primary);
            padding: 20px 25px;
            border-radius: var(--border-radius);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--primary);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
            gap: 8px;
            z-index: 2;
        }
        
        .btn i {
            font-size: 1rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 10px rgba(37, 101, 174, 0.2);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(37, 101, 174, 0.3);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
            box-shadow: 0 4px 10px rgba(255, 71, 87, 0.2);
        }
        
        .btn-danger:hover {
            background: #FF3342;
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(255, 71, 87, 0.3);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .dashboard-card {
            background: white;
            padding: 22px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: var(--transition);
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 24px rgba(0,0,0,0.12);
        }
        
        .card-icon {
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 15px;
            background: rgba(37, 101, 174, 0.1);
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .dashboard-card:hover .card-icon {
            background: var(--primary);
            color: white;
            transform: scale(1.05);
        }
        
        .card-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--dark);
        }
        
        .card-description {
            color: var(--gray);
            margin-bottom: 20px;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .card-btn {
            width: 100%;
            padding: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            border: none;
            border-radius: var(--border-radius);
            background: var(--primary);
            color: white;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(37, 101, 174, 0.15);
        }
        
        .card-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(37, 101, 174, 0.25);
        }
        
        .welcome-message {
            font-size: 1.1rem;
            color: var(--gray);
            margin-bottom: 5px;
            font-weight: 400;
        }
        
        .business-name {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.6rem;
        }
        
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 18px;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                height: auto;
            }
            
            .sidebar img {
                width: 55px;
                height: 55px;
                margin-bottom: 0;
                margin-right: 12px;
            }
            
            .sidebar h3 {
                margin-bottom: 0;
                font-size: 1.1rem;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 18px;
            }
            
            .business-name {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="usericon.png" alt="User Icon">
        <h3><?php echo htmlspecialchars($_SESSION['business_name']); ?></h3>
        <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <p class="welcome-message">Welcome to your dashboard,</p>
                <h1 class="business-name"><?php echo htmlspecialchars($_SESSION['business_name']); ?></h1>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3 class="card-title">Add New Product</h3>
                <p class="card-description">Add new items to your inventory with details</p>
                <button class="card-btn" onclick="window.location.href='add_product_process.php'">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <h3 class="card-title">Update Product</h3>
                <p class="card-description">Modify existing product details</p>
                <button class="card-btn" onclick="window.location.href='update_product.php'">
                    <i class="fas fa-pencil-alt"></i> Update
                </button>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h3 class="card-title">Remove Product</h3>
                <p class="card-description">Delete products from inventory</p>
                <button class="card-btn" onclick="window.location.href='remove_product.php'">
                    <i class="fas fa-minus"></i> Remove
                </button>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <h3 class="card-title">View Inventory</h3>
                <p class="card-description">Browse your complete product catalog</p>
                <button class="card-btn" onclick="window.location.href='view_inventory.php'">
                    <i class="fas fa-search"></i> View
                </button>
            </div>
        </div>
    </div>
</body>
</html>