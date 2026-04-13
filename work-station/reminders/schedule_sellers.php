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
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'schedule_date';

// Build query for Schedule sellers
$count_sql = "SELECT COUNT(*) FROM sales_person_sellers 
              WHERE user_uid = ? AND (customer_response = 'Schedule' OR customer_response = 'Shedule')";

$sql = "SELECT * FROM sales_person_sellers 
        WHERE user_uid = ? AND (customer_response = 'Schedule' OR customer_response = 'Shedule')";

$params = [$user_uid];
$count_params = [$user_uid];

// Add date range filter based on filter type
if (!empty($from_date) && !empty($to_date)) {
    if ($filter_type == 'schedule_date') {
        // Extract schedule date from call_timing or latest_update
        $count_sql .= " AND (DATE(STR_TO_DATE(SUBSTRING(call_timing, 11), '%d/%m/%Y')) BETWEEN ? AND ? 
                        OR DATE(STR_TO_DATE(SUBSTRING(latest_update, 11), '%d/%m/%Y')) BETWEEN ? AND ?)";
        $sql .= " AND (DATE(STR_TO_DATE(SUBSTRING(call_timing, 11), '%d/%m/%Y')) BETWEEN ? AND ? 
                 OR DATE(STR_TO_DATE(SUBSTRING(latest_update, 11), '%d/%m/%Y')) BETWEEN ? AND ?)";
        $params[] = $from_date;
        $params[] = $to_date;
        $params[] = $from_date;
        $params[] = $to_date;
        $count_params[] = $from_date;
        $count_params[] = $to_date;
        $count_params[] = $from_date;
        $count_params[] = $to_date;
    } elseif ($filter_type == 'entry_date') {
        $count_sql .= " AND DATE(entry_date) BETWEEN ? AND ?";
        $sql .= " AND DATE(entry_date) BETWEEN ? AND ?";
        $params[] = $from_date;
        $params[] = $to_date;
        $count_params[] = $from_date;
        $count_params[] = $to_date;
    } else {
        $count_sql .= " AND DATE(updated_at) BETWEEN ? AND ?";
        $sql .= " AND DATE(updated_at) BETWEEN ? AND ?";
        $params[] = $from_date;
        $params[] = $to_date;
        $count_params[] = $from_date;
        $count_params[] = $to_date;
    }
} elseif (!empty($from_date)) {
    if ($filter_type == 'schedule_date') {
        $count_sql .= " AND (DATE(STR_TO_DATE(SUBSTRING(call_timing, 11), '%d/%m/%Y')) >= ? 
                        OR DATE(STR_TO_DATE(SUBSTRING(latest_update, 11), '%d/%m/%Y')) >= ?)";
        $sql .= " AND (DATE(STR_TO_DATE(SUBSTRING(call_timing, 11), '%d/%m/%Y')) >= ? 
                 OR DATE(STR_TO_DATE(SUBSTRING(latest_update, 11), '%d/%m/%Y')) >= ?)";
        $params[] = $from_date;
        $params[] = $from_date;
        $count_params[] = $from_date;
        $count_params[] = $from_date;
    } elseif ($filter_type == 'entry_date') {
        $count_sql .= " AND DATE(entry_date) >= ?";
        $sql .= " AND DATE(entry_date) >= ?";
        $params[] = $from_date;
        $count_params[] = $from_date;
    } else {
        $count_sql .= " AND DATE(updated_at) >= ?";
        $sql .= " AND DATE(updated_at) >= ?";
        $params[] = $from_date;
        $count_params[] = $from_date;
    }
} elseif (!empty($to_date)) {
    if ($filter_type == 'schedule_date') {
        $count_sql .= " AND (DATE(STR_TO_DATE(SUBSTRING(call_timing, 11), '%d/%m/%Y')) <= ? 
                        OR DATE(STR_TO_DATE(SUBSTRING(latest_update, 11), '%d/%m/%Y')) <= ?)";
        $sql .= " AND (DATE(STR_TO_DATE(SUBSTRING(call_timing, 11), '%d/%m/%Y')) <= ? 
                 OR DATE(STR_TO_DATE(SUBSTRING(latest_update, 11), '%d/%m/%Y')) <= ?)";
        $params[] = $to_date;
        $params[] = $to_date;
        $count_params[] = $to_date;
        $count_params[] = $to_date;
    } elseif ($filter_type == 'entry_date') {
        $count_sql .= " AND DATE(entry_date) <= ?";
        $sql .= " AND DATE(entry_date) <= ?";
        $params[] = $to_date;
        $count_params[] = $to_date;
    } else {
        $count_sql .= " AND DATE(updated_at) <= ?";
        $sql .= " AND DATE(updated_at) <= ?";
        $params[] = $to_date;
        $count_params[] = $to_date;
    }
}

// Add search filter
if (!empty($search)) {
    $count_sql .= " AND (work_details_update LIKE ? OR phone_number LIKE ? OR customer_queries LIKE ? OR source_type LIKE ?)";
    $sql .= " AND (work_details_update LIKE ? OR phone_number LIKE ? OR customer_queries LIKE ? OR source_type LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
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

// Get Schedule sellers
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Extract schedule date from call_timing or latest_update
function getScheduleDate($seller) {
    // First check call_timing
    $call_timing = $seller['call_timing'] ?? '';
    if (!empty($call_timing) && strpos($call_timing, 'Schedule at ') === 0) {
        return str_replace('Schedule at ', '', $call_timing);
    }
    
    // Then check latest_update
    $latest_update = $seller['latest_update'] ?? '';
    if (!empty($latest_update) && strpos($latest_update, 'Schedule at ') === 0) {
        return str_replace('Schedule at ', '', $latest_update);
    }
    
    // Check if latest_update contains schedule date in the middle
    if (!empty($latest_update) && preg_match('/Schedule at (\d{2}\/\d{2}\/\d{4})/', $latest_update, $matches)) {
        return $matches[1];
    }
    
    return '-';
}

// Get schedule date for comparison
function getScheduleDateForCompare($seller) {
    $dateStr = getScheduleDate($seller);
    if ($dateStr != '-') {
        $dateParts = explode('/', $dateStr);
        if (count($dateParts) == 3) {
            return $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
        }
    }
    return null;
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php template('head-tag'); ?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Schedule Sellers - Work Station</title>

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
        .datepicker {
            cursor: pointer;
            background-color: white;
        }
        .datepicker-dropdown {
            z-index: 9999 !important;
        }
        .schedule-date-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
        }
        @media (max-width: 768px) {
            .text-truncate {
                max-width: 120px;
            }
            .schedule-date-badge {
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
                            <i class="bi bi-calendar-check text-success me-2"></i>
                            Scheduled Follow-ups
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Total: <?= $total_records ?> scheduled sellers
                        </p>
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
                                                   placeholder="Search by name, phone, queries..." 
                                                   value="<?= htmlspecialchars($search) ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Filter Type Selection -->
                                    <div class="col-12 col-md-2">
                                        <select class="form-select" name="filter_type" id="filter_type">
                                            <option value="schedule_date" <?= $filter_type == 'schedule_date' ? 'selected' : '' ?>>Schedule Date</option>
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
                                            <button class="btn btn-success" type="submit">
                                                <i class="bi bi-search me-1"></i>Filter
                                            </button>
                                            <?php if (!empty($search) || !empty($from_date) || !empty($to_date)): ?>
                                                <a href="schedule_sellers.php" class="btn btn-outline-secondary">
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
                                                <i class="bi bi-calendar-week me-1"></i>Next 7 Days
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary quick-date" data-days="30">
                                                <i class="bi bi-calendar-month me-1"></i>Next 30 Days
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
                                                        <?= $filter_type == 'schedule_date' ? 'Schedule Date' : ($filter_type == 'entry_date' ? 'Entry Date' : 'Last Update') ?>: 
                                                        <?= formatDate($from_date) ?> - <?= formatDate($to_date) ?>
                                                    </span>
                                                <?php elseif (!empty($from_date)): ?>
                                                    <span class="badge bg-info me-1">
                                                        <?= $filter_type == 'schedule_date' ? 'Schedule Date From' : ($filter_type == 'entry_date' ? 'Entry Date From' : 'Last Update From') ?>: 
                                                        <?= formatDate($from_date) ?>
                                                    </span>
                                                <?php elseif (!empty($to_date)): ?>
                                                    <span class="badge bg-info me-1">
                                                        <?= $filter_type == 'schedule_date' ? 'Schedule Date Until' : ($filter_type == 'entry_date' ? 'Entry Date Until' : 'Last Update Until') ?>: 
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
                                                <th class="px-3 py-3">Schedule Date</th>
                                                <th class="px-3 py-3">Status</th>
                                                <th class="px-3 py-3 text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($sellers)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center py-5">
                                                        <div class="py-4">
                                                            <i class="bi bi-calendar-check fs-1 text-muted d-block mb-3"></i>
                                                            <h5 class="text-muted mb-2">No Scheduled Sellers Found</h5>
                                                            <p class="text-muted mb-3">
                                                                <?php if (!empty($search) || !empty($from_date) || !empty($to_date)): ?>
                                                                    Try changing your filter criteria
                                                                <?php else: ?>
                                                                    No scheduled follow-ups available
                                                                <?php endif; ?>
                                                            </p>
                                                            <?php if (!empty($search) || !empty($from_date) || !empty($to_date)): ?>
                                                                <a href="schedule_sellers.php" class="btn btn-outline-secondary">
                                                                    <i class="bi bi-x-circle me-1"></i>Clear Filters
                                                                </a>

                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($sellers as $index => $seller): ?>
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
                                                            $scheduleDate = getScheduleDate($seller);
                                                            $dateClass = 'success';
                                                            $dateColor = 'green';
                                                            if ($scheduleDate != '-') {
                                                                $dateObj = DateTime::createFromFormat('d/m/Y', $scheduleDate);
                                                                if ($dateObj) {
                                                                    $today = new DateTime();
                                                                    if ($dateObj < $today) {
                                                                        $dateClass = 'danger';
                                                                        $dateColor = 'red';
                                                                    } elseif ($dateObj->diff($today)->days <= 3) {
                                                                        $dateClass = 'warning';
                                                                        $dateColor = 'orange';
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                            <span class="badge bg-<?= $dateClass ?> bg-opacity-10 text-<?= $dateClass ?> schedule-date-badge">
                                                                <i class="bi bi-calendar-date me-1"></i>
                                                                <?= htmlspecialchars($scheduleDate) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-3">
                                                            <?php
                                                            $status = $seller['current_status'] ?? 'Not yet';
                                                            $status_class = 'secondary';
                                                            if ($status == 'Upgraded') $status_class = 'success';
                                                            elseif ($status == 'In Progress') $status_class = 'info';
                                                            elseif ($status == 'Not yet') $status_class = 'warning';
                                                            elseif ($status == 'Deleted') $status_class = 'danger';
                                                            ?>
                                                            <span class="badge bg-<?= $status_class ?> bg-opacity-10 text-<?= $status_class ?>">
                                                                <?= htmlspecialchars($status) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-3 text-center">
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="<?= BASE_URL ?>work-station/sheets_edit_seller.php?id=<?= $seller['id'] ?>" 
                                                                   class="btn btn-outline-success" 
                                                                   title="Edit Seller">
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
                <div class="modal-header bg-success bg-opacity-10">
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

    <!-- Custom JS -->
    <script>
    $(document).ready(function() {
        // Initialize datepickers with proper settings
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
        
        // Quick date filter buttons (for schedule dates - next 7/30 days)
        $('.quick-date').on('click', function() {
            var days = $(this).data('days');
            var fromDate = new Date();
            var toDate = new Date();
            toDate.setDate(toDate.getDate() + days);
            
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
            
            // Set filter type to schedule_date
            $('#filter_type').val('schedule_date');
            
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
                    <div class="spinner-border text-success" role="status">
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
            const scheduleDate = getScheduleDateDisplay(seller);
            
            const html = `
                <div class="container-fluid px-0">
                    <div class="card mb-3 border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title text-success mb-3">
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
                            <h6 class="card-title text-success mb-3">
                                <i class="bi bi-chat-dots-fill me-2"></i>Response Information
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small mb-1">Customer Response</label>
                                    <div><span class="badge bg-success">${escapeHtml(seller.customer_response || 'N/A')}</span></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small mb-1">Schedule Date</label>
                                    <div><span class="badge bg-success bg-opacity-25 text-dark">${escapeHtml(scheduleDate)}</span></div>
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
                            <h6 class="card-title text-success mb-3">
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
                            <h6 class="card-title text-success mb-3">
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
        
        function getScheduleDateDisplay(seller) {
            let scheduleDate = '-';
            
            // First check call_timing
            if (seller.call_timing && seller.call_timing.startsWith('Schedule at ')) {
                scheduleDate = seller.call_timing.replace('Schedule at ', '');
            } 
            // Then check latest_update
            else if (seller.latest_update && seller.latest_update.startsWith('Schedule at ')) {
                scheduleDate = seller.latest_update.replace('Schedule at ', '');
            }
            // Check if latest_update contains schedule date in the middle
            else if (seller.latest_update && seller.latest_update.includes('Schedule at ')) {
                const match = seller.latest_update.match(/Schedule at (\d{2}\/\d{2}\/\d{4})/);
                if (match) {
                    scheduleDate = match[1];
                }
            }
            
            return scheduleDate;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showToast(type, title, message) {
            const id = 'toast-' + Date.now();
            let bgClass = 'bg-info';
            if (type === 'success') bgClass = 'bg-success';
            else if (type === 'danger') bgClass = 'bg-danger';
            else if (type === 'warning') bgClass = 'bg-warning';
            
            const toastHtml = `
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
            const toastElement = document.getElementById(id);
            if (toastElement) {
                const toast = new bootstrap.Toast(toastElement);
                toast.show();
                $(toastElement).on('hidden.bs.toast', function() {
                    $(this).remove();
                });
            }
        }
    });
    </script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>

</html>