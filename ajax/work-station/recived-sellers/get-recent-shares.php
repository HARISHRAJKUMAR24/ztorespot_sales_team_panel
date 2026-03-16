<?php
require_once "../../../config/config.php";
require_once "../../../lib/functions.php";

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

    error_log("Loading shares for user: " . $user_uid);

    // Get recent shares - only show shares where current user is either sender OR recipient
    $sql = "SELECT 
                s.id,
                s.seller_id,
                s.customer_name,
                s.phone_number,
                s.customer_response,
                s.notes,
                s.status,
                s.shared_at,
                ub.name as shared_by_name,
                ub.phone as shared_by_phone,
                uw.name as shared_with_name,
                uw.phone as shared_with_phone,
                CASE 
                    WHEN s.shared_by_user_uid = ? THEN 'sent'
                    ELSE 'received'
                END as share_type
            FROM shared_sellers s
            LEFT JOIN users ub ON s.shared_by_user_uid = ub.user_uid
            LEFT JOIN users uw ON s.shared_with_user_uid = uw.user_uid
            WHERE s.shared_by_user_uid = ? OR s.shared_with_user_uid = ?
            ORDER BY s.shared_at DESC
            LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_uid, $user_uid, $user_uid]);
    $shares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($shares) . " shares for user " . $user_uid);

    // Clean output buffer
    ob_end_clean();
    
    echo json_encode([
        'status' => 'success',
        'data' => $shares,
        'user_uid' => $user_uid,
        'count' => count($shares)
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get-recent-shares: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in get-recent-shares: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>