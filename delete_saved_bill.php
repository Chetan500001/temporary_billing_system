<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting
ini_set('display_errors', 0); // Disable display to prevent corrupting JSON
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
        'message' => 'Database connection failed',
        'error_code' => 'DB_CONNECTION_FAILED'
    ]));
}

// Validate session and input
if (!isset($_SESSION['customer_id'])) {
    die(json_encode([
        'success' => false, 
        'message' => 'Session expired. Please login again.',
        'error_code' => 'SESSION_EXPIRED'
    ]));
}

if (!isset($_POST['bill_id']) || !is_numeric($_POST['bill_id'])) {
    die(json_encode([
        'success' => false, 
        'message' => 'Invalid bill ID provided',
        'error_code' => 'INVALID_BILL_ID'
    ]));
}

$bill_id = (int)$_POST['bill_id'];
$customer_id = (int)$_SESSION['customer_id'];

// Initialize statements
$check_stmt = $delete_stmt = null;

try {
    // Verify the bill exists and belongs to customer
    $check_sql = "SELECT id FROM saved_bills WHERE id = ? AND customer_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        throw new Exception("Database verification error");
    }
    
    $check_stmt->bind_param("ii", $bill_id, $customer_id);
    if (!$check_stmt->execute()) {
        throw new Exception("Verification query failed");
    }
    
    $check_stmt->store_result();

    if ($check_stmt->num_rows === 0) {
        throw new Exception("Bill not found or doesn't belong to you");
    }

    // Delete the bill
    $delete_sql = "DELETE FROM saved_bills WHERE id = ? AND customer_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    
    if (!$delete_stmt) {
        throw new Exception("Database delete error");
    }
    
    $delete_stmt->bind_param("ii", $bill_id, $customer_id);
    if (!$delete_stmt->execute()) {
        throw new Exception("Delete operation failed");
    }

    $affected_rows = $delete_stmt->affected_rows;

    // Send clean JSON response
    echo json_encode([
        'success' => true,
        'message' => 'Bill successfully deleted',
        'bill_id' => $bill_id,
        'affected_rows' => $affected_rows
    ]);
    
    // Close statements and connection
    $check_stmt->close();
    $delete_stmt->close();
    $conn->close();
    exit();

} catch (Exception $e) {
    // Clean up resources
    if ($check_stmt) $check_stmt->close();
    if ($delete_stmt) $delete_stmt->close();
    if ($conn) $conn->close();
    
    // Send error response
    die(json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'DELETE_FAILED'
    ]));
}
?>