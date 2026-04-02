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
    $customer_doubts = trim($_POST['customer_doubts'] ?? '');
    $refund_info = trim($_POST['refund_info'] ?? '');
    
    // IMPORTANT: Get user's input from remembering_notes field
    $remembering_notes_user = trim($_POST['remembering_notes'] ?? '');

    // Log received data for debugging
    error_log("=== Received POST data ===");
    error_log("Remembering Notes (User Input): " . $remembering_notes_user);
    error_log("Call Duration: " . $call_duration);
    error_log("Call Back Time: " . $call_back_time);

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
    } elseif ($customer_response == 'Refund' && !empty($refund_info)) {
        if (preg_match('/Plan: ([^,]+)/', $refund_info, $matches)) {
            $plans_interested = trim($matches[1]);
        }
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

    // Build plan_data JSON for tracking with doubts
    $plan_data = null;
    if (!empty($plan_name) && ($customer_response == 'Plan Interested' || $customer_response == 'Plan Upgraded')) {
        $plan_data_array = [
            'plan_id' => $plan_id,
            'plan_name' => $plan_name,
            'duration' => $plan_duration,
            'amount' => $final_plan_amount,
            'seller_id' => $seller_id ?: null,
            'is_custom_duration' => $is_custom_duration,
            'added_date' => date('Y-m-d H:i:s')
        ];

        // Add doubts to plan_data if present
        if (!empty($customer_doubts)) {
            $plan_data_array['customer_doubts'] = $customer_doubts;
        }

        $plan_data = json_encode($plan_data_array);
        error_log("Plan data JSON: " . $plan_data);
    }

    // ============================================
    // FIXED: Remembering Notes - ONLY save user input
    // Do NOT add any auto-generated content
    // ============================================
    $remembering_notes_final = '';
    
    if (!empty($remembering_notes_user)) {
        // Save exactly what user typed
        $remembering_notes_final = $remembering_notes_user;
        error_log("Saving user's remembering notes as is: " . $remembering_notes_final);
    } else {
        // If user didn't type anything, save empty string
        $remembering_notes_final = '';
        error_log("No remembering notes provided by user");
    }

    // ============================================
    // Call Timing - ONLY store call duration
    // ============================================
    $final_call_timing = '';
    if (!empty($call_duration)) {
        $final_call_timing = $call_duration;
        error_log("Call Timing set to: " . $final_call_timing);
    }

    // ============================================
    // Build latest_update - This can include callback info and call duration
    // ============================================
    $latest_update = '';
    switch ($customer_response) {
        case 'Plan Upgraded':
            $latest_update = "Customer upgraded to " . $plan_name . " for " . $plan_duration . " (₹" . number_format($final_plan_amount, 2) . ")";
            if (!empty($customer_doubts)) {
                $latest_update .= "\n📝 Doubts: " . $customer_doubts;
            }
            if (!empty($call_duration)) {
                $latest_update .= "\n⏱️ Call Duration: " . $call_duration;
            }
            break;
        case 'Plan Interested':
            $latest_update = "Customer interested in " . $plan_name . " (₹" . number_format($final_plan_amount, 2) . ")";
            if (!empty($customer_doubts)) {
                $latest_update .= "\n📝 Doubts: " . $customer_doubts;
            }
            if (!empty($call_duration)) {
                $latest_update .= "\n⏱️ Call Duration: " . $call_duration;
            }
            break;
        case 'Later':
        case 'Call Back AT':
            $latest_update = "Customer asked to call back: " . $call_back_time;
            if (!empty($call_duration)) {
                $latest_update .= "\n⏱️ Call Duration: " . $call_duration;
            }
            break;
        case 'Schedule':
            $latest_update = "Scheduled for: " . $call_back_time;
            if (!empty($call_duration)) {
                $latest_update .= "\n⏱️ Call Duration: " . $call_duration;
            }
            break;
        case 'Refund':
            $latest_update = $refund_info;
            if (!empty($call_duration)) {
                $latest_update .= "\n⏱️ Call Duration: " . $call_duration;
            }
            break;
        default:
            $latest_update = $customer_response;
            if (!empty($call_duration)) {
                $latest_update .= "\n⏱️ Call Duration: " . $call_duration;
            }
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
            plan_amount,
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
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW()
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
        $final_plan_amount,
        $plan_data,
        $products_uploaded,
        $customer_response,
        $remembering_notes_final,  // ONLY user input, nothing auto-added
        $latest_update,
        $customer_status,
        $customer_queries,
        $final_call_timing,        // ONLY call duration
        $additional_notes
    ];

    error_log("=== Final Insert Parameters ===");
    error_log("Remembering Notes (User Input Only): " . $remembering_notes_final);
    error_log("Call Timing: " . $final_call_timing);
    error_log("Latest Update: " . $latest_update);
    error_log(print_r($params, true));

    $result = $stmt->execute($params);

    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log("SQL Error: " . print_r($errorInfo, true));
        throw new Exception('Failed to insert: ' . $errorInfo[2]);
    }

    $inserted_id = $pdo->lastInsertId();
    error_log("Seller inserted with ID: $inserted_id");

    // ============================================
    // UPDATE TARGET SETTINGS - ONLY FOR PLAN UPGRADED
    // ============================================
    $target_updated = false;
    if ($final_plan_amount > 0 && $customer_response == 'Plan Upgraded') {
        error_log("=== UPDATING TARGET SETTINGS ===");
        $target_updated = updateTargetProgress($pdo, $user_uid, $final_plan_amount, $inserted_id, $plan_data, $plan_name, $plan_duration, $customer_doubts);
        error_log("Target update result: " . ($target_updated ? "Success" : "Failed"));
    }

    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Seller added successfully',
        'id' => $inserted_id,
        'plan_amount' => $final_plan_amount,
        'target_updated' => $target_updated,
        'remembering_notes_saved' => $remembering_notes_final,
        'call_timing_saved' => $final_call_timing
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
 * Update target progress for the user (ONLY called for Plan Upgraded)
 */
function updateTargetProgress($pdo, $user_uid, $amount, $seller_id, $plan_data, $plan_name, $plan_duration, $customer_doubts)
{
    try {
        error_log("=== UPDATING TARGET PROGRESS ===");
        error_log("User: $user_uid, Amount: $amount, Seller ID: $seller_id");

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
            error_log("Found active target - ID: {$target['id']}");
            error_log("Target Amount: {$target['target_amount']}");
            error_log("Current Achieved: {$target['achieved_amount']}");

            // Update achieved amount
            $new_achieved = $target['achieved_amount'] + $amount;
            $new_percentage = ($new_achieved / $target['target_amount']) * 100;
            $new_percentage = round($new_percentage, 2);

            error_log("New Achieved: $new_achieved");
            error_log("New Percentage: $new_percentage%");

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
                'customer_doubts' => $customer_doubts,
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
                error_log("✅ Target updated successfully!");
                return true;
            } else {
                error_log("❌ Failed to update target");
                return false;
            }
        } else {
            error_log("⚠️ No active target found for user: $user_uid");
            return false;
        }
    } catch (Exception $e) {
        error_log("❌ Error updating target progress: " . $e->getMessage());
        return false;
    }
}
?>