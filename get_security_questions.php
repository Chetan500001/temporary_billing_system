<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$conn = new mysqli('localhost', 'root', '', 'billing_system');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

function clean_input($data) {
    return htmlspecialchars(trim($data));
}

$email = clean_input($_POST['email'] ?? '');
$user_type = clean_input($_POST['user_type'] ?? 'customer');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit();
}

// Determine which table to query based on user type
$table = ($user_type === 'business') ? 'businesses' : 'customers';
$email_field = ($user_type === 'business') ? 'email' : 'customer_email';

$stmt = $conn->prepare("SELECT security_question1, security_question2 FROM $table WHERE $email_field = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Email not found']);
    exit();
}

$stmt->bind_result($question1, $question2);
$stmt->fetch();

echo json_encode([
    'success' => true,
    'questions' => [$question1, $question2]
]);

$stmt->close();
$conn->close();
?>