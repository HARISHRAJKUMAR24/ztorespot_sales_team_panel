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
       DELETE OLD DATA
    ------------------------------ */
    $pdo->exec("TRUNCATE TABLE whatsapp_customers");

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

    // Expected column mapping
    $expectedColumns = [
        'seller_name' => 'Seller Name',
        'phone_number' => 'Phone Number',
        'assigned_by' => 'Assigned By',
        'update_1' => 'Update 1',
        'update_2' => 'Update 2',
        'update_3' => 'Update 3'
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
    if (!isset($headerMap['seller_name']) || !isset($headerMap['phone_number'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Required columns 'Seller Name' and 'Phone Number' not found"
        ]);
        exit;
    }

    $success = 0;
    $errors = [];
    $validRows = 0;
    $currentDate = null;

    $sql = "INSERT INTO whatsapp_customers
            (entry_date, seller_name, phone_number, assigned_by, update_1, update_2, update_3)
            VALUES
            (:entry_date, :seller_name, :phone_number, :assigned_by, :update_1, :update_2, :update_3)";

    $stmt = $pdo->prepare($sql);

    /* ------------------------------
       PROCESS ROWS
    ------------------------------ */
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        
        // IMPORTANT: For date detection, we need to check the seller_name column
        // because dates are typically in the first column (Seller Name position)
        $sellerNameIndex = $headerMap['seller_name'];
        $firstCell = trim($row[$sellerNameIndex] ?? '');
        
        // Also check if ALL other columns are empty (to confirm it's a date row)
        $otherColumnsEmpty = true;
        foreach ($headerMap as $field => $index) {
            if ($field == 'seller_name') continue; // Skip seller_name as we already checked
            if (!empty(trim($row[$index] ?? ''))) {
                $otherColumnsEmpty = false;
                break;
            }
        }
        
        /* =====================================================
           DETECT DATE ROW
           ===================================================== */
        // Check if this is a date row (has date in seller_name and all other columns empty)
        if ($otherColumnsEmpty && !empty($firstCell)) {
            
            // Try different date formats
            
            // Format: DD/MM/YYYY, DD.MM.YYYY, DD-MM-YYYY
            if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})$/', $firstCell, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                
                // Handle 2-digit year
                if (strlen($year) == 2) {
                    $year = '20' . $year;
                }
                
                // Validate date
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
            
            // If we get here, it might be a date but we couldn't parse it
            // Still treat as date row and continue
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

        // Get seller name
        $sellerName = isset($headerMap['seller_name']) ? trim($row[$headerMap['seller_name']] ?? '') : '';
        
        // Skip if seller name is empty or contains separator markers
        if (empty($sellerName) || $sellerName === '----' || strpos($sellerName, '---') !== false) {
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

        if (strlen($phone) != 10) {
            $errors[] = [
                "row" => $i + 1,
                "seller" => $sellerName,
                "error" => "Invalid phone number: must be 10 digits"
            ];
            continue;
        }

        // Get other fields
        $assignedBy = isset($headerMap['assigned_by']) ? trim($row[$headerMap['assigned_by']] ?? '') : '';
        $update1 = isset($headerMap['update_1']) ? trim($row[$headerMap['update_1']] ?? '') : '';
        $update2 = isset($headerMap['update_2']) ? trim($row[$headerMap['update_2']] ?? '') : '';
        $update3 = isset($headerMap['update_3']) ? trim($row[$headerMap['update_3']] ?? '') : '';

        // If no date has been found yet, skip this row (shouldn't happen with proper Excel format)
        if (!$currentDate) {
            $errors[] = [
                "row" => $i + 1,
                "seller" => $sellerName,
                "error" => "No date found before this row"
            ];
            continue;
        }

        try {
            $stmt->execute([
                ":entry_date" => $currentDate,
                ":seller_name" => substr($sellerName, 0, 255),
                ":phone_number" => $phone,
                ":assigned_by" => substr($assignedBy, 0, 100),
                ":update_1" => $update1,
                ":update_2" => $update2,
                ":update_3" => $update3
            ]);

            $success++;
        } catch (Exception $e) {
            $errors[] = [
                "row" => $i + 1,
                "seller" => $sellerName,
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
            "errors" => $errors
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}