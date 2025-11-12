<?php
session_start();
header('Content-Type: application/json');

// 1. Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// 2. Get product and business IDs from POST
$product_id = $_POST['product_id'] ?? null;
$business_id = $_POST['business_id'] ?? null;

if (!$product_id || !$business_id) {
    echo json_encode(['error' => 'Invalid product or business ID']);
    exit;
}

// 3. Database connection (replace credentials with yours)
$servername = "localhost";
$username = "root";
$password = ""; // or your DB password
$database = "billing_system"; // Replace with your DB name

$conn = new mysqli($servername, $username, $password, $database);

// 4. Check connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 5. Prepare and execute the delete query
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ? AND product_id = ? AND business_id = ?");

if (!$stmt) {
    echo json_encode(['error' => 'SQL prepare failed']);
    exit;
}

$stmt->bind_param("iii", $customer_id, $product_id, $business_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to remove item from cart']);
}

$stmt->close();
$conn->close();
?>
