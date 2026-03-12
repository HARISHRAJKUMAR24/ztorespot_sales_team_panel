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
            <main class="col-md-9 ms-sm-auto col-lg-10 px-3 px-md-4 py-4">
                <!-- Header -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center flex-wrap flex-md-nowrap pt-3 pb-2 mb-4 border-bottom gap-2">
                    <div>
                        <h1 class="h2 mb-1">
                            <i class="bi bi-file-spreadsheet text-success me-2"></i>
                            Sheets Follow Up
                        </h1>
                        <p class="text-muted mb-0">Manage your sheets follow up sellers</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="sheets_add_seller.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add New Seller
                        </a>
                        <a href="sheets_dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-4 col-md-3">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-center p-3">
                                <div class="text-center text-sm-start mb-2 mb-sm-0">
                                    <h6 class="card-title mb-1 small">Total Follow Ups</h6>
                                    <h3 class="mb-0" id="totalCount">0</h3>
                                </div>
                                <i class="bi bi-person-lines-fill fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-center p-3">
                                <div class="text-center text-sm-start mb-2 mb-sm-0">
                                    <h6 class="card-title mb-1 small">Later</h6>
                                    <h3 class="mb-0" id="laterCount">0</h3>
                                </div>
                                <i class="bi bi-clock fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-center p-3">
                                <div class="text-center text-sm-start mb-2 mb-sm-0">
                                    <h6 class="card-title mb-1 small">Call Back AT</h6>
                                    <h3 class="mb-0" id="callbackCount">0</h3>
                                </div>
                                <i class="bi bi-telephone-forward fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-center p-3">
                                <div class="text-center text-sm-start mb-2 mb-sm-0">
                                    <h6 class="card-title mb-1 small">Schedule</h6>
                                    <h3 class="mb-0" id="scheduleCount">0</h3>
                                </div>
                                <i class="bi bi-calendar-date fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <button class="btn btn-link text-decoration-none p-0 w-100 text-start d-flex justify-content-between align-items-center" 
                                    type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" 
                                    aria-expanded="true" aria-controls="filterCollapse">
                                <span>Filters</span>
                                <i class="bi bi-chevron-down d-md-none"></i>
                            </button>
                        </h5>
                    </div>
                    <div class="collapse show" id="filterCollapse">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Response Type</label>
                                    <select class="form-select form-select-sm" id="filterResponse">
                                        <option value="">All Responses</option>
                                        <option value="Later">Later</option>
                                        <option value="Call Back AT">Call Back AT</option>
                                        <option value="Schedule">Schedule</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Registration Status</label>
                                    <select class="form-select form-select-sm" id="filterRegStatus">
                                        <option value="">All Status</option>
                                        <option value="Yes">Registered</option>
                                        <option value="No">Not Registered</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Current Status</label>
                                    <select class="form-select form-select-sm" id="filterCurrentStatus">
                                        <option value="">All Status</option>
                                        <option value="Not yet">Not yet</option>
                                        <option value="Upgraded">Upgraded</option>
                                        <option value="In Progress">In Progress</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Date Range</label>
                                    <select class="form-select form-select-sm" id="filterDate">
                                        <option value="">All Time</option>
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month">This Month</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 d-flex gap-2">
                                    <button class="btn btn-primary btn-sm flex-fill" id="applyFilters">
                                        <i class="bi bi-funnel me-1"></i>Apply Filters
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm flex-fill" id="clearFilters">
                                        <i class="bi bi-x-circle me-1"></i>Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Table Card -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <h5 class="card-title mb-0">Sheets Follow Up List</h5>
                            <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="searchInput" 
                                           placeholder="Search by work details, phone...">
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
                        <div id="loadingSpinner" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading data...</p>
                        </div>

                        <!-- Data Table -->
                        <div id="dataTable" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-sort="id">ID <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="entry_date">Date <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="work_details_update">Work Details <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="phone_number">Phone <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="d-none d-lg-table-cell">Response</th>
                                            <th class="d-none d-lg-table-cell">Call Timing</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody">
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-4 px-3">
                                <div class="text-muted small mb-2 mb-sm-0" id="paginationInfo"></div>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                                </nav>
                            </div>
                        </div>

                        <!-- No Data Message -->
                        <div id="noData" class="text-center py-5" style="display: none;">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="mt-2 text-muted">No follow up sellers found</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Seller Details</h5>
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
        .sortable {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        .sortable:hover {
            background-color: #e9ecef !important;
        }
        .toast-container {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1060;
            max-width: 90%;
        }
        .badge {
            padding: 0.5em 0.8em;
            border-radius: 25px;
        }
        @media (max-width: 767.98px) {
            .table td, .table th {
                padding: 0.5rem 0.25rem;
                font-size: 0.875rem;
            }
            .btn-sm {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/follow-ups/sheets_followup_list.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>