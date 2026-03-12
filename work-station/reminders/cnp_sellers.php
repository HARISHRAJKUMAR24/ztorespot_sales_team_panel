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

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query for CNP sellers
$count_sql = "SELECT COUNT(*) FROM sales_person_sellers 
              WHERE user_uid = ? AND customer_response = 'CNP'";

$sql = "SELECT * FROM sales_person_sellers 
        WHERE user_uid = ? AND customer_response = 'CNP'";

if (!empty($search)) {
    $count_sql .= " AND (work_details_update LIKE ? OR phone_number LIKE ? OR customer_queries LIKE ?)";
    $sql .= " AND (work_details_update LIKE ? OR phone_number LIKE ? OR customer_queries LIKE ?)";
    $search_param = "%$search%";
}

$sql .= " ORDER BY entry_date DESC, id DESC LIMIT ? OFFSET ?";

// Get total records for pagination
$count_stmt = $pdo->prepare($count_sql);
if (!empty($search)) {
    $count_stmt->execute([$user_uid, $search_param, $search_param, $search_param]);
} else {
    $count_stmt->execute([$user_uid]);
}
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get CNP sellers
$stmt = $pdo->prepare($sql);
if (!empty($search)) {
    $stmt->execute([$user_uid, $search_param, $search_param, $search_param, $limit, $offset]);
} else {
    $stmt->execute([$user_uid, $limit, $offset]);
}
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <div>
                        <h1 class="h2 mb-1">
                            <i class="bi bi-telephone-x text-danger me-2"></i>
                            CNP (Call Not Picked) Sellers
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Total: <?= $total_records ?> sellers
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="sheets_followup_list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Follow Up
                        </a>
                    </div>
                </div>

                <!-- Search and Filter Bar -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-3">
                                <form method="GET" class="row g-3">
                                    <div class="col-12 col-md-8">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="bi bi-search"></i>
                                            </span>
                                            <input type="text" class="form-control" 
                                                   name="search" 
                                                   placeholder="Search by business name, phone number, or queries..." 
                                                   value="<?= htmlspecialchars($search) ?>">
                                            <button class="btn btn-success" type="submit">
                                                <i class="bi bi-search me-1"></i>Search
                                            </button>
                                            <?php if (!empty($search)): ?>
                                                <a href="cnp_sellers.php" class="btn btn-outline-secondary">
                                                    <i class="bi bi-x-circle me-1"></i>Clear
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-4 text-md-end">
                                        <span class="badge bg-danger bg-opacity-10 text-danger p-2">
                                            <i class="bi bi-telephone-x me-1"></i>
                                            CNP Records: <?= $total_records ?>
                                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sellers Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="px-3 py-3">#</th>
                                                <th class="px-3 py-3">Business Name</th>
                                                <th class="px-3 py-3">Phone Number</th>
                                                <th class="px-3 py-3">Seller Type</th>
                                                <th class="px-3 py-3">Entry Date</th>
                                                <th class="px-3 py-3">Latest Update</th>
                                                <th class="px-3 py-3">Current Status</th>
                                                <th class="px-3 py-3 text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($sellers)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center py-5">
                                                        <div class="py-4">
                                                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                                            <h5 class="text-muted mb-2">No CNP Sellers Found</h5>
                                                            <p class="text-muted mb-3">No call not picked records available</p>
                                                            <a href="sheets_followup_list.php" class="btn btn-success">
                                                                <i class="bi bi-arrow-left me-1"></i>Back to Follow Up
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($sellers as $index => $seller): ?>
                                                    <tr>
                                                        <td class="px-3"><?= $offset + $index + 1 ?></td>
                                                        <td class="px-3">
                                                            <div class="fw-semibold"><?= htmlspecialchars($seller['work_details_update'] ?? 'N/A') ?></div>
                                                            <?php if (!empty($seller['customer_queries'])): ?>
                                                                <small class="text-muted d-block text-truncate" style="max-width: 200px;">
                                                                    <i class="bi bi-chat-text me-1"></i>
                                                                    <?= htmlspecialchars(substr($seller['customer_queries'], 0, 30)) ?>...
                                                                </small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-3">
                                                            <span class="badge bg-light text-dark">
                                                                <i class="bi bi-telephone me-1"></i>
                                                                <?= htmlspecialchars($seller['phone_number']) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-3">
                                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                                <?= htmlspecialchars($seller['source_type'] ?? 'N/A') ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-3">
                                                            <?php if (!empty($seller['entry_date'])): ?>
                                                                <i class="bi bi-calendar3 me-1 text-muted"></i>
                                                                <?= date('d/m/Y', strtotime($seller['entry_date'])) ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-3">
                                                            <div class="text-truncate" style="max-width: 200px;">
                                                                <?= htmlspecialchars($seller['latest_update'] ?? 'N/A') ?>
                                                            </div>
                                                        </td>
                                                        <td class="px-3">
                                                            <?php
                                                            $status = $seller['current_status'] ?? 'Not yet';
                                                            $status_class = 'secondary';
                                                            if ($status == 'Upgraded') $status_class = 'success';
                                                            elseif ($status == 'In Progress') $status_class = 'warning';
                                                            elseif ($status == 'Deleted') $status_class = 'danger';
                                                            ?>
                                                            <span class="badge bg-<?= $status_class ?> bg-opacity-10 text-<?= $status_class ?>">
                                                                <?= htmlspecialchars($status) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-3 text-center">
                                                            <div class="btn-group btn-group-sm">
                                                                <!-- Edit Button with Pencil Icon -->
                                                                <a href="<?= BASE_URL ?>work-station/sheets_edit_seller.php?id=<?= $seller['id'] ?>" 
                                                                   class="btn btn-outline-success" 
                                                                   title="Edit Seller"
                                                                   data-bs-toggle="tooltip">
                                                                    <i class="bi bi-pencil-square"></i>
                                                                </a>
                                                                <button type="button" 
                                                                        class="btn btn-outline-info view-seller" 
                                                                        data-id="<?= $seller['id'] ?>"
                                                                        title="View Details"
                                                                        data-bs-toggle="tooltip">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="card-footer bg-white py-3">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center mb-0">
                                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" tabindex="-1">
                                                    <i class="bi bi-chevron-left"></i>
                                                </a>
                                            </li>
                                            
                                            <?php
                                            $start = max(1, $page - 2);
                                            $end = min($total_pages, $page + 2);
                                            
                                            if ($start > 1) {
                                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '">1</a></li>';
                                                if ($start > 2) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                            }
                                            
                                            for ($i = $start; $i <= $end; $i++) {
                                                $active = $i == $page ? 'active' : '';
                                                echo '<li class="page-item ' . $active . '">';
                                                echo '<a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '">' . $i . '</a>';
                                                echo '</li>';
                                            }
                                            
                                            if ($end < $total_pages) {
                                                if ($end < $total_pages - 1) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search) . '">' . $total_pages . '</a></li>';
                                            }
                                            ?>
                                            
                                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">
                                                    <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Seller Modal -->
    <div class="modal fade" id="viewSellerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="bi bi-person-badge text-success me-2"></i>
                        Seller Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="sellerDetails">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Close
                    </button>
                    <a href="#" id="editFromModal" class="btn btn-success">
                        <i class="bi bi-pencil-square me-1"></i>Edit Seller
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <style>
        .table td {
            vertical-align: middle;
        }
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
        }
        .badge {
            font-weight: 500;
            padding: 0.5em 0.75em;
        }
        .page-link {
            color: #198754;
        }
        .page-item.active .page-link {
            background-color: #198754;
            border-color: #198754;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(25, 135, 84, 0.05);
        }
        .text-truncate {
            max-width: 200px;
        }
        @media (max-width: 768px) {
            .text-truncate {
                max-width: 120px;
            }
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/reminders/cnp_sellers.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>