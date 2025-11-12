<?php
session_start();

if (!isset($_SESSION['customer_id']) || !isset($_GET['business_id'])) {
    echo "0";
    exit();
}

$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    echo "0";
    exit();
}

$stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE customer_id = ? AND business_id = ?");
$stmt->bind_param("ii", $_SESSION['customer_id'], $_GET['business_id']);
$stmt->execute();
$result = $stmt->get_result();

echo ($result->num_rows > 0) ? ($result->fetch_assoc()['count'] ?? "0") : "0";
$conn->close();
?>