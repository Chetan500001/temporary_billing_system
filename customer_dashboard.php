<?php
session_start();
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['customer_name'])) {
    header("Location: businesslogin.html");
    exit();
}

// If location is already set and not coming from back button, redirect to find_businesses.php
if (isset($_SESSION['city']) && isset($_SESSION['state']) && !isset($_GET['back'])) {
    header("Location: find_businesses.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | BILLMate</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2565AE;
            --primary-light: rgba(37, 101, 174, 0.1);
            --primary-dark: #1A4B8C;
            --secondary: #4A90E2;
            --accent: #2ED573;
            --danger: #FF4757;
            --light: #F8F9FF;
            --dark: #2F3542;
            --gray: #747D8C;
            --light-gray: #F1F2F6;
            --border-radius: 8px;
            --box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--light-gray), white);
            color: var(--dark);
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--box-shadow);
            position: relative;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        .header-text {
            display: flex;
            flex-direction: column;
        }
        
        .header h2 {
            font-weight: 600;
            font-size: 1.4rem;
        }
        
        .welcome-text {
            font-weight: 400;
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .customer-name {
            font-weight: 600;
        }
        
        .logout-link {
            text-decoration: none;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
        }
        
        .dashboard-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .location-card {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .location-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .location-card h2 {
            color: var(--primary-dark);
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.6rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: var(--gray);
            font-weight: 500;
        }
        
        select, .submit-btn {
            width: 100%;
            padding: 14px 18px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        select {
            border: 1px solid var(--light-gray);
            background-color: white;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 101, 174, 0.1);
        }
        
        .submit-btn {
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(37, 101, 174, 0.2);
        }
        
        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 101, 174, 0.3);
        }
        
        .error-message {
            color: var(--danger);
            background: rgba(255, 71, 87, 0.1);
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95rem;
            border-left: 3px solid var(--danger);
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 15px 25px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .logout-link {
                align-self: flex-end;
            }
            
            .dashboard-container {
                margin: 30px auto;
                padding: 0 15px;
            }
            
            .location-card {
                padding: 30px;
            }
            
            .location-card h2 {
                font-size: 1.4rem;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="header-text">
                <div class="welcome-text">Welcome back</div>
                <h2 class="customer-name"><?php echo htmlspecialchars($_SESSION['customer_name']); ?></h2>
            </div>
            <a href="logout.php" class="logout-link">
                <button class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="location-card">
            <h2>Select Your Location</h2>
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="set_location.php" method="POST">
                <div class="form-group">
                    <label for="state">State</label>
                    <select name="state" id="state" required>
                        <option value="">-- Select State --</option>
                        <option value="Delhi">Delhi</option>
                        <option value="Maharashtra">Maharashtra</option>
                        <option value="Punjab">Punjab</option>
                        <option value="Haryana">Haryana</option>
                        <option value="Gujrat">Gujrat</option>
                        <option value="Bihar">Bihar</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="city">City</label>
                    <select name="city" id="city" required disabled>
                        <option value="">-- Select City --</option>
                    </select>
                </div>

                <button type="submit" class="submit-btn">Continue to Businesses</button>
            </form>
        </div>
    </div>

    <script>
        const citiesByState = {
            'Delhi': ['New Delhi', 'Noida', 'Gurgaon', 'Ghaziabad', 'Faridabad'],
            'Maharashtra': ['Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Aurangabad'],
            'Punjab': ['Patiala', 'Amritsar', 'Jalandhar', 'Ludhiana', 'Bathinda'],
            'Haryana': ['Karnal','Gurgaon', 'Faridabad', 'Rohtak', 'Panipat', 'Hisar'],
            'Gujrat': ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar'],
            'Bihar': ['Patna', 'Muzaffarpur', 'Gaya', 'Bhagalpur', 'Darbhanga']
        };

        document.getElementById('state').addEventListener('change', function () {
            const citySelect = document.getElementById('city');
            const selectedState = this.value;
            citySelect.innerHTML = '<option value="">-- Select City --</option>';
            citySelect.disabled = !selectedState;
            
            if (selectedState && citiesByState[selectedState]) {
                citiesByState[selectedState].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        });
    </script>
</body>
</html>