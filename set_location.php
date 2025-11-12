<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['customer_name'])) {
    header("Location: businesslogin.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['state']) && !empty($_POST['city'])) {
        $_SESSION['state'] = $_POST['state'];
        $_SESSION['city'] = $_POST['city'];
        header("Location: find_businesses.php"); // Redirect to next page
        exit();
    } else {
        // If fields are empty, redirect back with error
        header("Location: customer_dashboard.php?error=Please select both state and city.");
        exit();
    }
} else {
    header("Location: customer_dashboard.php");
    exit();
}
?>
