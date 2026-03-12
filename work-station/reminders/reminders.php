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

// Get counts for different statuses - matching actual values in database
$statuses = [
    'cnp' => "customer_response = 'CNP'",
    'not_interested' => "customer_response = 'Not interested'", // Added missing
    'later' => "customer_response = 'Later'",
    'switch_off' => "customer_response = 'Switch Off'",
    'no_business' => "customer_response = 'No Business'", // Added missing
    'out_of_service' => "customer_response = 'Out of Service'",
    'whatsapp_sent' => "customer_response = 'Whatsapp Details sent'", // Added missing
    'call_back_at' => "customer_response = 'Call Back AT'", // Added missing
    'plan_upgraded' => "customer_response = 'Plan Upgraded'", // Added missing
    'testing' => "customer_response = 'Testing'", // Added missing
    'plan_interested' => "customer_response = 'Plan Interested'" // Added missing
];

$counts = [];
foreach ($statuses as $key => $condition) {
    $query = "SELECT COUNT(*) as count 
              FROM sales_person_sellers 
              WHERE user_uid = ? AND $condition";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_uid]);
    $counts[$key] = $stmt->fetchColumn();
}

// Get total count
$total_query = "SELECT COUNT(*) FROM sales_person_sellers WHERE user_uid = ?";
$stmt = $pdo->prepare($total_query);
$stmt->execute([$user_uid]);
$total_count = $stmt->fetchColumn();
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
                    <h1 class="h5 fw-semibold">
                        <i class="bi bi-telephone me-2 text-primary"></i>
                        Call Status Dashboard
                    </h1>
                    <div>
                        <span class="badge bg-primary rounded-pill px-3 py-2">
                            Total: <?= $total_count ?>
                        </span>
                        <a href="workstation_dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 ms-2">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>

                <!-- 3x2 Cards Grid -->
                <div class="row g-3 mt-2">
                    <!-- Card 1: CNP -->
                    <div class="col-md-4 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-danger bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-telephone-x text-danger"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">CNP (Call Not Picked)</h6>
                                        <h3 class="fw-bold text-danger mb-0"><?= $counts['cnp'] ?></h3>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="cnp_sellers.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Card 2: Later Call -->
                    <div class="col-md-4 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-clock-history text-info"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Later Call</h6>
                                        <h3 class="fw-bold text-info mb-0"><?= $counts['later'] ?></h3>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="filtered_list.php?status=Later" class="btn btn-outline-info btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Switch Off -->
                    <div class="col-md-4 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-secondary bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-power text-secondary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Switch Off</h6>
                                        <h3 class="fw-bold text-secondary mb-0"><?= $counts['switch_off'] ?></h3>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="filtered_list.php?status=Switch%20Off" class="btn btn-outline-secondary btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Out of Service -->
                    <div class="col-md-4 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-dark bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-exclamation-triangle text-dark"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Out of Service</h6>
                                        <h3 class="fw-bold text-dark mb-0"><?= $counts['out_of_service'] ?></h3>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="filtered_list.php?status=Out%20of%20Service" class="btn btn-outline-dark btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5: Not Interested -->
                    <div class="col-md-4 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-warning bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-hand-thumbs-down text-warning"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Not Interested</h6>
                                        <h3 class="fw-bold text-warning mb-0"><?= $counts['not_interested'] ?></h3>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="filtered_list.php?status=Not%20interested" class="btn btn-outline-warning btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
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