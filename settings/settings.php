<?php
require_once '../lib/functions.php';
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// Get current user data using user_uid from session
$user_uid = $_SESSION['user_uid'];
$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_uid = ?");
$stmt->execute([$user_uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set profile image path
$profile_image = !empty($user['profile_image']) 
    ? BASE_URL . $user['profile_image'] 
    : 'https://via.placeholder.com/150';
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php template('head-tag'); ?>

<body class="bg-light">
    <div class="toast-container"></div>

    <!-- SVG Icons -->
    <?php template('svg-icons'); ?>

    <!-- Navigation -->
    <?php template('top-navbar'); ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php template('side-navbar'); ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h2">Account Settings</h1>
                </div>

                <div class="row g-4">
                    <!-- Profile Image Card -->
                    <div class="col-lg-4">
                        <div class="card settings-card h-100">
                            <div class="card-body text-center p-4">
                                <h5 class="card-title mb-4">Profile Picture</h5>
                                
                                <div class="profile-image-container">
                                    <img src="<?= $profile_image ?>" 
                                         alt="Profile" 
                                         class="profile-image" 
                                         id="profilePreview">
                                    <div class="profile-image-upload">
                                        <i class="bi bi-camera-fill"></i>
                                        <input type="file" id="profileImage" name="profile_image" accept="image/*">
                                    </div>
                                </div>
                                
                                <!-- Custom File Upload Button -->
                                <div class="mt-4">
                                    <label for="profileImage" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-upload me-2"></i>Choose Photo
                                    </label>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Max size: 2MB (JPG, PNG, GIF)
                                    </small>
                                </div>
                                
                                <!-- Current Image Path Info -->
                               
                                
                                <div id="imageMessage" class="mt-3"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Details Card -->
                    <div class="col-lg-8">
                        <div class="card settings-card">
                            <div class="card-body p-4">
                                <h5 class="card-title mb-4">Personal Information</h5>
                                
                                <form id="settingsForm">
                                    <!-- User UID (Read Only) - Using user_uid -->
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">User ID</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                            <input type="text" class="form-control" value="<?= $user['user_uid'] ?>" readonly disabled>
                                        </div>
                                        <small class="text-muted">This is your unique ID and cannot be changed</small>
                                    </div>

                                    <!-- Grid Layout for Personal Info -->
                                    <div class="row g-3">
                                        <!-- Full Name -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                            </div>
                                        </div>

                                        <!-- Phone Number -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                                <input type="tel" name="phone" class="form-control" value="<?= $user['phone'] ?>" required>
                                            </div>
                                        </div>

                                        <!-- Email -->
                                        <div class="col-md-12">
                                            <label class="form-label fw-semibold">Email Address</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                                <input type="email" name="email" class="form-control" value="<?= $user['email'] ?>" placeholder="Enter your email">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex justify-content-end mt-4 gap-2">
                                        
                                        <button type="submit" class="btn btn-primary" id="saveBtn">
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add CSS -->
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
        }
        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
        }
        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-image-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #0d6efd;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
            transition: all 0.3s;
            opacity: 0;
            pointer-events: none;
        }
        .profile-image-container:hover .profile-image-upload {
            opacity: 1;
        }
        .profile-image-upload:hover {
            background: #0b5ed7;
            transform: scale(1.1);
        }
        .profile-image-upload input {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .settings-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .settings-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
        }
        .btn-outline-primary:hover {
            background-color: #0d6efd;
            color: white;
        }
        /* Hide default file input */
        #profileImage {
            display: none;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/settings/settings.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>

</html>