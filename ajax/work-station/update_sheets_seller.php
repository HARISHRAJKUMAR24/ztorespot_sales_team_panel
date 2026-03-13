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
    $customer_response = trim($_POST['customer_response'] ?? '');
    $selected_plan = trim($_POST['selected_plan'] ?? '');
    $upgraded_plan = trim($_POST['upgraded_plan'] ?? '');
    $upgraded_duration = trim($_POST['upgraded_duration'] ?? '');
    $products_uploaded = isset($_POST['products_uploaded']) ? intval($_POST['products_uploaded']) : 0;
    $refund_info = trim($_POST['refund_info'] ?? '');
    $call_back_time = trim($_POST['call_back_time'] ?? '');
    $remembering_notes = trim($_POST['remembering_notes'] ?? '');
    $latest_update = trim($_POST['latest_update'] ?? '');
    $current_status = trim($_POST['current_status'] ?? '');
    $customer_queries = trim($_POST['customer_queries'] ?? '');
    $call_timing = trim($_POST['call_timing'] ?? '');
    $entry_date = trim($_POST['entry_date'] ?? '');
    
    // Default values for fields that might not be sent
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
    } elseif ($customer_response == 'Refund' && !empty($refund_info)) {
        // Extract plan from refund info
        if (preg_match('/Plan: ([^,]+)/', $refund_info, $matches)) {
            $plans_interested = trim($matches[1]);
        }
    }
    
    // Determine final call timing
    $final_call_timing = !empty($call_timing) ? $call_timing : $call_back_time;
    
    // IMPORTANT: Clean the remembering notes - remove any auto-generated content
    // This ensures only manually entered notes are preserved
    
    // 1. Remove any "Call Duration: ..." lines
    $clean_notes = preg_replace('/Call Duration: [^\n]*(\n|$)/', '', $remembering_notes);
    // 2. Remove any "Upgraded Duration: ..." lines
    $clean_notes = preg_replace('/Upgraded Duration: [^\n]*(\n|$)/', '', $clean_notes);
    // 3. Remove any duplicate refund info (will be added back properly)
    $clean_notes = preg_replace('/Refund - Plan:.*?(\n|$)/', '', $clean_notes);
    // 4. Remove empty lines
    $clean_notes = preg_replace('/\n\s*\n/', "\n", $clean_notes);
    // 5. Trim
    $clean_notes = trim($clean_notes);
    
    // Now build the final remembering notes with ONLY the clean user notes
    $final_remembering_notes = $clean_notes;
    
    // Add refund info if present (as a single line, not duplicated)
    if (!empty($refund_info) && $customer_response == 'Refund') {
        if (!empty($final_remembering_notes)) {
            $final_remembering_notes = $refund_info . "\n" . $final_remembering_notes;
        } else {
            $final_remembering_notes = $refund_info;
        }
    }
    
    // DO NOT add Call Duration or Upgraded Duration to remembering notes anymore
    // These are stored in dedicated columns: call_timing and plan_duration
    
    // Create update history entry with Indian time
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
        'customer_response' => ['label' => 'Customer Response', 'old' => $existing['customer_response'], 'new' => $customer_response],
        'plans_interested' => ['label' => 'Plan', 'old' => $existing['plans_interested'], 'new' => $plans_interested],
        'plan_duration' => ['label' => 'Duration', 'old' => $existing['plan_duration'], 'new' => $upgraded_duration],
        'products_uploaded' => ['label' => 'Products Uploaded', 'old' => $existing['products_uploaded'], 'new' => $products_uploaded],
        'current_status' => ['label' => 'Current Status', 'old' => $existing['current_status'], 'new' => $current_status],
        'call_timing' => ['label' => 'Call Timing', 'old' => $existing['call_timing'], 'new' => $final_call_timing]
    ];
    
    foreach ($fields_to_track as $field => $data) {
        // Convert to string for comparison to handle null/empty properly
        $old_val = (string)$data['old'];
        $new_val = (string)$data['new'];
        
        if ($old_val !== $new_val) {
            $history_entry['changes'][$field] = [
                'field' => $data['label'],
                'old' => $data['old'],
                'new' => $data['new']
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
    
    // Add new history entry only if there are changes
    if (!empty($history_entry['changes'])) {
        array_unshift($update_history, $history_entry); // Add to beginning for latest first
    }
    
    // Encode history back to JSON
    $update_history_json = !empty($update_history) ? json_encode($update_history, JSON_PRETTY_PRINT) : null;
    
    // Update the record
    $sql = "UPDATE sales_person_sellers SET 
                work_details_update = ?,
                source_type = ?,
                registration_status = ?,
                phone_number = ?,
                plans_interested = ?,
                plan_duration = ?,
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
        $plans_interested,
        $upgraded_duration,
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
        'rowCount' => $rowCount,
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
?>