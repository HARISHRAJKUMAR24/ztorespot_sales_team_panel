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
$seller_name = $_POST['seller_name'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';
$store_name = $_POST['store_name'] ?? '';
$seller_id = $_POST['seller_id'] ?? '';
$status = $_POST['status'] ?? '';
$update_1 = $_POST['update_1'] ?? '';
$update_2 = $_POST['update_2'] ?? '';
$update_3 = $_POST['update_3'] ?? '';

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

// Clean phone number
$phone_number = preg_replace('/[^0-9]/', '', $phone_number);

$pdo = db();
$stmt = $pdo->prepare("UPDATE whatsapp_customers 
                       SET seller_name = ?, phone_number = ?, store_name = ?, 
                           seller_id = ?, status = ?, update_1 = ?, 
                           update_2 = ?, update_3 = ? 
                       WHERE id = ?");
$result = $stmt->execute([$seller_name, $phone_number, $store_name, $seller_id, 
                         $status, $update_1, $update_2, $update_3, $id]);

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'Customer updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update customer']);
}