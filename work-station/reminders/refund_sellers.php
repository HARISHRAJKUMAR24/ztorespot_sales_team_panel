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
                            <span class="badge bg-danger px-3 py-2 fs-6">
                                <i class="bi bi-arrow-return-left me-1"></i> Refunds
                            </span>
                            <h1 class="h2 mb-0">Refund Sellers</h1>
                        </div>
                        <p class="text-muted mb-0">Sellers who have requested or received refunds</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-danger bg-opacity-10 border-danger h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-danger mb-2">Total Refunds</span>
                                        <h2 class="mb-0" id="totalCount">0</h2>
                                        <small class="text-muted">Refund requests</small>
                                    </div>
                                    <div class="bg-danger bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-arrow-return-left fs-1 text-danger"></i>
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
                                        <span class="badge bg-warning mb-2">Total Amount</span>
                                        <h2 class="mb-0" id="totalAmount">₹0</h2>
                                        <small class="text-muted">Refund amount</small>
                                    </div>
                                    <div class="bg-warning bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-cash-stack fs-1 text-warning"></i>
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
                                        <span class="badge bg-info mb-2">This Week</span>
                                        <h2 class="mb-0" id="weekCount">0</h2>
                                        <small class="text-muted">Refunds this week</small>
                                    </div>
                                    <div class="bg-info bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-calendar-week fs-1 text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary bg-opacity-10 border-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary mb-2">This Month</span>
                                        <h2 class="mb-0" id="monthCount">0</h2>
                                        <small class="text-muted">Refunds this month</small>
                                    </div>
                                    <div class="bg-primary bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-calendar-month fs-1 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-funnel text-danger me-2"></i>
                            Filter Options
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Date Range</label>
                                <select class="form-select form-select-sm" id="dateFilter">
                                    <option value="">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                    <option value="year">This Year</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Plan</label>
                                <select class="form-select form-select-sm" id="planFilter">
                                    <option value="">All Plans</option>
                                    <option value="Welcome">Welcome Plan</option>
                                    <option value="Starter">Starter Plan</option>
                                    <option value="Professional">Professional Plan</option>
                                    <option value="Intermediate">Intermediate Plan</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Amount Range</label>
                                <select class="form-select form-select-sm" id="amountFilter">
                                    <option value="">All Amounts</option>
                                    <option value="0-1000">₹0 - ₹1,000</option>
                                    <option value="1000-5000">₹1,000 - ₹5,000</option>
                                    <option value="5000-10000">₹5,000 - ₹10,000</option>
                                    <option value="10000+">Above ₹10,000</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-danger w-100" id="applyFilters">
                                    <i class="bi bi-funnel me-1"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Table Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-arrow-return-left text-danger me-2"></i>
                                Refund List
                            </h5>
                            <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
                                <div class="input-group input-group-sm" style="min-width: 250px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search text-danger"></i>
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
                            <div class="spinner-border text-danger" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading refund data...</p>
                        </div>

                        <!-- Data Table -->
                        <div id="dataTable" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-danger bg-opacity-10">
                                        <tr>
                                            <th class="sortable" data-sort="id">ID <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="entry_date">Date <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="work_details_update">Business Name <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="phone_number">Phone <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th>Plan</th>
                                            <th>Refund Amount</th>
                                            <th>Refund Date</th>
                                            <th>Reason</th>
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
                            <i class="bi bi-arrow-return-left fs-1 text-danger opacity-50"></i>
                            <h5 class="mt-3 text-muted">No Refund Records Found</h5>
                            <p class="text-muted">Sellers with refunds will appear here</p>
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
                <div class="modal-header bg-danger bg-opacity-10">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-return-left text-danger me-2"></i>
                        Refund Details
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
        .sortable:hover { background-color: rgba(220, 53, 69, 0.1) !important; }
        .badge { padding: 0.5em 0.8em; border-radius: 25px; }
        .toast { min-width: 250px; }
        .card { transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; }
        
        .amount-badge {
            background-color: #ffc107;
            color: #000;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .table td, .table th { padding: 0.5rem; font-size: 0.875rem; }
            .btn-sm { padding: 0.25rem 0.4rem; }
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
       <script src="<?= BASE_URL ?>js/work-station/reminders/refund_sellers.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>