<?php
// ajax/login.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once "../../config/config.php";
require_once "../../lib/functions.php";

// Check if it's POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method"
    ]);
    exit;
}

// Get input
$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Please enter both login and password"
    ]);
    exit;
}

try {
    $pdo = db();
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }

    // Check if login is email or phone
    $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

    // Normalize phone number if not email
    if (!$isEmail) {
        $login = preg_replace('/[^0-9]/', '', $login);
    }

    // Find user
    if ($isEmail) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$login]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$login]);
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_uid'] = $user['user_uid'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;

        session_regenerate_id(true);

        // CREATE REMEMBER TOKEN
        $token = createRememberToken($pdo, $user['id']);
        if ($token) {
            // Set cookie for 30 days
            setcookie(
                "remember_token",
                $token,
                time() + (60 * 60 * 24 * 30), // 30 days
                "/"
            );
            error_log("✓ Token saved for user: " . $user['id'] . " - Token: " . $token);
        } else {
            error_log("✗ Failed to save token for user: " . $user['id']);
        }

        echo json_encode([
            "status" => "success",
            "message" => "Login successful"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid login credentials"
        ]);
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "An error occurred"
    ]);
}
?>