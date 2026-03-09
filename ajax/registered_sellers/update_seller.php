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
$store_name = isset($_POST['store_name']) ? trim($_POST['store_name']) : '';
$customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$lead_source_link = isset($_POST['lead_source_link']) ? trim($_POST['lead_source_link']) : '';
$assigned_by = isset($_POST['assigned_by']) ? trim($_POST['assigned_by']) : '';

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

if (empty($store_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Store name is required']);
    exit;
}

// Clean phone number - remove non-numeric characters
$phone_number = preg_replace('/[^0-9]/', '', $phone_number);

try {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE registered_sellers 
                           SET store_name = ?, customer_name = ?, phone_number = ?, 
                               status = ?, lead_source_link = ?, assigned_by = ? 
                           WHERE id = ?");
    $result = $stmt->execute([$store_name, $customer_name, $phone_number, $status, $lead_source_link, $assigned_by, $id]);

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Seller updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update seller']);
    }
} catch (PDOException $e) {
    error_log("Update seller error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}