<?php
require_once "../../../config/config.php";
require_once "../../../lib/functions.php";

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
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM sellers_workstation WHERE id = ? AND user_uid = ?");
    $stmt->execute([$id, $_SESSION['user_uid']]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($seller) {
        echo json_encode(['status' => 'success', 'data' => $seller]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Seller not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>