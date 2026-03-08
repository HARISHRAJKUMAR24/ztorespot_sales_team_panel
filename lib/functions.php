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
    // Check session
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        return true;
    }
    
    // Check remember token cookie
    if (isset($_COOKIE['remember_token'])) {
        $user = getUserByRememberToken($_COOKIE['remember_token']);
        if ($user) {
            // Restore session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_uid'] = $user['user_uid'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            return true;
        }
    }
    
    return false;
}

/**
 * Create remember me token
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
            return $token;
        }
        return false;
    } catch (Exception $e) {
        error_log("Error creating remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by remember token
 */
function getUserByRememberToken($token)
{
    try {
        $pdo = db();
        if (!$pdo) {
            return false;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
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
 */
function clearRememberToken($user_id)
{
    try {
        $pdo = db();
        if (!$pdo) {
            return false;
        }
        
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $result = $stmt->execute([$user_id]);
        
        setcookie("remember_token", "", time() - 3600, "/");
        
        return $result;
    } catch (Exception $e) {
        error_log("Error clearing remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Register a new user
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
            'user_uid' => $user_uid,
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

// ============================================
// PROFILE IMAGE FUNCTIONS USING USER_UID
// ============================================

/**
 * Handle profile image upload and deletion using user_uid
 * Structure: uploads/ZTS16482/2024/03/profile_1234567890.jpg
 * 
 * @param string $user_uid User UID (like ZTS16482)
 * @param array $file $_FILES['profile_image'] array
 * @return array ['success' => bool, 'message' => string, 'path' => string]
 */
function handleProfileImageUpload($user_uid, $file) {
    try {
        // Validate file
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!in_array($file['type'], $allowed_types)) {
            return ['success' => false, 'message' => 'Only JPG, PNG & GIF files are allowed'];
        }
        
        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File size must be less than 2MB'];
        }
        
        // Create directory structure: uploads/user_uid/year/month/
        $year = date('Y');
        $month = date('m');
        $date = date('d');
        $upload_dir = __DIR__ . "/../uploads/{$user_uid}/{$date}/{$month}/{$year}/";
        
        // Create directories recursively if they don't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                return ['success' => false, 'message' => 'Failed to create directory structure'];
            }
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            return ['success' => false, 'message' => 'Upload directory is not writable'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Relative path for database (from root)
        $relative_path = "uploads/{$user_uid}/{$date}/{$month}/{$year}/{$filename}";
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true, 
                'message' => 'Image uploaded successfully',
                'path' => $relative_path,
                'full_path' => $filepath
            ];
        } else {
            $error = error_get_last();
            return ['success' => false, 'message' => 'Failed to upload file: ' . ($error['message'] ?? 'Unknown error')];
        }
        
    } catch (Exception $e) {
        error_log("Profile image upload error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
    }
}

/**
 * Delete old profile image and clean up empty directories using user_uid
 * 
 * @param string $image_path Relative path to image (from database)
 * @return bool Success or failure
 */
function deleteProfileImage($image_path) {
    try {
        if (empty($image_path)) {
            return false;
        }
        
        $full_path = __DIR__ . '/../' . $image_path;
        
        // Delete the image file
        if (file_exists($full_path)) {
            if (!unlink($full_path)) {
                error_log("Failed to delete image: " . $full_path);
                return false;
            }
            
            // Clean up empty directories
            $month_dir = dirname($full_path);      // .../month/
            $year_dir = dirname($month_dir);       // .../year/
            $user_dir = dirname($year_dir);        // .../user_uid/
            
            // Remove month directory if empty
            if (is_dir($month_dir) && count(scandir($month_dir)) == 2) { // 2 = . and ..
                rmdir($month_dir);
                
                // Remove year directory if empty
                if (is_dir($year_dir) && count(scandir($year_dir)) == 2) {
                    rmdir($year_dir);
                    
                    // Remove user directory if empty
                    if (is_dir($user_dir) && count(scandir($user_dir)) == 2) {
                        rmdir($user_dir);
                    }
                }
            }
            
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Profile image deletion error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user profile image in database and handle old image deletion
 * Uses user_uid to identify the user
 * 
 * @param PDO $pdo Database connection
 * @param string $user_uid User UID (like ZTS16482)
 * @param string $new_image_path New image relative path
 * @return bool Success or failure
 */
function updateUserProfileImage($pdo, $user_uid, $new_image_path) {
    try {
        // Get old image path using user_uid
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE user_uid = ?");
        $stmt->execute([$user_uid]);
        $old_image = $stmt->fetchColumn();
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update database with new image using user_uid
        $update = $pdo->prepare("UPDATE users SET profile_image = ? WHERE user_uid = ?");
        $result = $update->execute([$new_image_path, $user_uid]);
        
        if ($result) {
            // Commit transaction first
            $pdo->commit();
            
            // Delete old image after successful database update
            if ($old_image) {
                deleteProfileImage($old_image);
            }
            
            return true;
        } else {
            $pdo->rollBack();
            return false;
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Update user profile image error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by user_uid
 * 
 * @param string $user_uid User UID (like ZTS16482)
 * @return array|false User data or false if not found
 */
function getUserByUid($user_uid) {
    try {
        $pdo = db();
        if (!$pdo) {
            return false;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_uid = ?");
        $stmt->execute([$user_uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            unset($user['password']);
        }
        
        return $user;
    } catch (Exception $e) {
        error_log("Error getting user by UID: " . $e->getMessage());
        return false;
    }
}
?>