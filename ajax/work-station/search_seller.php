<?php
require_once '../../config/config.php';
require_once '../../lib/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_uid = $_SESSION['user_uid'];
$search = isset($_POST['search']) ? trim($_POST['search']) : '';

if (empty($search)) {
    echo json_encode(['status' => 'error', 'message' => 'Search term is required', 'data' => []]);
    exit;
}

if (strlen($search) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter at least 2 characters', 'data' => []]);
    exit;
}

try {
    $pdo = db();
    
    // Check if table has correct column names
    // First, let's check what columns exist
    $checkColumns = $pdo->query("DESCRIBE sales_person_sellers");
    $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
    error_log("Available columns: " . print_r($columns, true));
    
    // Use correct column names based on your table structure
    // From your previous dump, the table has: work_details_update, phone_number, seller_id, customer_response
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            work_details_update as business_name, 
            phone_number, 
            seller_id, 
            customer_response, 
            latest_update,
            current_status
        FROM sales_person_sellers 
        WHERE user_uid = :user_uid 
        AND (
            phone_number LIKE :search 
            OR seller_id LIKE :search 
            OR work_details_update LIKE :search
        )
        ORDER BY 
            CASE 
                WHEN phone_number = :exact_search THEN 1
                WHEN seller_id = :exact_search THEN 2
                WHEN work_details_update = :exact_search THEN 3
                WHEN phone_number LIKE :starts_with THEN 4
                WHEN seller_id LIKE :starts_with THEN 5
                WHEN work_details_update LIKE :starts_with THEN 6
                ELSE 7
            END,
            created_at DESC
        LIMIT 15
    ");
    
    $searchParam = '%' . $search . '%';
    $startsWithParam = $search . '%';
    
    $stmt->execute([
        ':user_uid' => $user_uid,
        ':search' => $searchParam,
        ':exact_search' => $search,
        ':starts_with' => $startsWithParam
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log results for debugging
    error_log("Search term: $search, Results found: " . count($results));
    
    echo json_encode([
        'status' => 'success',
        'data' => $results,
        'count' => count($results),
        'search_term' => $search
    ]);
    
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage(), 
        'data' => []
    ]);
}
?>