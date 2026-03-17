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

// Get user details
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
                    <h1 class="h2 mb-2 mb-sm-0">
                        <i class="bi bi-share-fill text-primary me-2"></i>
                        Shared Sellers List
                    </h1>
                    <div class="d-flex gap-2">
                        <a href="share-seller.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Share New Seller
                        </a>
                        <a href="workstation_sellers_list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Sellers
                        </a>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Share Type</label>
                                        <select class="form-select" id="filterType">
                                            <option value="all">All Shares</option>
                                            <option value="sent">Sent by Me</option>
                                            <option value="received">Received by Me</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Status</label>
                                        <select class="form-select" id="filterStatus">
                                            <option value="all">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="accepted">Accepted</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Search</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="bi bi-search"></i>
                                            </span>
                                            <input type="text" class="form-control" id="searchInput" 
                                                   placeholder="Search by seller, phone, or person...">
                                            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button class="btn btn-primary w-100" id="applyFilters">
                                            <i class="bi bi-filter me-1"></i>Apply Filters
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Active Filters -->
                                <div class="mt-3" id="activeFilters" style="display: none;">
                                    <hr>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <span class="text-muted">Active Filters:</span>
                                        <span class="badge bg-info text-dark" id="activeTypeFilter"></span>
                                        <span class="badge bg-info text-dark" id="activeStatusFilter"></span>
                                        <span class="badge bg-info text-dark" id="activeSearchFilter"></span>
                                        <button class="btn btn-sm btn-outline-danger" id="clearAllFilters">
                                            <i class="bi bi-x-circle"></i> Clear All
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title">Total Shares</h6>
                                <h3 id="totalCount">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title">Pending</h6>
                                <h3 id="pendingCount">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title">Accepted</h6>
                                <h3 id="acceptedCount">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title">Rejected</h6>
                                <h3 id="rejectedCount">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shared Sellers Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-list-ul text-primary me-2"></i>
                                    All Shared Sellers
                                </h5>
                                <div>
                                    <span class="text-muted me-2" id="showingCount"></span>
                                    <button class="btn btn-sm btn-outline-secondary" id="refreshTable">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Type</th>
                                                <th>Seller ID</th>
                                                <th>Seller Details</th>
                                                <th>Shared With/By</th>
                                                <th>Response</th>
                                                <th>Status</th>
                                                <th>Shared At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sharedSellersTable">
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <div class="spinner-border text-primary mb-3" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <p class="text-muted">Loading shared sellers...</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="text-muted small" id="paginationInfo"></div>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination pagination-sm mb-0" id="pagination">
                                            <!-- Pagination will be dynamically added -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <!-- Share Details Modal -->
    <div class="modal fade" id="shareDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Share Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="shareDetailsContent">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-repeat text-primary me-2"></i>
                        Update Share Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="updateShareId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Status</label>
                        <select class="form-select" id="updateStatus">
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmUpdate">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .badge-sent { background-color: #0d6efd; color: white; }
        .badge-received { background-color: #198754; color: white; }
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-accepted { background-color: #198754; color: #fff; }
        .badge-rejected { background-color: #dc3545; color: #fff; }
        
        .table td {
            vertical-align: middle;
        }
        
        .share-type-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        
        .pagination {
            margin-bottom: 0;
        }
        
        .page-link {
            padding: 0.375rem 0.75rem;
        }
        
        .summary-card {
            transition: transform 0.2s;
        }
        
        .summary-card:hover {
            transform: translateY(-2px);
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/share-sellers/shared-sellers-list.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>