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
    'not_interested' => "customer_response = 'Not interested'",
    'later' => "customer_response = 'Later'",
    'schedule' => "customer_response = 'Schedule' OR customer_response = 'Shedule'", // Handle both spellings
    'switch_off' => "customer_response = 'Switch Off'",
    'no_business' => "customer_response = 'No Business'",
    'out_of_service' => "customer_response = 'Out of Service'",
    'whatsapp_sent' => "customer_response = 'Whatsapp Details sent'",
    'testing' => "customer_response = 'Testing'"
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

                <!-- 3x4 Cards Grid (12 cards) -->
                <div class="row g-3 mt-2">
                    <!-- Card 1: CNP - Danger -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-danger bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-telephone-x text-danger fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">CNP</h6>
                                        <h3 class="fw-bold text-danger mb-0"><?= $counts['cnp'] ?></h3>
                                        <small class="text-muted">Call Not Picked</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="cnp_sellers.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Later Call - Info -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-clock-history text-info fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Later Call</h6>
                                        <h3 class="fw-bold text-info mb-0"><?= $counts['later'] ?></h3>
                                        <small class="text-muted">Call Back Later</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="later_sellers.php" class="btn btn-outline-info btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Schedule Calls - Success -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-calendar-check text-success fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Schedule</h6>
                                        <h3 class="fw-bold text-success mb-0"><?= $counts['schedule'] ?></h3>
                                        <small class="text-muted">Scheduled Follow-ups</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="schedule_sellers.php" class="btn btn-outline-success btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 8: Switch Off - Secondary -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-secondary bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-power text-secondary fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Switch Off</h6>
                                        <h3 class="fw-bold text-secondary mb-0"><?= $counts['switch_off'] ?></h3>
                                        <small class="text-muted">Phone Switched Off</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="switchoff_sellers.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 10: Out of Service - Danger (Dark) -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-danger bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Out of Service</h6>
                                        <h3 class="fw-bold text-danger mb-0"><?= $counts['out_of_service'] ?></h3>
                                        <small class="text-muted">Number Inactive</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="out_of_services_sellers.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 11: WhatsApp Sent - Success (Light) -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-whatsapp text-success fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">WhatsApp Sent</h6>
                                        <h3 class="fw-bold text-success mb-0"><?= $counts['whatsapp_sent'] ?></h3>
                                        <small class="text-muted">Details Sent</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="filtered_list.php?status=Whatsapp%20Details%20sent" class="btn btn-outline-success btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 12: Testing - Info (Light) -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-bug text-info fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Testing</h6>
                                        <h3 class="fw-bold text-info mb-0"><?= $counts['testing'] ?></h3>
                                        <small class="text-muted">Test Calls</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="testing_sellers.php" class="btn btn-outline-info btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 7: Not Interested - Warning -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-warning bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-hand-thumbs-down text-warning fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Not Interested</h6>
                                        <h3 class="fw-bold text-warning mb-0"><?= $counts['not_interested'] ?></h3>
                                        <small class="text-muted">Rejected</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="not_interested_sellers.php" class="btn btn-outline-warning btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 9: No Business - Dark -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-dark bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-briefcase text-dark fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">No Business</h6>
                                        <h3 class="fw-bold text-dark mb-0"><?= $counts['no_business'] ?></h3>
                                        <small class="text-muted">Not Operating</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="no_business_sellers.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <style>
        .card-hover {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        /* Custom color classes */
        .bg-purple {
            background-color: #6f42c1 !important;
        }

        .bg-purple.bg-opacity-10 {
            background-color: rgba(111, 66, 193, 0.1) !important;
        }

        .text-purple {
            color: #6f42c1 !important;
        }

        .btn-outline-purple {
            color: #6f42c1;
            border-color: #6f42c1;
        }

        .btn-outline-purple:hover {
            color: #fff;
            background-color: #6f42c1;
            border-color: #6f42c1;
        }

        .bg-indigo {
            background-color: #6610f2 !important;
        }

        .bg-indigo.bg-opacity-10 {
            background-color: rgba(102, 16, 242, 0.1) !important;
        }

        .text-indigo {
            color: #6610f2 !important;
        }

        .btn-outline-indigo {
            color: #6610f2;
            border-color: #6610f2;
        }

        .btn-outline-indigo:hover {
            color: #fff;
            background-color: #6610f2;
            border-color: #6610f2;
        }

        .badge {
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .col-sm-6 {
                margin-bottom: 10px;
            }
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>

</html>