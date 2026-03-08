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
$stmt = $pdo->query("SELECT * FROM registered_sellers ORDER BY s_no DESC");
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="registered_sellers_export_' . date('Y-m-d') . '.xls"');
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
echo '<th>S.No</th>';
echo '<th>Date</th>';
echo '<th>Store Name</th>';
echo '<th>Customer Name</th>';
echo '<th>Phone Number</th>';
echo '<th>Status</th>';
echo '<th>Lead Source Link</th>';
echo '<th>Assigned By</th>';
echo '<th>Deleted By</th>';
echo '<th>Lead Source</th>';
echo '<th>Before/After Registered</th>';
echo '<th>Store Status</th>';
echo '<th>Major Reasons</th>';
echo '<th>Call Attempts</th>';
echo '<th>Follow Up Date</th>';
echo '<th>Notes</th>';
echo '<th>Created At</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($sellers as $seller) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($seller['s_no'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['date'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['store_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['customer_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['phone_number'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['status'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['lead_source_link'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['assigned_by'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['deleted_by'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['lead_source'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['before_after_registered'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['store_status'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['major_reasons'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['call_attempts'] ?? '0') . '</td>';
    echo '<td>' . htmlspecialchars($seller['follow_up_date'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['notes'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($seller['created_at'] ?? '') . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</body>';
echo '</html>';
exit;