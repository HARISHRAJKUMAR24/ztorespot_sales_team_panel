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

    // Get recent shares (both sent and received)
    $sql = "SELECT 
                s.*,
                ss.work_details_update as original_business_name,
                ss.phone_number as original_phone,
                ub.name as shared_by_name,
                ub.phone as shared_by_phone,
                uw.name as shared_with_name,
                uw.phone as shared_with_phone
            FROM shared_sellers s
            LEFT JOIN sales_person_sellers ss ON s.seller_id = ss.id
            LEFT JOIN users ub ON s.shared_by_user_uid = ub.user_uid
            LEFT JOIN users uw ON s.shared_with_user_uid = uw.user_uid
            WHERE s.shared_by_user_uid = ? OR s.shared_with_user_uid = ?
            ORDER BY s.shared_at DESC
            LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_uid, $user_uid]);
    $shares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'data' => $shares
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
?>