<?php
require_once "../../../config/config.php";
require_once "../../../lib/functions.php";

header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

try {
    $pdo = db();
    $user_uid = $_SESSION['user_uid'];
    
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $per_page = isset($_POST['per_page']) ? (int)$_POST['per_page'] : 10;
    $sort_column = isset($_POST['sort_column']) ? $_POST['sort_column'] : 'id';
    $sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'DESC';
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    $filters = isset($_POST['filters']) ? $_POST['filters'] : [];

    // Validate sort column
    $allowed_columns = ['id', 'entry_date', 'business_name', 'phone_number'];
    if (!in_array($sort_column, $allowed_columns)) {
        $sort_column = 'id';
    }

    // Validate sort order
    $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

    $offset = ($page - 1) * $per_page;

    // Build WHERE clause - Start with user_uid condition
    $whereConditions = ["user_uid = ?"];
    $params = [$user_uid];
    
    // Add follow-up condition (Later, Call Back AT, or Shedule)
    $whereConditions[] = "(customer_response = 'Later' OR customer_response = 'Call Back AT' OR customer_response = 'Shedule')";

    // Handle search
    if (!empty($search)) {
        $whereConditions[] = "(business_name LIKE ? OR phone_number LIKE ? OR customer_queries LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Handle filters
    if (!empty($filters['response'])) {
        $whereConditions[] = "customer_response = ?";
        $params[] = $filters['response'];
    }

    if (!empty($filters['seller_type'])) {
        $whereConditions[] = "seller_type = ?";
        $params[] = $filters['seller_type'];
    }

    if (!empty($filters['status'])) {
        $whereConditions[] = "customer_status = ?";
        $params[] = $filters['status'];
    }

    // Date range filter - these don't need parameters
    if (!empty($filters['date_range'])) {
        switch ($filters['date_range']) {
            case 'today':
                $whereConditions[] = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $whereConditions[] = "YEARWEEK(created_at) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $whereConditions[] = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
                break;
        }
    }

    $where_clause = "WHERE " . implode(" AND ", $whereConditions);

    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM sellers_workstation $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    
    // Bind parameters for count query
    foreach ($params as $index => $value) {
        $count_stmt->bindValue($index + 1, $value);
    }
    
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get data
    $sql = "SELECT * FROM sellers_workstation 
            $where_clause 
            ORDER BY `$sort_column` $sort_order 
            LIMIT ?, ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind WHERE clause parameters
    $paramPosition = 1;
    foreach ($params as $value) {
        $stmt->bindValue($paramPosition++, $value);
    }
    
    // Bind LIMIT parameters
    $stmt->bindValue($paramPosition++, $offset, PDO::PARAM_INT);
    $stmt->bindValue($paramPosition++, $per_page, PDO::PARAM_INT);
    
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get stats - Separate query for stats with all follow-up types
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN customer_response = 'Later' THEN 1 ELSE 0 END) as later_count,
                    SUM(CASE WHEN customer_response = 'Call Back AT' THEN 1 ELSE 0 END) as callback_count,
                    SUM(CASE WHEN customer_response = 'Shedule' THEN 1 ELSE 0 END) as shedule_count
                  FROM sellers_workstation 
                  WHERE user_uid = ? AND (customer_response = 'Later' OR customer_response = 'Call Back AT' OR customer_response = 'Shedule')";
    
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute([$user_uid]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // Ensure stats are not null
    if (!$stats) {
        $stats = [
            'total' => 0,
            'later_count' => 0,
            'callback_count' => 0,
            'shedule_count' => 0
        ];
    }

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

} catch (PDOException $e) {
    error_log("Database Error in get_followup_list.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General Error in get_followup_list.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'An error occurred'
    ]);
}
?>