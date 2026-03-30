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

// Get plan counts
$plan_counts = getPlanCounts($pdo, $user_uid);
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php template('head-tag'); ?>

<body class="bg-light">
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;"></div>

    <!-- SVG Icons -->
    <?php template('svg-icons'); ?>

    <!-- Navigation -->
    <?php template('top-navbar'); ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php template('side-navbar'); ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-3 px-md-4 py-4">
                <!-- Header -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center flex-wrap flex-md-nowrap pt-3 pb-2 mb-4 border-bottom gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary px-3 py-2 fs-6">
                                <i class="bi bi-arrow-repeat me-1"></i> Renewals
                            </span>
                            <h1 class="h2 mb-0">Renewal Management</h1>
                        </div>
                        <p class="text-muted mb-0">Track seller plan renewals and expiry dates</p>
                    </div>
                    <div class="d-flex gap-2">
                    </div>
                </div>

                <!-- Plan Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <a href="renewal_sellers.php?plan=welcome" class="text-decoration-none">
                            <div class="card bg-success bg-opacity-10 border-success h-100 card-hover">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-success mb-2">Welcome Plan</span>
                                            <h2 class="mb-0 text-success"><?= $plan_counts['welcome'] ?></h2>
                                            <small class="text-muted">Active sellers</small>
                                        </div>
                                        <div class="bg-success bg-opacity-25 p-3 rounded-circle">
                                            <i class="bi bi-gem fs-1 text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="renewal_sellers.php?plan=starter" class="text-decoration-none">
                            <div class="card bg-info bg-opacity-10 border-info h-100 card-hover">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-info mb-2">Starter Plan</span>
                                            <h2 class="mb-0 text-info"><?= $plan_counts['starter'] ?></h2>
                                            <small class="text-muted">Active sellers</small>
                                        </div>
                                        <div class="bg-info bg-opacity-25 p-3 rounded-circle">
                                            <i class="bi bi-rocket-takeoff fs-1 text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="renewal_sellers.php?plan=intermediate" class="text-decoration-none">
                            <div class="card bg-warning bg-opacity-10 border-warning h-100 card-hover">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-warning mb-2">Intermediate Plan</span>
                                            <h2 class="mb-0 text-warning"><?= $plan_counts['intermediate'] ?></h2>
                                            <small class="text-muted">Active sellers</small>
                                        </div>
                                        <div class="bg-warning bg-opacity-25 p-3 rounded-circle">
                                            <i class="bi bi-bar-chart-steps fs-1 text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="renewal_sellers.php?plan=professional" class="text-decoration-none">
                            <div class="card bg-primary bg-opacity-10 border-primary h-100 card-hover">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-primary mb-2">Professional Plan</span>
                                            <h2 class="mb-0 text-primary"><?= $plan_counts['professional'] ?></h2>
                                            <small class="text-muted">Active sellers</small>
                                        </div>
                                        <div class="bg-primary bg-opacity-25 p-3 rounded-circle">
                                            <i class="bi bi-briefcase fs-1 text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Renewal Status Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary bg-opacity-10 border-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary mb-2">Total Active</span>
                                        <h2 class="mb-0" id="activeCount">0</h2>
                                        <small class="text-muted">Active plans</small>
                                    </div>
                                    <div class="bg-primary bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-check-circle fs-1 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning bg-opacity-10 border-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-warning mb-2">Renewal Alert</span>
                                        <h2 class="mb-0" id="alertCount">0</h2>
                                        <small class="text-muted">Due for renewal</small>
                                    </div>
                                    <div class="bg-warning bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info bg-opacity-10 border-info h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-info mb-2">Near Expiry</span>
                                        <h2 class="mb-0" id="nearExpiryCount">0</h2>
                                        <small class="text-muted">30 days left</small>
                                    </div>
                                    <div class="bg-info bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-clock-history fs-1 text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger bg-opacity-10 border-danger h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-danger mb-2">Expired</span>
                                        <h2 class="mb-0" id="expiredCount">0</h2>
                                        <small class="text-muted">Past due date</small>
                                    </div>
                                    <div class="bg-danger bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-calendar-x fs-1 text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <ul class="nav nav-pills nav-fill gap-2" id="renewalTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-filter="all" type="button">
                                    <i class="bi bi-list-ul me-1"></i> All Renewals
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="active-tab" data-filter="active" type="button">
                                    <i class="bi bi-check-circle me-1 text-success"></i> Active
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="renewal-alert-tab" data-filter="renewal_alert" type="button">
                                    <i class="bi bi-exclamation-triangle me-1 text-warning"></i> Renewal Alert
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="near-expiry-tab" data-filter="near_expiry" type="button">
                                    <i class="bi bi-clock-history me-1 text-info"></i> Near Expiry
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="expired-tab" data-filter="expired" type="button">
                                    <i class="bi bi-calendar-x me-1 text-danger"></i> Expired
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Search and Table Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-arrow-repeat text-primary me-2"></i>
                                Renewal List
                            </h5>
                            <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
                                <div class="input-group input-group-sm" style="min-width: 250px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="searchInput" 
                                           placeholder="Search by name or phone...">
                                </div>
                                <select class="form-select form-select-sm" id="perPage" style="min-width: 70px;">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0 p-md-3">
                        <!-- Loading Spinner -->
                        <div id="loadingSpinner" class="text-center py-5" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading renewal data...</p>
                        </div>

                        <!-- Data Table -->
                        <div id="dataTable" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-primary bg-opacity-10">
                                        <tr>
                                            <th class="sortable" data-sort="id">ID <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="work_details_update">Business Name <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="phone_number">Phone <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th>Plan</th>
                                            <th>Start Date</th>
                                            <th>Duration</th>
                                            <th>Renewal Date</th>
                                            <th>Days Left</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody"></tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-4 px-3">
                                <div class="text-muted small mb-2 mb-sm-0" id="paginationInfo"></div>
                                <nav>
                                    <ul class="pagination pagination-sm" id="pagination"></ul>
                                </nav>
                            </div>
                        </div>

                        <!-- No Data Message -->
                        <div id="noData" class="text-center py-5" style="display: none;">
                            <i class="bi bi-arrow-repeat fs-1 text-primary opacity-50"></i>
                            <h5 class="mt-3 text-muted">No Renewal Records Found</h5>
                            <p class="text-muted">Sellers with active plans will appear here</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary bg-opacity-10">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-repeat text-primary me-2"></i>
                        Renewal Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="sellerDetails" class="row g-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .sortable { cursor: pointer; user-select: none; }
        .sortable:hover { background-color: rgba(13, 110, 253, 0.1) !important; }
        .badge { padding: 0.5em 0.8em; border-radius: 25px; }
        .toast { min-width: 250px; }
        .card { transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; }
        
        .card-hover {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
        }
        a .card-hover {
            color: inherit;
        }
        
        .days-badge {
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
        }
        
        .nav-pills .nav-link {
            color: #6c757d;
            border-radius: 50px;
            padding: 0.5rem 1rem;
        }
        
        .nav-pills .nav-link:hover {
            background-color: rgba(13, 110, 253, 0.1);
        }
        
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        
        @media (max-width: 768px) {
            .table td, .table th { padding: 0.5rem; font-size: 0.875rem; }
            .btn-sm { padding: 0.25rem 0.4rem; }
            .nav-pills .nav-link {
                padding: 0.4rem 0.5rem;
                font-size: 0.85rem;
            }
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/reminders/renewal_sellers.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>