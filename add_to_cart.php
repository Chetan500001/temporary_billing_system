<?php
session_start();

if (!isset($_SESSION['customer_id']) || !isset($_POST['product_id']) || !isset($_POST['business_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

$customer_id = $_SESSION['customer_id'];
$product_id = $_POST['product_id'];
$business_id = $_POST['business_id'];

// Check if product exists
$checkProduct = $conn->prepare("SELECT id FROM product WHERE id = ? AND business_id = ?");
$checkProduct->bind_param("ii", $product_id, $business_id);
$checkProduct->execute();
if ($checkProduct->get_result()->num_rows == 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit();
}

// Check if already in cart
$checkCart = $conn->prepare("SELECT id, quantity FROM cart WHERE customer_id = ? AND business_id = ? AND product_id = ?");
$checkCart->bind_param("iii", $customer_id, $business_id, $product_id);
$checkCart->execute();
$result = $checkCart->get_result();

if ($result->num_rows > 0) {
    // Update quantity
    $update = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
    $update->bind_param("i", $result->fetch_assoc()['id']);
    $update->execute();
} else {
    // Add new item
    $insert = $conn->prepare("INSERT INTO cart (customer_id, business_id, product_id, quantity) VALUES (?, ?, ?, 1)");
    $insert->bind_param("iii", $customer_id, $business_id, $product_id);
    $insert->execute();
}

header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
$conn->close();
?>