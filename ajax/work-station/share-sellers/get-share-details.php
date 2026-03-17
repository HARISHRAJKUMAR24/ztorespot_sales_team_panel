<?php
require_once "../../../config/config.php";
require_once "../../../lib/functions.php";

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
    
    // Debug: Log all GET parameters
    error_log("GET parameters in get-share-details: " . print_r($_GET, true));
    
    $share_id = isset($_GET['share_id']) ? intval($_GET['share_id']) : 0;

    if (!$share_id) {
        echo json_encode(['status' => 'error', 'message' => 'Share ID is required. Received: ' . json_encode($_GET)]);
        exit;
    }

    error_log("Loading share details for ID: " . $share_id . " for user: " . $user_uid);

    // Get share details - only if user is sender OR recipient
    $sql = "SELECT 
                s.id,
                s.seller_id,
                s.customer_name,
                s.phone_number,
                s.customer_response,
                s.notes,
                s.status,
                s.shared_at,
                s.shared_by_user_uid,
                s.shared_with_user_uid,
                ub.name as shared_by_name,
                ub.phone as shared_by_phone,
                ub.email as shared_by_email,
                uw.name as shared_with_name,
                uw.phone as shared_with_phone,
                uw.email as shared_with_email,
                CASE 
                    WHEN s.shared_by_user_uid = ? THEN 'sent'
                    ELSE 'received'
                END as share_type
            FROM shared_sellers s
            LEFT JOIN users ub ON s.shared_by_user_uid = ub.user_uid
            LEFT JOIN users uw ON s.shared_with_user_uid = uw.user_uid
            WHERE s.id = ? AND (s.shared_by_user_uid = ? OR s.shared_with_user_uid = ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_uid, $share_id, $user_uid, $user_uid]);
    $share = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$share) {
        echo json_encode(['status' => 'error', 'message' => 'Share not found or you do not have permission to view it']);
        exit;
    }

    error_log("Share details found: " . print_r($share, true));

    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'data' => $share
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get-share-details: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in get-share-details: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>