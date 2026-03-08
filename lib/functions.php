<?php
// lib/functions.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Template loader function
 */
function template($name)
{
    require __DIR__ . "/../templates/$name.php";
}

/**
 * Check if user is logged in via session
 */
function isLoggedIn()
{
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Create remember me token
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return string|false Token or false on failure
 */
function createRememberToken($pdo, $user_id)
{
    try {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        
        // Update user's remember_token in users table
        $updateStmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $result = $updateStmt->execute([$token, $user_id]);
        
        if ($result && $updateStmt->rowCount() > 0) {
            error_log("Token saved successfully for user: " . $user_id);
            return $token;
        } else {
            error_log("Failed to save token for user: " . $user_id);
            return false;
        }
    } catch (Exception $e) {
        error_log("Error creating remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by remember token
 * 
 * @param string $token Remember token
 * @return array|false User data or false if invalid/expired
 */
function getUserByRememberToken($token)
{
    try {
        $pdo = db();
        if (!$pdo) {
            return false;
        }
        
        // Find user by remember token
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Remove password from user data
            unset($user['password']);
        }
        
        return $user;
    } catch (Exception $e) {
        error_log("Error getting user by token: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear remember token (for logout)
 * 
 * @param int $user_id User ID
 * @return bool Success or failure
 */
function clearRememberToken($user_id)
{
    try {
        $pdo = db();
        if (!$pdo) {
            return false;
        }
        
        // Clear remember token for user
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $result = $stmt->execute([$user_id]);
        
        // Clear cookie
        setcookie("remember_token", "", time() - 3600, "/");
        
        return $result;
    } catch (Exception $e) {
        error_log("Error clearing remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Register a new user
 * 
 * @param PDO $pdo Database connection
 * @param string $name User's full name
 * @param string $phone User's phone number (cleaned)
 * @param string $email User's email (optional)
 * @param string $password User's password
 * @return array ['success' => bool, 'message' => string]
 */
function registerUser($pdo, $name, $phone, $email, $password) {
    try {
        // Check if phone already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $checkStmt->execute([$phone]);
        if ($checkStmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Phone number already registered'
            ];
        }

        // Check if email already exists (if provided)
        if (!empty($email)) {
            $checkEmailStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND email IS NOT NULL");
            $checkEmailStmt->execute([$email]);
            if ($checkEmailStmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Email already registered'
                ];
            }
        }

        // Generate unique user_uid
        $user_uid = generateUniqueUserUid($pdo);
        if (!$user_uid) {
            return [
                'success' => false,
                'message' => 'Unable to generate unique user ID. Please try again.'
            ];
        }

        // Hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Begin transaction
        $pdo->beginTransaction();
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (user_uid, name, phone, email, password, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $user_uid,
            $name,
            $phone,
            !empty($email) ? $email : null,
            $hash
        ]);
        
        if (!$result) {
            throw new Exception("Failed to insert user");
        }
        
        $userId = $pdo->lastInsertId();
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Account created successfully! Redirecting to login...',
            'user_id' => $userId,
            'phone' => $phone
        ];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Registration error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Registration failed. Please try again.'
        ];
    }
}

/**
 * Generate unique user UID
 */
function generateUniqueUserUid($pdo) {
    $maxAttempts = 10;
    $attempts = 0;

    while ($attempts < $maxAttempts) {
        $user_uid = "ZTS" . rand(10000, 99999);
        
        $checkUidStmt = $pdo->prepare("SELECT id FROM users WHERE user_uid = ?");
        $checkUidStmt->execute([$user_uid]);
        
        if (!$checkUidStmt->fetch()) {
            return $user_uid;
        }
        $attempts++;
    }
    return false;
}
?>