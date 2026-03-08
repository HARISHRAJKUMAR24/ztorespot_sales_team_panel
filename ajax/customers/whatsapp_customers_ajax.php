<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once "../../config/config.php";
require_once "../../lib/functions.php";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_uid = $_SESSION['user_uid'];
$action = $_REQUEST['action'] ?? '';
$pdo = db();

// GET LIST action
if ($action === 'get_list') {
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
    $offset = ($page - 1) * $per_page;
    
    $search = $_GET['search'] ?? '';
    $assigned_by = $_GET['assigned_by'] ?? '';
    $status = $_GET['status'] ?? '';
    $interest_level = $_GET['interest_level'] ?? '';
    
    try {
        // Build query
        $where = ["1=1"];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(seller_name LIKE :search OR phone_number LIKE :search OR notes LIKE :search OR update_1 LIKE :search OR update_2 LIKE :search OR update_3 LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($assigned_by)) {
            $where[] = "assigned_by = :assigned_by";
            $params[':assigned_by'] = $assigned_by;
        }
        
        if (!empty($status)) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($interest_level)) {
            $where[] = "interest_level = :interest_level";
            $params[':interest_level'] = $interest_level;
        }
        
        $where_clause = implode(" AND ", $where);
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM whatsapp_customers WHERE $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get data
        $sql = "SELECT * FROM whatsapp_customers 
                WHERE $where_clause 
                ORDER BY id DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $stats = getStatistics($pdo);
        
        $total_pages = ceil($total / $per_page);
        $start = $offset + 1;
        $end = min($offset + $per_page, $total);
        
        echo json_encode([
            'status' => 'success',
            'data' => $customers,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $total_pages,
                'start' => $start,
                'end' => $end
            ],
            'statistics' => $stats
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} 
// GET SINGLE CUSTOMER
elseif ($action === 'get_customer') {
    
    $id = $_GET['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            echo json_encode(['status' => 'success', 'data' => $customer]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} 
// UPDATE CUSTOMER
elseif ($action === 'update_customer') {
    
    $id = $_POST['id'] ?? 0;
    $seller_name = $_POST['seller_name'] ?? '';
    $phone_number = preg_replace('/[^0-9]/', '', $_POST['phone_number'] ?? '');
    $assigned_by = $_POST['assigned_by'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $interest_level = $_POST['interest_level'] ?? 'medium';
    $next_followup_date = $_POST['next_followup_date'] ?? null;
    $source = $_POST['source'] ?? '';
    $business_type = $_POST['business_type'] ?? '';
    $update_1 = $_POST['update_1'] ?? '';
    $update_2 = $_POST['update_2'] ?? '';
    $update_3 = $_POST['update_3'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($seller_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Seller name is required']);
        exit;
    }
    
    if (empty($phone_number) || strlen($phone_number) < 10) {
        echo json_encode(['status' => 'error', 'message' => 'Valid phone number is required']);
        exit;
    }
    
    try {
        // Check for duplicate phone
        $check = $pdo->prepare("SELECT id FROM whatsapp_customers WHERE phone_number = ? AND id != ?");
        $check->execute([$phone_number, $id]);
        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Phone number already exists']);
            exit;
        }
        
        $sql = "UPDATE whatsapp_customers SET 
                seller_name = ?, phone_number = ?, assigned_by = ?, status = ?, 
                interest_level = ?, next_followup_date = ?, source = ?, business_type = ?,
                update_1 = ?, update_2 = ?, update_3 = ?, notes = ?, last_contact_date = NOW()
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $seller_name, $phone_number, $assigned_by, $status, $interest_level,
            $next_followup_date, $source, $business_type, $update_1, $update_2, $update_3, $notes, $id
        ]);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Customer updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update customer']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} 
// DELETE CUSTOMER
elseif ($action === 'delete_customer') {
    
    $id = $_POST['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM whatsapp_customers WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Customer deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} 
// EXPORT TO CSV
elseif ($action === 'export') {
    
    $search = $_GET['search'] ?? '';
    $assigned_by = $_GET['assigned_by'] ?? '';
    $status = $_GET['status'] ?? '';
    $interest_level = $_GET['interest_level'] ?? '';
    
    try {
        // Build query
        $where = ["1=1"];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(seller_name LIKE :search OR phone_number LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($assigned_by)) {
            $where[] = "assigned_by = :assigned_by";
            $params[':assigned_by'] = $assigned_by;
        }
        
        if (!empty($status)) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($interest_level)) {
            $where[] = "interest_level = :interest_level";
            $params[':interest_level'] = $interest_level;
        }
        
        $where_clause = implode(" AND ", $where);
        
        $sql = "SELECT * FROM whatsapp_customers WHERE $where_clause ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=whatsapp_customers_export_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'ID', 'Seller Name', 'Phone Number', 'Assigned By', 'Status', 'Interest Level',
            'Next Follow-up', 'Source', 'Business Type', 'Store Name', 'Seller ID',
            'Update 1', 'Update 2', 'Update 3', 'Notes', 'Created At', 'Last Contact'
        ]);
        
        // Data
        foreach ($customers as $c) {
            fputcsv($output, [
                $c['id'],
                $c['seller_name'],
                $c['phone_number'],
                $c['assigned_by'],
                $c['status'],
                $c['interest_level'],
                $c['next_followup_date'],
                $c['source'],
                $c['business_type'],
                $c['store_name'],
                $c['seller_id'],
                $c['update_1'],
                $c['update_2'],
                $c['update_3'],
                $c['notes'],
                $c['created_at'],
                $c['last_contact_date']
            ]);
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Export failed: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

// Helper function to get statistics
function getStatistics($pdo) {
    try {
        $total = $pdo->query("SELECT COUNT(*) FROM whatsapp_customers")->fetchColumn();
        $high = $pdo->query("SELECT COUNT(*) FROM whatsapp_customers WHERE interest_level = 'high'")->fetchColumn();
        $today = date('Y-m-d');
        $followup = $pdo->prepare("SELECT COUNT(*) FROM whatsapp_customers WHERE next_followup_date = ?");
        $followup->execute([$today]);
        $incomplete = $pdo->query("SELECT COUNT(*) FROM whatsapp_customers WHERE is_incomplete_seller = 1")->fetchColumn();
        
        return [
            'total' => (int)$total,
            'high_interest' => (int)$high,
            'followup_today' => (int)$followup->fetchColumn(),
            'incomplete_sellers' => (int)$incomplete
        ];
    } catch (Exception $e) {
        return ['total' => 0, 'high_interest' => 0, 'followup_today' => 0, 'incomplete_sellers' => 0];
    }
}