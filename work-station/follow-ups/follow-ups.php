<?php
require_once '../../lib/functions.php';
require_once '../../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// Get current user data
$user_uid = $_SESSION['user_uid'];
$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_uid = ?");
$stmt->execute([$user_uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php template('head-tag'); ?>

<body class="bg-light">
    <!-- SVG Icons -->
    <?php template('svg-icons'); ?>

    <!-- Navigation -->
    <?php template('top-navbar'); ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php template('side-navbar'); ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h4">
                        <i class="bi bi-clock-history me-2"></i>
                        Workstation Follow Up
                    </h1>
                    <a href="workstation_dashboard.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>

                <!-- Cards -->
                <div class="row g-3">
                    <!-- Follow Up Sellers -->
                    <div class="col-12 col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="bi bi-person-lines-fill text-primary fs-1 mb-3 d-block"></i>
                                <h5 class="card-title">Follow Up Sellers</h5>
                                <p class="card-text small text-muted mb-3">
                                    View sellers with "Later" or "Call Back AT" response
                                </p>
                                <a href="workstation_followup_list.php" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-arrow-right-circle me-2"></i>View
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Sheet Follow Up -->
                    <div class="col-12 col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="bi bi-file-spreadsheet-fill text-success fs-1 mb-3 d-block"></i>
                                <h5 class="card-title">Sheet Follow Up</h5>
                                <p class="card-text small text-muted mb-3">
                                    View sellers imported from sheets that need follow up
                                </p>
                                <a href="sheets_followup_list.php" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-arrow-right-circle me-2"></i>View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info -->
                <div class="alert alert-info mt-3 py-2 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Click View buttons to see follow up lists
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>