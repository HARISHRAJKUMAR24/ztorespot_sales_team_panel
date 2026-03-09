<?php
require_once "../../config/config.php";
require_once "../../lib/functions.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_uid'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$pdo = db();

// Get all sellers
$sql = "SELECT * FROM upgrade_sellers ORDER BY id DESC";
$stmt = $pdo->query($sql);
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="upgrade_sellers_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'ID',
    'Store ID',
    'Seller Name',
    'Seller Contact',
    'Phone Number',
    'Seller Response',
    'Product Uploads',
    'Plan Name',
    'Plan Status',
    'Assigned By',
    'Platform Come',
    'Platform Known',
    'Month Name',
    'Created At',
    'Updated At'
]);

// Add data rows
foreach ($sellers as $seller) {
    fputcsv($output, [
        $seller['id'],
        $seller['store_id'],
        $seller['seller_name'],
        $seller['seller_contact'],
        $seller['phone_number'],
        $seller['seller_response'],
        $seller['product_uploads'],
        $seller['plan_name'],
        $seller['plan_status'],
        $seller['assigned_by'],
        $seller['platform_come'],
        $seller['platform_known'],
        $seller['month_name'],
        $seller['created_at'],
        $seller['updated_at']
    ]);
}

fclose($output);
exit;