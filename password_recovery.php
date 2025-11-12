<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'billing_system';

// Establish database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Initialize rate limiting
if (!isset($_SESSION['recovery_attempts'])) {
    $_SESSION['recovery_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

// Input sanitization function
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Password strength validation
function validate_password($password) {
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number";
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $step = clean_input($_POST['step'] ?? '1');
    
    // Check rate limiting
    if ($_SESSION['recovery_attempts'] > 5 && (time() - $_SESSION['last_attempt']) < 3600) {
        die(json_encode([
            'success' => false,
            'message' => 'Too many attempts. Please try again in an hour.'
        ]));
    }
    
    $_SESSION['recovery_attempts']++;
    $_SESSION['last_attempt'] = time();

    // Step 1: Verify email and get security questions
    if ($step == '1') {
        $user_type = clean_input($_POST['user_type']);
        $email = clean_input($_POST['email']);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die(json_encode([
                'success' => false,
                'message' => 'Please enter a valid email address'
            ]));
        }
        
        // Prepare appropriate query based on user type
        $table = ($user_type == 'customer') ? 'customers' : 'businesses';
        $email_field = ($user_type == 'customer') ? 'customer_email' : 'email';
        
        $stmt = $conn->prepare("SELECT security_question1, security_question2 FROM $table WHERE $email_field = ?");
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            die(json_encode([
                'success' => false,
                'message' => 'Database error. Please try again.'
            ]));
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Store verification data in session
            $_SESSION['recovery_email'] = $email;
            $_SESSION['recovery_user_type'] = $user_type;
            $_SESSION['recovery_step1_completed'] = true;
            
            // Reset attempts for this successful step
            $_SESSION['recovery_attempts'] = 0;
            
            echo json_encode([
                'success' => true,
                'question1' => $user['security_question1'],
                'question2' => $user['security_question2']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No account found with that email address'
            ]);
        }
        $stmt->close();
        exit();
    }
    
    // Step 2: Verify security answers
    if ($step == '2') {
        // Verify session state
        if (!isset($_SESSION['recovery_email']) || 
            !isset($_SESSION['recovery_user_type']) || 
            !isset($_SESSION['recovery_step1_completed'])) {
            die(json_encode([
                'success' => false,
                'message' => 'Session expired. Please start over.'
            ]));
        }
        
        $user_type = clean_input($_POST['user_type']);
        $email = clean_input($_POST['email']);
        $answer1 = strtolower(clean_input($_POST['security_answer1']));
        $answer2 = strtolower(clean_input($_POST['security_answer2']));
        
        // Verify session consistency
        if ($email !== $_SESSION['recovery_email'] || 
            $user_type !== $_SESSION['recovery_user_type']) {
            die(json_encode([
                'success' => false,
                'message' => 'Invalid request. Please start over.'
            ]));
        }
        
        // Prepare appropriate query
        $table = ($user_type == 'customer') ? 'customers' : 'businesses';
        $email_field = ($user_type == 'customer') ? 'customer_email' : 'email';
        
        $stmt = $conn->prepare("SELECT security_answer1, security_answer2 FROM $table WHERE $email_field = ?");
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            die(json_encode([
                'success' => false,
                'message' => 'Database error. Please try again.'
            ]));
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify answers (case insensitive)
            $answer1_correct = password_verify($answer1, $user['security_answer1']);
            $answer2_correct = password_verify($answer2, $user['security_answer2']);
            
            if ($answer1_correct && $answer2_correct) {
                // Answers correct - allow password reset
                $_SESSION['recovery_step2_completed'] = true;
                $_SESSION['recovery_verified'] = true;
                
                // Reset attempts for this successful step
                $_SESSION['recovery_attempts'] = 0;
                
                echo json_encode(['success' => true]);
            } else {
                // Track failed attempts
                $_SESSION['recovery_attempts']++;
                
                echo json_encode([
                    'success' => false,
                    'message' => 'The security answers you provided do not match our records'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Account not found. Please start over.'
            ]);
        }
        $stmt->close();
        exit();
    }
    
    // Step 3: Reset password
    if ($step == '3') {
        // Verify all previous steps were completed
        if (!isset($_SESSION['recovery_email']) || 
            !isset($_SESSION['recovery_user_type']) || 
            !isset($_SESSION['recovery_step1_completed']) || 
            !isset($_SESSION['recovery_step2_completed']) || 
            !isset($_SESSION['recovery_verified'])) {
            die(json_encode([
                'success' => false,
                'message' => 'Session expired. Please start over.'
            ]));
        }
        
        $user_type = clean_input($_POST['user_type']);
        $email = clean_input($_POST['email']);
        $new_password = clean_input($_POST['new_password']);
        $confirm_password = clean_input($_POST['confirm_password']);
        
        // Verify session consistency
        if ($email !== $_SESSION['recovery_email'] || 
            $user_type !== $_SESSION['recovery_user_type']) {
            die(json_encode([
                'success' => false,
                'message' => 'Invalid request. Please start over.'
            ]));
        }
        
        // Verify passwords match
        if ($new_password !== $confirm_password) {
            echo json_encode([
                'success' => false,
                'message' => 'Passwords do not match'
            ]);
            exit();
        }
        
        // Validate password strength
        $password_valid = validate_password($new_password);
        if ($password_valid !== true) {
            echo json_encode([
                'success' => false,
                'message' => $password_valid
            ]);
            exit();
        }
        
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Prepare appropriate query
        $table = ($user_type == 'customer') ? 'customers' : 'businesses';
        $password_field = ($user_type == 'customer') ? 'customer_password' : 'password';
        $email_field = ($user_type == 'customer') ? 'customer_email' : 'email';
        
        $stmt = $conn->prepare("UPDATE $table SET $password_field = ? WHERE $email_field = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Password changed successfully - clear all recovery session data
            unset(
                $_SESSION['recovery_email'],
                $_SESSION['recovery_user_type'],
                $_SESSION['recovery_step1_completed'],
                $_SESSION['recovery_step2_completed'],
                $_SESSION['recovery_verified'],
                $_SESSION['recovery_attempts'],
                $_SESSION['last_attempt']
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Password has been reset successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update password. Please try again.'
            ]);
        }
        $stmt->close();
        exit();
    }
}

// Close database connection
$conn->close();

// If directly accessed
die(json_encode([
    'success' => false,
    'message' => 'Invalid access method'
]));
?>