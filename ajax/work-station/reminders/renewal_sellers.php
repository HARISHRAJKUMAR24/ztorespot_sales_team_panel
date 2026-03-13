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
        $status_filter = $_POST['status_filter'] ?? 'all';
        $plan_filter = isset($_GET['plan']) ? $_GET['plan'] : ($_POST['plan_filter'] ?? 'all');
        
        $offset = ($page - 1) * $per_page;
        
        // Build query - Get all sellers with plans
        $where = "WHERE user_uid = ? AND (customer_response = 'Plan Upgraded' OR customer_response = 'Plan Interested' OR plans_interested != 'None')";
        $params = [$user_uid];
        
        // Add plan filter
        if ($plan_filter !== 'all') {
            $where .= " AND LOWER(plans_interested) LIKE ?";
            $params[] = "%$plan_filter%";
        }
        
        // Add search condition
        if (!empty($search)) {
            $where .= " AND (work_details_update LIKE ? OR phone_number LIKE ? OR plans_interested LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Validate sort column
        $allowed_columns = ['id', 'work_details_update', 'phone_number'];
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
        
        // Calculate renewal info for each row and filter by status if needed
        $filtered_rows = [];
        $stats = [
            'active' => 0,
            'renewal_alert' => 0,
            'near_expiry' => 0,
            'expired' => 0
        ];
        
        foreach ($rows as $row) {
            $renewal_info = calculateSellerRenewal($row);
            $row['renewal_info'] = $renewal_info;
            
            // Only show if should_show is true (within alert period)
            if ($renewal_info['should_show']) {
                // Update stats
                if ($renewal_info['status'] !== 'unknown') {
                    $stats[$renewal_info['status']]++;
                }
                
                // Apply status filter
                if ($status_filter === 'all' || $renewal_info['status'] === $status_filter) {
                    $filtered_rows[] = $row;
                }
            }
        }
        
        // If filtering by status, we need to recalculate total and pagination
        if ($status_filter !== 'all') {
            $total = count($filtered_rows);
            // Apply pagination manually
            $filtered_rows = array_slice($filtered_rows, $offset, $per_page);
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'rows' => $filtered_rows,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'stats' => $stats
            ]
        ]);
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
            // Add renewal info
            $seller['renewal_info'] = calculateSellerRenewal($seller);
            echo json_encode(['status' => 'success', 'data' => $seller]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Record not found']);
        }
        exit;
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    
} catch (PDOException $e) {
    error_log("Database Error in renewal_sellers_ajax: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General Error in renewal_sellers_ajax: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
?>