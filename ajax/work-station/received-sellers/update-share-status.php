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
    
    // Get POST data
    $share_id = isset($_POST['share_id']) ? intval($_POST['share_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    // Validate
    if (!$share_id) {
        echo json_encode(['status' => 'error', 'message' => 'Share ID is required']);
        exit;
    }

    if (!in_array($status, ['pending', 'accepted', 'rejected'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
        exit;
    }

    // Check if user is the recipient of this share (ONLY recipient can update status)
    $check_sql = "SELECT id FROM shared_sellers WHERE id = ? AND shared_with_user_uid = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$share_id, $user_uid]);
    
    if (!$check_stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to update this share']);
        exit;
    }

    // Update status
    $sql = "UPDATE shared_sellers SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $share_id]);

    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Status updated successfully'
    ]);

} catch (PDOException $e) {
    error_log("Database Error in update-share-status: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in update-share-status: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>