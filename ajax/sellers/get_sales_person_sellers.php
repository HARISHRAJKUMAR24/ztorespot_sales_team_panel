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

// Validate sort column
$allowed_columns = ['id', 'entry_date', 'work_details_update', 'source_type', 'phone_number', 'plans_interested', 'current_status'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'id';
}

$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(work_details_update LIKE :search OR source_type LIKE :search OR phone_number LIKE :search OR plans_interested LIKE :search OR customer_response LIKE :search OR latest_update LIKE :search OR remarks LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filters['source_type'])) {
    $where[] = "source_type = :source_type";
    $params[':source_type'] = $filters['source_type'];
}

if (!empty($filters['reg_status'])) {
    $where[] = "registration_status = :reg_status";
    $params[':reg_status'] = $filters['reg_status'];
}

if (!empty($filters['current_status'])) {
    $where[] = "current_status = :current_status";
    $params[':current_status'] = $filters['current_status'];
}

if (!empty($filters['plan'])) {
    $where[] = "plans_interested = :plan";
    $params[':plan'] = $filters['plan'];
}

// Has response filter
if (!empty($filters['has_response'])) {
    if ($filters['has_response'] === 'yes') {
        $where[] = "(customer_response IS NOT NULL AND customer_response != '')";
    } else if ($filters['has_response'] === 'no') {
        $where[] = "(customer_response IS NULL OR customer_response = '')";
    }
}

// Date range filter
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
$count_sql = "SELECT COUNT(*) as total FROM sales_person_sellers $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get data
$sql = "SELECT * FROM sales_person_sellers 
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
                SUM(CASE WHEN registration_status = 'Yes' THEN 1 ELSE 0 END) as registered_count,
                SUM(CASE WHEN current_status = 'Upgraded' THEN 1 ELSE 0 END) as upgraded_count,
                SUM(CASE WHEN source_type = 'Follow up Sellers' THEN 1 ELSE 0 END) as followup_count
              FROM sales_person_sellers";
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
?>