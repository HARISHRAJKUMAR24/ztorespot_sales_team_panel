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

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'entry_date';

// Build query for Call Back AT sellers
$count_sql = "SELECT COUNT(*) FROM sales_person_sellers 
              WHERE user_uid = ? AND customer_response = 'Call Back AT'";

$sql = "SELECT * FROM sales_person_sellers 
        WHERE user_uid = ? AND customer_response = 'Call Back AT'";

$params = [$user_uid];
$count_params = [$user_uid];

// Add date range filter based on filter type
if (!empty($from_date) && !empty($to_date)) {
    if ($filter_type == 'entry_date') {
        $count_sql .= " AND DATE(entry_date) BETWEEN ? AND ?";
        $sql .= " AND DATE(entry_date) BETWEEN ? AND ?";
    } else {
        $count_sql .= " AND DATE(updated_at) BETWEEN ? AND ?";
        $sql .= " AND DATE(updated_at) BETWEEN ? AND ?";
    }
    $params[] = $from_date;
    $params[] = $to_date;
    $count_params[] = $from_date;
    $count_params[] = $to_date;
} elseif (!empty($from_date)) {
    if ($filter_type == 'entry_date') {
        $count_sql .= " AND DATE(entry_date) >= ?";
        $sql .= " AND DATE(entry_date) >= ?";
    } else {
        $count_sql .= " AND DATE(updated_at) >= ?";
        $sql .= " AND DATE(updated_at) >= ?";
    }
    $params[] = $from_date;
    $count_params[] = $from_date;
} elseif (!empty($to_date)) {
    if ($filter_type == 'entry_date') {
        $count_sql .= " AND DATE(entry_date) <= ?";
        $sql .= " AND DATE(entry_date) <= ?";
    } else {
        $count_sql .= " AND DATE(updated_at) <= ?";
        $sql .= " AND DATE(updated_at) <= ?";
    }
    $params[] = $to_date;
    $count_params[] = $to_date;
}

// Add search filter
if (!empty($search)) {
    $count_sql .= " AND (work_details_update LIKE ? OR phone_number LIKE ? OR customer_queries LIKE ? OR source_type LIKE ? OR latest_update LIKE ?)";
    $sql .= " AND (work_details_update LIKE ? OR phone_number LIKE ? OR customer_queries LIKE ? OR source_type LIKE ? OR latest_update LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
}

$sql .= " ORDER BY entry_date DESC, id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Get total records for pagination
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get Call Back AT sellers
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to extract call back time from latest_update
function getCallBackTime($seller) {
    // First check if call_timing has value
    if (!empty($seller['call_timing'])) {
        return $seller['call_timing'];
    }
    
    // Extract from latest_update
    $latest_update = $seller['latest_update'] ?? '';
    if (!empty($latest_update)) {
        // Pattern: "Customer asked to call back at: Morning 9-11 AM"
        if (preg_match('/Customer asked to call back at:\s*(.+)/', $latest_update, $matches)) {
            return trim($matches[1]);
        }
    }
    
    return 'Not set';
}

// Format dates for display
function formatDate($date) {
    if (empty($date)) return '-';
    return date('d M Y', strtotime($date));
}

// Convert YYYY-MM-DD to DD/MM/YYYY for display in input
function formatDateForInput($date) {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}

// Truncate text function
function truncateText($text, $maxLength = 60) {
    if (empty($text)) return '-';
    if (strlen($text) <= $maxLength) return $text;
    return substr($text, 0, $maxLength) . '...';
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php template('head-tag'); ?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Call Back AT - Work Station</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Bootstrap Datepicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">
    
    <!-- jQuery (required for datepicker) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Datepicker JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>

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
            color: #6f42c1;
        }
        .page-item.active .page-link {
            background-color: #6f42c1;
            border-color: #6f42c1;
            color: #fff;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(111, 66, 193, 0.05);
        }
        .datepicker {
            cursor: pointer;
            background-color: white;
        }
        .datepicker-dropdown {
            z-index: 9999 !important;
        }
        .remembering-notes-cell {
            max-width: 300px;
            white-space: normal;
            word-wrap: break-word;
        }
        .call-time-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
        }
        @media (max-width: 768px) {
            .remembering-notes-cell {
                max-width: 150px;
            }
            .call-time-badge {
                font-size: 0.7rem;
                padding: 0.25rem 0.5rem;
            }
        }
    </style>
</head>

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
                            <i class="bi bi-telephone-forward text-purple me-2"></i>
                            Call Back AT
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Total: <?= $total_records ?> sellers waiting for specific time callback
                        </p>
                    </div>

                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-purple bg-opacity-10 border-purple h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-purple mb-2">Total Call Back AT</span>
                                        <h2 class="mb-0"><?= $total_records ?></h2>
                                        <small class="text-muted">Specific time callbacks</small>
                                    </div>
                                    <div class="bg-purple bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-telephone-forward fs-1 text-purple"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning bg-opacity-10 border-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-warning mb-2">Morning</span>
                                        <h2 class="mb-0" id="morningCount">0</h2>
                                        <small class="text-muted">9 AM - 1 PM</small>
                                    </div>
                                    <div class="bg-warning bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-sunrise fs-1 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info bg-opacity-10 border-info h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-info mb-2">Afternoon</span>
                                        <h2 class="mb-0" id="afternoonCount">0</h2>
                                        <small class="text-muted">2 PM - 6 PM</small>
                                    </div>
                                    <div class="bg-info bg-opacity-25 p-3 rounded-circle">
                                        <i class="bi bi-sunset fs-1 text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Bar -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-3">
                                <form method="GET" class="row g-3" id="filterForm">
                                    <!-- Search Input -->
                                    <div class="col-12 col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="bi bi-search"></i>
                                            </span>
                                            <input type="text" class="form-control" 
                                                   name="search" 
                                                   placeholder="Search by name, phone, call time..." 
                                                   value="<?= htmlspecialchars($search) ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Filter Type Selection -->
                                    <div class="col-12 col-md-2">
                                        <select class="form-select" name="filter_type" id="filter_type">
                                            <option value="entry_date" <?= $filter_type == 'entry_date' ? 'selected' : '' ?>>Entry Date</option>
                                            <option value="update_date" <?= $filter_type == 'update_date' ? 'selected' : '' ?>>Last Update Date</option>
                                        </select>
                                    </div>
                                    
                                    <!-- From Date -->
                                    <div class="col-12 col-md-2">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="bi bi-calendar3"></i>
                                            </span>
                                            <input type="text" class="form-control datepicker" 
                                                   name="from_date_display" 
                                                   id="from_date_display"
                                                   placeholder="From Date"
                                                   value="<?= htmlspecialchars(formatDateForInput($from_date)) ?>"
                                                   autocomplete="off">
                                            <input type="hidden" name="from_date" id="from_date" value="<?= htmlspecialchars($from_date) ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- To Date -->
                                    <div class="col-12 col-md-2">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="bi bi-calendar3"></i>
                                            </span>
                                            <input type="text" class="form-control datepicker" 
                                                   name="to_date_display" 
                                                   id="to_date_display"
                                                   placeholder="To Date"
                                                   value="<?= htmlspecialchars(formatDateForInput($to_date)) ?>"
                                                   autocomplete="off">
                                            <input type="hidden" name="to_date" id="to_date" value="<?= htmlspecialchars($to_date) ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="col-12 col-md-2">
                                        <div class="d-grid gap-2 d-md-flex">
                                            <button class="btn btn-purple" type="submit" style="background-color: #6f42c1; border-color: #6f42c1; color: white;">
                                                <i class="bi bi-search me-1"></i>Filter
                                            </button>
                                            <?php if (!empty($search) || !empty($from_date) || !empty($to_date)): ?>
                                                <a href="callback_at_sellers.php" class="btn btn-outline-secondary">
                                                    <i class="bi bi-x-circle me-1"></i>Clear
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>
                                
                                <!-- Quick Date Filters -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="d-flex gap-2 flex-wrap">
                                            <span class="text-muted me-2">Quick Filters:</span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary quick-date" data-days="7">
                                                <i class="bi bi-calendar-week me-1"></i>Last 7 Days
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary quick-date" data-days="30">
                                                <i class="bi bi-calendar-month me-1"></i>Last 30 Days
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary quick-date" data-days="90">
                                                <i class="bi bi-calendar-range me-1"></i>Last 90 Days
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearDatesBtn">
                                                <i class="bi bi-eraser me-1"></i>Clear Dates
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Active Filters Display -->
                                <?php if (!empty($search) || !empty($from_date) || !empty($to_date)): ?>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="alert alert-light border mb-0 py-2">
                                                <i class="bi bi-funnel-fill me-2 text-primary"></i>
                                                <strong>Active Filters:</strong>
                                                <?php if (!empty($search)): ?>
                                                    <span class="badge bg-primary me-1">Search: <?= htmlspecialchars($search) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($from_date) && !empty($to_date)): ?>
                                                    <span class="badge bg-info me-1">
                                                        <?= $filter_type == 'entry_date' ? 'Entry Date' : 'Last Update' ?>: 
                                                        <?= formatDate($from_date) ?> - <?= formatDate($to_date) ?>
                                                    </span>
                                                <?php elseif (!empty($from_date)): ?>
                                                    <span class="badge bg-info me-1">
                                                        <?= $filter_type == 'entry_date' ? 'Entry Date From' : 'Last Update From' ?>: 
                                                        <?= formatDate($from_date) ?>
                                                    </span>
                                                <?php elseif (!empty($to_date)): ?>
                                                    <span class="badge bg-info me-1">
                                                        <?= $filter_type == 'entry_date' ? 'Entry Date Until' : 'Last Update Until' ?>: 
                                                        <?= formatDate($to_date) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="badge bg-secondary">Total: <?= $total_records ?> records</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
                                                <th class="px-3 py-3">Entry Date</th>
                                                <th class="px-3 py-3">Business Name</th>
                                                <th class="px-3 py-3">Phone Number</th>
                                                <th class="px-3 py-3">Seller Type</th>
                                                <th class="px-3 py-3">Call Back Time</th>
                                                <th class="px-3 py-3 remembering-notes-cell">Remembering Notes</th>
                                                <th class="px-3 py-3 text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($sellers)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center py-5">
                                                        <div class="py-4">
                                                            <i class="bi bi-telephone-forward fs-1 text-muted d-block mb-3"></i>
                                                            <h5 class="text-muted mb-2">No Call Back AT Sellers Found</h5>
                                                            <p class="text-muted mb-3">
                                                                <?php if (!empty($search) || !empty($from_date) || !empty($to_date)): ?>
                                                                    Try changing your filter criteria
                                                                <?php else: ?>
                                                                    No sellers waiting for specific time callback
                                                                <?php endif; ?>
                                                            </p>
                                                            <?php if (!empty($search) || !empty($from_date) || !empty($to_date)): ?>
                                                                <a href="callback_at_sellers.php" class="btn btn-outline-secondary">
                                                                    <i class="bi bi-x-circle me-1"></i>Clear Filters
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php 
                                                $morningCount = 0;
                                                $afternoonCount = 0;
                                                foreach ($sellers as $index => $seller): 
                                                    $callTime = getCallBackTime($seller);
                                                    if (stripos($callTime, 'Morning') !== false || (stripos($callTime, 'AM') !== false && stripos($callTime, '9') !== false)) {
                                                        $morningCount++;
                                                    } elseif (stripos($callTime, 'Afternoon') !== false || (stripos($callTime, 'PM') !== false)) {
                                                        $afternoonCount++;
                                                    }
                                                ?>
                                                    <tr>
                                                        <td class="px-3"><?= $offset + $index + 1 ?></td>
                                                        <td class="px-3">
                                                            <?php if (!empty($seller['entry_date'])): ?>
                                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                                    <i class="bi bi-calendar3 me-1"></i>
                                                                    <?= date('d M Y', strtotime($seller['entry_date'])) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
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
                                                            <?php
                                                            $timeClass = 'secondary';
                                                            if (stripos($callTime, 'Morning') !== false) $timeClass = 'warning';
                                                            elseif (stripos($callTime, 'Afternoon') !== false) $timeClass = 'info';
                                                            ?>
                                                            <span class="badge bg-<?= $timeClass ?> bg-opacity-10 text-<?= $timeClass ?> call-time-badge">
                                                                <i class="bi bi-clock me-1"></i>
                                                                <?= htmlspecialchars($callTime) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-3 remembering-notes-cell">
                                                            <div class="small" title="<?= htmlspecialchars($seller['remembering_notes'] ?? '') ?>">
                                                                <?= nl2br(htmlspecialchars(truncateText($seller['remembering_notes'] ?? '-', 80))) ?>
                                                            </div>
                                                        </td>
                                                        <td class="px-3 text-center">
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="<?= BASE_URL ?>work-station/sheets_edit_seller.php?id=<?= $seller['id'] ?>" 
                                                                   class="btn btn-outline-purple" 
                                                                   title="Edit Seller"
                                                                   style="border-color: #6f42c1; color: #6f42c1;">
                                                                    <i class="bi bi-pencil-square"></i>
                                                                </a>
                                                                <button type="button" 
                                                                        class="btn btn-outline-info view-seller" 
                                                                        data-id="<?= $seller['id'] ?>"
                                                                        title="View Details">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    <tr>
                                                <?php endforeach; ?>
                                                <script>
                                                    document.getElementById('morningCount').innerText = '<?= $morningCount ?>';
                                                    document.getElementById('afternoonCount').innerText = '<?= $afternoonCount ?>';
                                                </script>
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
                                                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&filter_type=<?= urlencode($filter_type) ?>">
                                                    <i class="bi bi-chevron-left"></i>
                                                </a>
                                            </li>
                                            
                                            <?php
                                            $start = max(1, $page - 2);
                                            $end = min($total_pages, $page + 2);
                                            
                                            if ($start > 1) {
                                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&filter_type=' . urlencode($filter_type) . '">1</a></li>';
                                                if ($start > 2) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                            }
                                            
                                            for ($i = $start; $i <= $end; $i++) {
                                                $active = $i == $page ? 'active' : '';
                                                echo '<li class="page-item ' . $active . '">';
                                                echo '<a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&filter_type=' . urlencode($filter_type) . '">' . $i . '</a>';
                                                echo '</li>';
                                            }
                                            
                                            if ($end < $total_pages) {
                                                if ($end < $total_pages - 1) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search) . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&filter_type=' . urlencode($filter_type) . '">' . $total_pages . '</a></li>';
                                            }
                                            ?>
                                            
                                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&filter_type=<?= urlencode($filter_type) ?>">
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
                <div class="modal-header bg-purple bg-opacity-10">
                    <h5 class="modal-title">
                        <i class="bi bi-person-badge text-purple me-2"></i>
                        Seller Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="sellerDetails">
                    <div class="text-center py-4">
                        <div class="spinner-border text-purple" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Close
                    </button>
                    <a href="#" id="editFromModal" class="btn btn-purple" style="background-color: #6f42c1; border-color: #6f42c1; color: white;">
                        <i class="bi bi-pencil-square me-1"></i>Edit Seller
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <!-- Custom JS -->
    <script>
    $(document).ready(function() {
        // Initialize datepickers
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true,
            orientation: 'bottom',
            zIndexOffset: 9999
        }).on('changeDate', function(e) {
            var selectedDate = e.format('dd/mm/yyyy');
            var hiddenField = $(this).closest('.input-group').find('input[type="hidden"]');
            if (hiddenField.length) {
                var parts = selectedDate.split('/');
                if (parts.length === 3) {
                    var yyyyMmDd = parts[2] + '-' + parts[1] + '-' + parts[0];
                    hiddenField.val(yyyyMmDd);
                }
            }
        });
        
        // Quick date filter buttons
        $('.quick-date').on('click', function() {
            var days = $(this).data('days');
            var toDate = new Date();
            var fromDate = new Date();
            fromDate.setDate(toDate.getDate() - days);
            
            var fromDateStr = ('0' + fromDate.getDate()).slice(-2) + '/' + 
                              ('0' + (fromDate.getMonth() + 1)).slice(-2) + '/' + 
                              fromDate.getFullYear();
            var toDateStr = ('0' + toDate.getDate()).slice(-2) + '/' + 
                            ('0' + (toDate.getMonth() + 1)).slice(-2) + '/' + 
                            toDate.getFullYear();
            
            $('#from_date_display').val(fromDateStr);
            $('#to_date_display').val(toDateStr);
            
            var fromDateHidden = fromDate.getFullYear() + '-' + 
                                 ('0' + (fromDate.getMonth() + 1)).slice(-2) + '-' + 
                                 ('0' + fromDate.getDate()).slice(-2);
            var toDateHidden = toDate.getFullYear() + '-' + 
                               ('0' + (toDate.getMonth() + 1)).slice(-2) + '-' + 
                               ('0' + toDate.getDate()).slice(-2);
            
            $('#from_date').val(fromDateHidden);
            $('#to_date').val(toDateHidden);
            
            $('#filterForm').submit();
        });
        
        // Clear dates button
        $('#clearDatesBtn').on('click', function() {
            $('#from_date_display').val('');
            $('#to_date_display').val('');
            $('#from_date').val('');
            $('#to_date').val('');
            $('#filterForm').submit();
        });
        
        // View Seller Details
        $('.view-seller').on('click', function() {
            const sellerId = $(this).data('id');
            
            if (!sellerId) {
                showToast('warning', 'Warning!', 'Invalid seller ID');
                return;
            }
            
            $('#sellerDetails').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-purple" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading seller details...</p>
                </div>
            `);
            
            $('#viewSellerModal').modal('show');
            $('#editFromModal').attr('href', 'sheets_edit_seller.php?id=' + sellerId);
            
            $.ajax({
                url: BASE_URL + 'ajax/work-station/reminders/get_seller_details.php',
                type: 'POST',
                data: { id: sellerId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.data) {
                        displaySellerDetails(response.data);
                    } else {
                        $('#sellerDetails').html(`
                            <div class="alert alert-danger mb-0">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                ${response.message || 'Failed to load seller details'}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    $('#sellerDetails').html(`
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Error loading seller details. Please try again.
                        </div>
                    `);
                }
            });
        });
        
        // Display seller details in modal
        function displaySellerDetails(seller) {
            if (!seller) return;
            
            const registrationStatus = seller.registration_status || 'No';
            const statusClass = registrationStatus === 'Yes' ? 'success' : 'secondary';
            
            const currentStatus = seller.current_status || 'Not yet';
            let statusBadgeClass = 'secondary';
            if (currentStatus === 'Upgraded') statusBadgeClass = 'success';
            else if (currentStatus === 'In Progress') statusBadgeClass = 'info';
            else if (currentStatus === 'Not yet') statusBadgeClass = 'warning';
            else if (currentStatus === 'Deleted') statusBadgeClass = 'danger';
            
            const entryDate = seller.entry_date ? new Date(seller.entry_date).toLocaleDateString('en-GB') : 'Not set';
            const createdDate = seller.created_at ? new Date(seller.created_at).toLocaleString() : 'Unknown';
            const updatedDate = seller.updated_at ? new Date(seller.updated_at).toLocaleString() : 'Never';
            const callBackTime = getCallBackTimeFromSeller(seller);
            
            const html = `
                <div class="container-fluid px-0">
                    <!-- Call Back AT Alert -->
                    <div class="alert alert-purple mb-3" style="background-color: rgba(111, 66, 193, 0.1); border-color: #6f42c1;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-telephone-forward fs-3 me-3 text-purple"></i>
                            <div>
                                <h6 class="mb-0 text-purple">Call Back AT</h6>
                                <small class="text-muted">Customer requested callback at specific time: ${escapeHtml(callBackTime)}</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3 border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title text-purple mb-3">
                                <i class="bi bi-info-circle-fill me-2"></i>Basic Information
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small mb-1">Business Name</label>
                                    <div class="fw-semibold">${escapeHtml(seller.work_details_update || 'N/A')}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small mb-1">Phone Number</label>
                                    <div class="fw-semibold">
                                        <span class="badge bg-light text-dark fs-6">
                                            <i class="bi bi-telephone me-1"></i>
                                            ${escapeHtml(seller.phone_number || 'N/A')}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small mb-1">Seller Type</label>
                                    <div><span class="badge bg-info">${escapeHtml(seller.source_type || 'N/A')}</span></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small mb-1">Registration Status</label>
                                    <div><span class="badge bg-${statusClass}">${registrationStatus}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3 border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title text-purple mb-3">
                                <i class="bi bi-chat-dots-fill me-2"></i>Response Information
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small mb-1">Customer Response</label>
                                    <div><span class="badge bg-purple">${escapeHtml(seller.customer_response || 'N/A')}</span></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small mb-1">Call Back Time</label>
                                    <div><span class="badge bg-warning bg-opacity-25 text-dark">${escapeHtml(callBackTime)}</span></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small mb-1">Plans Interested</label>
                                    <div>${escapeHtml(seller.plans_interested || 'None')}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small mb-1">Current Status</label>
                                    <div><span class="badge bg-${statusBadgeClass}">${escapeHtml(currentStatus)}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3 border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title text-purple mb-3">
                                <i class="bi bi-journal-text me-2"></i>Notes & Updates
                            </h6>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="text-muted small mb-1">Remembering Notes</label>
                                    <div class="p-2 bg-white rounded">${escapeHtml(seller.remembering_notes || 'No notes')}</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="text-muted small mb-1">Latest Update</label>
                                    <div class="p-2 bg-white rounded">${escapeHtml(seller.latest_update || 'No updates')}</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="text-muted small mb-1">Customer Queries</label>
                                    <div class="p-2 bg-white rounded">${escapeHtml(seller.customer_queries || 'No queries')}</div>
                                </div>
                                <div class="col-12">
                                    <label class="text-muted small mb-1">Remarks</label>
                                    <div class="p-2 bg-white rounded">${escapeHtml(seller.remarks || 'No remarks')}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title text-purple mb-3">
                                <i class="bi bi-calendar3 me-2"></i>Dates
                            </h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small mb-1">Entry Date</label>
                                    <div><i class="bi bi-calendar-check me-1"></i>${entryDate}</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small mb-1">Created At</label>
                                    <div><i class="bi bi-clock me-1"></i>${createdDate}</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-muted small mb-1">Last Updated</label>
                                    <div><i class="bi bi-arrow-repeat me-1"></i>${updatedDate}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#sellerDetails').html(html);
        }
        
        function getCallBackTimeFromSeller(seller) {
            if (seller.call_timing && seller.call_timing.trim() !== '') {
                return seller.call_timing;
            }
            if (seller.latest_update) {
                var match = seller.latest_update.match(/Customer asked to call back at:\s*(.+)/);
                if (match) {
                    return match[1];
                }
            }
            return 'Not set';
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showToast(type, title, message) {
            var id = 'toast-' + Date.now();
            var bgClass = 'bg-info';
            if (type === 'success') bgClass = 'bg-success';
            else if (type === 'danger') bgClass = 'bg-danger';
            else if (type === 'warning') bgClass = 'bg-warning';
            
            var toastHtml = `
                <div id="${id}" class="toast text-white ${bgClass}" role="alert" aria-live="assertive" 
                     aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                    <div class="toast-header ${bgClass} text-white border-0">
                        <strong class="me-auto">${title}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            $('.toast-container').append(toastHtml);
            var toastElement = document.getElementById(id);
            if (toastElement) {
                var toast = new bootstrap.Toast(toastElement);
                toast.show();
                $(toastElement).on('hidden.bs.toast', function() {
                    $(this).remove();
                });
            }
        }
    });
    </script>
    <style>
        .btn-purple {
            background-color: #6f42c1;
            border-color: #6f42c1;
            color: white;
        }
        .btn-purple:hover {
            background-color: #5a32a3;
            border-color: #5a32a3;
            color: white;
        }
        .btn-outline-purple {
            border-color: #6f42c1;
            color: #6f42c1;
        }
        .btn-outline-purple:hover {
            background-color: #6f42c1;
            color: white;
        }
        .text-purple {
            color: #6f42c1 !important;
        }
        .bg-purple {
            background-color: #6f42c1 !important;
        }
        .bg-purple.bg-opacity-10 {
            background-color: rgba(111, 66, 193, 0.1) !important;
        }
        .bg-purple.bg-opacity-25 {
            background-color: rgba(111, 66, 193, 0.25) !important;
        }
        .alert-purple {
            background-color: rgba(111, 66, 193, 0.1);
            border-color: #6f42c1;
        }
    </style>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>

</html>