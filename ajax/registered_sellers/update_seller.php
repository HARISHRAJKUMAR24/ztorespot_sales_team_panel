<?php
require_once "../../config/config.php";
require_once "../../lib/functions.php";

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$store_name = $_POST['store_name'] ?? '';
$customer_name = $_POST['customer_name'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';
$status = $_POST['status'] ?? '';
$notes = $_POST['notes'] ?? '';

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

// Clean phone number
$phone_number = preg_replace('/[^0-9]/', '', $phone_number);

$pdo = db();
$stmt = $pdo->prepare("UPDATE registered_sellers 
                       SET store_name = ?, customer_name = ?, phone_number = ?, 
                           status = ?, notes = ? 
                       WHERE id = ?");
$result = $stmt->execute([$store_name, $customer_name, $phone_number, $status, $notes, $id]);

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'Seller updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update seller']);
}