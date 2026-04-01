<?php
require_once '../../../lib/functions.php';
require_once '../../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = db();
    $currentUser = getCurrentUser();
    $shareOption = $_POST['share_option'] ?? '';
    $shareToAll = isset($_POST['share_to_all']) && ($_POST['share_to_all'] === true || $_POST['share_to_all'] === 'true' || $_POST['share_to_all'] === '1');
    $sharedWithUsers = $_POST['shared_with_users'] ?? [];
    $notes = trim($_POST['notes'] ?? '');
    
    // Ensure sharedWithUsers is an array
    if (!is_array($sharedWithUsers)) {
        $sharedWithUsers = [$sharedWithUsers];
    }
    // Filter out empty values
    $sharedWithUsers = array_filter($sharedWithUsers);
    
    // Debug log
    error_log("Share request - Option: $shareOption, ShareToAll: " . ($shareToAll ? 'true' : 'false'));
    error_log("Shared With Users: " . print_r($sharedWithUsers, true));
    
    if ($shareOption === 'existing') {
        $sellerIds = $_POST['seller_ids'] ?? [];
        
        // Ensure sellerIds is an array
        if (!is_array($sellerIds)) {
            $sellerIds = [$sellerIds];
        }
        // Filter out empty values
        $sellerIds = array_filter($sellerIds);
        
        if (empty($sellerIds)) {
            echo json_encode(['status' => 'error', 'message' => 'No sellers selected']);
            exit;
        }
        
        // Get seller details
        $placeholders = implode(',', array_fill(0, count($sellerIds), '?'));
        $sellerStmt = $pdo->prepare("SELECT id, seller_id, work_details_update as business_name, phone_number, customer_response 
            FROM sales_person_sellers WHERE id IN ($placeholders) AND user_uid = ?");
        $params = array_merge($sellerIds, [$currentUser['user_uid']]);
        $sellerStmt->execute($params);
        $sellers = $sellerStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($sellers)) {
            echo json_encode(['status' => 'error', 'message' => 'No valid sellers found']);
            exit;
        }
    } else {
        // Create new seller first
        $sellerId = $_POST['seller_id'] ?? '';
        $businessName = $_POST['business_name'] ?? '';
        $phoneNumber = $_POST['phone_number'] ?? '';
        $customerResponse = $_POST['customer_response'] ?? '';
        
        if (empty($sellerId) || empty($businessName) || empty($phoneNumber) || empty($customerResponse)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing seller details']);
            exit;
        }
        
        // Check if seller already exists
        $checkStmt = $pdo->prepare("SELECT id FROM sales_person_sellers WHERE phone_number = ? AND user_uid = ?");
        $checkStmt->execute([$phoneNumber, $currentUser['user_uid']]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            $newSellerId = $existing['id'];
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO sales_person_sellers 
                (user_uid, entry_date, work_details_update, phone_number, seller_id, customer_response, created_at) 
                VALUES (?, CURDATE(), ?, ?, ?, ?, NOW())");
            $insertStmt->execute([
                $currentUser['user_uid'],
                $businessName,
                $phoneNumber,
                $sellerId,
                $customerResponse
            ]);
            $newSellerId = $pdo->lastInsertId();
        }
        
        $sellers = [[
            'id' => $newSellerId,
            'seller_id' => $sellerId,
            'business_name' => $businessName,
            'phone_number' => $phoneNumber,
            'customer_response' => $customerResponse
        ]];
    }
    
    // Get recipients
    if ($shareToAll) {
        $userStmt = $pdo->prepare("SELECT id, user_uid, name FROM users WHERE user_uid != ?");
        $userStmt->execute([$currentUser['user_uid']]);
        $recipients = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        if (empty($sharedWithUsers)) {
            echo json_encode(['status' => 'error', 'message' => 'No recipients selected']);
            exit;
        }
        $placeholders = implode(',', array_fill(0, count($sharedWithUsers), '?'));
        $userStmt = $pdo->prepare("SELECT id, user_uid, name FROM users WHERE user_uid IN ($placeholders)");
        $userStmt->execute($sharedWithUsers);
        $recipients = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if (empty($recipients)) {
        echo json_encode(['status' => 'error', 'message' => 'No valid recipients found']);
        exit;
    }
    
    // Create share records
    $successCount = 0;
    $totalShares = 0;
    
    $shareStmt = $pdo->prepare("INSERT INTO shared_seller_data 
        (seller_id, seller_uid, shared_by, shared_by_uid, shared_to, shared_to_uid, share_type, permission, notes, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'individual', 'view', ?, NOW())");
    
    foreach ($sellers as $seller) {
        foreach ($recipients as $recipient) {
            // Check if already shared
            $checkShare = $pdo->prepare("SELECT id FROM shared_seller_data 
                WHERE seller_id = ? AND shared_to = ? AND status = 'active'");
            $checkShare->execute([$seller['id'], $recipient['id']]);
            
            if (!$checkShare->fetch()) {
                $shareStmt->execute([
                    $seller['id'],
                    $seller['seller_id'],
                    $currentUser['id'],
                    $currentUser['user_uid'],
                    $recipient['id'],
                    $recipient['user_uid'],
                    $notes
                ]);
                $successCount++;
            }
            $totalShares++;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => "Successfully shared " . count($sellers) . " seller(s) with " . count($recipients) . " user(s)",
        'total_shares' => $totalShares,
        'success_count' => $successCount
    ]);
    
} catch (Exception $e) {
    error_log("Share seller error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>