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
$stmt = $pdo->query("SELECT * FROM whatsapp_customers ORDER BY created_at DESC");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="whatsapp_customers_export_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Create HTML table
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<style>
    td, th { border: 1px solid #000; padding: 5px; }
    th { background-color: #f2f2f2; }
</style>';
echo '</head>';
echo '<body>';
echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th>Seller Name</th>';
echo '<th>Phone Number</th>';
echo '<th>Assigned By</th>';
echo '<th>Update 1</th>';
echo '<th>Update 2</th>';
echo '<th>Update 3</th>';
echo '<th>Seller ID</th>';
echo '<th>Store Name</th>';
echo '<th>Lead Link</th>';
echo '<th>Lead Source</th>';
echo '<th>Before/After Registered</th>';
echo '<th>Store Status</th>';
echo '<th>Major Reasons</th>';
echo '<th>Status</th>';
echo '<th>Created At</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($customers as $customer) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($customer['seller_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['phone_number'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['assigned_by'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['update_1'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['update_2'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['update_3'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['seller_id'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['store_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['lead_link'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['lead_source'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['before_after_registered'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['store_status'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['major_reasons'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['status'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($customer['created_at'] ?? '') . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</body>';
echo '</html>';
exit;