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

    // Get and sanitize POST data
    $business_name = trim($_POST['business_name'] ?? '');
    $seller_type = trim($_POST['seller_type'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $seller_id = trim($_POST['seller_id'] ?? '');
    $customer_response = trim($_POST['customer_response'] ?? '');
    $plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
    $plan_name = trim($_POST['plan_name'] ?? '');
    $plan_duration = trim($_POST['plan_duration'] ?? '');
    $plan_amount = isset($_POST['plan_amount']) ? floatval($_POST['plan_amount']) : 0;
    $is_custom_duration = isset($_POST['is_custom_duration']) ? intval($_POST['is_custom_duration']) : 0;
    $products_uploaded = isset($_POST['products_uploaded']) ? intval($_POST['products_uploaded']) : 0;
    $call_back_time = trim($_POST['call_back_time'] ?? '');
    $customer_queries = trim($_POST['customer_queries'] ?? '');
    $customer_status = trim($_POST['customer_status'] ?? '');
    $call_duration = trim($_POST['call_duration'] ?? '');
    $additional_notes = trim($_POST['additional_notes'] ?? '');

    // Log received data for debugging
    error_log("=== Received POST data ===");
    error_log(print_r($_POST, true));
    error_log("Plan ID: $plan_id, Plan Name: $plan_name, Duration: $plan_duration, Amount: $plan_amount, Is Custom: $is_custom_duration");

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
    if (
        $seller_type == 'Register Seller' ||
        $customer_response == 'Plan Upgraded' ||
        $customer_response == 'Plan Interested'
    ) {
        $registration_status = 'Yes';
    }

    // Determine plans_interested
    $plans_interested = 'None';
    if (!empty($plan_name)) {
        $plans_interested = $plan_name;
    }

    // If plan_id is provided, get the amount from database
    $final_plan_amount = $plan_amount;
    if ($plan_id > 0 && !$is_custom_duration) {
        $plan_sql = "SELECT plan_name, duration, total_amount FROM subscription_plans WHERE id = ? AND status = 1";
        $plan_stmt = $pdo->prepare($plan_sql);
        $plan_stmt->execute([$plan_id]);
        $plan_data_db = $plan_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan_data_db) {
            $plan_name = $plan_data_db['plan_name'];
            $plan_duration = $plan_data_db['duration'];
            $final_plan_amount = floatval($plan_data_db['total_amount']);
            error_log("Fetched from DB - Plan: $plan_name, Duration: $plan_duration, Amount: $final_plan_amount");
        }
    }

    // Build plan_data JSON for tracking
    $plan_data = null;
    if (!empty($plan_name)) {
        $plan_data_array = [
            'plan_id' => $plan_id,
            'plan_name' => $plan_name,
            'duration' => $plan_duration,
            'amount' => $final_plan_amount,
            'seller_id' => $seller_id ?: null,
            'is_custom_duration' => $is_custom_duration,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $plan_data = json_encode($plan_data_array);
        error_log("Plan data JSON: " . $plan_data);
    }

    // Build remembering_notes
    $remembering_notes = [];
    if (!empty($call_duration)) {
        $remembering_notes[] = "Call Duration: " . $call_duration;
    }
    if (!empty($plan_duration)) {
        $remembering_notes[] = "Plan Duration: " . $plan_duration;
    }
    if ($final_plan_amount > 0) {
        $remembering_notes[] = "Plan Amount: ₹" . number_format($final_plan_amount, 2);
    }
    $remembering_notes_str = implode(". ", $remembering_notes);

    // Build latest_update
    $latest_update = '';
    switch ($customer_response) {
        case 'Plan Upgraded':
            $latest_update = "Customer upgraded to " . $plan_name . " for " . $plan_duration . " (₹" . number_format($final_plan_amount, 2) . ")";
            break;
        case 'Plan Interested':
            $latest_update = "Customer interested in " . $plan_name . " (₹" . number_format($final_plan_amount, 2) . ")";
            break;
        case 'Later':
        case 'Call Back AT':
            $latest_update = "Customer asked to call back: " . $call_back_time;
            break;
        case 'Schedule':
            $latest_update = "Scheduled for: " . $call_back_time;
            break;
        default:
            $latest_update = $customer_response;
    }

    // Insert into sales_person_sellers
    $sql = "INSERT INTO sales_person_sellers (
                user_uid, 
                work_details_update, 
                source_type, 
                registration_status, 
                phone_number, 
                seller_id,
                plans_interested, 
                plan_duration,
                plan_data,
                products_uploaded,
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
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW()
            )";

    $stmt = $pdo->prepare($sql);

    $params = [
        $user_uid,
        $business_name,
        $seller_type,
        $registration_status,
        $phone_number,
        $seller_id,
        $plans_interested,
        $plan_duration,
        $plan_data,
        $products_uploaded,
        $customer_response,
        $remembering_notes_str,
        $latest_update,
        $customer_status,
        $customer_queries,
        $call_back_time,
        $additional_notes
    ];

    error_log("Insert params: " . print_r($params, true));

    $result = $stmt->execute($params);

    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log("SQL Error: " . print_r($errorInfo, true));
        throw new Exception('Failed to insert: ' . $errorInfo[2]);
    }

    $inserted_id = $pdo->lastInsertId();
    error_log("Seller inserted with ID: $inserted_id");

    // Update target progress if plan amount > 0 and registration status is Yes
    if ($final_plan_amount > 0 && $registration_status == 'Yes') {
        error_log("Updating target progress - Amount: $final_plan_amount, Registration Status: $registration_status");
        updateTargetProgress($pdo, $user_uid, $final_plan_amount, $inserted_id, $plan_data, $plan_name, $plan_duration);
    } else {
        error_log("Skipping target update - Amount: $final_plan_amount, Registration Status: $registration_status");
    }

    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Seller added successfully',
        'id' => $inserted_id,
        'plan_amount' => $final_plan_amount
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

/**
 * Update target progress for the user
 */
function updateTargetProgress($pdo, $user_uid, $amount, $seller_id, $plan_data, $plan_name, $plan_duration) {
    try {
        error_log("=== Updating Target Progress ===");
        error_log("User: $user_uid, Amount: $amount, Seller ID: $seller_id, Plan: $plan_name, Duration: $plan_duration");
        
        // Get current active target for the user
        $target_sql = "SELECT id, target_amount, achieved_amount, achievement_percentage, plan_data 
                       FROM target_settings 
                       WHERE user_uid = ? 
                       AND status = 'active' 
                       AND start_date <= CURDATE() 
                       AND end_date >= CURDATE()";
        $target_stmt = $pdo->prepare($target_sql);
        $target_stmt->execute([$user_uid]);
        $target = $target_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($target) {
            error_log("Found active target - ID: {$target['id']}, Target Amount: {$target['target_amount']}, Current Achieved: {$target['achieved_amount']}");
            
            // Update achieved amount
            $new_achieved = $target['achieved_amount'] + $amount;
            $new_percentage = ($new_achieved / $target['target_amount']) * 100;
            $new_percentage = round($new_percentage, 2);
            
            // Update plan_data JSON
            $existing_plan_data = [];
            if (!empty($target['plan_data'])) {
                $existing_plan_data = json_decode($target['plan_data'], true);
                if (!is_array($existing_plan_data)) {
                    $existing_plan_data = [];
                }
            }
            
            // Parse the plan data
            $plan_info = json_decode($plan_data, true);
            
            $new_plan_entry = [
                'seller_id' => $seller_id,
                'plan_name' => $plan_name,
                'duration' => $plan_duration,
                'amount' => $amount,
                'added_date' => date('Y-m-d H:i:s'),
                'plan_data' => $plan_info
            ];
            $existing_plan_data[] = $new_plan_entry;
            $updated_plan_data = json_encode($existing_plan_data, JSON_PRETTY_PRINT);
            
            // Update target settings
            $update_sql = "UPDATE target_settings 
                           SET achieved_amount = ?, 
                               achievement_percentage = ?,
                               plan_data = ?
                           WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_result = $update_stmt->execute([$new_achieved, $new_percentage, $updated_plan_data, $target['id']]);
            
            if ($update_result) {
                error_log("Target updated successfully - New Achieved: $new_achieved, Percentage: $new_percentage%");
            } else {
                error_log("Failed to update target");
            }
        } else {
            error_log("No active target found for user: $user_uid");
        }
    } catch (Exception $e) {
        error_log("Error updating target progress: " . $e->getMessage());
    }
}
?>