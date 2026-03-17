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

    error_log("Loading shared sellers for user: " . $user_uid);

    // Get all shares where user is either sender or recipient
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
            WHERE s.shared_by_user_uid = ? OR s.shared_with_user_uid = ?
            ORDER BY s.shared_at DESC";

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
    error_log("Database Error in get-shared-sellers: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in get-shared-sellers: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>