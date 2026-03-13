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







/**
 * Calculate renewal date based on plan duration and start date with custom criteria
 * @param string $start_date The date when plan was purchased/upgraded (Y-m-d format)
 * @param string $duration Duration string (e.g., "1 Month", "3 Months", "1 Year")
 * @param string $plan The plan name (Welcome, Starter, Intermediate, Professional)
 * @return array Returns array with renewal_date, days_remaining, status, and alert_days
 */
function calculateRenewalDate($start_date, $duration, $plan = '') {
    if (empty($start_date) || empty($duration)) {
        return [
            'renewal_date' => null,
            'days_remaining' => null,
            'status' => 'unknown',
            'formatted_date' => 'N/A',
            'alert_days' => 0,
            'should_show' => false
        ];
    }
    
    try {
        $start = new DateTime($start_date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        // Parse duration
        $duration = strtolower(trim($duration));
        $interval = null;
        $months = 0;
        $years = 0;
        
        // Handle different duration formats
        if (strpos($duration, 'month') !== false) {
            $months = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT);
            if ($months <= 0) $months = 1;
            $interval = new DateInterval("P{$months}M");
        } elseif (strpos($duration, 'year') !== false) {
            $years = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT);
            if ($years <= 0) $years = 1;
            $interval = new DateInterval("P{$years}Y");
        } elseif (strpos($duration, 'day') !== false) {
            $days = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT);
            if ($days <= 0) $days = 30;
            $interval = new DateInterval("P{$days}D");
        } else {
            // Default to 1 month if unknown
            $months = 1;
            $interval = new DateInterval("P1M");
        }
        
        // Calculate renewal date
        $renewal = clone $start;
        $renewal->add($interval);
        $renewal->setTime(0, 0, 0);
        
        // Calculate days remaining
        $days_remaining = $today->diff($renewal)->days;
        if ($today > $renewal) {
            $days_remaining = -$days_remaining;
        }
        
        // Determine alert days based on plan and duration
        $alert_days = getAlertDays($plan, $months, $years);
        
        // Determine if should show in renewal list (within alert days or expired)
        $should_show = ($days_remaining <= $alert_days && $days_remaining > 0) || $days_remaining <= 0;
        
        // Determine status
        $status = 'active';
        if ($days_remaining < 0) {
            $status = 'expired';
        } elseif ($days_remaining <= $alert_days) {
            $status = 'renewal_alert';
        } elseif ($days_remaining <= 30) {
            $status = 'near_expiry';
        }
        
        return [
            'renewal_date' => $renewal->format('Y-m-d'),
            'formatted_date' => $renewal->format('d/m/Y'),
            'days_remaining' => $days_remaining,
            'status' => $status,
            'start_date' => $start->format('Y-m-d'),
            'duration' => $duration,
            'alert_days' => $alert_days,
            'should_show' => $should_show,
            'plan' => $plan,
            'months' => $months,
            'years' => $years
        ];
    } catch (Exception $e) {
        return [
            'renewal_date' => null,
            'days_remaining' => null,
            'status' => 'unknown',
            'formatted_date' => 'N/A',
            'alert_days' => 0,
            'should_show' => false
        ];
    }
}

/**
 * Get alert days based on plan and duration
 * @param string $plan Plan name
 * @param int $months Number of months
 * @param int $years Number of years
 * @return int Number of days before renewal to show alert
 */
function getAlertDays($plan, $months, $years) {
    $plan = strtolower(trim($plan));
    
    // Welcome Plan criteria
    if (strpos($plan, 'welcome') !== false) {
        if ($months <= 1) {
            return 10; // 10 days for 1 month
        } elseif ($years >= 1) {
            return 20; // 20 days for 1 year or more
        }
    }
    
    // Starter Plan criteria
    elseif (strpos($plan, 'starter') !== false) {
        if ($months <= 3) {
            return 20; // 20 days for up to 3 months
        } elseif ($years >= 1) {
            return 30; // 30 days for 1 year
        } elseif ($years >= 2) {
            return 30; // 30 days for 2+ years
        }
    }
    
    // Intermediate Plan criteria
    elseif (strpos($plan, 'intermediate') !== false) {
        if ($years >= 1) {
            return 30; // 30 days for 1 year
        } elseif ($years >= 2) {
            return 30; // 30 days for 2+ years
        }
    }
    
    // Professional Plan criteria
    elseif (strpos($plan, 'professional') !== false) {
        if ($years >= 1) {
            return 30; // 30 days for 1 year
        } elseif ($years >= 2) {
            return 30; // 30 days for 2+ years
        }
    }
    
    // Default
    return 15;
}

/**
 * Get renewal status badge HTML
 * @param int $days_remaining
 * @return string HTML badge
 */
function getRenewalStatusBadge($days_remaining) {
    if ($days_remaining === null) {
        return '<span class="badge bg-secondary">Unknown</span>';
    }
    
    if ($days_remaining < 0) {
        return '<span class="badge bg-danger">Expired</span>';
    } elseif ($days_remaining == 0) {
        return '<span class="badge bg-warning text-dark">Due Today</span>';
    } elseif ($days_remaining <= 7) {
        return '<span class="badge bg-warning">Expiring Soon</span>';
    } elseif ($days_remaining <= 15) {
        return '<span class="badge bg-info">Near Expiry</span>';
    } elseif ($days_remaining <= 30) {
        return '<span class="badge bg-primary">Active</span>';
    } else {
        return '<span class="badge bg-success">Active</span>';
    }
}

/**
 * Get renewal countdown text
 * @param int $days_remaining
 * @return string Countdown text
 */
function getRenewalCountdownText($days_remaining) {
    if ($days_remaining === null) {
        return 'N/A';
    }
    
    if ($days_remaining < 0) {
        $days = abs($days_remaining);
        return "Expired {$days} days ago";
    } elseif ($days_remaining == 0) {
        return "Due today";
    } elseif ($days_remaining == 1) {
        return "1 day remaining";
    } else {
        return "{$days_remaining} days remaining";
    }
}

/**
 * Extract duration from remembering_notes
 * @param string $notes
 * @return string|null
 */
function extractDurationFromNotes($notes) {
    if (empty($notes)) return null;
    
    // Try to find "Upgraded Duration: X" pattern
    if (preg_match('/Upgraded Duration: ([^\n\.]+)/', $notes, $matches)) {
        return trim($matches[1]);
    }
    
    // Try to find duration in other formats
    if (preg_match('/(\d+\s*(month|months|year|years|day|days))/i', $notes, $matches)) {
        return $matches[1];
    }
    
    // Try to find standalone duration like "1 Month", "3 Months", etc.
    if (preg_match('/\b(1|3|6|12)\s*(Month|Months|Year|Years)\b/i', $notes, $matches)) {
        return $matches[0];
    }
    
    return null;
}

/**
 * Calculate renewal for a seller
 * @param array $seller Seller data from database
 * @return array Renewal information
 */
function calculateSellerRenewal($seller) {
    $start_date = null;
    $duration = null;
    $plan = $seller['plans_interested'] ?? '';
    
    // Get start date from entry_date or created_at
    if (!empty($seller['entry_date'])) {
        $start_date = $seller['entry_date'];
    } elseif (!empty($seller['created_at'])) {
        $start_date = date('Y-m-d', strtotime($seller['created_at']));
    }
    
    // Get duration from remembering_notes
    if (!empty($seller['remembering_notes'])) {
        $duration = extractDurationFromNotes($seller['remembering_notes']);
    }
    
    // If no duration found, try to get from plan name or set default
    if (empty($duration)) {
        // Check if plan name contains duration info
        $plan_lower = strtolower($plan);
        if (strpos($plan_lower, 'year') !== false) {
            $duration = '1 Year';
        } elseif (strpos($plan_lower, 'month') !== false) {
            $duration = '1 Month';
        } else {
            $duration = '1 Month'; // Default
        }
    }
    
    return calculateRenewalDate($start_date, $duration, $plan);
}

/**
 * Get plan counts for dashboard cards
 * @param PDO $pdo Database connection
 * @param string $user_uid User UID
 * @return array Plan counts
 */
function getPlanCounts($pdo, $user_uid) {
    $plans = [
        'welcome' => 0,
        'starter' => 0,
        'intermediate' => 0,
        'professional' => 0,
        'total' => 0
    ];
    
    try {
        // Get all sellers with plans
        $sql = "SELECT plans_interested FROM sales_person_sellers 
                WHERE user_uid = ? AND plans_interested IS NOT NULL 
                AND plans_interested != 'None' AND plans_interested != ''";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_uid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $plan = strtolower($row['plans_interested'] ?? '');
            if (strpos($plan, 'welcome') !== false) {
                $plans['welcome']++;
            } elseif (strpos($plan, 'starter') !== false) {
                $plans['starter']++;
            } elseif (strpos($plan, 'intermediate') !== false) {
                $plans['intermediate']++;
            } elseif (strpos($plan, 'professional') !== false) {
                $plans['professional']++;
            }
            $plans['total']++;
        }
    } catch (Exception $e) {
        error_log("Error getting plan counts: " . $e->getMessage());
    }
    
    return $plans;
}

/**
 * Get plan badge HTML
 * @param string $plan Plan name
 * @return string HTML badge
 */
function getPlanBadge($plan) {
    $plan_lower = strtolower($plan ?? '');
    
    if (strpos($plan_lower, 'welcome') !== false) {
        return '<span class="badge bg-success">Welcome Plan</span>';
    } elseif (strpos($plan_lower, 'starter') !== false) {
        return '<span class="badge bg-info">Starter Plan</span>';
    } elseif (strpos($plan_lower, 'intermediate') !== false) {
        return '<span class="badge bg-warning text-dark">Intermediate Plan</span>';
    } elseif (strpos($plan_lower, 'professional') !== false) {
        return '<span class="badge bg-primary">Professional Plan</span>';
    } else {
        return '<span class="badge bg-secondary">' . htmlspecialchars($plan) . '</span>';
    }
}
