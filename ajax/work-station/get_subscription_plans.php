<?php
require_once "../../config/config.php";
require_once "../../lib/functions.php";

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
    
    // Get all active subscription plans with their full details
    $sql = "SELECT id, plan_name, duration, amount, gst_percentage, gst_amount, total_amount 
            FROM subscription_plans 
            WHERE status = 1 
            ORDER BY plan_name, 
                     FIELD(duration, '1 Month', '3 Months', '6 Months', '1 Year', '2 Years')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Loaded " . count($plans) . " subscription plans");
    
    echo json_encode([
        'status' => 'success',
        'data' => $plans
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>