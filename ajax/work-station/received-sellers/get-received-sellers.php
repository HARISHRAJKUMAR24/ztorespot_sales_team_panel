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

    error_log("Loading received sellers for user: " . $user_uid);

    // IMPORTANT: This query ONLY shows sellers shared WITH the current user
    // It does NOT show sellers sent BY the current user
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
                ub.user_uid as shared_by_user_uid
            FROM shared_sellers s
            LEFT JOIN users ub ON s.shared_by_user_uid = ub.user_uid
            WHERE s.shared_with_user_uid = ?  -- ONLY where current user is the recipient
            ORDER BY s.shared_at DESC
            LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_uid]);
    $received_sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($received_sellers) . " received sellers for user " . $user_uid);

    // Clean output buffer
    ob_end_clean();
    
    echo json_encode([
        'status' => 'success',
        'data' => $received_sellers,
        'user_uid' => $user_uid,
        'count' => count($received_sellers)
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get-received-sellers: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in get-received-sellers: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>