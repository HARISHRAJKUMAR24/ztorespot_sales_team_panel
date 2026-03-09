<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once "../../config/config.php";
require_once "../../lib/functions.php";

// Check if PhpSpreadsheet is installed
$autoload_path = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'PhpSpreadsheet library not installed. Please run: composer require phpoffice/phpspreadsheet'
    ]);
    exit;
}

require_once $autoload_path;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_uid'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not authenticated'
    ]);
    exit;
}

$user_uid = $_SESSION['user_uid'];
$action = $_POST['action'] ?? '';

if ($action !== 'bulk_upload') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

// Check file upload
if (!isset($_FILES['excel_file'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['excel_file'];
$current_month = $_POST['current_month'] ?? date('F Y');

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    $errorMsg = $uploadErrors[$file['error']] ?? 'Unknown upload error';
    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid file type. Please upload .xlsx, .xls, or .csv files only.'
    ]);
    exit;
}

try {

    $pdo = db();
    $pdo->beginTransaction();

    // Load file data
    if ($ext === 'csv') {
        $data = parseCSV($file['tmp_name']);
    } else {
        $data = parseExcel($file['tmp_name']);
    }

    if (empty($data)) {
        $pdo->rollBack();
        echo json_encode([
            'status' => 'error',
            'message' => 'No data found in the uploaded file'
        ]);
        exit;
    }

    $total_rows = count($data);
    $success_count = 0;
    $error_count = 0;
    $errors = [];

    // Prepare SQL statement
    $sql = "INSERT INTO upgrade_sellers (
                store_id,
                seller_name,
                seller_contact,
                phone_number,
                seller_response,
                product_uploads,
                plan_name,
                plan_status,
                assigned_by,
                platform_come,
                platform_known,
                month_name,
                created_at
            ) VALUES (
                :store_id,
                :seller_name,
                :seller_contact,
                :phone_number,
                :seller_response,
                :product_uploads,
                :plan_name,
                :plan_status,
                :assigned_by,
                :platform_come,
                :platform_known,
                :month_name,
                NOW()
            )";

    $stmt = $pdo->prepare($sql);

    $row_num = 1; // Start from row 1 (header row)

    foreach ($data as $row) {
        $row_num++;
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        // Extract data with proper column mapping
        $seller_name = trim($row['seller_name'] ?? $row['seller_name_id'] ?? $row['store_name'] ?? '');
        
        // Check if required field is empty
        if (empty($seller_name)) {
            $error_count++;
            $errors[] = [
                'row' => $row_num,
                'seller' => '-',
                'error' => 'Seller Name is required (Column B)'
            ];
            continue;
        }

        // Clean phone number
        $phone = preg_replace('/[^0-9]/', '', trim($row['phone_number'] ?? $row['phone'] ?? $row['mobile'] ?? ''));
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }

        // Convert product uploads to integer
        $product_uploads = 0;
        if (!empty($row['product_uploads'] ?? $row['product_upload'] ?? '')) {
            $product_uploads = intval(preg_replace('/[^0-9]/', '', $row['product_uploads'] ?? $row['product_upload'] ?? ''));
        }

        // Handle month detection from special rows
        $detected_month = detectMonthInRow($row);
        $final_month = $detected_month ?: $current_month;

        try {
            $stmt->execute([
                ':store_id' => substr(trim($row['store_id'] ?? $row['a'] ?? ''), 0, 50) ?: null,
                ':seller_name' => substr($seller_name, 0, 255),
                ':seller_contact' => substr(trim($row['seller_contact'] ?? $row['contact'] ?? $row['c'] ?? ''), 0, 255) ?: null,
                ':phone_number' => $phone ?: null,
                ':seller_response' => substr(trim($row['seller_response'] ?? $row['response'] ?? $row['e'] ?? ''), 0, 100) ?: null,
                ':product_uploads' => $product_uploads,
                ':plan_name' => substr(trim($row['plan_name'] ?? $row['plan'] ?? $row['g'] ?? ''), 0, 100) ?: null,
                ':plan_status' => substr(trim($row['plan_status'] ?? $row['status'] ?? $row['h'] ?? ''), 0, 50) ?: null,
                ':assigned_by' => substr(trim($row['assigned_by'] ?? $row['assigned'] ?? $row['i'] ?? ''), 0, 100) ?: null,
                ':platform_come' => substr(trim($row['platform_come'] ?? $row['platform'] ?? $row['j'] ?? ''), 0, 100) ?: null,
                ':platform_known' => trim($row['platform_known'] ?? $row['notes'] ?? $row['k'] ?? $row['l'] ?? '') ?: null,
                ':month_name' => $final_month
            ]);
            
            $success_count++;
            
        } catch (PDOException $e) {
            $error_count++;
            $errors[] = [
                'row' => $row_num,
                'seller' => $seller_name,
                'error' => $e->getMessage()
            ];
        }
    }

    // Commit transaction if any records were inserted
    if ($success_count > 0) {
        $pdo->commit();
    } else {
        $pdo->rollBack();
    }

    // Prepare response
    $response = [
        'status' => 'success',
        'data' => [
            'total_rows' => $total_rows,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'errors' => $errors
        ]
    ];

    // If no records were inserted, return error
    if ($success_count === 0 && $total_rows > 0) {
        $response['status'] = 'error';
        $response['message'] = 'No records were inserted. Please check your data format.';
        unset($response['data']);
    }

    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => 'System error: ' . $e->getMessage()
    ]);
}

/* ---------------------------
   DETECT MONTH HEADER
--------------------------- */
function detectMonthInRow($row) {
    $row_string = implode(' ', array_values($row));
    $row_string = strtoupper($row_string);
    
    $months = [
        'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
        'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
    ];
    
    foreach ($months as $month) {
        if (strpos($row_string, $month . ' MONTH') !== false || 
            strpos($row_string, $month . ' UPGRADE') !== false) {
            
            // Extract year if present
            preg_match('/\b(20\d{2})\b/', $row_string, $year_matches);
            $year = $year_matches[1] ?? date('Y');
            
            return $month . ' ' . $year . ' UPGRADE';
        }
    }
    
    return null;
}

/* ---------------------------
   CSV PARSER
--------------------------- */
function parseCSV($file)
{
    $data = [];
    $header = null;
    $current_month = null;

    if (($handle = fopen($file, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            // Check for month header
            $row_string = implode(' ', $row);
            if (preg_match('/\b(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)\s+MONTH\b/i', $row_string)) {
                $current_month = trim($row_string);
                continue;
            }
            
            if (!$header) {
                // Normalize headers
                $header = array_map(function ($h) {
                    $h = strtolower(trim($h));
                    $h = str_replace(' ', '_', $h);
                    $h = str_replace('/', '_', $h);
                    $h = str_replace('-', '_', $h);
                    $h = preg_replace('/[^a-z0-9_]/', '', $h);
                    
                    // Map common variations
                    $map = [
                        'store_id' => ['store_id', 'a', 'col_a', 'storeid'],
                        'seller_name' => ['seller_name', 'b', 'col_b', 'sellername', 'store_name'],
                        'seller_contact' => ['seller_contact', 'c', 'col_c', 'contact'],
                        'phone_number' => ['phone_number', 'd', 'col_d', 'phone', 'mobile'],
                        'seller_response' => ['seller_response', 'e', 'col_e', 'response'],
                        'product_uploads' => ['product_uploads', 'f', 'col_f', 'uploads', 'product_upload'],
                        'plan_name' => ['plan_name', 'g', 'col_g', 'plan'],
                        'plan_status' => ['plan_status', 'h', 'col_h', 'status'],
                        'assigned_by' => ['assigned_by', 'i', 'col_i', 'assigned'],
                        'platform_come' => ['platform_come', 'j', 'col_j', 'platform'],
                    ];
                    
                    foreach ($map as $standard => $variations) {
                        if (in_array($h, $variations)) {
                            return $standard;
                        }
                    }
                    
                    return $h;
                }, $row);
            } else {
                $row_data = [];
                foreach ($header as $k => $header_name) {
                    $row_data[$header_name] = $row[$k] ?? '';
                }
                
                // Add month if detected
                if ($current_month) {
                    $row_data['month_name'] = $current_month;
                }
                
                $data[] = $row_data;
            }
        }
        fclose($handle);
    }
    return $data;
}

/* ---------------------------
   EXCEL PARSER
--------------------------- */
function parseExcel($file)
{
    $data = [];
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    $current_month = null;

    if (empty($rows)) {
        return $data;
    }

    // Find header row (skip month headers)
    $header_row_index = 0;
    $headers = [];
    
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $row_string = implode(' ', $rows[$i]);
        
        // Check for month header
        if (preg_match('/\b(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)\s+MONTH\b/i', $row_string)) {
            $current_month = trim($row_string);
            continue;
        }
        
        // Look for header row (contains 'Store ID' or similar)
        if (preg_match('/store|seller|name|phone|plan/i', $row_string)) {
            $header_row_index = $i;
            $headers = array_map(function ($h) {
                $h = strtolower(trim($h));
                $h = str_replace(' ', '_', $h);
                $h = str_replace('/', '_', $h);
                $h = str_replace('-', '_', $h);
                $h = preg_replace('/[^a-z0-9_]/', '', $h);
                
                // Map common variations
                $map = [
                    'store_id' => ['store_id', 'a', 'col_a', 'storeid'],
                    'seller_name' => ['seller_name', 'b', 'col_b', 'sellername', 'store_name'],
                    'seller_contact' => ['seller_contact', 'c', 'col_c', 'contact'],
                    'phone_number' => ['phone_number', 'd', 'col_d', 'phone', 'mobile'],
                    'seller_response' => ['seller_response', 'e', 'col_e', 'response'],
                    'product_uploads' => ['product_uploads', 'f', 'col_f', 'uploads', 'product_upload'],
                    'plan_name' => ['plan_name', 'g', 'col_g', 'plan'],
                    'plan_status' => ['plan_status', 'h', 'col_h', 'status'],
                    'assigned_by' => ['assigned_by', 'i', 'col_i', 'assigned'],
                    'platform_come' => ['platform_come', 'j', 'col_j', 'platform'],
                ];
                
                foreach ($map as $standard => $variations) {
                    if (in_array($h, $variations)) {
                        return $standard;
                    }
                }
                
                return $h;
            }, $rows[$i]);
            break;
        }
    }

    // If no header found, use default column mapping
    if (empty($headers)) {
        $headers = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l'];
        $header_row_index = -1;
    }

    // Parse data rows
    for ($i = $header_row_index + 1; $i < count($rows); $i++) {
        $row_data = [];
        
        // Check if this row is a month header
        $row_string = implode(' ', $rows[$i]);
        if (preg_match('/\b(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)\s+MONTH\b/i', $row_string)) {
            $current_month = trim($row_string);
            continue;
        }
        
        // Skip if row is empty
        if (empty(array_filter($rows[$i]))) {
            continue;
        }
        
        foreach ($headers as $index => $header_name) {
            if (isset($rows[$i][$index])) {
                $row_data[$header_name] = $rows[$i][$index];
            }
        }
        
        // Add month if detected
        if ($current_month) {
            $row_data['month_name'] = $current_month;
        }
        
        $data[] = $row_data;
    }

    return $data;
}