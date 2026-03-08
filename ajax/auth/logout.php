<?php
// ajax/logout.php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON
header('Content-Type: application/json');

// Include database configuration and functions
require_once "../../config/config.php";
require_once "../../lib/functions.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit;
}

try {
    // Clear remember token from database
    $result = clearRememberToken($_SESSION['user_id']);
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    echo json_encode([
        "status" => "success",
        "message" => "Logged out successfully"
    ]);
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Logout failed"
    ]);
}
?>