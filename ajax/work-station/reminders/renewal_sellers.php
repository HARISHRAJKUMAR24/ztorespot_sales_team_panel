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
    
    // Get subscription plans for duration reference
    $plans_stmt = $pdo->prepare("SELECT id, plan_name, duration, total_amount FROM subscription_plans WHERE status = 1");
    $plans_stmt->execute();
    $subscription_plans = $plans_stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
        $where = "WHERE user_uid = ? AND (customer_response = 'Plan Upgraded' OR customer_response = 'Plan Interested' OR (plans_interested IS NOT NULL AND plans_interested != 'None'))";
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
        
        // Calculate renewal info for each row
        $filtered_rows = [];
        $stats = [
            'active' => 0,
            'renewal_alert' => 0,
            'near_expiry' => 0,
            'expired' => 0
        ];
        
        foreach ($rows as $row) {
            $renewal_info = calculateSellerRenewalWithPlans($row, $subscription_plans);
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
            $seller['renewal_info'] = calculateSellerRenewalWithPlans($seller, $subscription_plans);
            echo json_encode(['status' => 'success', 'data' => $seller]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Record not found']);
        }
        exit;
    }
    
    if ($action === 'delete') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            exit;
        }
        
        // Check if seller belongs to user
        $check_stmt = $pdo->prepare("SELECT id FROM sales_person_sellers WHERE id = ? AND user_uid = ?");
        $check_stmt->execute([$id, $user_uid]);
        
        if (!$check_stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Record not found or access denied']);
            exit;
        }
        
        // Soft delete - update status to 'Deleted'
        $update_stmt = $pdo->prepare("UPDATE sales_person_sellers SET current_status = 'Deleted', updated_at = NOW() WHERE id = ?");
        $result = $update_stmt->execute([$id]);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Seller deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete seller']);
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

/**
 * Calculate renewal date with proper plan duration from subscription_plans
 */
function calculateSellerRenewalWithPlans($seller, $subscription_plans) {
    $start_date = null;
    $duration = null;
    $plan = $seller['plans_interested'] ?? '';
    $plan_lower = strtolower($plan);
    
    // Get start date from entry_date or created_at
    if (!empty($seller['entry_date'])) {
        $start_date = $seller['entry_date'];
    } elseif (!empty($seller['created_at'])) {
        $start_date = date('Y-m-d', strtotime($seller['created_at']));
    }
    
    if (empty($start_date)) {
        return [
            'renewal_date' => null,
            'days_remaining' => null,
            'status' => 'unknown',
            'formatted_date' => 'N/A',
            'alert_days' => 0,
            'should_show' => false,
            'duration' => 'N/A',
            'start_date' => 'N/A'
        ];
    }
    
    // Try to get duration from subscription_plans first
    if (!empty($plan)) {
        foreach ($subscription_plans as $sp) {
            if (strtolower($sp['plan_name']) === $plan_lower) {
                $duration = $sp['duration'];
                break;
            }
        }
    }
    
    // If not found in subscription_plans, try to get from seller data
    if (empty($duration) && !empty($seller['plan_duration'])) {
        $duration = $seller['plan_duration'];
    }
    
    // If still no duration, try to extract from plan name
    if (empty($duration)) {
        if (strpos($plan_lower, 'year') !== false) {
            $duration = '1 Year';
        } elseif (strpos($plan_lower, 'month') !== false) {
            $duration = '1 Month';
        } else {
            $duration = '1 Month'; // Default
        }
    }
    
    // Calculate renewal date
    try {
        $start = new DateTime($start_date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        // Parse duration
        $duration_lower = strtolower(trim($duration));
        $interval = null;
        $months = 0;
        $years = 0;
        
        if (strpos($duration_lower, 'month') !== false) {
            $months = (int) filter_var($duration_lower, FILTER_SANITIZE_NUMBER_INT);
            if ($months <= 0) $months = 1;
            $interval = new DateInterval("P{$months}M");
        } elseif (strpos($duration_lower, 'year') !== false) {
            $years = (int) filter_var($duration_lower, FILTER_SANITIZE_NUMBER_INT);
            if ($years <= 0) $years = 1;
            $interval = new DateInterval("P{$years}Y");
        } elseif (strpos($duration_lower, 'day') !== false) {
            $days = (int) filter_var($duration_lower, FILTER_SANITIZE_NUMBER_INT);
            if ($days <= 0) $days = 30;
            $interval = new DateInterval("P{$days}D");
        } else {
            // Default to 1 month
            $months = 1;
            $interval = new DateInterval("P1M");
        }
        
        // Calculate renewal date
        $renewal = clone $start;
        $renewal->add($interval);
        $renewal->setTime(0, 0, 0);
        
        // Calculate days remaining
        $days_remaining = $today->diff($renewal)->days;
        if ($today > $renewal) {
            $days_remaining = -$days_remaining;
        }
        
        // Determine alert days based on plan and duration
        $alert_days = getAlertDaysByPlan($plan, $months, $years);
        
        // Determine if should show in renewal list
        $should_show = ($days_remaining <= $alert_days && $days_remaining > 0) || $days_remaining <= 0;
        
        // Determine status
        $status = 'active';
        if ($days_remaining < 0) {
            $status = 'expired';
        } elseif ($days_remaining <= $alert_days) {
            $status = 'renewal_alert';
        } elseif ($days_remaining <= 30) {
            $status = 'near_expiry';
        }
        
        return [
            'renewal_date' => $renewal->format('Y-m-d'),
            'formatted_date' => $renewal->format('d/m/Y'),
            'days_remaining' => $days_remaining,
            'status' => $status,
            'start_date' => $start->format('d/m/Y'),
            'duration' => $duration,
            'alert_days' => $alert_days,
            'should_show' => $should_show,
            'plan' => $plan,
            'months' => $months,
            'years' => $years
        ];
    } catch (Exception $e) {
        return [
            'renewal_date' => null,
            'days_remaining' => null,
            'status' => 'unknown',
            'formatted_date' => 'N/A',
            'alert_days' => 0,
            'should_show' => false,
            'duration' => $duration,
            'start_date' => date('d/m/Y', strtotime($start_date))
        ];
    }
}

/**
 * Get alert days based on plan and duration
 */
function getAlertDaysByPlan($plan, $months, $years) {
    $plan_lower = strtolower($plan);
    
    // Welcome Plan: 1 month = 10 days, 1+ year = 20 days
    if (strpos($plan_lower, 'welcome') !== false) {
        if ($months <= 1) {
            return 10; // 10 days for 1 month
        } elseif ($years >= 1) {
            return 20; // 20 days for 1 year or more
        }
    }
    
    // Starter Plan: up to 3 months = 20 days, 1+ year = 30 days
    elseif (strpos($plan_lower, 'starter') !== false) {
        if ($months <= 3 && $months > 0) {
            return 20; // 20 days for up to 3 months
        } elseif ($years >= 1) {
            return 30; // 30 days for 1 year
        } elseif ($years >= 2) {
            return 30; // 30 days for 2+ years
        }
    }
    
    // Intermediate Plan: Only yearly, 30 days alert
    elseif (strpos($plan_lower, 'intermediate') !== false) {
        if ($years >= 1) {
            return 30; // 30 days for 1 year
        } elseif ($years >= 2) {
            return 30; // 30 days for 2+ years
        }
        return 30; // Default for Intermediate
    }
    
    // Professional Plan: Only yearly, 30 days alert
    elseif (strpos($plan_lower, 'professional') !== false) {
        if ($years >= 1) {
            return 30; // 30 days for 1 year
        } elseif ($years >= 2) {
            return 30; // 30 days for 2+ years
        }
        return 30; // Default for Professional
    }
    
    // Default alert days
    return 15;
}
?>