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

// Validate session
if (!isset($_SESSION['customer_id'])) {
    die(json_encode([
        'success' => false, 
        'message' => 'Session expired. Please login again.',
        'error_code' => 'SESSION_EXPIRED'
    ]));
}

// Validate required POST data
$required_fields = ['business_id', 'business_name', 'bill_name', 'items', 'total'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || (is_string($_POST[$field]) && trim($_POST[$field]) === '')) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    die(json_encode([
        'success' => false, 
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
        'error_code' => 'MISSING_DATA'
    ]));
}

// Initialize variables
$stmt = $check_stmt = null;

try {
    // Prepare and validate data
    $data = [
        'customer_id' => (int)$_SESSION['customer_id'],
        'business_id' => (int)$_POST['business_id'],
        'business_name' => $conn->real_escape_string(htmlspecialchars($_POST['business_name'])),
        'bill_name' => $conn->real_escape_string(htmlspecialchars($_POST['bill_name'])),
        'items' => $_POST['items'],
        'total' => (float)$_POST['total'],
        'date' => date('Y-m-d H:i:s')
    ];

    // Validate items JSON
    $items_array = json_decode($data['items'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid items data format");
    }

    if (!is_array($items_array) || empty($items_array)) {
        throw new Exception("Items must be a non-empty array");
    }

    // Re-encode for storage
    $items_json = json_encode($items_array);
    if ($items_json === false) {
        throw new Exception("Failed to encode items data");
    }

    // Prepare SQL
    $sql = "INSERT INTO saved_bills (customer_id, business_id, business_name, bill_name, items, total, date) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database preparation failed");
    }

    // Bind parameters
    $bound = $stmt->bind_param(
        "iisssds",
        $data['customer_id'],
        $data['business_id'],
        $data['business_name'],
        $data['bill_name'],
        $items_json,
        $data['total'],
        $data['date']
    );

    if (!$bound) {
        throw new Exception("Parameter binding failed");
    }

    if (!$stmt->execute()) {
        throw new Exception("Execution failed: " . $stmt->error);
    }

    $inserted_id = $conn->insert_id;

    // Send clean JSON response
    echo json_encode([
        'success' => true,
        'message' => 'Bill saved successfully',
        'bill_id' => $inserted_id
    ]);
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    exit();

} catch (Exception $e) {
    // Clean up resources
    if ($stmt) $stmt->close();
    if ($check_stmt) $check_stmt->close();
    if ($conn) $conn->close();
    
    // Send error response
    die(json_encode([
        'success' => false,
        'message' => 'Failed to save bill: ' . $e->getMessage(),
        'error_code' => 'SAVE_FAILED'
    ]));
}
?>