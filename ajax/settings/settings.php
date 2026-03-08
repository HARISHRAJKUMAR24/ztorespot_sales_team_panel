<?php
// ajax/settings/settings.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once "../../config/config.php";
require_once "../../lib/functions.php";

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

if ($action === 'upload_image') {
    // Handle profile image upload using user_uid
    $result = handleProfileImageUpload($user_uid, $_FILES['profile_image'] ?? []);
    
    if ($result['success']) {
        // Update database and handle old image deletion
        $pdo = db();
        $update_result = updateUserProfileImage($pdo, $user_uid, $result['path']);
        
        if ($update_result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Profile image updated successfully',
                'image_path' => BASE_URL . $result['path']
            ]);
        } else {
            // If database update fails, delete the newly uploaded image
            deleteProfileImage($result['path']);
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => $result['message']]);
    }
    
} elseif ($action === 'update_profile') {
    // Update profile information using user_uid
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validate inputs
    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Name is required']);
        exit;
    }
    
    if (empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Phone is required']);
        exit;
    }
    
    // Clean phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    $pdo = db();
    
    // Check if phone exists for other users (using user_uid)
    $check = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND user_uid != ?");
    $check->execute([$phone, $user_uid]);
    if ($check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Phone number already used by another account']);
        exit;
    }
    
    // Check if email exists for other users (if provided)
    if (!empty($email)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND user_uid != ? AND email IS NOT NULL");
        $check->execute([$email, $user_uid]);
        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Email already used by another account']);
            exit;
        }
    }
    
    // Update user profile using user_uid
    $sql = "UPDATE users SET name = ?, phone = ?, email = ? WHERE user_uid = ?";
    $params = [$name, $phone, !empty($email) ? $email : null, $user_uid];
    
    $update = $pdo->prepare($sql);
    $result = $update->execute($params);
    
    if ($result) {
        // Update session variables
        $_SESSION['user_name'] = $name;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_email'] = $email;
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'name' => $name
        ]);
    } else {
        $errorInfo = $update->errorInfo();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to update profile: ' . ($errorInfo[2] ?? 'Unknown error')
        ]);
    }
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>