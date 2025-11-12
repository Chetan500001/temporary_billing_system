<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$conn = new mysqli('localhost', 'root', '', 'billing_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Utility function to sanitize input
function clean_input($data) {
    return htmlspecialchars(trim($data));
}

// Utility function to validate phone number
function is_valid_phone($phone) {
    return preg_match('/^[6-9]\d{9}$/', $phone); // Indian 10-digit mobile number starting with 6-9
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'] ?? '';

    // ----------------------- CUSTOMER LOGIN -----------------------
    if ($type == 'customer_login') {
        $email = clean_input($_POST['customer_email']);
        $password = clean_input($_POST['customer_password']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die("Invalid email format! <br><a href='businesslogin.html'>Go back</a>");
        }

        $stmt = $conn->prepare("SELECT id, customer_name, customer_password FROM customers WHERE customer_email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $name, $hashed_password);
        $stmt->fetch();

        if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
            $_SESSION['customer_id'] = $id;
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;
            unset($_SESSION['city'], $_SESSION['state']);
            header("Location: customer_dashboard.php");
            exit();
        } else {
            echo "Error: Invalid email or password! <br><a href='businesslogin.html'>Go back</a>";
        }
        $stmt->close();
        exit();
    }

    // ----------------------- BUSINESS LOGIN -----------------------
    if ($type == 'business_login') {
        $email = clean_input($_POST['email']);
        $password = clean_input($_POST['password']);

        $stmt = $conn->prepare("SELECT id, business_name, password, city, state, address, category FROM businesses WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $business_name, $hashed_password, $city, $state, $address, $category);
        $stmt->fetch();

        if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
            $_SESSION['business_id'] = $id;
            $_SESSION['business_name'] = $business_name;
            $_SESSION['business_city'] = $city;
            $_SESSION['business_state'] = $state;
            $_SESSION['business_address'] = $address;
            $_SESSION['business_category'] = $category;
            header("Location: business_dashboard.php");
            exit();
        } else {
            echo "Error: Invalid email or password! <br><a href='businesslogin.html'>Go back</a>";
        }
        $stmt->close();
        exit();
    }

    // ----------------------- CUSTOMER SIGNUP -----------------------
    if ($type == 'customer_signup') {
        $name = clean_input($_POST['customer_name']);
        $phone = clean_input($_POST['customer_phone']);
        $email = clean_input($_POST['customer_email']);
        $password = clean_input($_POST['customer_password']);
        $security_question1 = clean_input($_POST['security_question1']);
        $security_answer1 = clean_input($_POST['security_answer1']);
        $security_question2 = clean_input($_POST['security_question2']);
        $security_answer2 = clean_input($_POST['security_answer2']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die("Invalid email format! <br><a href='businesslogin.html'>Go back</a>");
        }
        if (!is_valid_phone($phone)) {
            die("Invalid phone number! Must be 10 digits starting with 6-9. <br><a href='businesslogin.html'>Go back</a>");
        }

        $stmt = $conn->prepare("SELECT id FROM customers WHERE customer_email=? OR customer_phone=?");
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            die("Customer already exists! <br><a href='businesslogin.html'>Go back</a>");
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_answer1 = password_hash(strtolower($security_answer1), PASSWORD_DEFAULT);
        $hashed_answer2 = password_hash(strtolower($security_answer2), PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO customers (customer_name, customer_phone, customer_email, customer_password, security_question1, security_answer1, security_question2, security_answer2) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $phone, $email, $hashed_password, $security_question1, $hashed_answer1, $security_question2, $hashed_answer2);
        if ($stmt->execute()) {
            echo "Customer signup successful! <br><a href='businesslogin.html'>Go to Login</a>";
        } else {
            echo "Signup failed. Try again.";
        }
        $stmt->close();
        exit();
    }

    // ----------------------- BUSINESS SIGNUP -----------------------
if ($type == 'business_signup') {
    $bname = clean_input($_POST['business_name']);
    $owner = clean_input($_POST['owner_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $category = clean_input($_POST['category']);
    $state = clean_input($_POST['state']);
    $city = clean_input($_POST['city']);
    $address = clean_input($_POST['address']);
    $description = clean_input($_POST['description'] ?? '');
    $gst = clean_input($_POST['gst_number'] ?? '');
    $password = clean_input($_POST['password']);
    $security_question1 = clean_input($_POST['security_question1']);
    $security_answer1 = clean_input($_POST['security_answer1']);
    $security_question2 = clean_input($_POST['security_question2']);
    $security_answer2 = clean_input($_POST['security_answer2']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format! <br><a href='businesslogin.html'>Go back</a>");
    }
    if (!is_valid_phone($phone)) {
        die("Invalid phone number! Must be 10 digits starting with 6-9. <br><a href='businesslogin.html'>Go back</a>");
    }

    $stmt = $conn->prepare("SELECT id FROM businesses WHERE email=? OR phone=?");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("Business already exists! <br><a href='businesslogin.html'>Go back</a>");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $hashed_answer1 = password_hash(strtolower($security_answer1), PASSWORD_DEFAULT);
    $hashed_answer2 = password_hash(strtolower($security_answer2), PASSWORD_DEFAULT);

    // Corrected INSERT statement with matching number of columns and values
    $stmt = $conn->prepare("INSERT INTO businesses (
        business_name, owner_name, email, phone, category, state, city, 
        address, description, gst_number, password, 
        security_question1, security_answer1, security_question2, security_answer2
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssssssssssss", 
        $bname, $owner, $email, $phone, $category, $state, $city,
        $address, $description, $gst, $hashed_password,
        $security_question1, $hashed_answer1, $security_question2, $hashed_answer2
    );

    if ($stmt->execute()) {
        echo "Business signup successful! <br><a href='businesslogin.html'>Go to Login</a>";
    } else {
        echo "Signup failed. Try again.";
    }
    $stmt->close();
    exit();
}
}

$conn->close();
?>