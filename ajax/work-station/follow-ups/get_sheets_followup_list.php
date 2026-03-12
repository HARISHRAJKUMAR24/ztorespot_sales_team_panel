<?php
require_once "../../../config/config.php";
require_once "../../../lib/functions.php";

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
    $user_uid = $_SESSION['user_uid'];
    
    // Get request parameters
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $per_page = isset($_POST['per_page']) ? (int)$_POST['per_page'] : 10;
    $sort_column = isset($_POST['sort_column']) ? $_POST['sort_column'] : 'id';
    $sort_order = isset($_POST['sort_order']) && strtoupper($_POST['sort_order']) === 'ASC' ? 'ASC' : 'DESC';
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    
    // Filters
    $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
    $response_filter = isset($filters['response']) ? $filters['response'] : '';
    $reg_status_filter = isset($filters['reg_status']) ? $filters['reg_status'] : '';
    $current_status_filter = isset($filters['current_status']) ? $filters['current_status'] : '';
    $date_range = isset($filters['date_range']) ? $filters['date_range'] : '';
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Allowed sort columns to prevent SQL injection
    $allowed_columns = ['id', 'entry_date', 'work_details_update', 'phone_number', 'customer_response', 'call_timing', 'current_status', 'created_at'];
    if (!in_array($sort_column, $allowed_columns)) {
        $sort_column = 'id';
    }
    
    // Build WHERE clause and params array
    $where_conditions = ["user_uid = ?"];
    $params = [$user_uid];
    
    // Search condition
    if (!empty($search)) {
        $where_conditions[] = "(work_details_update LIKE ? OR 
                               phone_number LIKE ? OR 
                               customer_response LIKE ? OR 
                               remarks LIKE ? OR
                               latest_update LIKE ? OR
                               remembering_notes LIKE ? OR
                               plans_interested LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Response filter (Later, Call Back AT, Schedule)
    if (!empty($response_filter)) {
        $where_conditions[] = "customer_response = ?";
        $params[] = $response_filter;
    }
    
    // Registration status filter
    if (!empty($reg_status_filter)) {
        $where_conditions[] = "registration_status = ?";
        $params[] = $reg_status_filter;
    }
    
    // Current status filter
    if (!empty($current_status_filter)) {
        $where_conditions[] = "current_status = ?";
        $params[] = $current_status_filter;
    }
    
    // Date range filter
    if (!empty($date_range)) {
        switch ($date_range) {
            case 'today':
                $where_conditions[] = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where_conditions[] = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $where_conditions[] = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
                break;
        }
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM sales_person_sellers WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get filtered data with pagination
    $query = "SELECT * FROM sales_person_sellers 
              WHERE $where_clause 
              ORDER BY $sort_column $sort_order 
              LIMIT ?, ?";
    
    $stmt = $pdo->prepare($query);
    
    // Merge params with pagination params
    $execute_params = array_merge($params, [$offset, $per_page]);
    $stmt->execute($execute_params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics for cards
    $stats_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN customer_response = 'Later' THEN 1 ELSE 0 END) as later_count,
                        SUM(CASE WHEN customer_response = 'Call Back AT' THEN 1 ELSE 0 END) as callback_count,
                        SUM(CASE WHEN customer_response = 'Schedule' THEN 1 ELSE 0 END) as schedule_count
                    FROM sales_person_sellers 
                    WHERE user_uid = ?";
    
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute([$user_uid]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ensure stats have default values
    $stats = [
        'total' => (int)($stats['total'] ?? 0),
        'later_count' => (int)($stats['later_count'] ?? 0),
        'callback_count' => (int)($stats['callback_count'] ?? 0),
        'schedule_count' => (int)($stats['schedule_count'] ?? 0)
    ];
    
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
    
} catch (Exception $e) {
    error_log("Error in get_sheets_followup_list.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}