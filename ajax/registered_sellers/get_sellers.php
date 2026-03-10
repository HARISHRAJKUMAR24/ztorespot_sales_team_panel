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

try {
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

    // Build WHERE clause using positional parameters (?) instead of named parameters
    $where = [];
    $params = []; // This will be a simple indexed array for positional parameters

    if (!empty($search)) {
        $where[] = "(store_name LIKE ? OR customer_name LIKE ? OR phone_number LIKE ? OR lead_source_link LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if (!empty($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['assigned_by'])) {
        $where[] = "assigned_by = ?";
        $params[] = $filters['assigned_by'];
    }

    if (!empty($filters['lead_source_link'])) {
        $where[] = "lead_source_link LIKE ?";
        $params[] = "%" . $filters['lead_source_link'] . "%";
    }

    // Date range filters don't need parameters
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
    
    // Bind parameters for count query
    foreach ($params as $index => $value) {
        $count_stmt->bindValue($index + 1, $value);
    }
    
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get data - Using the same parameter binding approach
    $sql = "SELECT id, date, store_name, customer_name, phone_number, status, 
                   lead_source_link, assigned_by, created_at 
            FROM registered_sellers 
            $where_clause 
            ORDER BY `$sort_column` $sort_order 
            LIMIT ?, ?";

    $stmt = $pdo->prepare($sql);
    
    // Bind WHERE clause parameters
    $paramIndex = 1;
    foreach ($params as $value) {
        $stmt->bindValue($paramIndex++, $value);
    }
    
    // Bind LIMIT parameters
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $per_page, PDO::PARAM_INT);
    
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

} catch (PDOException $e) {
    error_log("Database Error in registered_sellers: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in registered_sellers: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>