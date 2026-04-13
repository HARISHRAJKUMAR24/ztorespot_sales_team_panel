<?php
require_once '../lib/functions.php';
require_once '../config/config.php';

// Set Indian timezone
date_default_timezone_set('Asia/Kolkata');

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// Get current user info
$currentUser = getCurrentUser();
$user_uid = $_SESSION['user_uid'];
$pdo = db();

// Get date parameters
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-d', strtotime('first day of this month'));

// Function to get call updates for a date range
function getCallUpdatesByDateRange($user_uid, $start_date, $end_date) {
    global $pdo;
    
    $sql = "SELECT * FROM sales_person_sellers 
            WHERE user_uid = ? 
            AND DATE(updated_at) BETWEEN ? AND ?
            ORDER BY updated_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_uid, $start_date, $end_date]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse update history to count actual updates
    $updates = [];
    $response_counts = [];
    $sellers_updated = [];
    
    foreach ($results as $seller) {
        if (!empty($seller['update_history'])) {
            $history = json_decode($seller['update_history'], true);
            if (is_array($history)) {
                foreach ($history as $entry) {
                    $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
                    if ($entry_date >= $start_date && $entry_date <= $end_date) {
                        // Build changes string safely
                        $changes = [];
                        if (!empty($entry['changes'])) {
                            foreach ($entry['changes'] as $field => $change) {
                                $changes[] = [
                                    'field' => $change['field'] ?? $field,
                                    'old' => $change['old'] ?? '-',
                                    'new' => $change['new'] ?? '-'
                                ];
                            }
                        }
                        
                        $updates[] = [
                            'seller_id' => $seller['id'],
                            'seller_name' => $seller['work_details_update'],
                            'phone' => $seller['phone_number'],
                            'timestamp' => $entry['timestamp'],
                            'timestamp_formatted' => $entry['timestamp_formatted'] ?? date('d M Y, h:i A', strtotime($entry['timestamp'])),
                            'changes' => $changes,
                            'customer_response' => $seller['customer_response']
                        ];
                        
                        // Count responses
                        $response = $seller['customer_response'];
                        if (!isset($response_counts[$response])) {
                            $response_counts[$response] = 0;
                        }
                        $response_counts[$response]++;
                        
                        // Track unique sellers
                        if (!in_array($seller['id'], $sellers_updated)) {
                            $sellers_updated[] = $seller['id'];
                        }
                    }
                }
            }
        }
    }
    
    return [
        'updates' => $updates,
        'total_updates' => count($updates),
        'unique_sellers' => count($sellers_updated),
        'response_counts' => $response_counts
    ];
}

// Function to get updates for a specific date
function getUpdatesByDate($user_uid, $date) {
    global $pdo;
    
    $sql = "SELECT * FROM sales_person_sellers 
            WHERE user_uid = ? 
            AND DATE(updated_at) = ?
            ORDER BY updated_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_uid, $date]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updates = [];
    foreach ($results as $seller) {
        if (!empty($seller['update_history'])) {
            $history = json_decode($seller['update_history'], true);
            if (is_array($history)) {
                foreach ($history as $entry) {
                    if (date('Y-m-d', strtotime($entry['timestamp'])) == $date) {
                        $changes = [];
                        if (!empty($entry['changes'])) {
                            foreach ($entry['changes'] as $field => $change) {
                                $changes[] = [
                                    'field' => $change['field'] ?? $field,
                                    'old' => $change['old'] ?? '-',
                                    'new' => $change['new'] ?? '-'
                                ];
                            }
                        }
                        
                        $updates[] = [
                            'seller_name' => $seller['work_details_update'],
                            'phone' => $seller['phone_number'],
                            'timestamp_formatted' => $entry['timestamp_formatted'] ?? date('d M Y, h:i A', strtotime($entry['timestamp'])),
                            'changes' => $changes,
                            'customer_response' => $seller['customer_response']
                        ];
                    }
                }
            }
        }
    }
    
    return $updates;
}

// Get all statistics
$todayStats = getCallUpdatesByDateRange($user_uid, $today, $today);
$yesterdayStats = getCallUpdatesByDateRange($user_uid, $yesterday, $yesterday);
$weekStats = getCallUpdatesByDateRange($user_uid, $week_start, $today);
$monthStats = getCallUpdatesByDateRange($user_uid, $month_start, $today);

// Calculate comparisons
$vsYesterday = $todayStats['total_updates'] - $yesterdayStats['total_updates'];
$vsYesterdayPercent = $yesterdayStats['total_updates'] > 0 ? ($vsYesterday / $yesterdayStats['total_updates']) * 100 : 0;

// Prepare weekly data for popup
$weeklyData = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("-$i days", strtotime($today)));
    $weeklyData[$date] = [
        'date' => $date,
        'date_formatted' => date('d M Y', strtotime($date)),
        'day_name' => date('l', strtotime($date)),
        'updates' => getUpdatesByDate($user_uid, $date)
    ];
}

$responseColors = [
    'Plan Upgraded' => 'success',
    'Plan Interested' => 'warning',
    'CNP' => 'danger',
    'Later' => 'info',
    'Not interested' => 'secondary',
    'Switch Off' => 'dark',
    'No Business' => 'secondary',
    'Whatsapp Details sent' => 'primary',
    'Call Back AT' => 'info',
    'Out of Service' => 'danger',
    'Testing' => 'info',
    'Renewals' => 'primary',
    'Schedule' => 'success',
    'Refund' => 'danger'
];

// Safe JSON encode function
function safeJsonEncode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
?>

<!DOCTYPE html>
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
                <div class="d-flex justify-content-between align-items-center flex-wrap flex-md-nowrap pt-3 pb-2 mb-4 border-bottom gap-2">
                    <div>
                        <h1 class="h2 mb-2 mb-sm-0">
                            <i class="bi bi-telephone-outbound text-primary me-2"></i>
                            Call Report
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="bi bi-calendar3 me-1"></i>
                            Track daily call activities and updates
                        </p>
                    </div>
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Report
                    </button>
                </div>

                <!-- Statistics Cards - 4 cards row -->
                <div class="row mb-4">
                    <!-- Today Card - Clickable -->
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm h-100 card-clickable" onclick="showDayDetails('today', '<?= date('d M Y') ?>', 'today')">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-1">Today</h6>
                                        <h2 class="mb-0 fw-bold"><?= $todayStats['total_updates'] ?></h2>
                                        <small class="text-muted">Updates</small>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                        <i class="bi bi-calendar-day fs-4 text-primary"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-people me-1"></i> <?= $todayStats['unique_sellers'] ?> Sellers
                                    </small>
                                </div>
                                <?php if ($vsYesterday != 0): ?>
                                    <div class="mt-2">
                                        <small class="<?= $vsYesterday > 0 ? 'text-success' : 'text-danger' ?>">
                                            <i class="bi bi-arrow-<?= $vsYesterday > 0 ? 'up' : 'down' ?>"></i>
                                            <?= abs($vsYesterday) ?> vs yesterday (<?= round(abs($vsYesterdayPercent), 1) ?>%)
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Yesterday Card - Clickable -->
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm h-100 card-clickable" onclick="showDayDetails('yesterday', '<?= date('d M Y', strtotime($yesterday)) ?>', 'yesterday')">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-1">Yesterday</h6>
                                        <h2 class="mb-0 fw-bold"><?= $yesterdayStats['total_updates'] ?></h2>
                                        <small class="text-muted">Updates</small>
                                    </div>
                                    <div class="bg-secondary bg-opacity-10 p-3 rounded-circle">
                                        <i class="bi bi-calendar-yesterday fs-4 text-secondary"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-people me-1"></i> <?= $yesterdayStats['unique_sellers'] ?> Sellers
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- This Week Card - Clickable -->
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm h-100 card-clickable" onclick="showWeekDetails()">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-1">This Week</h6>
                                        <h2 class="mb-0 fw-bold"><?= $weekStats['total_updates'] ?></h2>
                                        <small class="text-muted">Total Updates</small>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                                        <i class="bi bi-calendar-week fs-4 text-info"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-people me-1"></i> <?= $weekStats['unique_sellers'] ?> Sellers
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- This Month Card - Clickable -->
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm h-100 card-clickable" onclick="showMonthDetails()">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-1">This Month</h6>
                                        <h2 class="mb-0 fw-bold"><?= $monthStats['total_updates'] ?></h2>
                                        <small class="text-muted">Total Updates</small>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                        <i class="bi bi-calendar-month fs-4 text-success"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-people me-1"></i> <?= $monthStats['unique_sellers'] ?> Sellers
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Response Type Distribution - Today -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-pie-chart text-primary me-2"></i>
                                    Today's Response Types
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($todayStats['response_counts'])): ?>
                                    <div class="row">
                                        <?php foreach ($todayStats['response_counts'] as $response => $count): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-<?= $responseColors[$response] ?? 'secondary' ?> px-3 py-2">
                                                        <?= htmlspecialchars($response) ?>
                                                    </span>
                                                    <span class="fw-bold"><?= $count ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-inbox fs-1 text-muted"></i>
                                        <p class="text-muted mt-2 mb-0">No updates today</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Response Type Distribution - This Week -->
                    <div class="col-md-6 mb-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-pie-chart text-primary me-2"></i>
                                    This Week's Response Types
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($weekStats['response_counts'])): ?>
                                    <div class="row">
                                        <?php foreach ($weekStats['response_counts'] as $response => $count): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-<?= $responseColors[$response] ?? 'secondary' ?> px-3 py-2">
                                                        <?= htmlspecialchars($response) ?>
                                                    </span>
                                                    <span class="fw-bold"><?= $count ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-inbox fs-1 text-muted"></i>
                                        <p class="text-muted mt-2 mb-0">No updates this week</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Summary Table - Clickable rows -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-calendar-week text-primary me-2"></i>
                                    This Week Summary (Click on any day to view details)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Day</th>
                                                <th>Date</th>
                                                <th class="text-center">Updates</th>
                                                <th class="text-center">Sellers</th>
                                                <th>Top Response</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            for ($i = 0; $i < 7; $i++) {
                                                $date = date('Y-m-d', strtotime("-$i days", strtotime($today)));
                                                $dayStats = getCallUpdatesByDateRange($user_uid, $date, $date);
                                                $dayName = date('l', strtotime($date));
                                                $dateFormatted = date('d M Y', strtotime($date));
                                                
                                                // Find top response
                                                $topResponse = '';
                                                $topCount = 0;
                                                foreach ($dayStats['response_counts'] as $response => $count) {
                                                    if ($count > $topCount) {
                                                        $topCount = $count;
                                                        $topResponse = $response;
                                                    }
                                                }
                                                ?>
                                                <tr class="clickable-row" onclick="showDayDetails('<?= $dayName ?>', '<?= $dateFormatted ?>', '<?= $date ?>')">
                                                    <td class="fw-semibold"><?= $dayName ?></td>
                                                    <td><?= $dateFormatted ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary rounded-pill px-3 py-2"><?= $dayStats['total_updates'] ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-secondary rounded-pill px-3 py-2"><?= $dayStats['unique_sellers'] ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($topResponse): ?>
                                                            <span class="badge bg-<?= $responseColors[$topResponse] ?? 'secondary' ?>">
                                                                <?= htmlspecialchars($topResponse) ?> (<?= $topCount ?>)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Modal Popup for Day Details -->
    <div class="modal fade" id="dayDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-day me-2"></i>
                        <span id="modalTitle">Call Updates</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Popup for Week Details -->
    <div class="modal fade" id="weekDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-week me-2"></i>
                        Week Summary (<?= date('d M', strtotime($week_start)) ?> - <?= date('d M Y') ?>)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="weekModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-info" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Popup for Month Details -->
    <div class="modal fade" id="monthDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-month me-2"></i>
                        Month Summary (<?= date('F Y') ?>)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="monthModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>

    <script>
        // Store data globally
        const weekData = <?= json_encode($weeklyData) ?>;
        
        // Function to get updates by date
        function getUpdatesByDate(date) {
            for (const [key, value] of Object.entries(weekData)) {
                if (value.date === date) {
                    return value.updates;
                }
            }
            return [];
        }
        
        // Show day details modal
        function showDayDetails(dayName, date, dateOrType) {
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.innerText = dayName + ' - ' + date + ' (Call Updates)';
            
            let updates = [];
            
            // Get updates based on parameter
            if (dateOrType === 'today') {
                updates = <?= json_encode($todayStats['updates']) ?>;
            } else if (dateOrType === 'yesterday') {
                updates = <?= json_encode($yesterdayStats['updates']) ?>;
            } else {
                updates = getUpdatesByDate(dateOrType);
            }
            
            if (updates && updates.length > 0) {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Seller Name</th>
                                    <th>Phone</th>
                                    <th>Response</th>
                                    <th>Changes Made</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                updates.forEach(update => {
                    const responseColors = {
                        'Plan Upgraded': 'success',
                        'Plan Interested': 'warning',
                        'CNP': 'danger',
                        'Later': 'info',
                        'Not interested': 'secondary',
                        'Switch Off': 'dark',
                        'No Business': 'secondary',
                        'Whatsapp Details sent': 'primary',
                        'Call Back AT': 'info',
                        'Out of Service': 'danger',
                        'Testing': 'info',
                        'Renewals': 'primary',
                        'Schedule': 'success',
                        'Refund': 'danger'
                    };
                    
                    let changesHtml = '';
                    if (update.changes && update.changes.length > 0) {
                        update.changes.forEach(change => {
                            changesHtml += `<small class="d-block">
                                <strong>${escapeHtml(change.field)}:</strong>
                                <span class="text-muted">${escapeHtml(change.old)}</span>
                                <i class="bi bi-arrow-right mx-1"></i>
                                <span class="text-success">${escapeHtml(change.new)}</span>
                            </small>`;
                        });
                    } else {
                        changesHtml = '<small class="text-muted">-</small>';
                    }
                    
                    html += `
                        <tr>
                            <td class="text-nowrap"><small>${update.timestamp_formatted || '-'}</small></td>
                            <td class="fw-semibold">${escapeHtml(update.seller_name)}</td>
                            <td>${escapeHtml(update.phone)}</td>
                            <td>
                                <span class="badge bg-${responseColors[update.customer_response] || 'secondary'}">
                                    ${escapeHtml(update.customer_response)}
                                </span>
                            </td>
                            <td>${changesHtml}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                modalBody.innerHTML = html;
            } else {
                modalBody.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-telephone-x fs-1 text-muted"></i>
                        <h5 class="text-muted mt-3">No call updates on ${date}</h5>
                        <p class="text-muted">No updates recorded for this day</p>
                    </div>
                `;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('dayDetailsModal'));
            modal.show();
        }
        
        // Show week details modal
        function showWeekDetails() {
            const modalBody = document.getElementById('weekModalBody');
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Day</th>
                                <th>Date</th>
                                <th class="text-center">Updates</th>
                                <th class="text-center">Sellers</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            for (const [date, data] of Object.entries(weekData)) {
                const updatesCount = data.updates.length;
                const uniqueSellers = [...new Set(data.updates.map(u => u.seller_name))].length;
                const dateFormatted = data.date_formatted;
                const dayName = data.day_name;
                const updatesJson = JSON.stringify(data.updates).replace(/</g, '\\u003c').replace(/>/g, '\\u003e');
                
                html += `
                    <tr class="clickable-row" onclick="showDayDetails('${dayName}', '${dateFormatted}', '${date}')">
                        <td class="fw-semibold">${dayName}</td>
                        <td>${dateFormatted}</td>
                        <td class="text-center">
                            <span class="badge bg-primary rounded-pill px-3 py-2">${updatesCount}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary rounded-pill px-3 py-2">${uniqueSellers}</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); showDayDetails('${dayName}', '${dateFormatted}', '${date}')">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </td>
                    </tr>
                `;
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            modalBody.innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('weekDetailsModal'));
            modal.show();
        }
        
        // Show month details modal
        function showMonthDetails() {
            const modalBody = document.getElementById('monthModalBody');
            
            // Get month data
            let monthHtml = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th class="text-center">Updates</th>
                                <th class="text-center">Sellers</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            // Process month data from PHP
            <?php
            $monthData = [];
            $currentDate = strtotime($month_start);
            $endDate = strtotime($today);
            while ($currentDate <= $endDate) {
                $date = date('Y-m-d', $currentDate);
                $dayStats = getCallUpdatesByDateRange($user_uid, $date, $date);
                $monthData[] = [
                    'date' => $date,
                    'date_formatted' => date('d M Y', $currentDate),
                    'day_name' => date('l', $currentDate),
                    'updates' => $dayStats['updates'],
                    'total_updates' => $dayStats['total_updates'],
                    'unique_sellers' => $dayStats['unique_sellers']
                ];
                $currentDate = strtotime('+1 day', $currentDate);
            }
            ?>
            
            const monthData = <?= json_encode($monthData) ?>;
            
            monthData.forEach(data => {
                monthHtml += `
                    <tr class="clickable-row" onclick="showDayDetails('${data.day_name}', '${data.date_formatted}', '${data.date}')">
                        <td class="fw-semibold">${data.date_formatted}</td>
                        <td>${data.day_name}</td>
                        <td class="text-center">
                            <span class="badge bg-primary rounded-pill px-3 py-2">${data.total_updates}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary rounded-pill px-3 py-2">${data.unique_sellers}</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); showDayDetails('${data.day_name}', '${data.date_formatted}', '${data.date}')">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            monthHtml += `
                        </tbody>
                    </table>
                </div>
            `;
            
            modalBody.innerHTML = monthHtml;
            const modal = new bootstrap.Modal(document.getElementById('monthDetailsModal'));
            modal.show();
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

    <style>
        @media print {
            .sidebar, .topbar, .btn, .menu-toggle {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .modal {
                display: none !important;
            }
        }
        
        .badge {
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .table th {
            font-weight: 600;
            color: #1f2937;
        }
        
        .card {
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .card-clickable {
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .card-clickable:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }
        
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .clickable-row:hover {
            background-color: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .badge {
                font-size: 0.7rem;
                padding: 0.25rem 0.5rem;
            }
            .table td, .table th {
                font-size: 0.8rem;
            }
        }
    </style>
</body>

</html>