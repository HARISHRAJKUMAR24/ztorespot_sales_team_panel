<?php
require_once "../../config/config.php";
require_once "../../lib/functions.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    
    // Get and sanitize POST data - with defaults for missing fields
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $business_name = trim($_POST['business_name'] ?? '');
    $seller_type = trim($_POST['seller_type'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $customer_response = trim($_POST['customer_response'] ?? '');
    $selected_plan = trim($_POST['selected_plan'] ?? '');
    $upgraded_plan = trim($_POST['upgraded_plan'] ?? '');
    $upgraded_duration = trim($_POST['upgraded_duration'] ?? '');
    $call_back_time = trim($_POST['call_back_time'] ?? '');
    $remembering_notes = trim($_POST['remembering_notes'] ?? '');
    $latest_update = trim($_POST['latest_update'] ?? '');
    $current_status = trim($_POST['current_status'] ?? '');
    $customer_queries = trim($_POST['customer_queries'] ?? '');
    $call_timing = trim($_POST['call_timing'] ?? '');
    $entry_date = trim($_POST['entry_date'] ?? '');
    
    // Default values for fields that might not be sent
    $registration_status = 'No'; // Default
    $plans_interested = 'None'; // Default
    $video_canva = ''; // Default
    $remarks = ''; // Default
    
    // Validate ID
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid seller ID']);
        exit;
    }
    
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
    
    // Check if record belongs to this user
    $check_sql = "SELECT id, phone_number FROM sales_person_sellers WHERE id = ? AND user_uid = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$id, $user_uid]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        echo json_encode(['status' => 'error', 'message' => 'Record not found or access denied']);
        exit;
    }
    
    // Check if phone number exists for other records
    if ($phone_number !== $existing['phone_number']) {
        $dup_sql = "SELECT id FROM sales_person_sellers WHERE phone_number = ? AND user_uid = ? AND id != ?";
        $dup_stmt = $pdo->prepare($dup_sql);
        $dup_stmt->execute([$phone_number, $user_uid, $id]);
        
        if ($dup_stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Phone number already exists for another record']);
            exit;
        }
    }
    
    // Determine registration status based on available data
    if ($seller_type == 'Register Seller' || 
        $customer_response == 'Plan Upgraded' || 
        $customer_response == 'Plan Interested') {
        $registration_status = 'Yes';
    }
    
    // Determine plans_interested based on available data
    if (!empty($selected_plan)) {
        $plans_interested = $selected_plan;
    } elseif (!empty($upgraded_plan)) {
        $plans_interested = $upgraded_plan;
    }
    
    // Determine final call timing
    $final_call_timing = !empty($call_timing) ? $call_timing : $call_back_time;
    
    // Update the record
    $sql = "UPDATE sales_person_sellers SET 
                work_details_update = ?,
                source_type = ?,
                registration_status = ?,
                phone_number = ?,
                plans_interested = ?,
                customer_response = ?,
                remembering_notes = ?,
                latest_update = ?,
                current_status = ?,
                customer_queries = ?,
                video_canva = ?,
                call_timing = ?,
                remarks = ?,
                entry_date = ?,
                updated_at = NOW()
            WHERE id = ? AND user_uid = ?";
    
    $stmt = $pdo->prepare($sql);
    
    $params = [
        $business_name,
        $seller_type,
        $registration_status,
        $phone_number,
        $plans_interested,
        $customer_response,
        $remembering_notes,
        $latest_update,
        $current_status,
        $customer_queries,
        $video_canva,  // Default empty string
        $final_call_timing,
        $remarks,      // Default empty string
        $entry_date,
        $id,
        $user_uid
    ];
    
    // Log the parameters for debugging
    error_log("Update params for ID $id: " . print_r($params, true));
    
    $result = $stmt->execute($params);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log("SQL Error on update: " . print_r($errorInfo, true));
        throw new Exception('Failed to update: ' . $errorInfo[2]);
    }
    
    // Check if any rows were affected
    $rowCount = $stmt->rowCount();
    
    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'message' => $rowCount > 0 ? 'Seller updated successfully' : 'No changes were made to the record',
        'id' => $id,
        'rowCount' => $rowCount
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error in update: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in update: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>