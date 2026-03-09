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
$sort_column = isset($_POST['sort_column']) ? $_POST['sort_column'] : 'id';
$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'DESC';
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$filters = isset($_POST['filters']) ? $_POST['filters'] : [];

// Validate sort column to prevent SQL injection
$allowed_columns = ['id', 'date', 'store_name', 'customer_name', 'phone_number', 'status', 'assigned_by', 'created_at', 'lead_source_link'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'id';
}

// Validate sort order
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(store_name LIKE :search OR customer_name LIKE :search OR phone_number LIKE :search OR lead_source_link LIKE :search)";
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

if (!empty($filters['lead_source_link'])) {
    $where[] = "lead_source_link LIKE :lead_source_link";
    $params[':lead_source_link'] = "%" . $filters['lead_source_link'] . "%";
}

if (!empty($filters['date_range'])) {
    switch ($filters['date_range']) {
        case 'today':
            $where[] = "DATE(date) = CURDATE()";
            break;
        case 'week':
            $where[] = "YEARWEEK(date) = YEARWEEK(CURDATE())";
            break;
        case 'month':
            $where[] = "MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
            break;
        case 'year':
            $where[] = "YEAR(date) = YEAR(CURDATE())";
            break;
    }
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM registered_sellers $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get data - Explicitly select columns to ensure correct mapping
$sql = "SELECT id, date, store_name, customer_name, phone_number, status, 
               lead_source_link, assigned_by, created_at 
        FROM registered_sellers 
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

// Debug: Check if lead_source_link exists in the first row
if (count($rows) > 0) {
    error_log("First row columns: " . implode(", ", array_keys($rows[0])));
    error_log("lead_source_link value: " . ($rows[0]['lead_source_link'] ?? 'NULL'));
}

// Get stats
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'In Active' THEN 1 ELSE 0 END) as inactive,
                0 as followup
              FROM registered_sellers";
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