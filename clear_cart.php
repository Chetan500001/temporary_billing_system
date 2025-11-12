<?php
session_start();
header('Content-Type: application/json');

// Database Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "billing_system";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false, 
        'message' => 'Database connection failed'
    ]));
}

// Validate session and input
if (!isset($_SESSION['customer_id']) || !isset($_POST['business_id'])) {
    die(json_encode([
        'success' => false, 
        'message' => 'Invalid request'
    ]));
}

$customer_id = (int)$_SESSION['customer_id'];
$business_id = (int)$_POST['business_id'];

// Initialize statement
$stmt = null;

try {
    // Prepare and execute delete statement
    $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ? AND business_id = ?");
    if (!$stmt) {
        throw new Exception("Database error");
    }
    
    $stmt->bind_param("ii", $customer_id, $business_id);
    if (!$stmt->execute()) {
        throw new Exception("Delete operation failed");
    }

    // Success response - keep it simple
    echo json_encode(['success' => true]);
    
    // Exit immediately after sending response
    exit();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
} finally {
    // Clean up resources
    if ($stmt) $stmt->close();
    if ($conn) $conn->close();
}
?>