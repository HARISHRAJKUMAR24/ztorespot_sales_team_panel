<?php
require_once "../../../config/config.php";
require_once "../../../lib/functions.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

try {
    $pdo = db();
    $user_uid = $_SESSION['user_uid'];
    
    // Get seller ID
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid seller ID']);
        exit;
    }
    
    // Fetch seller details
    $sql = "SELECT * FROM sales_person_sellers WHERE id = ? AND user_uid = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $user_uid]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seller) {
        echo json_encode(['status' => 'error', 'message' => 'Seller not found']);
        exit;
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $seller
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred'
    ]);
}
?>