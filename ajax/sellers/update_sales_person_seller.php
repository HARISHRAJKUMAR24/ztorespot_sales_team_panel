<?php
require_once "../../config/config.php";
require_once "../../lib/functions.php";

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

$pdo = db();

$sql = "UPDATE sales_person_sellers SET
        entry_date = :entry_date,
        work_details_update = :work_details_update,
        source_type = :source_type,
        registration_status = :registration_status,
        phone_number = :phone_number,
        plans_interested = :plans_interested,
        customer_response = :customer_response,
        remembering_notes = :remembering_notes,
        latest_update = :latest_update,
        current_status = :current_status,
        customer_queries = :customer_queries,
        video_canva = :video_canva,
        call_timing = :call_timing,
        remarks = :remarks
        WHERE id = :id";

$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([
        ':id' => $id,
        ':entry_date' => $_POST['entry_date'] ?: null,
        ':work_details_update' => $_POST['work_details_update'] ?? '',
        ':source_type' => $_POST['source_type'] ?? '',
        ':registration_status' => $_POST['registration_status'] ?? '',
        ':phone_number' => $_POST['phone_number'] ?? '',
        ':plans_interested' => $_POST['plans_interested'] ?? '',
        ':customer_response' => $_POST['customer_response'] ?? '',
        ':remembering_notes' => $_POST['remembering_notes'] ?? '',
        ':latest_update' => $_POST['latest_update'] ?? '',
        ':current_status' => $_POST['current_status'] ?? '',
        ':customer_queries' => $_POST['customer_queries'] ?? '',
        ':video_canva' => $_POST['video_canva'] ?? '',
        ':call_timing' => $_POST['call_timing'] ?? '',
        ':remarks' => $_POST['remarks'] ?? ''
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Seller updated successfully']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update: ' . $e->getMessage()]);
}