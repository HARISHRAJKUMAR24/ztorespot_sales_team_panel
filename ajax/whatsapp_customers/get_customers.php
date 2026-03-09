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
$allowed_columns = ['id', 'entry_date', 'seller_name', 'phone_number', 'assigned_by'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'id';
}

$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(seller_name LIKE :search OR phone_number LIKE :search OR assigned_by LIKE :search OR update_1 LIKE :search OR update_2 LIKE :search OR update_3 LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filters['assigned_by'])) {
    $where[] = "assigned_by LIKE :assigned_by";
    $params[':assigned_by'] = "%" . $filters['assigned_by'] . "%";
}

// Update 1 filter
if (!empty($filters['update1'])) {
    if ($filters['update1'] === 'yes') {
        $where[] = "(update_1 IS NOT NULL AND update_1 != '')";
    } else if ($filters['update1'] === 'no') {
        $where[] = "(update_1 IS NULL OR update_1 = '')";
    }
}

// Update 2 filter
if (!empty($filters['update2'])) {
    if ($filters['update2'] === 'yes') {
        $where[] = "(update_2 IS NOT NULL AND update_2 != '')";
    } else if ($filters['update2'] === 'no') {
        $where[] = "(update_2 IS NULL OR update_2 = '')";
    }
}

// Update 3 filter
if (!empty($filters['update3'])) {
    if ($filters['update3'] === 'yes') {
        $where[] = "(update_3 IS NOT NULL AND update_3 != '')";
    } else if ($filters['update3'] === 'no') {
        $where[] = "(update_3 IS NULL OR update_3 = '')";
    }
}

if (!empty($filters['has_date'])) {
    if ($filters['has_date'] === 'yes') {
        $where[] = "entry_date IS NOT NULL";
    } else if ($filters['has_date'] === 'no') {
        $where[] = "entry_date IS NULL";
    }
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

// Get stats - count customers with updates
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN (update_1 IS NOT NULL AND update_1 != '') THEN 1 ELSE 0 END) as update1_count,
                SUM(CASE WHEN (update_2 IS NOT NULL AND update_2 != '') THEN 1 ELSE 0 END) as update2_count,
                SUM(CASE WHEN (update_3 IS NOT NULL AND update_3 != '') THEN 1 ELSE 0 END) as update3_count
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
?>