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

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'get_data') {
        try {
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
            $sort_column = $_POST['sort_column'] ?? 'id';
            $sort_order = $_POST['sort_order'] ?? 'DESC';
            $search = $_POST['search'] ?? '';
            $filters = $_POST['filters'] ?? [];

            $offset = ($page - 1) * $per_page;

            // Build query
            $where = "WHERE user_uid = ?";
            $params = [$user_uid];

            if (!empty($search)) {
                $where .= " AND (work_details_update LIKE ? OR phone_number LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if (!empty($filters['response'])) {
                $where .= " AND customer_response = ?";
                $params[] = $filters['response'];
            }

            if (!empty($filters['reg_status'])) {
                $where .= " AND registration_status = ?";
                $params[] = $filters['reg_status'];
            }

            if (!empty($filters['current_status'])) {
                $where .= " AND current_status = ?";
                $params[] = $filters['current_status'];
            }

            // Get total count
            $count_sql = "SELECT COUNT(*) FROM sales_person_sellers $where";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetchColumn();

            // Get rows
            $sql = "SELECT * FROM sales_person_sellers $where 
                    ORDER BY $sort_column $sort_order 
                    LIMIT ? OFFSET ?";
            $params[] = $per_page;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get stats
            $stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN customer_response = 'Later' THEN 1 ELSE 0 END) as later_count,
                SUM(CASE WHEN customer_response = 'Call Back AT' THEN 1 ELSE 0 END) as callback_count,
                SUM(CASE WHEN customer_response = 'Schedule' THEN 1 ELSE 0 END) as schedule_count
                FROM sales_person_sellers WHERE user_uid = ?";
            $stats_stmt = $pdo->prepare($stats_sql);
            $stats_stmt->execute([$user_uid]);
            $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'rows' => $rows,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $per_page,
                    'stats' => $stats
                ]
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($_POST['action'] === 'delete') {
        try {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM sales_person_sellers WHERE id = ? AND user_uid = ?");
            $stmt->execute([$id, $user_uid]);

            echo json_encode(['status' => 'success', 'message' => 'Deleted successfully']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($_POST['action'] === 'get_details') {
        try {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("SELECT * FROM sales_person_sellers WHERE id = ? AND user_uid = ?");
            $stmt->execute([$id, $user_uid]);
            $seller = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => $seller]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    exit;
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php template('head-tag'); ?>

<body class="bg-light">
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;"></div>

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
                            <i class="bi bi-file-spreadsheet text-success me-2"></i>
                            Sheets Follow Up
                        </h1>
                        <p class="text-muted mb-0">Manage your sheets follow up sellers</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>work-station/workstation_add_seller.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add New Seller
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h6 class="card-title mb-1 small">Total</h6>
                                    <h3 class="mb-0" id="totalCount">0</h3>
                                </div>
                                <i class="bi bi-people fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h6 class="card-title mb-1 small">Later</h6>
                                    <h3 class="mb-0" id="laterCount">0</h3>
                                </div>
                                <i class="bi bi-clock fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h6 class="card-title mb-1 small">Call Back</h6>
                                    <h3 class="mb-0" id="callbackCount">0</h3>
                                </div>
                                <i class="bi bi-telephone fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h6 class="card-title mb-1 small">Schedule</h6>
                                    <h3 class="mb-0" id="scheduleCount">0</h3>
                                </div>
                                <i class="bi bi-calendar fs-2 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <button class="btn btn-link text-decoration-none p-0 w-100 text-start d-flex justify-content-between align-items-center"
                                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                <span>Filters</span>
                                <i class="bi bi-chevron-down d-md-none"></i>
                            </button>
                        </h5>
                    </div>
                    <div class="collapse show" id="filterCollapse">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Response Type</label>
                                    <select class="form-select form-select-sm" id="filterResponse">
                                        <option value="">All</option>
                                        <option value="Later">Later</option>
                                        <option value="Call Back AT">Call Back AT</option>
                                        <option value="Schedule">Schedule</option>
                                        <option value="CNP">CNP</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Registration Status</label>
                                    <select class="form-select form-select-sm" id="filterRegStatus">
                                        <option value="">All</option>
                                        <option value="Yes">Registered</option>
                                        <option value="No">Not Registered</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Current Status</label>
                                    <select class="form-select form-select-sm" id="filterCurrentStatus">
                                        <option value="">All</option>
                                        <option value="Not yet">Not yet</option>
                                        <option value="Upgraded">Upgraded</option>
                                        <option value="In Progress">In Progress</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small fw-bold">Date Range</label>
                                    <select class="form-select form-select-sm" id="filterDate">
                                        <option value="">All</option>
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month">This Month</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 d-flex gap-2">
                                    <button class="btn btn-primary btn-sm flex-fill" id="applyFilters">
                                        <i class="bi bi-funnel me-1"></i>Apply
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm flex-fill" id="clearFilters">
                                        <i class="bi bi-x-circle me-1"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Table Card -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <h5 class="card-title mb-0">Sheets Follow Up List</h5>
                            <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="searchInput"
                                        placeholder="Search by name or phone...">
                                </div>
                                <select class="form-select form-select-sm" id="perPage" style="min-width: 70px;">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0 p-md-3">
                        <!-- Loading Spinner -->
                        <div id="loadingSpinner" class="text-center py-5" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading data...</p>
                        </div>

                        <!-- Data Table -->
                        <div id="dataTable" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-sort="id" style="width: 5%;">ID <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="entry_date" style="width: 10%;">Date <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="work_details_update" style="width: 20%;">Work Details <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th class="sortable" data-sort="phone_number" style="width: 10%;">Phone <i class="bi bi-arrow-down-up ms-1"></i></th>
                                            <th style="width: 12%;">Response</th>
                                            <th style="width: 28%;">Remembering Notes</th>
                                            <th class="text-center" style="width: 10%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody"></tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-4 px-3">
                                <div class="text-muted small mb-2 mb-sm-0" id="paginationInfo"></div>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                                </nav>
                            </div>
                        </div>

                        <!-- No Data Message -->
                        <div id="noData" class="text-center py-5" style="display: none;">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="mt-2 text-muted">No sellers found</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Seller Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="sellerDetails" class="row g-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .sortable {
            cursor: pointer;
            user-select: none;
        }

        .sortable:hover {
            background-color: #e9ecef !important;
        }

        .badge {
            padding: 0.5em 0.8em;
            border-radius: 25px;
        }

        .toast {
            min-width: 250px;
        }

        .remembering-note {
            max-width: 280px;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.4;
        }

        @media (max-width: 768px) {
            .table td,
            .table th {
                padding: 0.5rem;
                font-size: 0.875rem;
            }

            .btn-sm {
                padding: 0.25rem 0.4rem;
            }
            
            .remembering-note {
                max-width: 180px;
            }
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            let currentPage = 1;
            let perPage = 10;
            let totalPages = 1;
            let sortColumn = 'id';
            let sortOrder = 'DESC';
            let searchTerm = '';
            let filters = {
                response: '',
                reg_status: '',
                current_status: '',
                date_range: ''
            };

            // Load initial data
            loadData();

            // Search input
            let searchTimer;
            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimer);
                searchTerm = $(this).val();
                searchTimer = setTimeout(function() {
                    currentPage = 1;
                    loadData();
                }, 500);
            });

            // Per page change
            $('#perPage').on('change', function() {
                perPage = parseInt($(this).val());
                currentPage = 1;
                loadData();
            });

            // Sorting
            $(document).on('click', '.sortable', function() {
                const column = $(this).data('sort');
                if (sortColumn === column) {
                    sortOrder = sortOrder === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    sortColumn = column;
                    sortOrder = 'ASC';
                }

                $('.sortable i').removeClass('bi-arrow-up bi-arrow-down').addClass('bi-arrow-down-up');
                const icon = $(this).find('i');
                icon.removeClass('bi-arrow-down-up').addClass(sortOrder === 'ASC' ? 'bi-arrow-up' : 'bi-arrow-down');

                loadData();
            });

            // Apply filters
            $('#applyFilters').on('click', function() {
                filters = {
                    response: $('#filterResponse').val(),
                    reg_status: $('#filterRegStatus').val(),
                    current_status: $('#filterCurrentStatus').val(),
                    date_range: $('#filterDate').val()
                };
                currentPage = 1;
                loadData();
            });

            // Clear filters
            $('#clearFilters').on('click', function() {
                $('#filterResponse').val('');
                $('#filterRegStatus').val('');
                $('#filterCurrentStatus').val('');
                $('#filterDate').val('');

                filters = {
                    response: '',
                    reg_status: '',
                    current_status: '',
                    date_range: ''
                };
                currentPage = 1;
                loadData();
            });

            // Pagination click
            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && page !== currentPage) {
                    currentPage = page;
                    loadData();
                }
            });

            // Load data function
            function loadData() {
                $('#loadingSpinner').show();
                $('#dataTable, #noData').hide();

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        action: 'get_data',
                        page: currentPage,
                        per_page: perPage,
                        sort_column: sortColumn,
                        sort_order: sortOrder,
                        search: searchTerm,
                        filters: filters
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loadingSpinner').hide();

                        if (response.status === 'success') {
                            if (response.data.rows.length > 0) {
                                renderTable(response.data.rows);
                                renderPagination(response.data.total, response.data.page, response.data.per_page);
                                updateStats(response.data.stats);
                                $('#dataTable').show();
                            } else {
                                $('#noData').show();
                                updateStats(response.data.stats);
                            }
                        } else {
                            showToast('danger', 'Error', response.message);
                        }
                    },
                    error: function() {
                        $('#loadingSpinner').hide();
                        $('#noData').show();
                        showToast('danger', 'Error', 'Failed to load data');
                    }
                });
            }

            // Render table
            function renderTable(rows) {
                let html = '';

                rows.forEach(function(row) {
                    let dateDisplay = row.entry_date ? new Date(row.entry_date + 'T00:00:00').toLocaleDateString('en-GB') : '-';

                    let badgeClass = 'bg-secondary';
                    if (row.customer_response === 'Later') badgeClass = 'bg-warning';
                    else if (row.customer_response === 'Call Back AT') badgeClass = 'bg-info';
                    else if (row.customer_response === 'Schedule') badgeClass = 'bg-success';
                    else if (row.customer_response === 'CNP') badgeClass = 'bg-danger';
                    else if (row.customer_response === 'Plan Upgraded') badgeClass = 'bg-primary';
                    else if (row.customer_response === 'Plan Interested') badgeClass = 'bg-info';

                    // Get remembering notes - clean up if needed
                    let rememberingNotes = row.remembering_notes || '-';
                    if (rememberingNotes !== '-') {
                        // Remove JSON strings if present
                        rememberingNotes = rememberingNotes.replace(/\{"customer_doubts".*?\}/g, '');
                        rememberingNotes = rememberingNotes.replace(/\n\n+/g, '\n');
                        rememberingNotes = rememberingNotes.trim();
                        if (rememberingNotes === '') rememberingNotes = '-';
                    }

                    html += '<tr>';
                    html += `<td class="fw-bold">${escapeHtml(row.id)}</td>`;
                    html += `<td>${dateDisplay}</td>`;
                    html += `<td class="fw-medium">${escapeHtml(row.work_details_update || '-')}</td>`;
                    html += `<td>${escapeHtml(row.phone_number || '-')}</td>`;
                    html += `<td><span class="badge ${badgeClass}">${escapeHtml(row.customer_response || '-')}</span></td>`;
                    html += `<td class="remembering-note"><small>${escapeHtml(rememberingNotes)}</small></td>`;
                    html += `<td class="text-center text-nowrap">
                        <button class="btn btn-sm btn-outline-primary view-btn" data-id="${row.id}" title="View">
                            <i class="bi bi-eye"></i>
                        </button>
                        <a href="../sheets_edit_seller.php?id=${row.id}" class="btn btn-sm btn-outline-warning" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${row.id}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>`;
                    html += '</tr>';
                });

                $('#tableBody').html(html);
            }

            // Render pagination
            function renderPagination(total, page, perPage) {
                totalPages = Math.ceil(total / perPage);
                let html = '';

                if (totalPages > 1) {
                    html += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${page - 1}">&laquo;</a>
                    </li>`;

                    for (let i = 1; i <= totalPages; i++) {
                        if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                            html += `<li class="page-item ${i === page ? 'active' : ''}">
                                <a class="page-link" href="#" data-page="${i}">${i}</a>
                            </li>`;
                        } else if (i === page - 3 || i === page + 3) {
                            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                        }
                    }

                    html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${page + 1}">&raquo;</a>
                    </li>`;
                }

                $('#pagination').html(html);

                const start = (page - 1) * perPage + 1;
                const end = Math.min(page * perPage, total);
                $('#paginationInfo').html(`Showing ${start} to ${end} of ${total} entries`);
            }

            // Update stats
            function updateStats(stats) {
                $('#totalCount').text(stats.total || 0);
                $('#laterCount').text(stats.later_count || 0);
                $('#callbackCount').text(stats.callback_count || 0);
                $('#scheduleCount').text(stats.schedule_count || 0);
            }

            // View button click
            $(document).on('click', '.view-btn', function() {
                const id = $(this).data('id');

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        action: 'get_details',
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showSellerDetails(response.data);
                            new bootstrap.Modal(document.getElementById('viewModal')).show();
                        } else {
                            showToast('danger', 'Error', response.message);
                        }
                    }
                });
            });

            // Show seller details
            function showSellerDetails(seller) {
                let html = '';

                let dateDisplay = seller.entry_date ? new Date(seller.entry_date + 'T00:00:00').toLocaleDateString('en-GB') : '-';
                let createdDisplay = seller.created_at ? new Date(seller.created_at).toLocaleString('en-GB') : '-';

                const fields = [
                    ['ID', seller.id],
                    ['Entry Date', dateDisplay],
                    ['Work Details', seller.work_details_update],
                    ['Source Type', seller.source_type],
                    ['Registration Status', seller.registration_status],
                    ['Phone Number', seller.phone_number],
                    ['Plans Interested', seller.plans_interested],
                    ['Customer Response', seller.customer_response],
                    ['Remembering Notes', seller.remembering_notes],
                    ['Latest Update', seller.latest_update],
                    ['Current Status', seller.current_status],
                    ['Customer Queries', seller.customer_queries],
                    ['Video/Canva', seller.video_canva],
                    ['Call Timing', seller.call_timing],
                    ['Remarks', seller.remarks],
                    ['Import Batch', seller.import_batch],
                    ['Created At', createdDisplay]
                ];

                fields.forEach(function(field) {
                    if (field[1] && field[1] !== '' && field[1] !== null && field[1] !== '-') {
                        let displayValue = field[1].toString();
                        // Clean up JSON if needed
                        if (field[0] === 'Remembering Notes') {
                            displayValue = displayValue.replace(/\{"customer_doubts".*?\}/g, '');
                            displayValue = displayValue.replace(/\n\n+/g, '\n');
                            displayValue = displayValue.trim();
                            if (displayValue === '') displayValue = '-';
                        }
                        html += `
                        <div class="col-12 col-sm-6 mb-3">
                            <div class="p-2 bg-light rounded">
                                <small class="text-muted d-block">${field[0]}</small>
                                <strong class="d-block">${escapeHtml(displayValue)}</strong>
                            </div>
                        </div>
                    `;
                    }
                });

                $('#sellerDetails').html(html);
            }

            // Delete button click
            $(document).on('click', '.delete-btn', function() {
                const id = $(this).data('id');

                if (confirm('Are you sure you want to delete this seller?')) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        data: {
                            action: 'delete',
                            id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                showToast('success', 'Success', 'Seller deleted successfully');
                                loadData();
                            } else {
                                showToast('danger', 'Error', response.message);
                            }
                        }
                    });
                }
            });

            // Escape HTML
            function escapeHtml(text) {
                if (!text || text === null || text === '') return '-';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Toast message
            function showToast(type, title, message) {
                const id = 'toast-' + Date.now();
                const bgClass = type === 'success' ? 'bg-success' : type === 'danger' ? 'bg-danger' : 'bg-warning';

                const html = `
                <div id="${id}" class="toast text-white ${bgClass}" role="alert" data-bs-autohide="true" data-bs-delay="3000">
                    <div class="toast-body d-flex justify-content-between align-items-center">
                        <div><strong>${title}</strong> ${message}</div>
                        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="toast"></button>
                    </div>
                </div>`;

                $('.toast-container').append(html);
                new bootstrap.Toast(document.getElementById(id)).show();
                $(`#${id}`).on('hidden.bs.toast', function() {
                    $(this).remove();
                });
            }
        });
    </script>

    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>

</html>