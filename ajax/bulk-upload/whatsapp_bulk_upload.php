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

if ($_POST['action'] !== 'bulk_upload') {
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

if (!in_array($ext, ['xlsx','xls','csv'])) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Invalid file type"
    ]);
    exit;
}

try {

    $pdo = db();

    /* ------------------------------
       DELETE OLD DATA (AS REQUESTED)
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
            "status"=>"error",
            "message"=>"File empty"
        ]);
        exit;
    }

    $headers = array_map(function($h){

        $h = strtolower(trim($h));
        $h = str_replace(' ','_',$h);
        $h = preg_replace('/[^a-z0-9_]/','',$h);

        return $h;

    }, $rows[0]);

    $success = 0;
    $errors = [];

    $sql = "INSERT INTO whatsapp_customers
            (seller_name,phone_number,assigned_by,update_1,update_2,update_3,created_by)
            VALUES
            (:seller_name,:phone_number,:assigned_by,:update_1,:update_2,:update_3,:created_by)";

    $stmt = $pdo->prepare($sql);

    /* ------------------------------
       LOOP ROWS
    ------------------------------ */

    for($i=1;$i<count($rows);$i++){

        $row = $rows[$i];
        $data=[];

        foreach($headers as $k=>$header){

            $data[$header] = $row[$k] ?? '';

        }

        /* ------------------------------
           CLEAN PHONE NUMBER
        ------------------------------ */

        $phone = trim($data['phone_number'] ?? '');

        if (is_numeric($phone)) {
            $phone = number_format($phone,0,'','');
        }

        $phone = preg_replace('/[^0-9]/','',$phone);

        if(strlen($phone) > 10){
            $phone = substr($phone,-10);
        }

        if(strlen($phone) < 10){

            $errors[]=[
                "row"=>$i+1,
                "error"=>"Invalid phone"
            ];

            continue;
        }

        try{

            $stmt->execute([

                ":seller_name" => substr($data['seller_name'] ?? '',0,255),

                ":phone_number" => $phone,

                ":assigned_by" => substr($data['assigned_by'] ?? '',0,100),

                ":update_1" => $data['update_1'] ?? '',
                ":update_2" => $data['update_2'] ?? '',
                ":update_3" => $data['update_3'] ?? '',

                ":created_by" => $user_uid

            ]);

            $success++;

        }
        catch(Exception $e){

            $errors[]=[
                "row"=>$i+1,
                "error"=>$e->getMessage()
            ];

        }

    }

    echo json_encode([

        "status"=>"success",

        "data"=>[

            "total_rows"=>count($rows)-1,
            "success_count"=>$success,
            "error_count"=>count($errors),
            "errors"=>$errors

        ]

    ]);

}
catch(Exception $e){

    echo json_encode([
        "status"=>"error",
        "message"=>$e->getMessage()
    ]);

}