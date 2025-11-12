<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "billing_system";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (isset($_POST['signup'])) { // Signup process
        $business_name = $_POST['business_name'];
        $gst_number = $_POST['gst_number'];
        $owner_name = $_POST['owner_name'];
        $phone = $_POST['phone'];
        $state = $_POST['state'];
        $city = $_POST['city'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO businesses (business_name, gst_number, owner_name, email, phone, password, state, city) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $business_name, $gst_number, $owner_name, $email, $phone, $hashed_password, $state, $city);
        
        if ($stmt->execute()) {
            echo "Signup successful!";
        } else {
            echo "Error: " . $conn->error;
        }
        $stmt->close();
    }
    
    if (isset($_POST['login'])) { // Login process
        $sql = "SELECT id, business_name, email, state, city, password FROM businesses WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $business_name, $email, $state, $city, $hashed_password);
        $stmt->fetch();
        
        if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
            $_SESSION['business_id'] = $id;
            $_SESSION['business_name'] = $business_name;
            $_SESSION['email'] = $email;
            $_SESSION['state'] = $state;
            $_SESSION['city'] = $city;
            header("Location: dashboard.php"); // Redirect to user dashboard
            exit();
        } else {
            echo "Invalid email or password.";
        }
        $stmt->close();
    }
}

$conn->close();
?>