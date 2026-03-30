<?php
require_once '../lib/functions.php';
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// Get current user info
$currentUser = getCurrentUser();

// Get all user targets (individual)
function getAllUserTargets($user_uid) {
    try {
        $pdo = db();
        if (!$pdo) {
            return [];
        }
        
        $sql = "SELECT ts.*, 
                DATE_FORMAT(ts.start_date, '%d %b %Y') as start_date_formatted,
                DATE_FORMAT(ts.end_date, '%d %b %Y') as end_date_formatted,
                DATE_FORMAT(ts.created_at, '%d %b %Y') as created_date_formatted,
                adm.username as created_by_name,
                CASE 
                    WHEN ts.status = 'active' AND ts.end_date < CURDATE() THEN 'overdue'
                    WHEN ts.achievement_percentage >= 100 THEN 'achieved'
                    ELSE ts.status
                END as display_status
                FROM target_settings ts
                LEFT JOIN admin_users adm ON ts.created_by = adm.id
                WHERE ts.user_uid = ? 
                AND ts.target_type = 'individual'
                ORDER BY ts.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_uid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting user targets: " . $e->getMessage());
        return [];
    }
}

// Get all team targets
function getAllTeamTargets() {
    try {
        $pdo = db();
        if (!$pdo) {
            return [];
        }
        
        $sql = "SELECT ts.*, 
                DATE_FORMAT(ts.start_date, '%d %b %Y') as start_date_formatted,
                DATE_FORMAT(ts.end_date, '%d %b %Y') as end_date_formatted,
                DATE_FORMAT(ts.created_at, '%d %b %Y') as created_date_formatted,
                adm.username as created_by_name,
                CASE 
                    WHEN ts.status = 'active' AND ts.end_date < CURDATE() THEN 'overdue'
                    WHEN ts.achievement_percentage >= 100 THEN 'achieved'
                    ELSE ts.status
                END as display_status
                FROM target_settings ts
                LEFT JOIN admin_users adm ON ts.created_by = adm.id
                WHERE ts.target_type = 'team'
                ORDER BY ts.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting team targets: " . $e->getMessage());
        return [];
    }
}

// Get current active target
function getCurrentActiveTarget($user_uid) {
    try {
        $pdo = db();
        if (!$pdo) {
            return null;
        }
        
        $current_date = date('Y-m-d');
        
        $sql = "SELECT ts.*, 
                DATE_FORMAT(ts.start_date, '%d %b %Y') as start_date_formatted,
                DATE_FORMAT(ts.end_date, '%d %b %Y') as end_date_formatted,
                CASE 
                    WHEN ts.achievement_percentage >= 100 THEN 'achieved'
                    WHEN ts.end_date < CURDATE() THEN 'overdue'
                    ELSE 'active'
                END as progress_status
                FROM target_settings ts 
                WHERE ts.user_uid = ? 
                AND ts.target_type = 'individual'
                AND ts.status = 'active'
                AND ts.start_date <= ? 
                AND ts.end_date >= ?
                ORDER BY ts.created_at DESC 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_uid, $current_date, $current_date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting current active target: " . $e->getMessage());
        return null;
    }
}

// Get current active team target
function getCurrentActiveTeamTarget() {
    try {
        $pdo = db();
        if (!$pdo) {
            return null;
        }
        
        $current_date = date('Y-m-d');
        
        $sql = "SELECT ts.*, 
                DATE_FORMAT(ts.start_date, '%d %b %Y') as start_date_formatted,
                DATE_FORMAT(ts.end_date, '%d %b %Y') as end_date_formatted,
                CASE 
                    WHEN ts.achievement_percentage >= 100 THEN 'achieved'
                    WHEN ts.end_date < CURDATE() THEN 'overdue'
                    ELSE 'active'
                END as progress_status
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
        error_log("Error getting current active team target: " . $e->getMessage());
        return null;
    }
}

$userTargets = getAllUserTargets($currentUser['user_uid'] ?? '');
$teamTargets = getAllTeamTargets();
$currentTarget = getCurrentActiveTarget($currentUser['user_uid'] ?? '');
$currentTeamTarget = getCurrentActiveTeamTarget();
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
                    <h1 class="h2 mb-1">🎯 My Targets</h1>
                    <p class="text-muted">View and manage all your individual and team targets</p>
                </div>

            </div>

            <!-- Current Active Target Section -->
            <?php if ($currentTarget): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5 class="mb-1">
                            <i class="bi bi-star-fill text-warning me-2"></i>
                            Current Active Target
                        </h5>
                        <small class="text-muted">Period: <?= $currentTarget['start_date_formatted'] ?> - <?= $currentTarget['end_date_formatted'] ?></small>
                    </div>
                    <span class="badge bg-<?= $currentTarget['progress_status'] == 'achieved' ? 'success' : ($currentTarget['progress_status'] == 'overdue' ? 'danger' : 'primary') ?> fs-6 px-3 py-2">
                        <?= strtoupper($currentTarget['progress_status']) ?>
                    </span>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Target Amount</small>
                        <strong class="fs-4 text-primary">₹<?= number_format($currentTarget['target_amount'], 2) ?></strong>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Achieved Amount</small>
                        <strong class="fs-4 text-success">₹<?= number_format($currentTarget['achieved_amount'] ?? 0, 2) ?></strong>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Progress</small>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 10px;">
                                <div class="progress-bar bg-<?= ($currentTarget['achievement_percentage'] ?? 0) >= 100 ? 'success' : 'primary' ?>" 
                                     style="width: <?= min(100, $currentTarget['achievement_percentage'] ?? 0) ?>%"></div>
                            </div>
                            <strong><?= round($currentTarget['achievement_percentage'] ?? 0) ?>%</strong>
                        </div>
                    </div>
                </div>
                <?php if (!empty($currentTarget['notes'])): ?>
                    <div class="mt-3 p-2 bg-light rounded">
                        <small class="text-muted"><i class="bi bi-chat"></i> Notes: <?= nl2br(htmlspecialchars($currentTarget['notes'])) ?></small>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Current Active Team Target Section -->
            <?php if ($currentTeamTarget): ?>
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5 class="mb-1">
                            <i class="bi bi-people-fill text-info me-2"></i>
                            Current Active Team Target
                        </h5>
                        <small class="text-muted">Period: <?= $currentTeamTarget['start_date_formatted'] ?> - <?= $currentTeamTarget['end_date_formatted'] ?></small>
                    </div>
                    <span class="badge bg-<?= $currentTeamTarget['progress_status'] == 'achieved' ? 'success' : ($currentTeamTarget['progress_status'] == 'overdue' ? 'danger' : 'info') ?> fs-6 px-3 py-2">
                        <?= strtoupper($currentTeamTarget['progress_status']) ?>
                    </span>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Target Amount</small>
                        <strong class="fs-4 text-info">₹<?= number_format($currentTeamTarget['target_amount'], 2) ?></strong>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Achieved Amount</small>
                        <strong class="fs-4 text-success">₹<?= number_format($currentTeamTarget['achieved_amount'] ?? 0, 2) ?></strong>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted d-block">Progress</small>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 10px;">
                                <div class="progress-bar bg-<?= ($currentTeamTarget['achievement_percentage'] ?? 0) >= 100 ? 'success' : 'info' ?>" 
                                     style="width: <?= min(100, $currentTeamTarget['achievement_percentage'] ?? 0) ?>%"></div>
                            </div>
                            <strong><?= round($currentTeamTarget['achievement_percentage'] ?? 0) ?>%</strong>
                        </div>
                    </div>
                </div>
                <?php if (!empty($currentTeamTarget['notes'])): ?>
                    <div class="mt-3 p-3 bg-light rounded border-start border-info border-4">
                        <small class="text-muted d-block mb-1"><i class="bi bi-sticky text-info"></i> <strong>Important Notes:</strong></small>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($currentTeamTarget['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Toggle Buttons Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-center gap-4 flex-wrap">
                        <!-- My Individual Targets Toggle -->
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="toggleIndividual" checked style="width: 3em; height: 1.5em;">
                            <label class="form-check-label fw-semibold ms-2" for="toggleIndividual">
                                <i class="bi bi-person-badge text-primary me-1"></i>
                                My Individual Targets
                                <span class="badge bg-primary ms-2"><?= count($userTargets) ?></span>
                            </label>
                        </div>
                        
                        <!-- Team Targets Toggle -->
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="toggleTeam" checked style="width: 3em; height: 1.5em;">
                            <label class="form-check-label fw-semibold ms-2" for="toggleTeam">
                                <i class="bi bi-people text-info me-1"></i>
                                Team Targets
                                <span class="badge bg-info ms-2"><?= count($teamTargets) ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Individual Targets History -->
            <div id="individualTargetsSection" class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-badge text-primary me-2"></i>
                        My Individual Targets History
                        <span class="badge bg-secondary ms-2"><?= count($userTargets) ?> Total</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($userTargets)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th>Target Amount</th>
                                        <th>Achieved</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userTargets as $target): ?>
                                        <tr>
                                            <td>
                                                <small><?= $target['start_date_formatted'] ?><br>to<br><?= $target['end_date_formatted'] ?></small>
                                            </td>
                                            <td>
                                                <strong class="text-primary">₹<?= number_format($target['target_amount'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <strong class="text-success">₹<?= number_format($target['achieved_amount'] ?? 0, 2) ?></strong>
                                            </td>
                                            <td style="min-width: 100px;">
                                                <div class="d-flex align-items-center gap-1">
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar" style="width: <?= min(100, $target['achievement_percentage'] ?? 0) ?>%"></div>
                                                    </div>
                                                    <small><?= round($target['achievement_percentage'] ?? 0) ?>%</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $target['display_status'] == 'achieved' ? 'success' : 
                                                    ($target['display_status'] == 'overdue' ? 'danger' : 
                                                    ($target['display_status'] == 'completed' ? 'info' : 
                                                    ($target['display_status'] == 'cancelled' ? 'secondary' : 'warning'))) ?>">
                                                    <?= strtoupper($target['display_status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($target['notes'])): ?>
                                                    <button class="btn btn-sm btn-link text-decoration-none p-0" 
                                                            onclick="showNote('<?= addslashes(htmlspecialchars($target['notes'])) ?>')">
                                                        <i class="bi bi-chat-text"></i> View
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= $target['created_date_formatted'] ?></small>
                                                <?php if ($target['created_by_name']): ?>
                                                    <br><small class="text-muted">by <?= htmlspecialchars($target['created_by_name']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-archive fs-1 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">No individual targets found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Team Targets History -->
            <div id="teamTargetsSection" class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-people text-info me-2"></i>
                        Team Targets History
                        <span class="badge bg-secondary ms-2"><?= count($teamTargets) ?> Total</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($teamTargets)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th>Target Amount</th>
                                        <th>Achieved</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teamTargets as $target): ?>
                                        <tr>
                                            <td>
                                                <small><?= $target['start_date_formatted'] ?><br>to<br><?= $target['end_date_formatted'] ?></small>
                                            </td>
                                            <td>
                                                <strong class="text-info">₹<?= number_format($target['target_amount'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <strong class="text-success">₹<?= number_format($target['achieved_amount'] ?? 0, 2) ?></strong>
                                            </td>
                                            <td style="min-width: 100px;">
                                                <div class="d-flex align-items-center gap-1">
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-info" style="width: <?= min(100, $target['achievement_percentage'] ?? 0) ?>%"></div>
                                                    </div>
                                                    <small><?= round($target['achievement_percentage'] ?? 0) ?>%</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $target['display_status'] == 'achieved' ? 'success' : 
                                                    ($target['display_status'] == 'overdue' ? 'danger' : 
                                                    ($target['display_status'] == 'completed' ? 'info' : 
                                                    ($target['display_status'] == 'cancelled' ? 'secondary' : 'warning'))) ?>">
                                                    <?= strtoupper($target['display_status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($target['notes'])): ?>
                                                    <button class="btn btn-sm btn-link text-decoration-none p-0" 
                                                            onclick="showNote('<?= addslashes(htmlspecialchars($target['notes'])) ?>')">
                                                        <i class="bi bi-chat-text"></i> View
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= $target['created_date_formatted'] ?></small>
                                                <?php if ($target['created_by_name']): ?>
                                                    <br><small class="text-muted">by <?= htmlspecialchars($target['created_by_name']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-people fs-1 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">No team targets found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Modal for viewing notes -->
<div class="modal fade" id="noteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bi bi-sticky me-2"></i>Target Notes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="noteContent" class="mb-0" style="white-space: pre-wrap;"></p>
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
    // Toggle functionality for Individual Targets
    const toggleIndividual = document.getElementById('toggleIndividual');
    const individualSection = document.getElementById('individualTargetsSection');
    
    toggleIndividual.addEventListener('change', function() {
        if (this.checked) {
            individualSection.style.display = 'block';
        } else {
            individualSection.style.display = 'none';
        }
    });
    
    // Toggle functionality for Team Targets
    const toggleTeam = document.getElementById('toggleTeam');
    const teamSection = document.getElementById('teamTargetsSection');
    
    toggleTeam.addEventListener('change', function() {
        if (this.checked) {
            teamSection.style.display = 'block';
        } else {
            teamSection.style.display = 'none';
        }
    });
    
    function showNote(note) {
        document.getElementById('noteContent').innerHTML = note.replace(/\n/g, '<br>');
        var noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
        noteModal.show();
    }
</script>

<style>
    .form-switch .form-check-input {
        cursor: pointer;
    }
    
    .form-switch .form-check-input:checked {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    @media (max-width: 768px) {
        .table td, .table th {
            font-size: 12px;
            padding: 0.5rem;
        }
    }
</style>

</body>
</html>