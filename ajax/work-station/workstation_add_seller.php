<?php
require_once "../../config/config.php";
require_once "../../lib/functions.php";

header('Content-Type: application/json');

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
    
    // Get and sanitize POST data
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
    
    // Validate required fields
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
    
    // Validate phone number format
    if (!preg_match('/^\d{10}$/', $phone_number)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format. Please enter 10 digits.']);
        exit;
    }
    
    // Validate based on customer response
    if ($customer_response === 'Plan Interested' && empty($selected_plan)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a plan']);
        exit;
    }
    
    if ($customer_response === 'Plan Upgraded') {
        if (empty($upgraded_plan)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select the upgraded plan']);
            exit;
        }
        if (empty($upgraded_duration)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select the duration']);
            exit;
        }
    }
    
    if ($customer_response === 'Later' && empty($call_back_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select call back time']);
        exit;
    }
    
    // Check if phone number already exists for this user
    $check_sql = "SELECT id FROM sellers_workstation WHERE phone_number = ? AND user_uid = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$phone_number, $user_uid]);
    
    if ($check_stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'This phone number already exists in your list']);
        exit;
    }
    
    // Insert data
    $sql = "INSERT INTO sellers_workstation (
                user_uid, business_name, seller_type, phone_number, customer_response,
                selected_plan, upgraded_plan, upgraded_duration, call_back_time,
                customer_queries, customer_status, call_duration, additional_notes,
                entry_date, created_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                CURDATE(), NOW()
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_uid, $business_name, $seller_type, $phone_number, $customer_response,
        $selected_plan, $upgraded_plan, $upgraded_duration, $call_back_time,
        $customer_queries, $customer_status, $call_duration, $additional_notes
    ]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Seller added successfully',
        'id' => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error in workstation_add_seller.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred. Please try again.'
    ]);
} catch (Exception $e) {
    error_log("General Error in workstation_add_seller.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>