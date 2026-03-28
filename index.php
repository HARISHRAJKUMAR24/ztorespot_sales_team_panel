<?php
require_once './lib/functions.php';
require_once './config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// Get current user info
$currentUser = getCurrentUser();
$user_uid = $_SESSION['user_uid'];
$pdo = db();

// Get current month targets
function getCurrentMonthTargets($user_uid) {
    try {
        $pdo = db();
        if (!$pdo) {
            return [];
        }
        
        $current_month = date('Y-m');
        
        $sql = "SELECT ts.*, 
                DATE_FORMAT(ts.start_date, '%d %b') as start_date_formatted,
                DATE_FORMAT(ts.end_date, '%d %b') as end_date_formatted
                FROM target_settings ts 
                WHERE ts.user_uid = ? 
                AND ts.target_type = 'individual'
                AND ts.status = 'active'
                AND (DATE_FORMAT(ts.start_date, '%Y-%m') = ? 
                OR DATE_FORMAT(ts.end_date, '%Y-%m') = ?)
                ORDER BY ts.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_uid, $current_month, $current_month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting current month targets: " . $e->getMessage());
        return [];
    }
}

// Get team target with notes
function getTeamTargetWithNotes() {
    try {
        $pdo = db();
        if (!$pdo) {
            return null;
        }
        
        $current_date = date('Y-m-d');
        
        $sql = "SELECT ts.* 
                FROM target_settings ts 
                WHERE ts.target_type = 'team'
                AND ts.status = 'active'
                AND ts.start_date <= ? 
                AND ts.end_date >= ?
                ORDER BY ts.created_at DESC 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_date, $current_date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting team target: " . $e->getMessage());
        return null;
    }
}

$currentMonthTargets = getCurrentMonthTargets($currentUser['user_uid'] ?? '');
$teamTarget = getTeamTargetWithNotes();

// Get counts for different statuses
$statuses = [
    'cnp' => "customer_response = 'CNP'",
    'not_interested' => "customer_response = 'Not interested'",
    'later' => "customer_response = 'Later'",
    'schedule' => "customer_response = 'Schedule' OR customer_response = 'Shedule'",
    'switch_off' => "customer_response = 'Switch Off'",
    'no_business' => "customer_response = 'No Business'",
    'out_of_service' => "customer_response = 'Out of Service'",
    'whatsapp_sent' => "customer_response = 'Whatsapp Details sent'",
    'testing' => "customer_response = 'Testing'"
];

$counts = [];
foreach ($statuses as $key => $condition) {
    $query = "SELECT COUNT(*) as count 
              FROM sales_person_sellers 
              WHERE user_uid = ? AND $condition";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_uid]);
    $counts[$key] = $stmt->fetchColumn();
}

// Get total count
$total_query = "SELECT COUNT(*) FROM sales_person_sellers WHERE user_uid = ?";
$stmt = $pdo->prepare($total_query);
$stmt->execute([$user_uid]);
$total_count = $stmt->fetchColumn();
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php template('head-tag'); ?>

<body class="bg-light">

<?php template('svg-icons'); ?>
<?php template('top-navbar'); ?>

<div class="container-fluid">
    <div class="row">

        <?php template('side-navbar'); ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                <div>
                    <h1 class="h2 mb-1">Welcome back, <?= htmlspecialchars($currentUser['name'] ?? 'User') ?>! 👋</h1>
                    <p class="text-muted">Your sales target and call status overview for <?= date('F Y') ?></p>
                </div>
                <div>
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        Total Sellers: <?= $total_count ?>
                    </span>
                </div>
            </div>

            <!-- Target Section - First Row -->
            <div class="row mb-4">
                <!-- Current Month Individual Targets -->
                <div class="col-12 col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-month text-primary me-2"></i>
                                Current Month Targets
                                <span class="badge bg-primary ms-2"><?= date('F Y') ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($currentMonthTargets)): ?>
                                <?php foreach ($currentMonthTargets as $target): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap">
                                            <div>
                                                <h6 class="mb-1">Target Period</h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar-range"></i> 
                                                    <?= $target['start_date_formatted'] ?> - <?= $target['end_date_formatted'] ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-<?= ($target['achievement_percentage'] ?? 0) >= 100 ? 'success' : 'warning' ?> mt-1 mt-sm-0">
                                                <?= round($target['achievement_percentage'] ?? 0) ?>% Achieved
                                            </span>
                                        </div>
                                        
                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Target Amount</small>
                                                <strong class="text-primary">₹<?= number_format($target['target_amount'], 2) ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Achieved Amount</small>
                                                <strong class="text-success">₹<?= number_format($target['achieved_amount'] ?? 0, 2) ?></strong>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($target['notes'])): ?>
                                            <div class="mt-3 p-3 bg-light rounded">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="bi bi-chat-text"></i> <strong>Notes:</strong>
                                                </small>
                                                <p class="mb-0 small"><?= nl2br(htmlspecialchars($target['notes'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                    <p class="text-muted mt-2 mb-0">No active targets for this month</p>

                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Team Target with Notes -->
                <div class="col-12 col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-people text-info me-2"></i>
                                Team Target
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($teamTarget): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap">
                                        <div>
                                            <h6 class="mb-1">Target Period</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-range"></i> 
                                                <?= date('d M Y', strtotime($teamTarget['start_date'])) ?> - 
                                                <?= date('d M Y', strtotime($teamTarget['end_date'])) ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?= ($teamTarget['achievement_percentage'] ?? 0) >= 100 ? 'success' : 'warning' ?> mt-1 mt-sm-0">
                                            <?= round($teamTarget['achievement_percentage'] ?? 0) ?>% Achieved
                                        </span>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Target Amount</small>
                                            <strong class="text-info">₹<?= number_format($teamTarget['target_amount'], 2) ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Achieved Amount</small>
                                            <strong class="text-success">₹<?= number_format($teamTarget['achieved_amount'] ?? 0, 2) ?></strong>
                                        </div>
                                    </div>
                                    
                                    <!-- NOTES SECTION - PROMINENTLY DISPLAYED -->
                                    <div class="mt-4 p-3 bg-info bg-opacity-10 rounded border-start border-info border-4">
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-sticky fs-4 text-info me-2"></i>
                                            <div class="flex-grow-1">
                                                <strong class="d-block mb-2 text-info">Important Notes:</strong>
                                                <?php if (!empty($teamTarget['notes'])): ?>
                                                    <div class="team-notes-content">
                                                        <?= nl2br(htmlspecialchars($teamTarget['notes'])) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0 fst-italic">No notes added for this target</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-people fs-1 text-muted"></i>
                                    <p class="text-muted mt-2 mb-0">No active team target</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call Status Dashboard Section -->
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="h5 fw-semibold">
                        <i class="bi bi-telephone me-2 text-primary"></i>
                        Call Status Dashboard
                    </h4>
                </div>

                <!-- 3x4 Cards Grid (12 cards) -->
                <div class="row g-3">
                    <!-- Card 1: CNP - Danger -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-danger bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-telephone-x text-danger fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">CNP</h6>
                                        <h3 class="fw-bold text-danger mb-0"><?= $counts['cnp'] ?></h3>
                                        <small class="text-muted">Call Not Picked</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="cnp_sellers.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Later Call - Info -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-clock-history text-info fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Later Call</h6>
                                        <h3 class="fw-bold text-info mb-0"><?= $counts['later'] ?></h3>
                                        <small class="text-muted">Call Back Later</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="later_sellers.php" class="btn btn-outline-info btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Schedule Calls - Success -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-calendar-check text-success fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Schedule</h6>
                                        <h3 class="fw-bold text-success mb-0"><?= $counts['schedule'] ?></h3>
                                        <small class="text-muted">Scheduled Follow-ups</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="schedule_sellers.php" class="btn btn-outline-success btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Switch Off - Secondary -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-secondary bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-power text-secondary fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Switch Off</h6>
                                        <h3 class="fw-bold text-secondary mb-0"><?= $counts['switch_off'] ?></h3>
                                        <small class="text-muted">Phone Switched Off</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="switchoff_sellers.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5: Out of Service - Danger -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-danger bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Out of Service</h6>
                                        <h3 class="fw-bold text-danger mb-0"><?= $counts['out_of_service'] ?></h3>
                                        <small class="text-muted">Number Inactive</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="out_of_services_sellers.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6: WhatsApp Sent - Success -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-whatsapp text-success fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">WhatsApp Sent</h6>
                                        <h3 class="fw-bold text-success mb-0"><?= $counts['whatsapp_sent'] ?></h3>
                                        <small class="text-muted">Details Sent</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="filtered_list.php?status=Whatsapp%20Details%20sent" class="btn btn-outline-success btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 7: Testing - Info -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-bug text-info fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Testing</h6>
                                        <h3 class="fw-bold text-info mb-0"><?= $counts['testing'] ?></h3>
                                        <small class="text-muted">Test Calls</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="testing_sellers.php" class="btn btn-outline-info btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 8: Not Interested - Warning -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-warning bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-hand-thumbs-down text-warning fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">Not Interested</h6>
                                        <h3 class="fw-bold text-warning mb-0"><?= $counts['not_interested'] ?></h3>
                                        <small class="text-muted">Rejected</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="not_interested_sellers.php" class="btn btn-outline-warning btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 9: No Business - Dark -->
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-dark bg-opacity-10 p-2 rounded-circle">
                                            <i class="bi bi-briefcase text-dark fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0 small">No Business</h6>
                                        <h3 class="fw-bold text-dark mb-0"><?= $counts['no_business'] ?></h3>
                                        <small class="text-muted">Not Operating</small>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="no_business_sellers.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>dashboard.js"></script>
<script src="<?= BASE_URL ?>js/auth/logout.js"></script>

<style>
    .card-hover {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    .team-notes-content {
        white-space: pre-wrap;
        word-wrap: break-word;
        line-height: 1.6;
    }

    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }

    @media (max-width: 768px) {
        .col-sm-6 {
            margin-bottom: 10px;
        }
    }
</style>

</body>
</html>