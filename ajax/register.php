<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config/config.php";
require_once "../lib/functions.php";

// Set header to return JSON response
header('Content-Type: application/json');

// Check if it's POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "status" => "error",
        "msg" => "Invalid request method"
    ]);
    exit;
}

// Get and sanitize input data
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Remove any non-numeric characters from phone
$phone = preg_replace('/[^0-9]/', '', $phone);

// Validate required fields
if (empty($name)) {
    echo json_encode([
        "status" => "error",
        "msg" => "Name is required"
    ]);
    exit;
}

if (empty($phone)) {
    echo json_encode([
        "status" => "error",
        "msg" => "Phone number is required"
    ]);
    exit;
}

if (empty($password)) {
    echo json_encode([
        "status" => "error",
        "msg" => "Password is required"
    ]);
    exit;
}

// Validate phone number format
if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
    echo json_encode([
        "status" => "error",
        "msg" => "Phone number must be 10-15 digits"
    ]);
    exit;
}

// Validate email if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "msg" => "Invalid email format"
    ]);
    exit;
}

// Validate password length
if (strlen($password) < 6) {
    echo json_encode([
        "status" => "error",
        "msg" => "Password must be at least 6 characters"
    ]);
    exit;
}

try {
    // Get database connection
    $pdo = db();
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    // Register user using the function from functions.php
    $result = registerUser($pdo, $name, $phone, $email, $password);
    
    if ($result['success']) {
        echo json_encode([
            "status" => "success",
            "msg" => $result['message']
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "msg" => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Registration error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        "status" => "error",
        "msg" => "Registration failed. Please try again."
    ]);
}
?>