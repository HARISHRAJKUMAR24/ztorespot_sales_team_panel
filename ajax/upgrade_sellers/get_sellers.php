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
$allowed_columns = ['id', 'store_id', 'seller_name', 'seller_contact', 'phone_number', 'plan_name', 'plan_status', 'product_uploads', 'assigned_by'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'id';
}

$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(seller_name LIKE :search OR seller_contact LIKE :search OR phone_number LIKE :search OR plan_name LIKE :search OR platform_come LIKE :search OR assigned_by LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filters['assigned_by'])) {
    $where[] = "assigned_by LIKE :assigned_by";
    $params[':assigned_by'] = "%" . $filters['assigned_by'] . "%";
}

if (!empty($filters['plan_status'])) {
    $where[] = "plan_status LIKE :plan_status";
    $params[':plan_status'] = "%" . $filters['plan_status'] . "%";
}

if (!empty($filters['plan_name'])) {
    $where[] = "plan_name LIKE :plan_name";
    $params[':plan_name'] = "%" . $filters['plan_name'] . "%";
}

if (!empty($filters['has_products'])) {
    if ($filters['has_products'] === 'yes') {
        $where[] = "product_uploads > 0";
    } else if ($filters['has_products'] === 'no') {
        $where[] = "(product_uploads = 0 OR product_uploads IS NULL)";
    }
}

if (!empty($filters['month']) && !empty($filters['year'])) {
    $where[] = "month_name LIKE :month_year";
    $params[':month_year'] = "%" . $filters['month'] . "%" . $filters['year'] . "%";
} else if (!empty($filters['month'])) {
    $where[] = "month_name LIKE :month";
    $params[':month'] = "%" . $filters['month'] . "%";
} else if (!empty($filters['year'])) {
    $where[] = "month_name LIKE :year";
    $params[':year'] = "%" . $filters['year'] . "%";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM upgrade_sellers $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get data
$sql = "SELECT * FROM upgrade_sellers 
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
                SUM(CASE WHEN plan_status LIKE '%Active%' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN product_uploads > 0 THEN 1 ELSE 0 END) as products_count,
                (SELECT COUNT(*) FROM upgrade_sellers WHERE month_name LIKE CONCAT('%', DATE_FORMAT(CURDATE(), '%M %Y'), '%')) as month_count
              FROM upgrade_sellers";
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