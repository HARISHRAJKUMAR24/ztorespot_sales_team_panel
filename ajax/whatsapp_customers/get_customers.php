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

$pdo = db();
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$per_page = isset($_POST['per_page']) ? (int)$_POST['per_page'] : 10;
$sort_column = isset($_POST['sort_column']) ? $_POST['sort_column'] : 'seller_name';
$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'ASC';
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$filters = isset($_POST['filters']) ? $_POST['filters'] : [];

// Validate sort column
$allowed_columns = ['seller_name', 'phone_number', 'seller_id', 'store_name', 'status', 'assigned_by'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'seller_name';
}

$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(seller_name LIKE :search OR phone_number LIKE :search OR seller_id LIKE :search OR store_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filters['status'])) {
    $where[] = "status = :status";
    $params[':status'] = $filters['status'];
}

if (!empty($filters['assigned_by'])) {
    $where[] = "assigned_by = :assigned_by";
    $params[':assigned_by'] = $filters['assigned_by'];
}

if (!empty($filters['lead_source'])) {
    $where[] = "lead_source LIKE :lead_source";
    $params[':lead_source'] = "%" . $filters['lead_source'] . "%";
}

if (!empty($filters['date_range'])) {
    switch ($filters['date_range']) {
        case 'today':
            $where[] = "DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $where[] = "YEARWEEK(created_at) = YEARWEEK(CURDATE())";
            break;
        case 'month':
            $where[] = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
            break;
        case 'year':
            $where[] = "YEAR(created_at) = YEAR(CURDATE())";
            break;
    }
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM whatsapp_customers $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get data
$sql = "SELECT * FROM whatsapp_customers 
        $where_clause 
        ORDER BY $sort_column $sort_order 
        LIMIT :offset, :per_page";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
              FROM whatsapp_customers";
$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'data' => [
        'rows' => $rows,
        'total' => (int)$total,
        'page' => $page,
        'per_page' => $per_page,
        'stats' => $stats
    ]
]);