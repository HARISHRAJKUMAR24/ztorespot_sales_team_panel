<?php
require_once "../../config/config.php";
require_once "../../lib/functions.php";

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

try {
    $pdo = db();
    $user_uid = $_SESSION['user_uid'];
    
    // Get POST data
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $business_name = trim($_POST['business_name'] ?? '');
    $seller_type = trim($_POST['seller_type'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $customer_response = trim($_POST['customer_response'] ?? '');
    $selected_plan = trim($_POST['selected_plan'] ?? '');
    $upgraded_plan = trim($_POST['upgraded_plan'] ?? '');
    $upgraded_duration = trim($_POST['upgraded_duration'] ?? '');
    $call_back_time = trim($_POST['call_back_time'] ?? '');
    $customer_queries = trim($_POST['customer_queries'] ?? '');
    $customer_status = trim($_POST['customer_status'] ?? '');
    $call_duration = trim($_POST['call_duration'] ?? '');
    $additional_notes = trim($_POST['additional_notes'] ?? '');
    
    // Validate
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        exit;
    }
    
    if (empty($business_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Business name is required']);
        exit;
    }
    
    if (empty($phone_number)) {
        echo json_encode(['status' => 'error', 'message' => 'Phone number is required']);
        exit;
    }
    
    if (empty($customer_response)) {
        echo json_encode(['status' => 'error', 'message' => 'Customer response is required']);
        exit;
    }
    
    if (!preg_match('/^\d{10}$/', $phone_number)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format']);
        exit;
    }
    
    // Check if phone number exists for another seller
    $check_sql = "SELECT id FROM sellers_workstation WHERE phone_number = ? AND user_uid = ? AND id != ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$phone_number, $user_uid, $id]);
    
    if ($check_stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'This phone number already exists for another seller']);
        exit;
    }
    
    // Update
    $sql = "UPDATE sellers_workstation SET
                business_name = ?,
                seller_type = ?,
                phone_number = ?,
                customer_response = ?,
                selected_plan = ?,
                upgraded_plan = ?,
                upgraded_duration = ?,
                call_back_time = ?,
                customer_queries = ?,
                customer_status = ?,
                call_duration = ?,
                additional_notes = ?
            WHERE id = ? AND user_uid = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $business_name, $seller_type, $phone_number, $customer_response,
        $selected_plan, $upgraded_plan, $upgraded_duration, $call_back_time,
        $customer_queries, $customer_status, $call_duration, $additional_notes,
        $id, $user_uid
    ]);
    
    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Seller updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update seller']);
    }
    
} catch (PDOException $e) {
    error_log("Database Error in workstation_update_seller.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General Error in workstation_update_seller.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
?>