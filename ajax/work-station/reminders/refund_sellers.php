<?php
require_once "../../../config/config.php";
require_once "../../../lib/functions.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

try {
    $pdo = db();
    $user_uid = $_SESSION['user_uid'];
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_data') {
        // Get parameters
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $sort_column = $_POST['sort_column'] ?? 'id';
        $sort_order = $_POST['sort_order'] ?? 'DESC';
        $search = $_POST['search'] ?? '';
        $date_filter = $_POST['date_filter'] ?? '';
        $plan_filter = $_POST['plan_filter'] ?? '';
        $amount_filter = $_POST['amount_filter'] ?? '';
        
        $offset = ($page - 1) * $per_page;
        
        // Build query - Only Refund responses
        $where = "WHERE user_uid = ? AND customer_response = 'Refund'";
        $params = [$user_uid];
        
        // Add plan filter
        if (!empty($plan_filter)) {
            $where .= " AND plans_interested = ?";
            $params[] = $plan_filter;
        }
        
        // Add amount filter (search in remembering_notes)
        if (!empty($amount_filter)) {
            switch($amount_filter) {
                case '0-1000':
                    $where .= " AND remembering_notes REGEXP 'Amount: [0-9]+(\\.[0-9]+)?' AND CAST(REGEXP_SUBSTR(remembering_notes, 'Amount: ([0-9.]+)') AS DECIMAL(10,2)) BETWEEN 0 AND 1000";
                    break;
                case '1000-5000':
                    $where .= " AND remembering_notes REGEXP 'Amount: [0-9]+(\\.[0-9]+)?' AND CAST(REGEXP_SUBSTR(remembering_notes, 'Amount: ([0-9.]+)') AS DECIMAL(10,2)) BETWEEN 1000 AND 5000";
                    break;
                case '5000-10000':
                    $where .= " AND remembering_notes REGEXP 'Amount: [0-9]+(\\.[0-9]+)?' AND CAST(REGEXP_SUBSTR(remembering_notes, 'Amount: ([0-9.]+)') AS DECIMAL(10,2)) BETWEEN 5000 AND 10000";
                    break;
                case '10000+':
                    $where .= " AND remembering_notes REGEXP 'Amount: [0-9]+(\\.[0-9]+)?' AND CAST(REGEXP_SUBSTR(remembering_notes, 'Amount: ([0-9.]+)') AS DECIMAL(10,2)) > 10000";
                    break;
            }
        }
        
        // Add search condition
        if (!empty($search)) {
            $where .= " AND (work_details_update LIKE ? OR phone_number LIKE ? OR plans_interested LIKE ? OR remembering_notes LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Add date filter
        if (!empty($date_filter)) {
            switch($date_filter) {
                case 'today':
                    $where .= " AND DATE(entry_date) = CURDATE()";
                    break;
                case 'week':
                    $where .= " AND YEARWEEK(entry_date) = YEARWEEK(CURDATE())";
                    break;
                case 'month':
                    $where .= " AND MONTH(entry_date) = MONTH(CURDATE()) AND YEAR(entry_date) = YEAR(CURDATE())";
                    break;
                case 'year':
                    $where .= " AND YEAR(entry_date) = YEAR(CURDATE())";
                    break;
            }
        }
        
        // Validate sort column
        $allowed_columns = ['id', 'entry_date', 'work_details_update', 'phone_number'];
        if (!in_array($sort_column, $allowed_columns)) {
            $sort_column = 'id';
        }
        
        // Validate sort order
        $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM sales_person_sellers $where";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();
        
        // Get rows
        $sql = "SELECT * FROM sales_person_sellers $where 
                ORDER BY $sort_column $sort_order 
                LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get stats for Refund only
        $stats_sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN YEARWEEK(entry_date) = YEARWEEK(CURDATE()) THEN 1 ELSE 0 END) as week_count,
            SUM(CASE WHEN MONTH(entry_date) = MONTH(CURDATE()) AND YEAR(entry_date) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as month_count
            FROM sales_person_sellers 
            WHERE user_uid = ? AND customer_response = 'Refund'";
        $stats_stmt = $pdo->prepare($stats_sql);
        $stats_stmt->execute([$user_uid]);
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'rows' => $rows,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'stats' => $stats
            ]
        ]);
        exit;
    }
    
    if ($action === 'delete') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM sales_person_sellers 
                               WHERE id = ? AND user_uid = ? AND customer_response = 'Refund'");
        $result = $stmt->execute([$id, $user_uid]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Refund record deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Record not found or already deleted']);
        }
        exit;
    }
    
    if ($action === 'get_details') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM sales_person_sellers WHERE id = ? AND user_uid = ?");
        $stmt->execute([$id, $user_uid]);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($seller) {
            echo json_encode(['status' => 'success', 'data' => $seller]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Record not found']);
        }
        exit;
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    
} catch (PDOException $e) {
    error_log("Database Error in refund_sellers_ajax: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General Error in refund_sellers_ajax: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
?>