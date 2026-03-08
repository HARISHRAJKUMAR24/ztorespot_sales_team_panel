<?php
// ajax/forgot-password.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Set default timezone to Asia/Kolkata (or your local timezone)
date_default_timezone_set('Asia/Kolkata');

require_once "../../config/config.php";
require_once "../../lib/functions.php";
try {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            throw new Exception("Email is required");
        }
        
        $pdo = db();
        if (!$pdo) {
            throw new Exception("Database connection failed");
        }
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Email not registered']);
            exit;
        }
        
        // Create table
        $pdo->exec("CREATE TABLE IF NOT EXISTS otp_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(150) NOT NULL,
            otp VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Delete old OTPs
        $delete = $pdo->prepare("DELETE FROM otp_verifications WHERE email = ?");
        $delete->execute([$email]);
        
        // Set OTP to 987654
        $otp = '987654';
        
        // Calculate expiry using PHP time (10 minutes from now)
        $expires = date('Y-m-d H:i:s', time() + 600); // 600 seconds = 10 minutes
        
        $insert = $pdo->prepare("INSERT INTO otp_verifications (email, otp, expires_at) VALUES (?, ?, ?)");
        $insert->execute([$email, $otp, $expires]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'OTP sent successfully',
            'debug_otp' => $otp,
            'debug_expires' => $expires,
            'server_time' => date('Y-m-d H:i:s')
        ]);
        
    } elseif ($action === 'verify_otp') {
        $email = trim($_POST['email'] ?? '');
        $otp = trim($_POST['otp'] ?? '');
        
        if (empty($email) || empty($otp)) {
            throw new Exception("Email and OTP are required");
        }
        
        $pdo = db();
        if (!$pdo) {
            throw new Exception("Database connection failed");
        }
        
        // Get current server time
        $now = date('Y-m-d H:i:s');
        
        // Check if OTP is valid (using string comparison since dates are in same format)
        $stmt = $pdo->prepare("SELECT * FROM otp_verifications 
                               WHERE email = ? AND otp = ? AND expires_at >= ? 
                               ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $otp, $now]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Delete used OTP
            $delete = $pdo->prepare("DELETE FROM otp_verifications WHERE id = ?");
            $delete->execute([$result['id']]);
            
            session_start();
            $_SESSION['otp_verified'] = $email;
            
            echo json_encode([
                'status' => 'success',
                'message' => 'OTP verified successfully'
            ]);
        } else {
            // Check if OTP exists but expired
            $expiredStmt = $pdo->prepare("SELECT * FROM otp_verifications 
                                         WHERE email = ? AND otp = ? ORDER BY created_at DESC");
            $expiredStmt->execute([$email, $otp]);
            $expired = $expiredStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($expired) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'OTP expired. Please request a new one.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid OTP. Please try again.'
                ]);
            }
        }
        
    } elseif ($action === 'reset_password') {
        session_start();
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== $email) {
            throw new Exception("OTP verification required");
        }
        
        if ($password !== $confirm) {
            throw new Exception("Passwords do not match");
        }
        
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters");
        }
        
        $pdo = db();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $result = $stmt->execute([$hash, $email]);
        
        if ($result) {
            unset($_SESSION['otp_verified']);
            echo json_encode([
                'status' => 'success',
                'message' => 'Password reset successfully'
            ]);
        } else {
            throw new Exception("Failed to reset password");
        }
        
    } else {
        throw new Exception("Invalid action");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>