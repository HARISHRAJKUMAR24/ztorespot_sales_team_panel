<?php
require_once "../../config/config.php";
require_once "../../lib/functions.php";

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production

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
    
    // Get and sanitize POST data from the form
    $business_name = trim($_POST['business_name'] ?? ''); // This will go to work_details_update
    $seller_type = trim($_POST['seller_type'] ?? ''); // This will go to source_type
    $phone_number = trim($_POST['phone_number'] ?? '');
    $customer_response = trim($_POST['customer_response'] ?? ''); // This will go to customer_response
    $selected_plan = trim($_POST['selected_plan'] ?? ''); // This will go to plans_interested
    $upgraded_plan = trim($_POST['upgraded_plan'] ?? ''); // This will be combined with notes
    $upgraded_duration = trim($_POST['upgraded_duration'] ?? ''); // This will be combined with notes
    $call_back_time = trim($_POST['call_back_time'] ?? ''); // This will go to call_timing
    $customer_queries = trim($_POST['customer_queries'] ?? ''); // This will go to customer_queries
    $customer_status = trim($_POST['customer_status'] ?? ''); // This will go to current_status
    $call_duration = trim($_POST['call_duration'] ?? ''); // This will go to remembering_notes or latest_update
    $additional_notes = trim($_POST['additional_notes'] ?? ''); // This will go to remarks
    
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
    
    // Check if phone number already exists for this user in sales_person_sellers table
    $check_sql = "SELECT id FROM sales_person_sellers WHERE phone_number = ? AND user_uid = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$phone_number, $user_uid]);
    
    if ($check_stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'This phone number already exists in your list']);
        exit;
    }
    
    // Determine registration status based on seller_type or customer_response
    $registration_status = 'No'; // Default
    if ($seller_type == 'Register Seller' || $customer_response == 'Plan Upgraded' || $customer_response == 'Plan Interested') {
        $registration_status = 'Yes';
    }
    
    // Determine plans_interested based on selected_plan or upgraded_plan
    $plans_interested = '';
    if (!empty($selected_plan)) {
        $plans_interested = $selected_plan;
    } elseif (!empty($upgraded_plan)) {
        $plans_interested = $upgraded_plan;
    } else {
        $plans_interested = 'None';
    }
    
    // Combine notes for remembering_notes and latest_update
    $remembering_notes = '';
    $latest_update = '';
    
    if (!empty($call_duration)) {
        $remembering_notes .= "Call Duration: " . $call_duration . ". ";
    }
    
    if (!empty($upgraded_duration)) {
        $remembering_notes .= "Upgraded Duration: " . $upgraded_duration . ". ";
    }
    
    if (!empty($additional_notes)) {
        $remarks = $additional_notes;
    } else {
        $remarks = '';
    }
    
    // Set latest_update based on customer_response
    if ($customer_response == 'Plan Upgraded') {
        $latest_update = "Customer upgraded to " . $upgraded_plan . " for " . $upgraded_duration;
    } elseif ($customer_response == 'Plan Interested') {
        $latest_update = "Customer interested in " . $selected_plan;
    } elseif ($customer_response == 'Later' || $customer_response == 'Call Back AT') {
        $latest_update = "Customer asked to call back: " . $call_back_time;
    } elseif ($customer_response == 'Shedule') {
        $latest_update = "Scheduled for: " . $call_back_time;
    } else {
        $latest_update = $customer_response;
    }
    
    // Insert data into sales_person_sellers table
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
    $result = $stmt->execute([
        $user_uid,                      // user_uid
        $business_name,                  // work_details_update
        $seller_type,                    // source_type
        $registration_status,            // registration_status
        $phone_number,                    // phone_number
        $plans_interested,                // plans_interested
        $customer_response,               // customer_response
        $remembering_notes,               // remembering_notes
        $latest_update,                    // latest_update
        $customer_status,                  // current_status
        $customer_queries,                 // customer_queries
        $call_back_time,                    // call_timing
        $remarks                             // remarks
    ]);
    
    if (!$result) {
        throw new Exception('Failed to insert record');
    }
    
    $inserted_id = $pdo->lastInsertId();
    
    // Clear output buffer and send success response
    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Seller added successfully to follow-up list',
        'id' => $inserted_id
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error in workstation_add_seller.php: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred. Please try again.'
    ]);
} catch (Exception $e) {
    error_log("General Error in workstation_add_seller.php: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>