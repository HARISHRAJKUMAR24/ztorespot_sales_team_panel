<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
header('Content-Type: application/json');

require_once "../../config/config.php";
require_once "../../lib/functions.php";

// Check if composer autoload exists
$autoload_path = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
} else {
    echo json_encode(['status' => 'error', 'message' => 'PhpSpreadsheet not installed. Run: composer require phpoffice/phpspreadsheet']);
    exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
$action = $_POST['action'] ?? '';

// Simple test action
if ($action === 'test') {
    echo json_encode(['status' => 'success', 'message' => 'AJAX is working']);
    exit;
}

if ($action === 'bulk_upload') {
    
    // Check if file was uploaded
    if (!isset($_FILES['excel_file'])) {
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
        exit;
    }
    
    if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $error_msg = $upload_errors[$_FILES['excel_file']['error']] ?? 'Unknown upload error';
        echo json_encode(['status' => 'error', 'message' => $error_msg]);
        exit;
    }
    
    $file = $_FILES['excel_file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed_ext = ['xlsx', 'xls', 'csv'];
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Please upload Excel or CSV file']);
        exit;
    }
    
    // Validate file size (10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'File size must be less than 10MB']);
        exit;
    }
    
    // Process file
    $data = [];
    $errors = [];
    $success_count = 0;
    $duplicates_count = 0;
    
    try {
        // Parse the file
        if ($file_ext === 'csv') {
            $data = parseCSV($file['tmp_name']);
        } else {
            $data = parseExcel($file['tmp_name']);
        }
        
        $total_rows = count($data);
        
        // Check if we have data
        if (empty($data)) {
            echo json_encode(['status' => 'error', 'message' => 'File is empty or no valid data found']);
            exit;
        }
        
        // Get database connection
        $pdo = db();
        
        // Get headers from first row
        $headers = array_keys($data[0]);
        
        // Check if required fields exist
        $required_fields = ['seller_name', 'phone_number'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!in_array($field, $headers)) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields: ' . implode(', ', $missing_fields)]);
            exit;
        }
        
        // Prepare insert statement
        $sql = "INSERT INTO whatsapp_customers (
                    seller_name, phone_number, assigned_by, update_1, update_2, update_3,
                    seller_id, store_name, lead_link, lead_source, before_after_registered,
                    store_status, major_reasons, created_by
                ) VALUES (
                    :seller_name, :phone_number, :assigned_by, :update_1, :update_2, :update_3,
                    :seller_id, :store_name, :lead_link, :lead_source, :before_after_registered,
                    :store_status, :major_reasons, :created_by
                )";
        
        $stmt = $pdo->prepare($sql);
        
        // Process each row
        $row_num = 1; // Start from 1 (headers are row 0)
        $phone_numbers = [];
        
        foreach ($data as $row) {
            $row_num++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Get phone number and clean it
            $phone = isset($row['phone_number']) ? trim($row['phone_number']) : '';
            $phone = preg_replace('/[^0-9]/', '', $phone);
            
            // Validate phone number
            if (empty($phone)) {
                $errors[] = ['row' => $row_num, 'error' => 'Phone number is required'];
                continue;
            }
            
            if (strlen($phone) < 10) {
                $errors[] = ['row' => $row_num, 'error' => 'Phone number must be at least 10 digits'];
                continue;
            }
            
            // Check for duplicates in this upload
            if (in_array($phone, $phone_numbers)) {
                $duplicates_count++;
                $errors[] = ['row' => $row_num, 'error' => 'Duplicate phone number in same file'];
                continue;
            }
            
            // Check for duplicates in database
            $check = $pdo->prepare("SELECT id FROM whatsapp_customers WHERE phone_number = ?");
            $check->execute([$phone]);
            if ($check->fetch()) {
                $duplicates_count++;
                $errors[] = ['row' => $row_num, 'error' => 'Phone number already exists in database'];
                continue;
            }
            
            $phone_numbers[] = $phone;
            
            // Prepare data for insertion
            $params = [
                ':seller_name' => substr($row['seller_name'] ?? '', 0, 255),
                ':phone_number' => $phone,
                ':assigned_by' => substr($row['assigned_by'] ?? '', 0, 100),
                ':update_1' => $row['update_1'] ?? '',
                ':update_2' => $row['update_2'] ?? '',
                ':update_3' => $row['update_3'] ?? '',
                ':seller_id' => substr($row['seller_id'] ?? '', 0, 50),
                ':store_name' => substr($row['store_name'] ?? '', 0, 255),
                ':lead_link' => substr($row['lead_link'] ?? '', 0, 255),
                ':lead_source' => substr($row['lead_source'] ?? '', 0, 100),
                ':before_after_registered' => substr($row['before_after_registered'] ?? '', 0, 50),
                ':store_status' => substr($row['store_status'] ?? '', 0, 50),
                ':major_reasons' => $row['major_reasons'] ?? '',
                ':created_by' => $user_uid
            ];
            
            try {
                $stmt->execute($params);
                $success_count++;
            } catch (PDOException $e) {
                $errors[] = ['row' => $row_num, 'error' => 'Database error: ' . $e->getMessage()];
            }
        }
        
        // Prepare response
        $response = [
            'status' => 'success',
            'message' => 'Upload completed',
            'data' => [
                'total_rows' => $total_rows,
                'success_count' => $success_count,
                'error_count' => count($errors),
                'duplicates_count' => $duplicates_count,
                'errors' => $errors
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

// Parse CSV file
function parseCSV($filepath) {
    $data = [];
    $header = null;
    
    if (($handle = fopen($filepath, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            // Clean the row
            $row = array_map('trim', $row);
            
            if (!$header) {
                // Create headers
                $header = array_map(function($h) {
                    $h = strtolower($h);
                    $h = str_replace(' ', '_', $h);
                    $h = str_replace('-', '_', $h);
                    $h = preg_replace('/[^a-z0-9_]/', '', $h);
                    return $h;
                }, $row);
            } else {
                // Create associative array
                $row_data = [];
                foreach ($header as $index => $key) {
                    if (!empty($key)) {
                        $row_data[$key] = $row[$index] ?? '';
                    }
                }
                if (!empty($row_data)) {
                    $data[] = $row_data;
                }
            }
        }
        fclose($handle);
    }
    
    return $data;
}

// Parse Excel file
function parseExcel($filepath) {
    $data = [];
    
    try {
        $spreadsheet = IOFactory::load($filepath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        if (empty($rows)) {
            return $data;
        }
        
        // Get headers from first row
        $headers = array_map(function($h) {
            if (empty($h)) return '';
            $h = strtolower(trim($h));
            $h = str_replace(' ', '_', $h);
            $h = str_replace('-', '_', $h);
            $h = preg_replace('/[^a-z0-9_]/', '', $h);
            return $h;
        }, $rows[0]);
        
        // Remove empty headers
        $valid_headers = [];
        $header_indices = [];
        foreach ($headers as $index => $header) {
            if (!empty($header)) {
                $valid_headers[] = $header;
                $header_indices[] = $index;
            }
        }
        
        // Process data rows
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $row_data = [];
            
            foreach ($header_indices as $pos => $col_index) {
                $header = $valid_headers[$pos];
                $value = $row[$col_index] ?? '';
                
                // Handle Excel dates
                if (is_numeric($value) && $value > 40000) {
                    try {
                        $date = Date::excelToDateTimeObject($value);
                        $value = $date->format('Y-m-d');
                    } catch (Exception $e) {
                        // Keep as is
                    }
                }
                
                $row_data[$header] = $value;
            }
            
            // Only add if row has some data
            if (!empty(array_filter($row_data))) {
                $data[] = $row_data;
            }
        }
        
    } catch (Exception $e) {
        throw new Exception('Error parsing Excel: ' . $e->getMessage());
    }
    
    return $data;
}