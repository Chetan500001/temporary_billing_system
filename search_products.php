<?php
session_start();
header('Content-Type: application/json');

// DB connection
$conn = new mysqli('localhost', 'root', '', 'billing_system');
if ($conn->connect_error) {
    die(json_encode([]));
}

// Get filters
$query = $_GET['query'] ?? '';
$business_id = $_GET['business_id'] ?? 'all';
$category = $_GET['category'] ?? '';
$city = $_SESSION['city'] ?? '';

$sql = "SELECT p.*, b.business_name 
        FROM product p 
        JOIN businesses b ON p.business_id = b.id 
        WHERE b.city = ? AND p.product_name LIKE ?";

$params = [$city, '%' . $query . '%'];
$types = "ss";

// Optional filters
if ($business_id !== 'all') {
    $sql .= " AND p.business_id = ?";
    $params[] = $business_id;
    $types .= "i";
}
if (!empty($category) && $category !== 'all') {
    $sql .= " AND p.category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);

$stmt->close();
$conn->close();
?>
