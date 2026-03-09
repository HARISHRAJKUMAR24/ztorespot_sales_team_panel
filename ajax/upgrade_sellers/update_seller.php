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
$store_id = isset($_POST['store_id']) ? trim($_POST['store_id']) : null;
$seller_name = isset($_POST['seller_name']) ? trim($_POST['seller_name']) : '';
$seller_contact = isset($_POST['seller_contact']) ? trim($_POST['seller_contact']) : null;
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$seller_response = isset($_POST['seller_response']) ? trim($_POST['seller_response']) : null;
$product_uploads = isset($_POST['product_uploads']) ? (int)$_POST['product_uploads'] : 0;
$plan_name = isset($_POST['plan_name']) ? trim($_POST['plan_name']) : null;
$plan_status = isset($_POST['plan_status']) ? trim($_POST['plan_status']) : null;
$assigned_by = isset($_POST['assigned_by']) ? trim($_POST['assigned_by']) : null;
$platform_come = isset($_POST['platform_come']) ? trim($_POST['platform_come']) : null;
$platform_known = isset($_POST['platform_known']) ? trim($_POST['platform_known']) : null;
$month_name = isset($_POST['month_name']) ? trim($_POST['month_name']) : null;

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
$sql = "UPDATE upgrade_sellers SET 
        store_id = :store_id,
        seller_name = :seller_name,
        seller_contact = :seller_contact,
        phone_number = :phone_number,
        seller_response = :seller_response,
        product_uploads = :product_uploads,
        plan_name = :plan_name,
        plan_status = :plan_status,
        assigned_by = :assigned_by,
        platform_come = :platform_come,
        platform_known = :platform_known,
        month_name = :month_name
        WHERE id = :id";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
    ':store_id' => $store_id,
    ':seller_name' => $seller_name,
    ':seller_contact' => $seller_contact,
    ':phone_number' => $phone_number,
    ':seller_response' => $seller_response,
    ':product_uploads' => $product_uploads,
    ':plan_name' => $plan_name,
    ':plan_status' => $plan_status,
    ':assigned_by' => $assigned_by,
    ':platform_come' => $platform_come,
    ':platform_known' => $platform_known,
    ':month_name' => $month_name,
    ':id' => $id
]);

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'Seller updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update seller']);
}