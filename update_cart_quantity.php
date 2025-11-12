<?php
session_start();

if (!isset($_SESSION['customer_id']) || !isset($_POST['cart_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

$cart_id = $_POST['cart_id'];
$change = (int)$_POST['change'];

// Verify cart item belongs to user
$verifyStmt = $conn->prepare("SELECT quantity FROM cart WHERE id = ? AND customer_id = ?");
$verifyStmt->bind_param("ii", $cart_id, $_SESSION['customer_id']);
$verifyStmt->execute();
$result = $verifyStmt->get_result();

if ($result->num_rows == 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Cart item not found']);
    exit();
}

$current = $result->fetch_assoc()['quantity'];
$newQuantity = $current + $change;

if ($newQuantity < 1) {
    // Remove item
    $deleteStmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
    $deleteStmt->bind_param("i", $cart_id);
    $deleteStmt->execute();
} else {
    // Update quantity
    $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $newQuantity, $cart_id);
    $updateStmt->execute();
}

header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
$conn->close();
?>