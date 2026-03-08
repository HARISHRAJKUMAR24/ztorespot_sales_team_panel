<?php
require_once '../lib/functions.php';
require_once '../config/config.php';

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
                <!-- Header with responsive buttons -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center flex-wrap flex-md-nowrap pt-3 pb-2 mb-4 border-bottom gap-2">
                    <h1 class="h2 mb-2 mb-sm-0">Registered Sellers</h1>
                    <div class="d-flex gap-2 w-100 w-sm-auto">
                        <a href="bulk-upload/bulk_upload_registered_sellers.php" class="btn btn-success flex-fill flex-sm-grow-0">
                            <i class="bi bi-upload me-1"></i><span class="d-none d-sm-inline">Bulk Upload</span><span class="d-sm-none">Upload</span>
                        </a>
                        <button class="btn btn-primary flex-fill flex-sm-grow-0" id="exportBtn">
                            <i class="bi bi-download me-1"></i><span class="d-none d-sm-inline">Export</span><span class="d-sm-none">Export</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Cards - Responsive grid -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-center p-3">
                                <div class="text-center text-sm-start mb-2 mb-sm-0">
                                    <h6 class="card-title mb-1 small">Total</h6>
                                    <h3 class="mb-0" id="totalCount">0</h3>
                                </div>
                                <i class="bi bi-people fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-center p-3">
                                <div class="text-center text-sm-start mb-2 mb-sm-0">
                                    <h6 class="card-title mb-1 small">Active</h6>
                                    <h3 class="mb-0" id="activeCount">0</h3>
                                </div>
                                <i class="bi bi-check-circle fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-center p-3">
                                <div class="text-center text-sm-start mb-2 mb-sm-0">
                                    <h6 class="card-title mb-1 small">Inactive</h6>
                                    <h3 class="mb-0" id="inactiveCount">0</h3>
                                </div>
                                <i class="bi bi-x-circle fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-center p-3">
                                <div class="text-center text-sm-start mb-2 mb-sm-0">
                                    <h6 class="card-title mb-1 small">Follow-ups</h6>
                                    <h3 class="mb-0" id="followupCount">0</h3>
                                </div>
                                <i class="bi bi-calendar-check fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Card - Responsive -->
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
                                    <label class="form-label small fw-bold">Status</label>
                                    <select class="form-select form-select-sm" id="filterStatus">
                                        <option value="">All Status</option>
                                        <option value="Active">Active</option>
                                        <option value="In Active">In Active</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Assigned By</label>
                                    <select class="form-select form-select-sm" id="filterAssigned">
                                        <option value="">All</option>
                                        <option value="Prabha">Prabha</option>
                                        <option value="Sivagami">Sivagami</option>
                                        <option value="Suvalakshmi">Suvalakshmi</option>
                                        <option value="Harini">Harini</option>
                                        <option value="Gowsika">Gowsika</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Lead Source</label>
                                    <select class="form-select form-select-sm" id="filterSource">
                                        <option value="">All Sources</option>
                                        <option value="FB">FB (Ads)</option>
                                        <option value="Instagram">Instagram</option>
                                        <option value="Google">Google</option>
                                        <option value="Youtube">Youtube</option>
                                        <option value="Quora">Quora</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Date Range</label>
                                    <select class="form-select form-select-sm" id="filterDate">
                                        <option value="">All Time</option>
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month">This Month</option>
                                        <option value="year">This Year</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 d-flex gap-2">
                                    <button class="btn btn-primary btn-sm flex-fill" id="applyFilters">
                                        <i class="bi bi-funnel me-1"></i>Apply
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm flex-fill" id="clearFilters">
                                        <i class="bi bi-x-circle me-1"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Table Card - Responsive -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <h5 class="card-title mb-0">Sellers List</h5>
                            <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="searchInput" 
                                           placeholder="Search...">
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

                        <!-- Data Table - Horizontal scroll on mobile -->
                        <div id="dataTable" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable d-none d-md-table-cell" data-sort="s_no">S.No <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="date">Date <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="store_name">Store <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable d-none d-lg-table-cell" data-sort="customer_name">Customer <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="phone_number">Phone <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="status">Status <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="d-none d-xl-table-cell">Source</th>
                                            <th class="d-none d-sm-table-cell">Assigned</th>
                                            <th class="text-center">Calls</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody">
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination - Responsive -->
                            <nav aria-label="Page navigation" class="mt-4 px-3">
                                <ul class="pagination pagination-sm flex-wrap justify-content-center" id="pagination"></ul>
                                <div class="text-center text-muted small mt-2" id="paginationInfo"></div>
                            </nav>
                        </div>

                        <!-- No Data Message -->
                        <div id="noData" class="text-center py-5" style="display: none;">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="mt-2 text-muted">No sellers found</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Details Modal - Responsive -->
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

    <!-- Edit Modal - Responsive -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Seller</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="editId">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Store Name</label>
                            <input type="text" class="form-control" id="editStoreName">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Customer Name</label>
                            <input type="text" class="form-control" id="editCustomerName">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" class="form-control" id="editPhone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select class="form-select" id="editStatus">
                                <option value="Active">Active</option>
                                <option value="In Active">In Active</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Notes</label>
                            <textarea class="form-control" id="editNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEdit">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Responsive styles */
        .sortable {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        .sortable:hover {
            background-color: #e9ecef !important;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .toast-container {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1060;
            max-width: 90%;
        }
        
        /* Table cell padding for mobile */
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
        
        /* Better touch targets for mobile */
        @media (max-width: 575.98px) {
            .btn {
                padding: 0.5rem 0.75rem;
            }
            .pagination .page-link {
                padding: 0.4rem 0.65rem;
            }
        }
        
        /* Stats cards responsive text */
        .card-title small {
            font-size: 0.75rem;
        }
        
        /* Modal fullscreen on mobile */
        @media (max-width: 767.98px) {
            .modal-fullscreen-md-down {
                width: 100%;
                height: 100%;
                margin: 0;
                max-width: none;
            }
            .modal-fullscreen-md-down .modal-content {
                height: 100%;
                border-radius: 0;
            }
        }
        
        /* Smooth transitions */
        .collapse {
            transition: all 0.3s ease;
        }
        
        /* Fix for filter button on mobile */
        .btn-link:focus {
            box-shadow: none;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/sellers/registered_sellers.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>