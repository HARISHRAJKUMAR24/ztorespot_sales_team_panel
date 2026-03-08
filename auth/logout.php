<?php
// auth/logout.php
require_once "../config/config.php";
require_once "../lib/functions.php";

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear remember token from database if user is logged in
if (isset($_SESSION['user_id'])) {
    $pdo = db();
    if ($pdo) {
        // Clear the remember token from database
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear remember me cookie
setcookie("remember_token", "", time() - 3600, "/");

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: " . BASE_URL . "auth/login.php");
exit;
?>