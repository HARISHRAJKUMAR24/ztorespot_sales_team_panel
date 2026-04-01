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

// Get current month and year
$current_month = date('m');
$current_year = date('Y');
$current_month_name = date('F Y');

// Function to get plan counts for current month
function getCurrentMonthPlanCounts($user_uid) {
    global $pdo;
    $current_month = date('m');
    $current_year = date('Y');
    
    $sql = "SELECT 
                plans_interested,
                COUNT(*) as count,
                SUM(CASE WHEN customer_response = 'Plan Upgraded' THEN 1 ELSE 0 END) as upgraded_count,
                SUM(CASE WHEN customer_response = 'Plan Interested' THEN 1 ELSE 0 END) as interested_count
            FROM sales_person_sellers 
            WHERE user_uid = ? 
            AND MONTH(created_at) = ? 
            AND YEAR(created_at) = ?
            AND plans_interested IS NOT NULL 
            AND plans_interested != 'None'
            AND plans_interested != ''
            GROUP BY plans_interested
            ORDER BY count DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_uid, $current_month, $current_year]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get total plan counts (all time)
function getTotalPlanCounts($user_uid) {
    global $pdo;
    
    $sql = "SELECT 
                plans_interested,
                COUNT(*) as count,
                SUM(CASE WHEN customer_response = 'Plan Upgraded' THEN 1 ELSE 0 END) as upgraded_count,
                SUM(CASE WHEN customer_response = 'Plan Interested' THEN 1 ELSE 0 END) as interested_count
            FROM sales_person_sellers 
            WHERE user_uid = ? 
            AND plans_interested IS NOT NULL 
            AND plans_interested != 'None'
            AND plans_interested != ''
            GROUP BY plans_interested
            ORDER BY count DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_uid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get plan counts by month (for chart)
function getPlanCountsByMonth($user_uid) {
    global $pdo;
    
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%b %Y') as month,
                YEAR(created_at) as year,
                MONTH(created_at) as month_num,
                plans_interested,
                COUNT(*) as count
            FROM sales_person_sellers 
            WHERE user_uid = ? 
            AND plans_interested IS NOT NULL 
            AND plans_interested != 'None'
            AND plans_interested != ''
            GROUP BY YEAR(created_at), MONTH(created_at), plans_interested
            ORDER BY created_at DESC
            LIMIT 12";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_uid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get monthly totals
function getMonthlyTotals($user_uid) {
    global $pdo;
    
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%b %Y') as month,
                YEAR(created_at) as year,
                MONTH(created_at) as month_num,
                COUNT(*) as total_plans,
                SUM(CASE WHEN customer_response = 'Plan Upgraded' THEN 1 ELSE 0 END) as total_upgraded,
                SUM(CASE WHEN customer_response = 'Plan Interested' THEN 1 ELSE 0 END) as total_interested
            FROM sales_person_sellers 
            WHERE user_uid = ? 
            AND plans_interested IS NOT NULL 
            AND plans_interested != 'None'
            AND plans_interested != ''
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY created_at DESC
            LIMIT 6";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_uid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all data
$currentMonthPlans = getCurrentMonthPlanCounts($user_uid);
$totalPlans = getTotalPlanCounts($user_uid);
$monthlyData = getPlanCountsByMonth($user_uid);
$monthlyTotals = getMonthlyTotals($user_uid);

// Calculate totals
$currentMonthTotal = array_sum(array_column($currentMonthPlans, 'count'));
$currentMonthUpgraded = array_sum(array_column($currentMonthPlans, 'upgraded_count'));
$currentMonthInterested = array_sum(array_column($currentMonthPlans, 'interested_count'));

$totalAllTime = array_sum(array_column($totalPlans, 'count'));
$totalUpgradedAllTime = array_sum(array_column($totalPlans, 'upgraded_count'));
$totalInterestedAllTime = array_sum(array_column($totalPlans, 'interested_count'));

// Prepare chart data
$chartLabels = [];
$chartData = [];
foreach ($totalPlans as $plan) {
    $chartLabels[] = $plan['plans_interested'];
    $chartData[] = $plan['count'];
}

// Prepare monthly chart data
$monthlyChartLabels = [];
$monthlyChartUpgraded = [];
$monthlyChartInterested = [];
foreach (array_reverse($monthlyTotals) as $month) {
    $monthlyChartLabels[] = $month['month'];
    $monthlyChartUpgraded[] = $month['total_upgraded'];
    $monthlyChartInterested[] = $month['total_interested'];
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
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center flex-wrap flex-md-nowrap pt-3 pb-2 mb-4 border-bottom gap-2">
                    <div>
                        <h1 class="h2 mb-2 mb-sm-0">
                            <i class="bi bi-graph-up text-primary me-2"></i>
                            Plan Reports
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= $current_month_name ?> | Total Plans: <?= $currentMonthTotal ?>
                        </p>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> Export Report
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportToPDF()"><i class="bi bi-file-pdf me-2"></i>Export as PDF</a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportToExcel()"><i class="bi bi-file-excel me-2"></i>Export as Excel</a></li>
                            <li><a class="dropdown-item" href="#" onclick="window.print()"><i class="bi bi-printer me-2"></i>Print Report</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-calendar-week fs-4 text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0">This Month Plans</h6>
                                        <h2 class="mb-0 fw-bold"><?= $currentMonthTotal ?></h2>
                                        <small class="text-muted"><?= $current_month_name ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-arrow-up-circle fs-4 text-success"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0">Upgraded Plans</h6>
                                        <h2 class="mb-0 fw-bold text-success"><?= $currentMonthUpgraded ?></h2>
                                        <small class="text-muted">This month</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-star fs-4 text-warning"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0">Interested Plans</h6>
                                        <h2 class="mb-0 fw-bold text-warning"><?= $currentMonthInterested ?></h2>
                                        <small class="text-muted">This month</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-trophy fs-4 text-info"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-0">Total All Time</h6>
                                        <h2 class="mb-0 fw-bold"><?= $totalAllTime ?></h2>
                                        <small class="text-muted">Overall plans</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Current Month Plans Card -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-calendar-month text-primary me-2"></i>
                                    <?= $current_month_name ?> Plans
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($currentMonthPlans)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Plan Name</th>
                                                    <th class="text-center">Total</th>
                                                    <th class="text-center">Upgraded</th>
                                                    <th class="text-center">Interested</th>
                                                    <th class="text-center">%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($currentMonthPlans as $plan): 
                                                    $percentage = ($plan['count'] / $currentMonthTotal) * 100;
                                                ?>
                                                    <tr>
                                                        <td class="fw-semibold">
                                                            <?= htmlspecialchars($plan['plans_interested']) ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-primary rounded-pill px-3 py-2"><?= $plan['count'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success rounded-pill px-3 py-2"><?= $plan['upgraded_count'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-warning rounded-pill px-3 py-2"><?= $plan['interested_count'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="progress" style="height: 8px;">
                                                                <div class="progress-bar bg-primary" style="width: <?= $percentage ?>%"></div>
                                                            </div>
                                                            <small class="text-muted"><?= round($percentage, 1) ?>%</small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr class="fw-bold">
                                                    <td>Total</td>
                                                    <td class="text-center"><?= $currentMonthTotal ?></td>
                                                    <td class="text-center"><?= $currentMonthUpgraded ?></td>
                                                    <td class="text-center"><?= $currentMonthInterested ?></td>
                                                    <td class="text-center">100%</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                        <p class="text-muted mt-2 mb-0">No plans found for <?= $current_month_name ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Overall Plans Card -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-pie-chart text-primary me-2"></i>
                                    Overall Plan Distribution
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($totalPlans)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Plan Name</th>
                                                    <th class="text-center">Total</th>
                                                    <th class="text-center">Upgraded</th>
                                                    <th class="text-center">Interested</th>
                                                    <th class="text-center">%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($totalPlans as $plan): 
                                                    $percentage = ($plan['count'] / $totalAllTime) * 100;
                                                ?>
                                                    <tr>
                                                        <td class="fw-semibold">
                                                            <?= htmlspecialchars($plan['plans_interested']) ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-primary rounded-pill px-3 py-2"><?= $plan['count'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success rounded-pill px-3 py-2"><?= $plan['upgraded_count'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-warning rounded-pill px-3 py-2"><?= $plan['interested_count'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="progress" style="height: 8px;">
                                                                <div class="progress-bar bg-primary" style="width: <?= $percentage ?>%"></div>
                                                            </div>
                                                            <small class="text-muted"><?= round($percentage, 1) ?>%</small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr class="fw-bold">
                                                    <td>Total</td>
                                                    <td class="text-center"><?= $totalAllTime ?></td>
                                                    <td class="text-center"><?= $totalUpgradedAllTime ?></td>
                                                    <td class="text-center"><?= $totalInterestedAllTime ?></td>
                                                    <td class="text-center">100%</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-archive fs-1 text-muted"></i>
                                        <p class="text-muted mt-2 mb-0">No plans found overall</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Monthly Trend Chart -->
                    <div class="col-12 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-bar-chart-line text-primary me-2"></i>
                                    Monthly Trend (Last 6 Months)
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyTrendChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Monthly Breakdown Table -->
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-table text-primary me-2"></i>
                                    Monthly Breakdown
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($monthlyTotals)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Month</th>
                                                    <th class="text-center">Total Plans</th>
                                                    <th class="text-center">Upgraded</th>
                                                    <th class="text-center">Interested</th>
                                                    <th class="text-center">Upgrade Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($monthlyTotals as $month): 
                                                    $upgradeRate = $month['total_plans'] > 0 ? ($month['total_upgraded'] / $month['total_plans']) * 100 : 0;
                                                ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?= htmlspecialchars($month['month']) ?></td>
                                                        <td class="text-center">
                                                            <span class="badge bg-primary rounded-pill px-3 py-2"><?= $month['total_plans'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success rounded-pill px-3 py-2"><?= $month['total_upgraded'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-warning rounded-pill px-3 py-2"><?= $month['total_interested'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                                    <div class="progress-bar bg-success" style="width: <?= $upgradeRate ?>%"></div>
                                                                </div>
                                                                <small class="fw-bold"><?= round($upgradeRate, 1) ?>%</small>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-table fs-1 text-muted"></i>
                                        <p class="text-muted mt-2 mb-0">No monthly data available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SheetJS for Excel Export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
    <!-- html2pdf for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>

    <script>
        // Monthly Trend Chart
        const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthlyChartLabels) ?>,
                datasets: [
                    {
                        label: 'Upgraded Plans',
                        data: <?= json_encode($monthlyChartUpgraded) ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 8
                    },
                    {
                        label: 'Interested Plans',
                        data: <?= json_encode($monthlyChartInterested) ?>,
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: '#f59e0b',
                        borderWidth: 1,
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + ' plans';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Plans'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        });

        // Export to PDF
        function exportToPDF() {
            const element = document.querySelector('.scrollable-content');
            const opt = {
                margin: [0.5, 0.5, 0.5, 0.5],
                filename: 'plan_report_<?= date('Y_m_d') ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, letterRendering: true },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' }
            };
            html2pdf().set(opt).from(element).save();
        }

        // Export to Excel
        function exportToExcel() {
            const tables = document.querySelectorAll('.table-responsive table');
            const workbook = XLSX.utils.book_new();
            
            tables.forEach((table, index) => {
                const worksheet = XLSX.utils.table_to_sheet(table);
                let sheetName = '';
                if (index === 0) sheetName = 'Current_Month_Plans';
                else if (index === 1) sheetName = 'Overall_Plans';
                else if (index === 2) sheetName = 'Monthly_Breakdown';
                else sheetName = `Sheet_${index + 1}`;
                XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
            });
            
            XLSX.writeFile(workbook, `plan_report_<?= date('Y_m_d') ?>.xlsx`);
        }
    </script>

    <style>
        @media print {
            .sidebar, .topbar, .btn, .dropdown, .menu-toggle {
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
        }
        
        .progress {
            background-color: #e9ecef;
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