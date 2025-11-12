<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Security: ensure business is logged in
if (!isset($_SESSION['business_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit(json_encode(['error' => 'Unauthorized access']));
}

// 2. Database connection
$conn = new mysqli('localhost', 'root', '', 'billing_system');
if ($conn->connect_error) {
    header("HTTP/1.1 500 Internal Server Error");
    exit(json_encode(['error' => 'Database connection failed']));
}

// 3. Input validation helper
function validate_input($data, $type = 'text') {
    if ($data === null) return null;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    switch ($type) {
        case 'number':
            return is_numeric($data) ? (float)$data : false;
        case 'id':
            return is_numeric($data) ? (int)$data : false;
        case 'category':
            return preg_match('/^[a-zA-Z0-9\s\-]+$/', $data) ? $data : false;
        case 'brand':
            return preg_match('/^[a-zA-Z0-9\s\-&]+$/', $data) ? $data : false;
        default:
            return $data;
    }
}

// 4. Sanitize & validate incoming POST data
$product_id          = validate_input($_POST['product_id'] ?? '', 'id');
$product_name        = validate_input($_POST['product_name'] ?? '');
$product_description = validate_input($_POST['product_description'] ?? '');
$actual_price        = validate_input($_POST['actual_price'] ?? '', 'number');
$offered_price       = validate_input($_POST['offered_price'] ?? '', 'number');
$business_id         = $_SESSION['business_id'];

// Handle category selection (existing or new)
$category_option = validate_input($_POST['category_option'] ?? '');
$category = null;
if ($category_option === 'existing') {
    $category = validate_input($_POST['existing_category'] ?? '', 'category');
} elseif ($category_option === 'new') {
    $category = validate_input($_POST['new_category'] ?? '', 'category');
}

// Handle brand selection (existing, new, or none)
$brand_option = validate_input($_POST['brand_option'] ?? '');
$brand = null;
if ($brand_option === 'existing') {
    $brand = validate_input($_POST['existing_brand'] ?? '', 'brand');
} elseif ($brand_option === 'new') {
    $brand = validate_input($_POST['new_brand'] ?? '', 'brand');
} // else remains null for 'none' option

// 5. Collect validation errors
$errors = [];
if (!$product_id)               $errors[] = "Invalid product ID.";
if (empty($product_name))       $errors[] = "Product name is required.";
if (strlen($product_name) > 100) $errors[] = "Product name too long (max 100 chars).";
if (empty($product_description))$errors[] = "Description is required.";
if (strlen($product_description) > 500) $errors[] = "Description too long (max 500 chars).";
if ($actual_price === false)    $errors[] = "Invalid actual price.";
if ($offered_price === false)   $errors[] = "Invalid offered price.";
if ($offered_price > $actual_price) $errors[] = "Offered price cannot exceed actual price.";
if ($category === false)        $errors[] = "Invalid category format.";
if ($brand === false)           $errors[] = "Invalid brand format.";

if (!empty($errors)) {
    header("HTTP/1.1 400 Bad Request");
    exit(json_encode(['errors' => $errors]));
}

// 6. Verify this product belongs to the logged-in business
$check = $conn->prepare("SELECT id FROM product WHERE id = ? AND business_id = ?");
$check->bind_param("ii", $product_id, $business_id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode(['error' => "Product not found or access denied."]));
}
$check->close();

// 7. Perform the update inside a transaction
$conn->begin_transaction();

try {
    $update = $conn->prepare("
        UPDATE product SET
            product_name        = ?,
            product_description = ?,
            actual_price        = ?,
            offered_price       = ?,
            category            = ?,
            brand               = ?
        WHERE
            id = ? AND business_id = ?
    ");
    
    $update->bind_param(
        "ssddssii",
        $product_name,
        $product_description,
        $actual_price,
        $offered_price,
        $category,
        $brand,
        $product_id,
        $business_id
    );
    
    if (!$update->execute()) {
        throw new Exception("Update failed: " . $update->error);
    }

    $conn->commit();
    
    // Return updated product data for frontend
    $updated_product = [
        'id' => $product_id,
        'product_name' => $product_name,
        'product_description' => $product_description,
        'actual_price' => $actual_price,
        'offered_price' => $offered_price,
        'category' => $category,
        'brand' => $brand
    ];
    
    header('Content-Type: application/json');
    echo json_encode([
        'success'  => true,
        'message'  => 'Product updated successfully.',
        'product'  => $updated_product
    ]);

} catch (Exception $e) {
    $conn->rollback();
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($update) && $update instanceof mysqli_stmt) {
        $update->close();
    }
    $conn->close();
}