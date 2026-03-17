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
                        <i class="bi bi-inbox-fill text-success me-2"></i>
                        Received Sellers
                    </h1>
                    <div class="d-flex gap-2">
                        <a href="share-seller.php" class="btn btn-primary">
                            <i class="bi bi-share me-1"></i>Share Sellers
                        </a>
                        <a href="workstation_sellers_list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                    <div>
                        <strong>Sellers Shared With You</strong><br>
                        These are sellers that other team members have shared with you. You can view their details and update their status.
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Status</label>
                                        <select class="form-select" id="filterStatus">
                                            <option value="all">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="accepted">Accepted</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Shared By</label>
                                        <select class="form-select" id="filterSharedBy">
                                            <option value="all">All Users</option>
                                            <?php
                                            // Get distinct users who have shared with current user
                                            $users_stmt = $pdo->prepare("
                                                SELECT DISTINCT u.user_uid, u.name 
                                                FROM shared_sellers s
                                                JOIN users u ON s.shared_by_user_uid = u.user_uid
                                                WHERE s.shared_with_user_uid = ?
                                                ORDER BY u.name
                                            ");
                                            $users_stmt->execute([$user_uid]);
                                            $shared_by_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

                                            foreach ($shared_by_users as $shared_by):
                                            ?>
                                                <option value="<?= htmlspecialchars($shared_by['user_uid']) ?>">
                                                    <?= htmlspecialchars($shared_by['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Search</label>
                                        <input type="text" class="form-control" id="searchInput" placeholder="Search by seller name or phone...">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button class="btn btn-success w-100" id="applyFilters">
                                            <i class="bi bi-filter me-1"></i>Apply
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Received Sellers Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-inbox text-success me-2"></i>
                                    Sellers Shared With Me
                                </h5>
                                <span class="badge bg-success" id="totalCount">0</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Seller Details</th>
                                                <th>Shared By</th>
                                                <th>Response</th>
                                                <th>Status</th>
                                                <th>Shared At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="receivedSellersTable">
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                                    Loading received sellers...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white py-3">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mb-0" id="pagination">
                                        <!-- Pagination will be dynamically added -->
                                    </ul>
                                </nav>
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
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle me-2"></i>
                        Received Seller Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-repeat me-2"></i>
                        Update Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="btn btn-success" id="confirmUpdate">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-accepted {
            background-color: #198754;
            color: #fff;
        }

        .badge-rejected {
            background-color: #dc3545;
            color: #fff;
        }

        .table td {
            vertical-align: middle;
        }

        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .modal-header.bg-success {
            background-color: #198754 !important;
        }

        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }

        .btn-success:hover {
            background-color: #157347;
            border-color: #146c43;
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/received-sellers/received-sellers.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>

</html>