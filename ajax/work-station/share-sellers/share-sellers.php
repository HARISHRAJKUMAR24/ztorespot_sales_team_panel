<?php
require_once "../../../config/config.php";
require_once "../../../lib/functions.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
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
    $shared_by_user_uid = $_SESSION['user_uid'];

    // Log received POST data for debugging
    error_log("POST data: " . print_r($_POST, true));

    // Get POST data
    $share_option = $_POST['share_option'] ?? '';
    $shared_with_users = isset($_POST['shared_with_users']) ? $_POST['shared_with_users'] : [];
    $notes = trim($_POST['notes'] ?? '');
    $share_to_all = isset($_POST['share_to_all']) ? filter_var($_POST['share_to_all'], FILTER_VALIDATE_BOOLEAN) : false;

    // Log the processed data
    error_log("share_option: " . $share_option);
    error_log("shared_with_users: " . print_r($shared_with_users, true));
    error_log("share_to_all: " . ($share_to_all ? 'true' : 'false'));

    // Validate common fields
    if (!$share_to_all && empty($shared_with_users)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select at least one user to share with']);
        exit;
    }

    // If share_to_all is true, get all users except current user
    if ($share_to_all) {
        $stmt = $pdo->prepare("SELECT user_uid FROM users WHERE user_uid != ?");
        $stmt->execute([$shared_by_user_uid]);
        $shared_with_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Share to all users: " . print_r($shared_with_users, true));
    }

    // Ensure shared_with_users is an array
    if (!is_array($shared_with_users)) {
        $shared_with_users = [$shared_with_users];
    }

    $success_count = 0;
    $failed_users = [];
    $total_shares = 0;

    // Begin transaction
    $pdo->beginTransaction();

    if ($share_option === 'existing') {
        // Share existing sellers (multiple)
        $seller_ids = isset($_POST['seller_ids']) ? $_POST['seller_ids'] : [];
        
        // Ensure seller_ids is an array
        if (!is_array($seller_ids)) {
            $seller_ids = [$seller_ids];
        }
        
        error_log("seller_ids: " . print_r($seller_ids, true));
        
        if (empty($seller_ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select at least one seller to share']);
            exit;
        }

        // Get all selected sellers details
        $placeholders = implode(',', array_fill(0, count($seller_ids), '?'));
        $params = $seller_ids;
        $params[] = $shared_by_user_uid;
        
        $stmt = $pdo->prepare("
            SELECT id, work_details_update as customer_name, phone_number, customer_response 
            FROM sales_person_sellers 
            WHERE id IN ($placeholders) AND user_uid = ?
        ");
        $stmt->execute($params);
        $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("Found sellers: " . print_r($sellers, true));

        if (empty($sellers)) {
            echo json_encode(['status' => 'error', 'message' => 'No valid sellers found']);
            exit;
        }

        // Prepare insert statement
        $sql = "INSERT INTO shared_sellers (
                    seller_id, shared_by_user_uid, shared_with_user_uid, 
                    customer_name, phone_number, customer_response, notes, status, shared_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

        $insert_stmt = $pdo->prepare($sql);

        // Insert for each seller and each user
        foreach ($sellers as $seller) {
            foreach ($shared_with_users as $shared_with_user_uid) {
                $shared_with_user_uid = trim($shared_with_user_uid);
                
                // Skip if sharing with self
                if ($shared_with_user_uid === $shared_by_user_uid) {
                    continue;
                }

                // Check if recipient exists
                $check_user = $pdo->prepare("SELECT user_uid FROM users WHERE user_uid = ?");
                $check_user->execute([$shared_with_user_uid]);
                if (!$check_user->fetch()) {
                    if (!in_array($shared_with_user_uid, $failed_users)) {
                        $failed_users[] = $shared_with_user_uid;
                    }
                    continue;
                }

                $params = [
                    $seller['id'],
                    $shared_by_user_uid,
                    $shared_with_user_uid,
                    $seller['customer_name'],
                    $seller['phone_number'],
                    $seller['customer_response'],
                    $notes
                ];

                if ($insert_stmt->execute($params)) {
                    $success_count++;
                    error_log("Inserted share for seller {$seller['id']} to user $shared_with_user_uid");
                } else {
                    error_log("Failed to insert share for seller {$seller['id']} to user $shared_with_user_uid");
                }
                $total_shares++;
            }
        }
    } else {
        // Create new seller data (single seller with manual ID)
        $seller_id = isset($_POST['seller_id']) ? intval($_POST['seller_id']) : 0;
        $customer_name = trim($_POST['customer_name'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');
        $customer_response = trim($_POST['customer_response'] ?? '');

        error_log("New seller data - ID: $seller_id, Name: $customer_name, Phone: $phone_number, Response: $customer_response");

        if (!$seller_id || $seller_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Valid seller ID is required']);
            exit;
        }

        if (empty($customer_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Customer name is required']);
            exit;
        }

        if (empty($phone_number)) {
            echo json_encode(['status' => 'error', 'message' => 'Phone number is required']);
            exit;
        }

        if (!preg_match('/^\d{10}$/', $phone_number)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format. Please enter 10 digits.']);
            exit;
        }

        if (empty($customer_response)) {
            echo json_encode(['status' => 'error', 'message' => 'Customer response is required']);
            exit;
        }

        // Check if seller ID already exists
        $check_seller = $pdo->prepare("SELECT id FROM sales_person_sellers WHERE id = ? AND user_uid = ?");
        $check_seller->execute([$seller_id, $shared_by_user_uid]);
        $seller_exists = $check_seller->fetch();
        
        if (!$seller_exists) {
            // Insert the new seller into sales_person_sellers table only if it doesn't exist
            $insert_seller_sql = "INSERT INTO sales_person_sellers (id, user_uid, work_details_update, phone_number, customer_response, created_at) 
                                  VALUES (?, ?, ?, ?, ?, NOW())";
            $insert_seller_stmt = $pdo->prepare($insert_seller_sql);
            
            try {
                $insert_seller_stmt->execute([$seller_id, $shared_by_user_uid, $customer_name, $phone_number, $customer_response]);
                error_log("New seller inserted with ID: $seller_id");
            } catch (PDOException $e) {
                error_log("Seller insert warning: " . $e->getMessage());
                // Continue anyway - we can still share even if insert fails
            }
        } else {
            error_log("Seller ID $seller_id already exists, skipping insert");
        }

        // Prepare insert statement for sharing
        $sql = "INSERT INTO shared_sellers (
                    seller_id, shared_by_user_uid, shared_with_user_uid, 
                    customer_name, phone_number, customer_response, notes, status, shared_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

        $insert_stmt = $pdo->prepare($sql);

        // Insert for each selected user
        foreach ($shared_with_users as $shared_with_user_uid) {
            $shared_with_user_uid = trim($shared_with_user_uid);
            
            // Skip if sharing with self
            if ($shared_with_user_uid === $shared_by_user_uid) {
                continue;
            }

            // Check if recipient exists
            $check_user = $pdo->prepare("SELECT user_uid FROM users WHERE user_uid = ?");
            $check_user->execute([$shared_with_user_uid]);
            if (!$check_user->fetch()) {
                $failed_users[] = $shared_with_user_uid;
                continue;
            }

            $params = [
                $seller_id,
                $shared_by_user_uid,
                $shared_with_user_uid,
                $customer_name,
                $phone_number,
                $customer_response,
                $notes
            ];

            if ($insert_stmt->execute($params)) {
                $success_count++;
                error_log("Inserted share for new seller $seller_id to user $shared_with_user_uid");
            } else {
                error_log("Failed to insert share for new seller $seller_id to user $shared_with_user_uid");
            }
            $total_shares++;
        }
    }

    $pdo->commit();

    $message = "Successfully shared with $success_count user(s)";
    if (!empty($failed_users)) {
        $message .= ". Failed for " . count($failed_users) . " user(s)";
    }

    if ($success_count === 0) {
        $message = "No shares were created. Please check if recipients exist and are valid.";
    }

    ob_end_clean();
    echo json_encode([
        'status' => $success_count > 0 ? 'success' : 'error',
        'message' => $message,
        'success_count' => $success_count,
        'failed_count' => count($failed_users),
        'total_shares' => $total_shares,
        'share_to_all' => $share_to_all
    ]);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Share Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>