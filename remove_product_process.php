<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Verify session
if (!isset($_SESSION['business_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access - Session expired',
        'error_code' => 'SESSION_EXPIRED'
    ]);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'billing_system');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error_code' => 'DB_CONNECTION_FAILED',
        'error_details' => $conn->connect_error
    ]);
    exit;
}

// Set charset
$conn->set_charset("utf8mb4");

// Validate product ID
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID',
        'error_code' => 'INVALID_PRODUCT_ID',
        'received_id' => $_POST['product_id'] ?? 'null'
    ]);
    exit;
}

$product_id = (int)$_POST['product_id'];
$business_id = (int)$_SESSION['business_id'];

// Start transaction
$conn->begin_transaction();

try {
    // 1. Verify product exists and belongs to this business
    $check_sql = "SELECT id FROM product WHERE id = ? AND business_id = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $check_stmt->bind_param("ii", $product_id, $business_id);
    if (!$check_stmt->execute()) {
        throw new Exception("Execute failed: " . $check_stmt->error);
    }
    
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows === 0) {
        throw new Exception("Product not found or doesn't belong to your business");
    }
    $check_stmt->close();

    // // 2. Check for dependencies in other tables
    // $check_dependencies = $conn->prepare("SELECT id FROM order_items WHERE product_id = ? LIMIT 1");
    // if ($check_dependencies) {
    //     $check_dependencies->bind_param("i", $product_id);
    //     if ($check_dependencies->execute()) {
    //         $check_dependencies->store_result();
    //         if ($check_dependencies->num_rows > 0) {
    //             throw new Exception("Cannot delete - product exists in orders");
    //         }
    //     }
    //     $check_dependencies->close();
    // }

    // 3. Delete the product
    $delete_sql = "DELETE FROM product WHERE id = ? AND business_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    
    if (!$delete_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $delete_stmt->bind_param("ii", $product_id, $business_id);
    if (!$delete_stmt->execute()) {
        throw new Exception("Execute failed: " . $delete_stmt->error);
    }
    
    $affected_rows = $delete_stmt->affected_rows;
    $delete_stmt->close();
    
    if ($affected_rows === 0) {
        throw new Exception("No rows affected - product may have already been deleted");
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully',
        'product_id' => $product_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Product Deletion Error - Business ID: $business_id, Product ID: $product_id - " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete product: ' . $e->getMessage(),
        'error_code' => 'DELETION_FAILED',
        'error_details' => $e->getMessage(),
        'product_id' => $product_id,
        'business_id' => $business_id
    ]);
} finally {
    $conn->close();
}