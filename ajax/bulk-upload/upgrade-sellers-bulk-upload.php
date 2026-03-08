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

    /* --------------------------------
       OPTION 1: DELETE OLD DATA
       Uncomment the line below to truncate table before import
    -------------------------------- */
    // $pdo->exec("TRUNCATE TABLE upgrade_sellers");

    /* --------------------------------
       OPTION 2: INSERT ONLY (DEFAULT)
       Keep existing data, just add new records
    -------------------------------- */

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
    $duplicates_count = 0;
    $errors = [];

    // Prepare SQL statement
    $sql = "INSERT INTO upgrade_sellers (
                date,
                seller_name_id,
                work_details_update,
                source_type,
                registration_status,
                cs_mobile,
                plans_interested,
                customer_responses,
                remembering,
                latest_update,
                current_status,
                customer_queries,
                video_canva,
                timings,
                remarks,
                created_by,
                created_at
            ) VALUES (
                :date,
                :seller_name_id,
                :work_details_update,
                :source_type,
                :registration_status,
                :cs_mobile,
                :plans_interested,
                :customer_responses,
                :remembering,
                :latest_update,
                :current_status,
                :customer_queries,
                :video_canva,
                :timings,
                :remarks,
                :created_by,
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM upgrade_sellers WHERE seller_name_id = ?");

    $row_num = 1; // Start from row 1 (header row)

    foreach ($data as $row) {
        $row_num++;
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        // Extract and clean data
        $seller_name_id = trim($row['seller_name_id'] ?? '');
        
        // Check if required field is empty
        if (empty($seller_name_id)) {
            $error_count++;
            $errors[] = [
                'row' => $row_num,
                'seller' => '-',
                'error' => 'Seller Name/ID is required'
            ];
            continue;
        }

        // Check for duplicates (optional - remove if you want to allow duplicates)
        $checkStmt->execute([$seller_name_id]);
        if ($checkStmt->fetchColumn() > 0) {
            $duplicates_count++;
            $errors[] = [
                'row' => $row_num,
                'seller' => $seller_name_id,
                'error' => 'Duplicate seller entry (skipped)'
            ];
            continue;
        }

        // Process date field
        $date_val = null;
        if (!empty($row['date'])) {
            if (is_numeric($row['date'])) {
                // Excel serial date
                try {
                    $date_val = Date::excelToDateTimeObject($row['date'])->format('Y-m-d');
                } catch (Exception $e) {
                    $date_val = null;
                }
            } else {
                // Try to parse string date
                $timestamp = strtotime($row['date']);
                if ($timestamp !== false) {
                    $date_val = date('Y-m-d', $timestamp);
                }
            }
        }

        // Clean phone number
        $cs_mobile = preg_replace('/[^0-9]/', '', trim($row['cs_mobile'] ?? ''));
        if (strlen($cs_mobile) > 10) {
            $cs_mobile = substr($cs_mobile, -10);
        }

        try {
            $stmt->execute([
                ':date' => $date_val,
                ':seller_name_id' => substr($seller_name_id, 0, 255),
                ':work_details_update' => substr($row['work_details_update'] ?? '', 0, 500),
                ':source_type' => substr($row['source_type'] ?? '', 0, 50),
                ':registration_status' => substr($row['registration_status'] ?? '', 0, 10),
                ':cs_mobile' => $cs_mobile ?: null,
                ':plans_interested' => substr($row['plans_interested'] ?? '', 0, 100),
                ':customer_responses' => $row['customer_responses'] ?? null,
                ':remembering' => $row['remembering'] ?? null,
                ':latest_update' => $row['latest_update'] ?? null,
                ':current_status' => substr($row['current_status'] ?? '', 0, 100),
                ':customer_queries' => $row['customer_queries'] ?? null,
                ':video_canva' => $row['video_canva'] ?? null,
                ':timings' => $row['timings'] ?? null,
                ':remarks' => $row['remarks'] ?? null,
                ':created_by' => $user_uid
            ]);
            
            $success_count++;
            
        } catch (PDOException $e) {
            $error_count++;
            $errors[] = [
                'row' => $row_num,
                'seller' => $seller_name_id,
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
            'duplicates_count' => $duplicates_count,
            'errors' => $errors
        ]
    ];

    // If no records were inserted, return error
    if ($success_count === 0 && $total_rows > 0) {
        $response['status'] = 'error';
        $response['message'] = 'No records were inserted. Please check your data.';
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
   CSV PARSER
--------------------------- */
function parseCSV($file)
{
    $data = [];
    $header = null;

    if (($handle = fopen($file, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
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
                        'seller_name_id' => ['sellernameid', 'seller_name_id', 'seller_name', 'sellerid', 'seller_id'],
                        'cs_mobile' => ['csmobile', 'cs_mobile', 'cs_mobile_number', 'mobile_number', 'phone'],
                        'work_details_update' => ['workdetailsupdate', 'work_details_update', 'work_details', 'work_update'],
                        'source_type' => ['sourcetype', 'source_type', 'aiseny_organic_direct', 'source'],
                        'registration_status' => ['registrationstatus', 'registration_status', 'reg_not_reg', 'reg_status'],
                        'plans_interested' => ['plansinterested', 'plans_interested', 'plans'],
                        'customer_responses' => ['customerresponses', 'customer_responses', 'responses'],
                        'current_status' => ['currentstatus', 'current_status', 'status'],
                        'customer_queries' => ['customerqueries', 'customer_queries', 'queries'],
                        'video_canva' => ['videocanva', 'video_canva', 'video'],
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

    if (empty($rows)) {
        return $data;
    }

    // Normalize headers
    $headers = array_map(function ($h) {
        $h = strtolower(trim($h));
        $h = str_replace(' ', '_', $h);
        $h = str_replace('/', '_', $h);
        $h = str_replace('-', '_', $h);
        $h = preg_replace('/[^a-z0-9_]/', '', $h);
        
        // Map common variations
        $map = [
            'seller_name_id' => ['sellernameid', 'seller_name_id', 'seller_name', 'sellerid', 'seller_id'],
            'cs_mobile' => ['csmobile', 'cs_mobile', 'cs_mobile_number', 'mobile_number', 'phone'],
            'work_details_update' => ['workdetailsupdate', 'work_details_update', 'work_details', 'work_update'],
            'source_type' => ['sourcetype', 'source_type', 'aiseny_organic_direct', 'source'],
            'registration_status' => ['registrationstatus', 'registration_status', 'reg_not_reg', 'reg_status'],
            'plans_interested' => ['plansinterested', 'plans_interested', 'plans'],
            'customer_responses' => ['customerresponses', 'customer_responses', 'responses'],
            'current_status' => ['currentstatus', 'current_status', 'status'],
            'customer_queries' => ['customerqueries', 'customer_queries', 'queries'],
            'video_canva' => ['videocanva', 'video_canva', 'video'],
        ];
        
        foreach ($map as $standard => $variations) {
            if (in_array($h, $variations)) {
                return $standard;
            }
        }
        
        return $h;
    }, $rows[0]);

    // Parse data rows
    for ($i = 1; $i < count($rows); $i++) {
        $row_data = [];
        foreach ($headers as $k => $header) {
            $row_data[$header] = $rows[$i][$k] ?? '';
        }
        
        // Only add if not empty
        if (!empty(array_filter($row_data))) {
            $data[] = $row_data;
        }
    }

    return $data;
}