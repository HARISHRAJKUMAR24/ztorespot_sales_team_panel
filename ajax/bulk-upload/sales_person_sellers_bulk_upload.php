<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once "../../config/config.php";
require_once "../../lib/functions.php";

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

session_start();

if (!isset($_SESSION['user_uid'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit;
}

$user_uid = $_SESSION['user_uid'];

if (!isset($_POST['action']) || $_POST['action'] !== 'bulk_upload') {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid action"
    ]);
    exit;
}

if (!isset($_FILES['excel_file'])) {
    echo json_encode([
        "status" => "error",
        "message" => "No file uploaded"
    ]);
    exit;
}

$file = $_FILES['excel_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid file type"
    ]);
    exit;
}

try {
    $pdo = db();
    
    /* ------------------------------
       LOAD EXCEL
    ------------------------------ */
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    if (count($rows) <= 1) {
        echo json_encode([
            "status" => "error",
            "message" => "File has no data rows"
        ]);
        exit;
    }

    // Expected column mapping based on your Excel structure
    $expectedColumns = [
        'work_details_update' => 'Work Details Update',
        'source_type' => 'Aiseny/Organic/Direct',
        'registration_status' => 'Reg/Not Reg (Yes/No)',
        'phone_number' => 'CS Mobile Number',
        'plans_interested' => 'Plans Interested',
        'customer_response' => 'Customer Responses',
        'remembering_notes' => 'Remembering',
        'latest_update' => 'Latest Update',
        'current_status' => 'Current Status',
        'customer_queries' => 'Customer Queries',
        'video_canva' => 'Video/Canva',
        'call_timing' => 'Timings',
        'remarks' => 'Remarks'
    ];

    // Find column indices
    $headerMap = [];
    $firstRow = $rows[0];
    
    foreach ($firstRow as $index => $header) {
        $header = trim($header ?? '');
        if (empty($header)) continue;
        
        foreach ($expectedColumns as $field => $expectedHeader) {
            if (strcasecmp($header, $expectedHeader) === 0) {
                $headerMap[$field] = $index;
                break;
            }
        }
    }

    // Check required columns
    if (!isset($headerMap['work_details_update']) || !isset($headerMap['phone_number'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Required columns 'Work Details Update' and 'CS Mobile Number' not found"
        ]);
        exit;
    }

    $success = 0;
    $errors = [];
    $validRows = 0;
    $currentDate = null;
    $batchId = date('YmdHis') . '_' . uniqid();

    $sql = "INSERT INTO sales_person_sellers
            (user_uid, entry_date, work_details_update, source_type, registration_status, 
             phone_number, plans_interested, customer_response, remembering_notes, 
             latest_update, current_status, customer_queries, video_canva, call_timing, remarks,
             import_batch, created_at)
            VALUES
            (:user_uid, :entry_date, :work_details_update, :source_type, :registration_status,
             :phone_number, :plans_interested, :customer_response, :remembering_notes,
             :latest_update, :current_status, :customer_queries, :video_canva, :call_timing, :remarks,
             :import_batch, NOW())";

    $stmt = $pdo->prepare($sql);

    /* ------------------------------
       PROCESS ROWS
    ------------------------------ */
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        
        // Get work_details_update column index for date detection
        $workUpdateIndex = $headerMap['work_details_update'];
        $firstCell = trim($row[$workUpdateIndex] ?? '');
        
        // Check if other columns are empty to confirm date row
        $otherColumnsEmpty = true;
        foreach ($headerMap as $field => $index) {
            if ($field == 'work_details_update') continue;
            if (!empty(trim($row[$index] ?? ''))) {
                $otherColumnsEmpty = false;
                break;
            }
        }
        
        /* =====================================================
           DETECT DATE ROW
           ===================================================== */
        if ($otherColumnsEmpty && !empty($firstCell)) {
            
            // Try different date formats from your Excel (19.02.2026, 20.02.2026, etc.)
            
            // Format: DD.MM.YYYY (19.02.2026)
            if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2,4})$/', $firstCell, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                
                if (strlen($year) == 2) {
                    $year = '20' . $year;
                }
                
                if (checkdate($month, $day, $year)) {
                    $currentDate = $year . '-' . $month . '-' . $day;
                }
                continue;
            }
            
            // Format: DD/MM/YYYY
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $firstCell, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                
                if (strlen($year) == 2) {
                    $year = '20' . $year;
                }
                
                if (checkdate($month, $day, $year)) {
                    $currentDate = $year . '-' . $month . '-' . $day;
                }
                continue;
            }
            
            // Format: "1 February 2024" or similar
            $timestamp = strtotime($firstCell);
            if ($timestamp !== false) {
                $currentDate = date('Y-m-d', $timestamp);
                continue;
            }
            
            continue;
        }

        // Skip completely empty rows
        $isEmpty = true;
        foreach ($row as $cell) {
            if (!empty(trim($cell ?? ''))) {
                $isEmpty = false;
                break;
            }
        }
        
        if ($isEmpty) {
            continue;
        }

        // Get work details update (seller name)
        $workDetails = isset($headerMap['work_details_update']) ? trim($row[$headerMap['work_details_update']] ?? '') : '';
        
        // Skip if work details is empty or contains separator markers
        if (empty($workDetails) || $workDetails === '----' || strpos($workDetails, '---') !== false) {
            continue;
        }

        $validRows++;

        // Get phone number
        $phone = isset($headerMap['phone_number']) ? trim($row[$headerMap['phone_number']] ?? '') : '';
        
        // Handle numbers with =91 prefix
        if (strpos($phone, '=') === 0) {
            $phone = substr($phone, 1);
        }
        
        // Clean phone number - remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove 91 prefix if present
        if (strlen($phone) > 10 && substr($phone, 0, 2) == '91') {
            $phone = substr($phone, 2);
        }
        
        // Take last 10 digits
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }

        // Phone number validation - sometimes it might be empty in the sheet
        if (!empty($phone) && strlen($phone) != 10) {
            $errors[] = [
                "row" => $i + 1,
                "seller" => $workDetails,
                "error" => "Invalid phone number format"
            ];
            // Continue anyway - don't skip
        }

        // If no date has been found yet, use current date
        if (!$currentDate) {
            $currentDate = date('Y-m-d');
        }

        // Get all other fields
        $sourceType = isset($headerMap['source_type']) ? trim($row[$headerMap['source_type']] ?? '') : '';
        $regStatus = isset($headerMap['registration_status']) ? trim($row[$headerMap['registration_status']] ?? '') : '';
        $plansInterested = isset($headerMap['plans_interested']) ? trim($row[$headerMap['plans_interested']] ?? '') : '';
        $customerResponse = isset($headerMap['customer_response']) ? trim($row[$headerMap['customer_response']] ?? '') : '';
        $rememberingNotes = isset($headerMap['remembering_notes']) ? trim($row[$headerMap['remembering_notes']] ?? '') : '';
        $latestUpdate = isset($headerMap['latest_update']) ? trim($row[$headerMap['latest_update']] ?? '') : '';
        $currentStatus = isset($headerMap['current_status']) ? trim($row[$headerMap['current_status']] ?? '') : '';
        $customerQueries = isset($headerMap['customer_queries']) ? trim($row[$headerMap['customer_queries']] ?? '') : '';
        $videoCanva = isset($headerMap['video_canva']) ? trim($row[$headerMap['video_canva']] ?? '') : '';
        $callTiming = isset($headerMap['call_timing']) ? trim($row[$headerMap['call_timing']] ?? '') : '';
        $remarks = isset($headerMap['remarks']) ? trim($row[$headerMap['remarks']] ?? '') : '';

        try {
            $stmt->execute([
                ":user_uid" => $user_uid,
                ":entry_date" => $currentDate,
                ":work_details_update" => substr($workDetails, 0, 255),
                ":source_type" => substr($sourceType, 0, 50),
                ":registration_status" => substr($regStatus, 0, 20),
                ":phone_number" => $phone,
                ":plans_interested" => substr($plansInterested, 0, 100),
                ":customer_response" => $customerResponse,
                ":remembering_notes" => $rememberingNotes,
                ":latest_update" => $latestUpdate,
                ":current_status" => substr($currentStatus, 0, 50),
                ":customer_queries" => $customerQueries,
                ":video_canva" => substr($videoCanva, 0, 255),
                ":call_timing" => substr($callTiming, 0, 100),
                ":remarks" => $remarks,
                ":import_batch" => $batchId
            ]);

            $success++;
        } catch (Exception $e) {
            $errors[] = [
                "row" => $i + 1,
                "seller" => $workDetails,
                "error" => $e->getMessage()
            ];
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "total_rows" => count($rows) - 1,
            "valid_rows" => $validRows,
            "success_count" => $success,
            "error_count" => count($errors),
            "errors" => $errors,
            "batch_id" => $batchId
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}