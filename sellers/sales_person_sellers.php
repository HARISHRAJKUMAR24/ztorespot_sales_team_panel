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

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : 'all';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build query conditions
$conditions = ["user_uid = ?"];
$params = [$user_uid];

// Apply filters
if ($filter === 'registered') {
    $conditions[] = "registration_status = 'Yes'";
} elseif ($filter === 'not_registered') {
    $conditions[] = "registration_status = 'No'";
} elseif ($filter === 'upgraded') {
    $conditions[] = "current_status = 'Upgraded'";
} elseif ($filter === 'in_progress') {
    $conditions[] = "current_status = 'In Progress'";
} elseif ($filter === 'cnp') {
    $conditions[] = "customer_response = 'CNP'";
} elseif ($filter === 'not_interested') {
    $conditions[] = "customer_response = 'Not interested'";
} elseif ($filter === 'call_back') {
    $conditions[] = "customer_response IN ('Later', 'Call Back AT')";
} elseif ($filter === 'whatsapp') {
    $conditions[] = "customer_response = 'Whatsapp Details sent'";
} elseif ($filter === 'today') {
    $conditions[] = "entry_date = CURDATE()";
} elseif ($filter === 'week') {
    $conditions[] = "entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter === 'month') {
    $conditions[] = "entry_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

// Date range filter
if (!empty($date_from)) {
    $conditions[] = "entry_date >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $conditions[] = "entry_date <= ?";
    $params[] = $date_to;
}

// Search filter
if (!empty($search)) {
    $conditions[] = "(work_details_update LIKE ? OR phone_number LIKE ? OR customer_queries LIKE ? OR source_type LIKE ? OR current_status LIKE ? OR customer_response LIKE ?)";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param);
}

$where_clause = implode(" AND ", $conditions);

// Count total records
$count_sql = "SELECT COUNT(*) FROM sales_person_sellers WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Add pagination params
$sql = "SELECT * FROM sales_person_sellers WHERE $where_clause ORDER BY entry_date DESC, id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Get sellers
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get comprehensive statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN registration_status = 'Yes' THEN 1 ELSE 0 END) as registered,
    SUM(CASE WHEN registration_status = 'No' THEN 1 ELSE 0 END) as not_registered,
    SUM(CASE WHEN current_status = 'Upgraded' THEN 1 ELSE 0 END) as upgraded,
    SUM(CASE WHEN current_status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN current_status = 'Deleted' THEN 1 ELSE 0 END) as deleted,
    SUM(CASE WHEN customer_response = 'CNP' THEN 1 ELSE 0 END) as cnp,
    SUM(CASE WHEN customer_response = 'Not interested' THEN 1 ELSE 0 END) as not_interested,
    SUM(CASE WHEN customer_response = 'Later' THEN 1 ELSE 0 END) as later,
    SUM(CASE WHEN customer_response = 'Call Back AT' THEN 1 ELSE 0 END) as call_back,
    SUM(CASE WHEN customer_response = 'Whatsapp Details sent' THEN 1 ELSE 0 END) as whatsapp,
    SUM(CASE WHEN customer_response = 'Plan Upgraded' THEN 1 ELSE 0 END) as plan_upgraded,
    SUM(CASE WHEN customer_response = 'Out of Service' THEN 1 ELSE 0 END) as out_of_service,
    SUM(CASE WHEN customer_response = 'Switch Off' THEN 1 ELSE 0 END) as switch_off,
    SUM(CASE WHEN customer_response = 'No Business' THEN 1 ELSE 0 END) as no_business,
    SUM(CASE WHEN source_type = 'Register Seller' THEN 1 ELSE 0 END) as register_seller,
    SUM(CASE WHEN source_type = 'Follow up Sellers' THEN 1 ELSE 0 END) as follow_up,
    SUM(CASE WHEN source_type = 'Aisensy' THEN 1 ELSE 0 END) as aisensy,
    SUM(CASE WHEN source_type IS NULL OR source_type = '' THEN 1 ELSE 0 END) as unknown_source
    FROM sales_person_sellers 
    WHERE user_uid = ?";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute([$user_uid]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
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
                <!-- Header with Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>work-station/dashboard.php" class="text-success">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>work-station/sheets_followup_list.php" class="text-success">Follow Up</a></li>
                        <li class="breadcrumb-item active" aria-current="page">All Sellers (Including CNP)</li>
                    </ol>
                </nav>

                <!-- Header -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center flex-wrap flex-md-nowrap pt-3 pb-2 mb-4 border-bottom gap-2">
                    <div>
                        <h1 class="h2 mb-1">
                            <i class="bi bi-grid-3x3-gap-fill text-success me-2"></i>
                            All Sellers <span class="badge bg-success bg-opacity-10 text-success fs-6 ms-2">Including CNP</span>
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Total: <strong><?= number_format($total_records) ?></strong> sellers | 
                            <i class="bi bi-telephone-x text-danger ms-2 me-1"></i>CNP: <strong><?= number_format($stats['cnp'] ?? 0) ?></strong>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-success" onclick="exportToExcel()" id="exportBtn">
                            <i class="bi bi-file-earmark-excel me-1"></i>Export
                        </button>
                        <button class="btn btn-success" onclick="window.location.reload()">
                            <i class="bi bi-arrow-repeat me-1"></i>Refresh
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards - Scrollable on mobile -->
                <div class="stats-scroll mb-4">
                    <div class="d-flex flex-nowrap gap-2 pb-2">
                        <div class="stat-card card bg-primary bg-opacity-10 border-0 shadow-sm" style="min-width: 120px;">
                            <div class="card-body p-2 p-md-3">
                                <h6 class="text-primary mb-1"><i class="bi bi-people"></i> Total</h6>
                                <h3 class="mb-0"><?= number_format($stats['total'] ?? 0) ?></h3>
                            </div>
                        </div>
                        <div class="stat-card card bg-success bg-opacity-10 border-0 shadow-sm" style="min-width: 120px;">
                            <div class="card-body p-2 p-md-3">
                                <h6 class="text-success mb-1"><i class="bi bi-check-circle"></i> Registered</h6>
                                <h3 class="mb-0"><?= number_format($stats['registered'] ?? 0) ?></h3>
                            </div>
                        </div>
                        <div class="stat-card card bg-warning bg-opacity-10 border-0 shadow-sm" style="min-width: 120px;">
                            <div class="card-body p-2 p-md-3">
                                <h6 class="text-warning mb-1"><i class="bi bi-star"></i> Upgraded</h6>
                                <h3 class="mb-0"><?= number_format($stats['upgraded'] ?? 0) ?></h3>
                            </div>
                        </div>
                        <div class="stat-card card bg-info bg-opacity-10 border-0 shadow-sm" style="min-width: 120px;">
                            <div class="card-body p-2 p-md-3">
                                <h6 class="text-info mb-1"><i class="bi bi-hourglass"></i> In Progress</h6>
                                <h3 class="mb-0"><?= number_format($stats['in_progress'] ?? 0) ?></h3>
                            </div>
                        </div>
                        <div class="stat-card card bg-danger bg-opacity-10 border-0 shadow-sm" style="min-width: 120px;">
                            <div class="card-body p-2 p-md-3">
                                <h6 class="text-danger mb-1"><i class="bi bi-telephone-x"></i> CNP</h6>
                                <h3 class="mb-0"><?= number_format($stats['cnp'] ?? 0) ?></h3>
                            </div>
                        </div>
                        <div class="stat-card card bg-secondary bg-opacity-10 border-0 shadow-sm" style="min-width: 120px;">
                            <div class="card-body p-2 p-md-3">
                                <h6 class="text-secondary mb-1"><i class="bi bi-x-circle"></i> Not Interested</h6>
                                <h3 class="mb-0"><?= number_format($stats['not_interested'] ?? 0) ?></h3>
                            </div>
                        </div>
                        <div class="stat-card card bg-purple bg-opacity-10 border-0 shadow-sm" style="min-width: 120px; background-color: rgba(111, 66, 193, 0.1);">
                            <div class="card-body p-2 p-md-3">
                                <h6 class="text-purple mb-1" style="color: #6f42c1;"><i class="bi bi-telephone"></i> Call Back</h6>
                                <h3 class="mb-0"><?= number_format(($stats['later'] ?? 0) + ($stats['call_back'] ?? 0)) ?></h3>
                            </div>
                        </div>
                        <div class="stat-card card bg-pink bg-opacity-10 border-0 shadow-sm" style="min-width: 120px; background-color: rgba(214, 51, 132, 0.1);">
                            <div class="card-body p-2 p-md-3">
                                <h6 class="text-pink mb-1" style="color: #d63384;"><i class="bi bi-whatsapp"></i> WhatsApp</h6>
                                <h3 class="mb-0"><?= number_format($stats['whatsapp'] ?? 0) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Search and Filter Bar -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-3">
                                <form method="GET" id="filterForm" class="row g-3">
                                    <div class="col-12 col-lg-5">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="bi bi-search text-success"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0 ps-0" 
                                                   name="search" id="searchInput"
                                                   placeholder="Search by business, phone, queries, status..." 
                                                   value="<?= htmlspecialchars($search) ?>">
                                            <?php if (!empty($search)): ?>
                                                <a href="?page=1&filter=<?= $filter ?>" class="btn btn-outline-secondary" type="button">
                                                    <i class="bi bi-x-lg"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-6 col-lg-2">
                                        <select name="filter" class="form-select" id="filterSelect">
                                            <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Sellers</option>
                                            <option value="registered" <?= $filter == 'registered' ? 'selected' : '' ?>>Registered</option>
                                            <option value="not_registered" <?= $filter == 'not_registered' ? 'selected' : '' ?>>Not Registered</option>
                                            <option value="upgraded" <?= $filter == 'upgraded' ? 'selected' : '' ?>>Upgraded</option>
                                            <option value="in_progress" <?= $filter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="cnp" <?= $filter == 'cnp' ? 'selected' : '' ?>>CNP Only</option>
                                            <option value="not_interested" <?= $filter == 'not_interested' ? 'selected' : '' ?>>Not Interested</option>
                                            <option value="call_back" <?= $filter == 'call_back' ? 'selected' : '' ?>>Call Back</option>
                                            <option value="whatsapp" <?= $filter == 'whatsapp' ? 'selected' : '' ?>>WhatsApp Sent</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-lg-2">
                                        <select name="date_range" class="form-select" id="dateRangeSelect">
                                            <option value="">All Dates</option>
                                            <option value="today" <?= $filter == 'today' ? 'selected' : '' ?>>Today</option>
                                            <option value="week" <?= $filter == 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                                            <option value="month" <?= $filter == 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-lg-2 d-none" id="customDateFrom">
                                        <input type="date" class="form-control" name="date_from" value="<?= $date_from ?>" placeholder="From">
                                    </div>
                                    <div class="col-6 col-lg-1 d-none" id="customDateTo">
                                        <input type="date" class="form-control" name="date_to" value="<?= $date_to ?>" placeholder="To">
                                    </div>
                                    <div class="col-6 col-lg-1">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-funnel me-1"></i>Filter
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Filter Chips -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2">
                            <a href="?filter=all" class="btn btn-sm <?= $filter == 'all' ? 'btn-success' : 'btn-outline-success' ?>">
                                <i class="bi bi-grid"></i> All
                            </a>
                            <a href="?filter=registered" class="btn btn-sm <?= $filter == 'registered' ? 'btn-success' : 'btn-outline-success' ?>">
                                <i class="bi bi-check-circle"></i> Registered
                            </a>
                            <a href="?filter=upgraded" class="btn btn-sm <?= $filter == 'upgraded' ? 'btn-success' : 'btn-outline-success' ?>">
                                <i class="bi bi-star"></i> Upgraded
                            </a>
                            <a href="?filter=in_progress" class="btn btn-sm <?= $filter == 'in_progress' ? 'btn-success' : 'btn-outline-success' ?>">
                                <i class="bi bi-hourglass"></i> In Progress
                            </a>
                            <a href="?filter=cnp" class="btn btn-sm <?= $filter == 'cnp' ? 'btn-danger' : 'btn-outline-danger' ?>">
                                <i class="bi bi-telephone-x"></i> CNP
                            </a>
                            <a href="?filter=not_interested" class="btn btn-sm <?= $filter == 'not_interested' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                                <i class="bi bi-x-circle"></i> Not Interested
                            </a>
                            <a href="?filter=call_back" class="btn btn-sm <?= $filter == 'call_back' ? 'btn-warning' : 'btn-outline-warning' ?>">
                                <i class="bi bi-telephone"></i> Call Back
                            </a>
                            <a href="?filter=whatsapp" class="btn btn-sm <?= $filter == 'whatsapp' ? 'btn-info' : 'btn-outline-info' ?>">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Sellers Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-success">
                                    <i class="bi bi-table me-2"></i>
                                    Sellers List
                                </h5>
                                <span class="badge bg-success"><?= $offset + 1 ?> - <?= min($offset + $limit, $total_records) ?> of <?= $total_records ?></span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="sellersTable">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="px-3 py-3" width="50">#</th>
                                                <th class="px-3 py-3">Business Details</th>
                                                <th class="px-3 py-3">Contact</th>
                                                <th class="px-3 py-3">Type</th>
                                                <th class="px-3 py-3">Response</th>
                                                <th class="px-3 py-3">Status</th>
                                                <th class="px-3 py-3">Entry Date</th>
                                                <th class="px-3 py-3 text-center" width="100">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($sellers)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center py-5">
                                                        <div class="py-4">
                                                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                                            <h5 class="text-muted mb-2">No Sellers Found</h5>
                                                            <p class="text-muted mb-3">No records match your current filters</p>
                                                            <a href="all_sellers_including_cnp.php" class="btn btn-success">
                                                                <i class="bi bi-arrow-repeat me-1"></i>Reset Filters
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($sellers as $index => $seller): ?>
                                                    <tr class="seller-row" data-id="<?= $seller['id'] ?>">
                                                        <td class="px-3 fw-medium"><?= $offset + $index + 1 ?></td>
                                                        <td class="px-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar-circle bg-success bg-opacity-10 text-success me-2">
                                                                    <?= strtoupper(substr($seller['work_details_update'] ?? 'B', 0, 1)) ?>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-semibold business-name"><?= htmlspecialchars($seller['work_details_update'] ?? 'N/A') ?></div>
                                                                    <?php if (!empty($seller['seller_id'])): ?>
                                                                        <small class="text-success d-block">
                                                                            <i class="bi bi-tag me-1"></i>ID: <?= htmlspecialchars($seller['seller_id']) ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-3">
                                                            <div class="d-flex flex-column">
                                                                <span class="fw-medium">
                                                                    <i class="bi bi-telephone me-1 text-muted"></i>
                                                                    <?= htmlspecialchars($seller['phone_number'] ?? 'N/A') ?>
                                                                </span>
                                                                <?php if (!empty($seller['call_timing'])): ?>
                                                                    <small class="text-muted">
                                                                        <i class="bi bi-clock me-1"></i><?= htmlspecialchars($seller['call_timing']) ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td class="px-3">
                                                            <?php
                                                            $source = $seller['source_type'] ?? 'N/A';
                                                            $source_class = 'secondary';
                                                            if ($source == 'Register Seller') $source_class = 'success';
                                                            elseif ($source == 'Follow up Sellers') $source_class = 'warning';
                                                            elseif ($source == 'Aisensy') $source_class = 'info';
                                                            
                                                            $source_icon = 'question-circle';
                                                            if ($source == 'Register Seller') $source_icon = 'person-plus';
                                                            elseif ($source == 'Follow up Sellers') $source_icon = 'arrow-repeat';
                                                            elseif ($source == 'Aisensy') $source_icon = 'whatsapp';
                                                            ?>
                                                            <span class="badge bg-<?= $source_class ?> bg-opacity-10 text-<?= $source_class ?> px-2 py-1">
                                                                <i class="bi bi-<?= $source_icon ?> me-1"></i>
                                                                <?= htmlspecialchars($source) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-3">
                                                            <?php
                                                            $response = $seller['customer_response'] ?? 'N/A';
                                                            $response_class = 'secondary';
                                                            $response_icon = 'dash-circle';
                                                            
                                                            if ($response == 'Not interested') {
                                                                $response_class = 'danger';
                                                                $response_icon = 'x-circle';
                                                            } elseif ($response == 'Later' || $response == 'Call Back AT') {
                                                                $response_class = 'warning';
                                                                $response_icon = 'telephone';
                                                            } elseif ($response == 'Whatsapp Details sent') {
                                                                $response_class = 'info';
                                                                $response_icon = 'whatsapp';
                                                            } elseif ($response == 'Plan Upgraded') {
                                                                $response_class = 'success';
                                                                $response_icon = 'star';
                                                            } elseif ($response == 'CNP') {
                                                                $response_class = 'dark';
                                                                $response_icon = 'telephone-x';
                                                            } elseif ($response == 'Out of Service' || $response == 'Switch Off') {
                                                                $response_class = 'secondary';
                                                                $response_icon = 'power';
                                                            } elseif ($response == 'No Business') {
                                                                $response_class = 'danger';
                                                                $response_icon = 'building-slash';
                                                            }
                                                            ?>
                                                            <span class="badge bg-<?= $response_class ?> bg-opacity-10 text-<?= $response_class ?> px-2 py-1">
                                                                <i class="bi bi-<?= $response_icon ?> me-1"></i>
                                                                <?= htmlspecialchars($response) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-3">
                                                            <?php
                                                            $status = $seller['current_status'] ?? 'Not yet';
                                                            $status_class = 'secondary';
                                                            $status_icon = 'question';
                                                            
                                                            if ($status == 'Upgraded') {
                                                                $status_class = 'success';
                                                                $status_icon = 'star';
                                                            } elseif ($status == 'In Progress') {
                                                                $status_class = 'warning';
                                                                $status_icon = 'hourglass';
                                                            } elseif ($status == 'Deleted') {
                                                                $status_class = 'danger';
                                                                $status_icon = 'trash';
                                                            } elseif ($status == 'Not yet') {
                                                                $status_icon = 'clock';
                                                            }
                                                            ?>
                                                            <span class="badge bg-<?= $status_class ?> bg-opacity-10 text-<?= $status_class ?> px-2 py-1">
                                                                <i class="bi bi-<?= $status_icon ?> me-1"></i>
                                                                <?= htmlspecialchars($status) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-3">
                                                            <?php if (!empty($seller['entry_date'])): ?>
                                                                <div class="d-flex flex-column">
                                                                    <span><i class="bi bi-calendar3 me-1 text-muted"></i><?= date('d/m/Y', strtotime($seller['entry_date'])) ?></span>
                                                                    <?php
                                                                    $days_ago = floor((time() - strtotime($seller['entry_date'])) / (60 * 60 * 24));
                                                                    if ($days_ago <= 7) {
                                                                        echo '<small class="text-success">' . $days_ago . ' days ago</small>';
                                                                    } elseif ($days_ago <= 30) {
                                                                        echo '<small class="text-warning">' . $days_ago . ' days ago</small>';
                                                                    } else {
                                                                        echo '<small class="text-muted">' . $days_ago . ' days ago</small>';
                                                                    }
                                                                    ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-3 text-center">
                                                            <div class="btn-group btn-group-sm" role="group">
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
                                                                <button type="button" 
                                                                        class="btn btn-outline-primary copy-phone" 
                                                                        data-phone="<?= $seller['phone_number'] ?>"
                                                                        title="Copy Phone Number"
                                                                        data-bs-toggle="tooltip">
                                                                    <i class="bi bi-files"></i>
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
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                                        <div class="text-muted small">
                                            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_records) ?> of <?= $total_records ?> entries
                                        </div>
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination pagination-sm mb-0">
                                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" aria-label="First">
                                                        <i class="bi bi-chevron-double-left"></i>
                                                    </a>
                                                </li>
                                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                                                        <i class="bi bi-chevron-left"></i>
                                                    </a>
                                                </li>
                                                
                                                <?php
                                                $start = max(1, $page - 2);
                                                $end = min($total_pages, $page + 2);
                                                
                                                for ($i = $start; $i <= $end; $i++):
                                                ?>
                                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                                                        <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                </li>
                                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" aria-label="Last">
                                                        <i class="bi bi-chevron-double-right"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Seller Modal - Enhanced -->
    <div class="modal fade" id="viewSellerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-badge me-2"></i>
                        Seller Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="sellerDetails">
                    <div class="text-center py-5">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading seller details...</p>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Close
                    </button>
                    <a href="#" id="editFromModal" class="btn btn-success" target="_blank">
                        <i class="bi bi-pencil-square me-1"></i>Edit Seller
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick View Modal (for hover) -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title">Quick Info</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="quickViewContent">
                    Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <style>
        /* Custom Styles */
        .avatar-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }
        
        .stats-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .stats-scroll::-webkit-scrollbar {
            height: 5px;
        }
        
        .stats-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .stats-scroll::-webkit-scrollbar-thumb {
            background: #198754;
            border-radius: 10px;
        }
        
        .stats-scroll::-webkit-scrollbar-thumb:hover {
            background: #146c43;
        }
        
        .stat-card {
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }
        
        .seller-row {
            transition: background-color 0.2s;
            cursor: pointer;
        }
        
        .seller-row:hover {
            background-color: rgba(25, 135, 84, 0.05);
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
        
        .page-link:focus {
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
        }
        
        /* Table row hover effect */
        .table-hover tbody tr:hover {
            background-color: rgba(25, 135, 84, 0.05);
        }
        
        /* Custom badge colors */
        .bg-purple {
            background-color: #6f42c1 !important;
        }
        
        .text-purple {
            color: #6f42c1 !important;
        }
        
        .bg-pink {
            background-color: #d63384 !important;
        }
        
        .text-pink {
            color: #d63384 !important;
        }
        
        /* Loading animation */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
        
        /* Responsive table */
        @media (max-width: 768px) {
            .table td, .table th {
                font-size: 0.9rem;
            }
            
            .btn-group .btn {
                padding: 0.2rem 0.4rem;
            }
            
            .badge {
                font-size: 0.75rem;
            }
        }
        
        /* Copy button animation */
        .copy-phone.copied {
            background-color: #198754;
            color: white;
            border-color: #198754;
        }
        
        /* Table row expand animation */
        .seller-row td {
            transition: all 0.2s;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #198754;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #146c43;
        }
    </style>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Define BASE_URL for JavaScript
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
     <script src="<?= BASE_URL ?>js/work-station/sales-person-sellers/sales-person-sellers.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>