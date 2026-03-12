<?php
require_once "../../config/config.php";
require_once "../../lib/functions.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Temporarily enable for debugging

// Start output buffering
ob_start();

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
    
    // Log the customer_response value
    error_log("Attempting to insert customer_response: '" . $customer_response . "'");
    
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
    
    // Validate phone number
    if (!preg_match('/^\d{10}$/', $phone_number)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format']);
        exit;
    }
    
    // Check if phone number exists
    $check_sql = "SELECT id FROM sales_person_sellers WHERE phone_number = ? AND user_uid = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$phone_number, $user_uid]);
    
    if ($check_stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Phone number already exists']);
        exit;
    }
    
    // Determine registration status
    $registration_status = 'No';
    if ($seller_type == 'Register Seller' || 
        $customer_response == 'Plan Upgraded' || 
        $customer_response == 'Plan Interested') {
        $registration_status = 'Yes';
    }
    
    // Determine plans_interested
    $plans_interested = 'None';
    if (!empty($selected_plan)) {
        $plans_interested = $selected_plan;
    } elseif (!empty($upgraded_plan)) {
        $plans_interested = $upgraded_plan;
    }
    
    // Build remembering_notes
    $remembering_notes = [];
    if (!empty($call_duration)) {
        $remembering_notes[] = "Call Duration: " . $call_duration;
    }
    if (!empty($upgraded_duration)) {
        $remembering_notes[] = "Upgraded Duration: " . $upgraded_duration;
    }
    $remembering_notes_str = implode(". ", $remembering_notes);
    
    // Build latest_update
    $latest_update = '';
    switch($customer_response) {
        case 'Plan Upgraded':
            $latest_update = "Customer upgraded to " . $upgraded_plan . " for " . $upgraded_duration;
            break;
        case 'Plan Interested':
            $latest_update = "Customer interested in " . $selected_plan;
            break;
        case 'Later':
        case 'Call Back AT':
            $latest_update = "Customer asked to call back: " . $call_back_time;
            break;
        case 'Shedule':
            $latest_update = "Scheduled for: " . $call_back_time;
            break;
        default:
            $latest_update = $customer_response;
    }
    
    // First, let's check what values are allowed in the constraint
    // Run this query manually in phpMyAdmin to see the constraint
    // SHOW CREATE TABLE sales_person_sellers;
    
    // For now, let's try to insert with the exact value that worked before
    $sql = "INSERT INTO sales_person_sellers (
                user_uid, 
                work_details_update, 
                source_type, 
                registration_status, 
                phone_number, 
                plans_interested, 
                customer_response, 
                remembering_notes, 
                latest_update, 
                current_status, 
                customer_queries, 
                call_timing, 
                remarks,
                entry_date, 
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW()
            )";
    
    $stmt = $pdo->prepare($sql);
    
    $params = [
        $user_uid,
        $business_name,
        $seller_type,
        $registration_status,
        $phone_number,
        $plans_interested,
        $customer_response, // This is the value causing the issue
        $remembering_notes_str,
        $latest_update,
        $customer_status,
        $customer_queries,
        $call_back_time,
        $additional_notes
    ];
    
    // Log the parameters
    error_log("Insert params: " . print_r($params, true));
    
    $result = $stmt->execute($params);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log("SQL Error: " . print_r($errorInfo, true));
        throw new Exception('Failed to insert: ' . $errorInfo[2]);
    }
    
    $inserted_id = $pdo->lastInsertId();
    
    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Seller added successfully',
        'id' => $inserted_id
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>