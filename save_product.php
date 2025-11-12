<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the business is logged in
if (!isset($_SESSION['business_id'])) {
    die("Error: Business ID is not set in the session.");
}

// Database connection
$host = 'localhost';
$dbname = 'billing_system';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Sanitize and retrieve form data
$product_name = clean_input($_POST['product_name'] ?? '');
$product_description = clean_input($_POST['product_description'] ?? '');
$actual_price = clean_input($_POST['actual_price'] ?? '');
$offered_price = clean_input($_POST['offered_price'] ?? '');
$business_id = $_SESSION['business_id'];

// Determine category based on user selection
$category_option = clean_input($_POST['category_option'] ?? '');
$category = '';

if ($category_option === 'existing') {
    $category = clean_input($_POST['existing_category'] ?? '');
} elseif ($category_option === 'new') {
    $category = clean_input($_POST['new_category'] ?? '');
}

// Determine brand based on user selection
$brand_option = clean_input($_POST['brand_option'] ?? '');
$brand = null; // Initialize as null

if ($brand_option === 'existing') {
    $brand = !empty($_POST['existing_brand']) ? clean_input($_POST['existing_brand']) : null;
} elseif ($brand_option === 'new') {
    $brand = !empty($_POST['new_brand']) ? clean_input($_POST['new_brand']) : null;
}

// Validate required inputs
if (empty($product_name) || empty($product_description) || 
    empty($actual_price) || empty($offered_price) || empty($category)) {
    die("Error: All required fields must be filled.");
}

// Prepare and bind the SQL statement to insert the product
$stmt = $conn->prepare("INSERT INTO product (
    business_id, 
    product_name, 
    product_description, 
    actual_price, 
    offered_price,
    category,
    brand
) VALUES (?, ?, ?, ?, ?, ?, ?)");

// Corrected type definition string - added one more 's' for brand
$stmt->bind_param("issddss", 
    $business_id, 
    $product_name, 
    $product_description, 
    $actual_price, 
    $offered_price,
    $category,
    $brand
);

// Execute the statement and check for success
if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Product added successfully!'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $stmt->error
    ]);
}

// Close connections
$stmt->close();
$conn->close();

function clean_input($data) {
    if ($data === null) {
        return null;
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>