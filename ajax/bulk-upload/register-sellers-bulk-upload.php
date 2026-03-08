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

    $sql = "INSERT INTO registered_sellers (
                s_no,date,store_name,customer_name,phone_number,status,
                lead_source_link,assigned_by,deleted_by,lead_source,
                before_after_registered,store_status,major_reasons,
                created_by,created_at
            )
            VALUES (
                :s_no,:date,:store_name,:customer_name,:phone_number,:status,
                :lead_source_link,:assigned_by,:deleted_by,:lead_source,
                :before_after_registered,:store_status,:major_reasons,
                :created_by,NOW()
            )";

    $stmt = $pdo->prepare($sql);

    $success_count = 0;
    $errors = [];

    $row_num = 1;

    foreach ($data as $row) {

        $row_num++;

        if (empty(array_filter($row))) {
            continue;
        }

        /* --------------------------------
           FIX PHONE NUMBER
        -------------------------------- */

        $phone = trim($row['phone_number'] ?? '');

        if (is_numeric($phone)) {
            $phone = number_format($phone, 0, '', '');
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }

        if (strlen($phone) < 10) {
            $errors[] = [
                'row' => $row_num,
                'error' => 'Invalid phone number'
            ];
            continue;
        }

        /* --------------------------------
           PROCESS DATE
        -------------------------------- */

        $date_val = null;

        if (!empty($row['date'])) {

            if (is_numeric($row['date'])) {

                try {
                    $date_val = Date::excelToDateTimeObject($row['date'])
                        ->format('Y-m-d');
                } catch (Exception $e) {
                    $date_val = null;
                }
            } else {
                $date_val = date('Y-m-d', strtotime($row['date']));
            }
        }

        try {

            $stmt->execute([

                ':s_no' => isset($row['s_no']) ? (int)$row['s_no'] : null,

                ':date' => $date_val,

                ':store_name' => substr($row['store_name'] ?? '', 0, 255),

                ':customer_name' => substr($row['customer_name'] ?? '', 0, 255),

                ':phone_number' => $phone,

                ':status' => substr($row['status'] ?? '', 0, 50),

                ':lead_source_link' => substr($row['lead_source_link'] ?? '', 0, 500),

                ':assigned_by' => substr($row['assigned_by'] ?? '', 0, 100),

                ':deleted_by' => substr($row['deleted_by'] ?? '', 0, 100),

                ':lead_source' => substr($row['lead_source'] ?? '', 0, 100),

                ':before_after_registered' => substr($row['before_after_registered'] ?? '', 0, 50),

                ':store_status' => substr($row['store_status'] ?? '', 0, 50),

                ':major_reasons' => $row['major_reasons'] ?? '',

                ':created_by' => $user_uid

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
   CSV PARSER
--------------------------- */

function parseCSV($file)
{

    $data = [];
    $header = null;

    if (($handle = fopen($file, 'r')) !== false) {

        while (($row = fgetcsv($handle)) !== false) {

            if (!$header) {

                $header = array_map(function ($h) {

                    $h = strtolower(trim($h));
                    $h = str_replace(' ', '_', $h);
                    return preg_replace('/[^a-z0-9_]/', '', $h);
                }, $row);
            } else {

                $data[] = array_combine($header, $row);
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

    $headers = array_map(function ($h) {

        $h = strtolower(trim($h));
        $h = str_replace(' ', '_', $h);
        return preg_replace('/[^a-z0-9_]/', '', $h);
    }, $rows[0]);

    for ($i = 1; $i < count($rows); $i++) {

        $row_data = [];

        foreach ($headers as $k => $header) {

            $row_data[$header] = $rows[$i][$k] ?? '';
        }

        if (!empty(array_filter($row_data))) {
            $data[] = $row_data;
        }
    }

    return $data;
}
