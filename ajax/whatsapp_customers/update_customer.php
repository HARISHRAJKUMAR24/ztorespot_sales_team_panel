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
$entry_date = isset($_POST['entry_date']) && !empty($_POST['entry_date']) ? $_POST['entry_date'] : null;
$seller_name = isset($_POST['seller_name']) ? trim($_POST['seller_name']) : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$assigned_by = isset($_POST['assigned_by']) ? trim($_POST['assigned_by']) : '';
$update_1 = isset($_POST['update_1']) ? trim($_POST['update_1']) : '';
$update_2 = isset($_POST['update_2']) ? trim($_POST['update_2']) : '';
$update_3 = isset($_POST['update_3']) ? trim($_POST['update_3']) : '';

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

if (empty($seller_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Seller name is required']);
    exit;
}

if (empty($phone_number)) {
    echo json_encode(['status' => 'error', 'message' => 'Phone number is required']);
    exit;
}

// Clean phone number
$phone_number = preg_replace('/[^0-9]/', '', $phone_number);

$pdo = db();
$sql = "UPDATE whatsapp_customers SET 
        entry_date = :entry_date,
        seller_name = :seller_name,
        phone_number = :phone_number,
        assigned_by = :assigned_by,
        update_1 = :update_1,
        update_2 = :update_2,
        update_3 = :update_3
        WHERE id = :id";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
    ':entry_date' => $entry_date,
    ':seller_name' => $seller_name,
    ':phone_number' => $phone_number,
    ':assigned_by' => $assigned_by,
    ':update_1' => $update_1,
    ':update_2' => $update_2,
    ':update_3' => $update_3,
    ':id' => $id
]);

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'Customer updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update customer']);
}
?>