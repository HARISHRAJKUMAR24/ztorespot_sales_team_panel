<?php
require_once "../../config/config.php";
require_once "../../lib/functions.php";

header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    // Validate sort column
    $allowed_columns = ['id', 'entry_date', 'seller_name', 'phone_number', 'assigned_by'];
    if (!in_array($sort_column, $allowed_columns)) {
        $sort_column = 'id';
    }

    // Validate sort order
    $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

    $offset = ($page - 1) * $per_page;

    // Build WHERE clause and parameters - USING POSITIONAL PARAMETERS INSTEAD OF NAMED
    $whereConditions = [];
    $params = [];
    $paramIndex = 0;

    // Handle search
    if (!empty($search)) {
        $whereConditions[] = "(seller_name LIKE ? OR phone_number LIKE ? OR assigned_by LIKE ? OR update_1 LIKE ? OR update_2 LIKE ? OR update_3 LIKE ?)";
        $searchParam = "%$search%";
        for ($i = 0; $i < 6; $i++) {
            $params[] = $searchParam;
        }
    }

    // Handle filters
    if (!empty($filters['assigned_by'])) {
        $whereConditions[] = "assigned_by = ?";
        $params[] = $filters['assigned_by'];
    }

    // Update 1 filter
    if (!empty($filters['update1'])) {
        if ($filters['update1'] === 'yes') {
            $whereConditions[] = "(update_1 IS NOT NULL AND update_1 != '')";
        } else if ($filters['update1'] === 'no') {
            $whereConditions[] = "(update_1 IS NULL OR update_1 = '')";
        }
    }

    // Update 2 filter
    if (!empty($filters['update2'])) {
        if ($filters['update2'] === 'yes') {
            $whereConditions[] = "(update_2 IS NOT NULL AND update_2 != '')";
        } else if ($filters['update2'] === 'no') {
            $whereConditions[] = "(update_2 IS NULL OR update_2 = '')";
        }
    }

    // Update 3 filter
    if (!empty($filters['update3'])) {
        if ($filters['update3'] === 'yes') {
            $whereConditions[] = "(update_3 IS NOT NULL AND update_3 != '')";
        } else if ($filters['update3'] === 'no') {
            $whereConditions[] = "(update_3 IS NULL OR update_3 = '')";
        }
    }

    // Has date filter
    if (!empty($filters['has_date'])) {
        if ($filters['has_date'] === 'yes') {
            $whereConditions[] = "entry_date IS NOT NULL";
        } else if ($filters['has_date'] === 'no') {
            $whereConditions[] = "entry_date IS NULL";
        }
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
            case 'year':
                $whereConditions[] = "YEAR(created_at) = YEAR(CURDATE())";
                break;
        }
    }

    $where_clause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM whatsapp_customers $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    
    // Bind parameters for count query
    foreach ($params as $index => $value) {
        $count_stmt->bindValue($index + 1, $value);
    }
    
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get data
    $sql = "SELECT * FROM whatsapp_customers 
            $where_clause 
            ORDER BY `$sort_column` $sort_order 
            LIMIT ?, ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind search and filter parameters
    $paramPosition = 1;
    foreach ($params as $value) {
        $stmt->bindValue($paramPosition++, $value);
    }
    
    // Bind limit parameters
    $stmt->bindValue($paramPosition++, $offset, PDO::PARAM_INT);
    $stmt->bindValue($paramPosition++, $per_page, PDO::PARAM_INT);
    
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get stats (unfiltered for dashboard cards)
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

} catch (PDOException $e) {
    $error_message = $e->getMessage();
    error_log("Database Error in get_customers.php: " . $error_message);
    
    // Log more details for debugging
    error_log("SQL State: " . $e->errorInfo[0] ?? 'Unknown');
    error_log("Error Code: " . $e->errorInfo[1] ?? 'Unknown');
    
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $error_message
    ]);
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("General Error in get_customers.php: " . $error_message);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error: ' . $error_message
    ]);
}
?>