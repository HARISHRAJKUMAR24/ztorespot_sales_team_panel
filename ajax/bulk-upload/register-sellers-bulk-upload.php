<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once "../../config/config.php";
require_once "../../lib/functions.php";

$autoload_path = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'PhpSpreadsheet not installed'
    ]);
    exit;
}

require_once $autoload_path;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_uid'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not logged in'
    ]);
    exit;
}

$user_uid = $_SESSION['user_uid'];
$action = $_POST['action'] ?? '';

if ($action !== 'bulk_upload') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

if (!isset($_FILES['excel_file'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['excel_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type']);
    exit;
}

try {
    $pdo = db();

    /* --------------------------------
       DELETE OLD DATA BEFORE IMPORT
    -------------------------------- */
    $pdo->exec("TRUNCATE TABLE registered_sellers");

    /* --------------------------------
       LOAD FILE
    -------------------------------- */
    if ($ext === 'csv') {
        $data = parseCSV($file['tmp_name']);
    } else {
        $data = parseExcel($file['tmp_name']);
    }

    if (empty($data)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No data found'
        ]);
        exit;
    }

    $total_rows = count($data);

    /* --------------------------------
       INSERT DATA - WITHOUT S.NO
    -------------------------------- */
    $sql = "INSERT INTO registered_sellers (
                date, store_name, customer_name, phone_number, 
                status, lead_source_link, assigned_by
            )
            VALUES (
                :date, :store_name, :customer_name, :phone_number,
                :status, :lead_source_link, :assigned_by
            )";

    $stmt = $pdo->prepare($sql);

    $success_count = 0;
    $errors = [];
    $row_num = 1; // Excel row count (header is row 1)

    foreach ($data as $row) {
        $row_num++;
        
        // Skip completely empty rows
        $row_data = array_filter($row);
        if (empty($row_data)) {
            continue;
        }

        // Skip rows that look like notes/comments (metadata)
        $first_col = isset($row['s_no']) ? trim($row['s_no']) : '';
        if (!empty($first_col) && !is_numeric($first_col) && strlen($first_col) > 5) {
            continue; // Skip rows with text in S.No column
        }

        /* --------------------------------
           FIX PHONE NUMBER
        -------------------------------- */
        $phone = '';
        if (isset($row['phone_number'])) {
            $phone = trim($row['phone_number']);
            
            // Handle Excel scientific notation or formatted numbers
            if (is_numeric($phone) && strpos($phone, 'E') !== false) {
                $phone = number_format(floatval($phone), 0, '', '');
            } elseif (is_numeric($phone) && strpos($phone, '.') !== false) {
                $phone = number_format(floatval($phone), 0, '', '');
            }
            
            // Remove all non-numeric characters
            $phone = preg_replace('/[^0-9]/', '', $phone);
            
            // Take last 10 digits if longer
            if (strlen($phone) > 10) {
                $phone = substr($phone, -10);
            }
        }

        // Validate - must have exactly 10 digits
        if (strlen($phone) !== 10) {
            $errors[] = [
                'row' => $row_num,
                'error' => 'Invalid phone number: ' . ($row['phone_number'] ?? 'empty')
            ];
            continue;
        }

        /* --------------------------------
           PROCESS DATE
        -------------------------------- */
        $date_val = null;
        if (isset($row['date']) && !empty($row['date'])) {
            $date_val = parseFlexibleDate($row['date']);
        }

        /* --------------------------------
           TRUNCATE TEXT FIELDS
        -------------------------------- */
        $store_name = isset($row['store_name']) ? substr(trim($row['store_name']), 0, 255) : null;
        $customer_name = isset($row['customer_name']) ? substr(trim($row['customer_name']), 0, 255) : null;
        $status = isset($row['status']) ? substr(trim($row['status']), 0, 50) : null;
        $lead_link = isset($row['lead_source_link']) ? substr(trim($row['lead_source_link']), 0, 500) : null;
        $assigned_by = isset($row['assigned_by']) ? substr(trim($row['assigned_by']), 0, 100) : null;

        try {
            $stmt->execute([
                ':date' => $date_val,
                ':store_name' => $store_name,
                ':customer_name' => $customer_name,
                ':phone_number' => $phone,
                ':status' => $status,
                ':lead_source_link' => $lead_link,
                ':assigned_by' => $assigned_by
            ]);

            $success_count++;
        } catch (PDOException $e) {
            $errors[] = [
                'row' => $row_num,
                'error' => $e->getMessage()
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_rows' => $total_rows,
            'success_count' => $success_count,
            'error_count' => count($errors),
            'errors' => $errors
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/* ---------------------------
   FLEXIBLE DATE PARSER
--------------------------- */
function parseFlexibleDate($raw_date) {
    if (empty($raw_date)) {
        return null;
    }

    $raw_date = trim($raw_date);
    
    // Case 1: Excel numeric date (like 45234)
    if (is_numeric($raw_date) && $raw_date > 40000 && $raw_date < 50000) {
        try {
            return Date::excelToDateTimeObject($raw_date)->format('Y-m-d');
        } catch (Exception $e) {
            // fall through
        }
    }

    // Case 2: Format like "10.12.23" (DD.MM.YY)
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2,4})$/', $raw_date, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        
        if (strlen($year) == 2) {
            $year = '20' . $year;
        }
        
        if (checkdate($month, $day, $year)) {
            return "$year-$month-$day";
        }
    }

    // Case 3: Format like "2025-08-10 00:00:00"
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw_date, $matches)) {
        return $matches[1];
    }

    // Case 4: Skip time-only entries
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $raw_date)) {
        return null;
    }

    // Case 5: Try strtotime
    $timestamp = strtotime($raw_date);
    if ($timestamp !== false && $timestamp > 0) {
        return date('Y-m-d', $timestamp);
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

    if (($handle = fopen($file, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            if (!$header) {
                $header = array_map('cleanHeader', $row);
            } else {
                $assoc_row = [];
                foreach ($header as $idx => $col_name) {
                    $assoc_row[$col_name] = $row[$idx] ?? '';
                }
                $data[] = $assoc_row;
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

    $headers = array_map('cleanHeader', $rows[0]);

    for ($i = 1; $i < count($rows); $i++) {
        $row_data = [];
        $has_data = false;
        
        foreach ($headers as $k => $header) {
            $value = $rows[$i][$k] ?? '';
            $row_data[$header] = $value;
            
            if (!empty($value) && trim($value) !== '') {
                $has_data = true;
            }
        }

        if ($has_data) {
            $data[] = $row_data;
        }
    }

    return $data;
}

/* ---------------------------
   CLEAN HEADER FUNCTION
--------------------------- */
function cleanHeader($header) {
    $header = strtolower(trim($header));
    $header = str_replace(' ', '_', $header);
    $header = str_replace('.', '', $header); // Remove dots
    $header = str_replace('-', '_', $header);
    return preg_replace('/[^a-z0-9_]/', '', $header);
}