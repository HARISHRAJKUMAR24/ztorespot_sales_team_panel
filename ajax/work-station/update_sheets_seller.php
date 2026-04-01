<?php
// Set Indian timezone
date_default_timezone_set('Asia/Kolkata');

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
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $business_name = trim($_POST['business_name'] ?? '');
    $seller_type = trim($_POST['seller_type'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $seller_id = trim($_POST['seller_id'] ?? '');
    $customer_response = trim($_POST['customer_response'] ?? '');
    $plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
    $plan_name = trim($_POST['plan_name'] ?? '');
    $plan_duration = trim($_POST['plan_duration'] ?? '');
    $plan_amount = isset($_POST['plan_amount']) ? floatval($_POST['plan_amount']) : 0;
    $products_uploaded = isset($_POST['products_uploaded']) ? intval($_POST['products_uploaded']) : 0;
    $refund_info = trim($_POST['refund_info'] ?? '');
    $call_back_time = trim($_POST['call_back_time'] ?? '');
    $remembering_notes = trim($_POST['remembering_notes'] ?? '');
    $latest_update = trim($_POST['latest_update'] ?? '');
    $current_status = trim($_POST['current_status'] ?? '');
    $customer_queries = trim($_POST['customer_queries'] ?? '');
    $customer_doubts_json = trim($_POST['customer_doubts_json'] ?? '');
    $call_timing = trim($_POST['call_timing'] ?? '');
    $entry_date = trim($_POST['entry_date'] ?? '');

    // Default values
    $registration_status = 'No';
    $plans_interested = 'None';
    $video_canva = '';
    $remarks = '';

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

    // Check if record belongs to this user and get current data
    $check_sql = "SELECT * FROM sales_person_sellers WHERE id = ? AND user_uid = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$id, $user_uid]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(['status' => 'error', 'message' => 'Record not found or access denied']);
        exit;
    }

    // Store old plan amount for target update
    $old_plan_amount = floatval($existing['plan_amount'] ?? 0);
    $old_customer_response = $existing['customer_response'];

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

    // Determine registration status
    if ($seller_type == 'Register Seller' || $customer_response == 'Plan Upgraded' || $customer_response == 'Plan Interested') {
        $registration_status = 'Yes';
    }

    // Determine plans_interested
    if (!empty($plan_name)) {
        $plans_interested = $plan_name;
    } elseif ($customer_response == 'Refund' && !empty($refund_info)) {
        if (preg_match('/Plan: ([^,]+)/', $refund_info, $matches)) {
            $plans_interested = trim($matches[1]);
        }
    }

    // Determine final call timing
    $final_call_timing = !empty($call_timing) ? $call_timing : $call_back_time;

    // Clean remembering notes
    $clean_notes = $remembering_notes;
    $clean_notes = preg_replace('/Call Duration: [^\n]*(\n|$)/', '', $clean_notes);
    $clean_notes = preg_replace('/Upgraded Duration: [^\n]*(\n|$)/', '', $clean_notes);
    $clean_notes = preg_replace('/Plan Duration: [^\n]*(\n|$)/', '', $clean_notes);
    $clean_notes = preg_replace('/Plan Amount: ₹[^\n]*(\n|$)/', '', $clean_notes);
    $clean_notes = preg_replace('/Refund - Plan:.*?(\n|$)/', '', $clean_notes);
    $clean_notes = preg_replace('/{"customer_doubts":.*?}/s', '', $clean_notes);
    $clean_notes = preg_replace('/\n\s*\n/', "\n", $clean_notes);
    $clean_notes = trim($clean_notes);

    $final_remembering_notes = $clean_notes;

    // Add refund info if present
    if (!empty($refund_info) && $customer_response == 'Refund') {
        if (!empty($final_remembering_notes)) {
            $final_remembering_notes = $refund_info . "\n" . $final_remembering_notes;
        } else {
            $final_remembering_notes = $refund_info;
        }
    }

    // Add doubts JSON to notes
    if (!empty($customer_doubts_json) && ($customer_response == 'Plan Interested' || $customer_response == 'Plan Upgraded')) {
        if (!empty($final_remembering_notes)) {
            $final_remembering_notes = $final_remembering_notes . "\n\n" . $customer_doubts_json;
        } else {
            $final_remembering_notes = $customer_doubts_json;
        }
    }

    // Create plan_data JSON with doubts
    $plan_data = null;
    if (!empty($plan_name) && ($customer_response == 'Plan Interested' || $customer_response == 'Plan Upgraded')) {
        $plan_data_array = [
            'plan_id' => $plan_id,
            'plan_name' => $plan_name,
            'duration' => $plan_duration,
            'amount' => $plan_amount,
            'seller_id' => $seller_id ?: null,
            'added_date' => date('Y-m-d H:i:s')
        ];
        
        // Add doubts if present
        if (!empty($customer_doubts_json)) {
            $doubts_data = json_decode($customer_doubts_json, true);
            if ($doubts_data) {
                $plan_data_array['customer_doubts'] = $doubts_data;
            }
        }
        
        $plan_data = json_encode($plan_data_array);
    }

    // Create update history entry
    $history_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'timestamp_formatted' => date('d M Y, h:i A'),
        'user_uid' => $user_uid,
        'changes' => []
    ];

    // Track changes
    $fields_to_track = [
        'work_details_update' => ['label' => 'Business Name', 'old' => $existing['work_details_update'], 'new' => $business_name],
        'source_type' => ['label' => 'Seller Type', 'old' => $existing['source_type'], 'new' => $seller_type],
        'phone_number' => ['label' => 'Phone Number', 'old' => $existing['phone_number'], 'new' => $phone_number],
        'seller_id' => ['label' => 'Seller ID', 'old' => $existing['seller_id'] ?? '', 'new' => $seller_id],
        'customer_response' => ['label' => 'Customer Response', 'old' => $existing['customer_response'], 'new' => $customer_response],
        'plans_interested' => ['label' => 'Plan', 'old' => $existing['plans_interested'], 'new' => $plans_interested],
        'plan_duration' => ['label' => 'Duration', 'old' => $existing['plan_duration'], 'new' => $plan_duration],
        'plan_amount' => ['label' => 'Plan Amount', 'old' => $existing['plan_amount'] ?? 0, 'new' => $plan_amount],
        'products_uploaded' => ['label' => 'Products Uploaded', 'old' => $existing['products_uploaded'], 'new' => $products_uploaded],
        'current_status' => ['label' => 'Current Status', 'old' => $existing['current_status'], 'new' => $current_status],
        'call_timing' => ['label' => 'Call Timing', 'old' => $existing['call_timing'], 'new' => $final_call_timing]
    ];
    
    // Add doubts to history if changed
    if (!empty($customer_doubts_json)) {
        $old_doubts = '';
        if (preg_match('/"customer_doubts":\s*"([^"]*)"/', $existing['remembering_notes'], $matches)) {
            $old_doubts = $matches[1];
        }
        $doubts_data = json_decode($customer_doubts_json, true);
        $new_doubts = $doubts_data['customer_doubts'] ?? '';
        
        if ($old_doubts !== $new_doubts) {
            $fields_to_track['customer_doubts'] = [
                'label' => 'Customer Doubts',
                'old' => $old_doubts ?: '-',
                'new' => $new_doubts ?: '-'
            ];
        }
    }

    foreach ($fields_to_track as $field => $data) {
        $old_val = trim((string)($data['old'] ?? ''));
        $new_val = trim((string)($data['new'] ?? ''));

        if ($old_val !== $new_val) {
            $history_entry['changes'][$field] = [
                'field' => $data['label'],
                'old' => $old_val !== '' ? $old_val : '-',
                'new' => $new_val !== '' ? $new_val : '-'
            ];
        }
    }

    // Decode existing history
    $update_history = [];
    if (!empty($existing['update_history'])) {
        $update_history = json_decode($existing['update_history'], true);
        if (!is_array($update_history)) {
            $update_history = [];
        }
    }

    // Add new history entry if there are changes
    if (!empty($history_entry['changes'])) {
        array_unshift($update_history, $history_entry);
    }

    $update_history_json = !empty($update_history) ? json_encode($update_history, JSON_PRETTY_PRINT) : null;

    // Update the record
    $sql = "UPDATE sales_person_sellers SET 
                work_details_update = ?,
                source_type = ?,
                registration_status = ?,
                phone_number = ?,
                seller_id = ?,
                plans_interested = ?,
                plan_duration = ?,
                plan_amount = ?,
                plan_data = ?,
                products_uploaded = ?,
                customer_response = ?,
                remembering_notes = ?,
                latest_update = ?,
                current_status = ?,
                customer_queries = ?,
                video_canva = ?,
                call_timing = ?,
                remarks = ?,
                entry_date = ?,
                update_history = ?,
                updated_at = NOW()
            WHERE id = ? AND user_uid = ?";

    $stmt = $pdo->prepare($sql);

    $params = [
        $business_name,
        $seller_type,
        $registration_status,
        $phone_number,
        $seller_id,
        $plans_interested,
        $plan_duration,
        $plan_amount,
        $plan_data,
        $products_uploaded,
        $customer_response,
        $final_remembering_notes,
        $latest_update,
        $current_status,
        $customer_queries,
        $video_canva,
        $final_call_timing,
        $remarks,
        $entry_date,
        $update_history_json,
        $id,
        $user_uid
    ];

    error_log("Update params for ID $id: " . print_r($params, true));

    $result = $stmt->execute($params);

    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log("SQL Error on update: " . print_r($errorInfo, true));
        throw new Exception('Failed to update: ' . $errorInfo[2]);
    }

    $rowCount = $stmt->rowCount();

    // ============================================
    // UPDATE TARGET SETTINGS - ONLY FOR PLAN UPGRADED
    // ============================================
    $target_updated = false;
    $target_message = '';

    // Check if this is a Plan Upgraded and amount has changed
    if ($customer_response == 'Plan Upgraded' && $plan_amount > 0) {
        // Calculate the difference between new and old amount
        $amount_difference = $plan_amount - $old_plan_amount;
        
        if ($amount_difference != 0) {
            error_log("Updating target settings - User: $user_uid, Amount Difference: $amount_difference");
            
            // Call function to update target progress
            $target_updated = updateTargetProgressEdit($pdo, $user_uid, $amount_difference, $id, $plan_data, $plan_name, $plan_duration);
            $target_message = $target_updated ? " Target progress updated." : " Target progress could not be updated.";
        } else {
            $target_message = " No change in plan amount, target unchanged.";
        }
    } else {
        error_log("Skipping target update - Response: $customer_response, Amount: $plan_amount");
        $target_message = " Not a plan upgrade, target unchanged.";
    }

    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'message' => ($rowCount > 0 ? 'Seller updated successfully' : 'No changes were made') . $target_message,
        'id' => $id,
        'rowCount' => $rowCount,
        'target_updated' => $target_updated,
        'changes_made' => !empty($history_entry['changes']),
        'indian_time' => date('d M Y, h:i A')
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

/**
 * Update target progress for edit (with amount difference)
 */
function updateTargetProgressEdit($pdo, $user_uid, $amount_difference, $seller_id, $plan_data, $plan_name, $plan_duration) {
    try {
        error_log("=== UPDATING TARGET PROGRESS FROM EDIT ===");
        error_log("User: $user_uid, Amount Difference: $amount_difference");
        
        // Get current active target
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
            // Update achieved amount with the difference
            $new_achieved = $target['achieved_amount'] + $amount_difference;
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
            
            $plan_info = json_decode($plan_data, true);
            
            $new_plan_entry = [
                'seller_id' => $seller_id,
                'plan_name' => $plan_name,
                'duration' => $plan_duration,
                'amount' => $amount_difference,
                'action' => 'edit_update',
                'added_date' => date('Y-m-d H:i:s')
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
                error_log("New Achieved: $new_achieved, New Percentage: $new_percentage%");
                return true;
            }
        } else {
            error_log("⚠️ No active target found for user: $user_uid");
        }
        return false;
        
    } catch (Exception $e) {
        error_log("❌ Error updating target progress: " . $e->getMessage());
        return false;
    }
}
?>