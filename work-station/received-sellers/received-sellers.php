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

// Get shared sellers data (received)
function getReceivedSellers($user_uid, $status = null) {
    try {
        $pdo = db();
        
        $sql = "SELECT ssd.*, 
                s.id as seller_db_id,
                s.work_details_update as business_name,
                s.phone_number,
                s.seller_id,
                s.customer_response,
                s.entry_date,
                s.plans_interested,
                s.current_status,
                s.remembering_notes as seller_notes,
                u.name as shared_by_name,
                u.phone as shared_by_phone,
                u.user_uid as shared_by_user_uid
                FROM shared_seller_data ssd
                LEFT JOIN sales_person_sellers s ON ssd.seller_id = s.id
                LEFT JOIN users u ON ssd.shared_by = u.id
                WHERE ssd.shared_to_uid = ? 
                AND ssd.status = 'active'
                AND (ssd.expiry_date IS NULL OR ssd.expiry_date > NOW())
                ORDER BY ssd.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_uid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting received sellers: " . $e->getMessage());
        return [];
    }
}

// Get statistics
function getReceivedStats($user_uid) {
    try {
        $pdo = db();
        
        $sql = "SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT shared_by) as unique_sharers,
                COUNT(DISTINCT seller_id) as unique_sellers
                FROM shared_seller_data 
                WHERE shared_to_uid = ? 
                AND status = 'active'
                AND (expiry_date IS NULL OR expiry_date > NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_uid]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting received stats: " . $e->getMessage());
        return ['total' => 0, 'unique_sharers' => 0, 'unique_sellers' => 0];
    }
}

$receivedSellers = getReceivedSellers($user_uid);
$stats = getReceivedStats($user_uid);
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

            <main class="col-md-9 ms-sm-auto col-lg-10 px-3 px-md-4 py-4">
                <!-- Header -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center flex-wrap flex-md-nowrap pt-3 pb-2 mb-4 border-bottom gap-2">
                    <div>
                        <h1 class="h2 mb-2 mb-sm-0">
                            <i class="bi bi-inbox-fill text-primary me-2"></i>
                            Received Seller Data
                        </h1>
                        <p class="text-muted mb-0">Sellers shared with you by other team members</p>
                    </div>
                    <a href="share-seller.php" class="btn btn-primary">
                        <i class="bi bi-share me-2"></i>Share Seller Data
                    </a>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-inbox fs-4 text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-0"><?= $stats['total'] ?></h3>
                                        <small class="text-muted">Total Shares Received</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-people fs-4 text-success"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-0"><?= $stats['unique_sharers'] ?></h3>
                                        <small class="text-muted">Unique Sharers</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-shop fs-4 text-info"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-0"><?= $stats['unique_sellers'] ?></h3>
                                        <small class="text-muted">Unique Sellers</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by business name, phone, or sharer...">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="filterResponse">
                                    <option value="">All Responses</option>
                                    <option value="Plan Upgraded">Plan Upgraded</option>
                                    <option value="Plan Interested">Plan Interested</option>
                                    <option value="CNP">CNP</option>
                                    <option value="Later">Later</option>
                                    <option value="Not interested">Not interested</option>
                                    <option value="Switch Off">Switch Off</option>
                                    <option value="No Business">No Business</option>
                                    <option value="Whatsapp Details sent">Whatsapp Details sent</option>
                                    <option value="Out of Service">Out of Service</option>
                                    <option value="Testing">Testing</option>
                                    <option value="Renewals">Renewals</option>
                                    <option value="Schedule">Schedule</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-secondary" id="clearFilters">
                                        <i class="bi bi-x-circle"></i> Clear Filters
                                    </button>
                                    <button class="btn btn-outline-primary" id="refreshData">
                                        <i class="bi bi-arrow-repeat"></i> Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Received Sellers Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-check me-2 text-primary"></i>
                            Shared Sellers List
                            <span class="badge bg-primary ms-2" id="resultCount"><?= count($receivedSellers) ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="sellersTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 20%">Seller Details</th>
                                        <th style="width: 15%">Contact</th>
                                        <th style="width: 15%">Response</th>
                                        <th style="width: 15%">Shared By</th>
                                        <th style="width: 15%">Shared On</th>
                                        <th style="width: 15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="sellersTableBody">
                                    <?php if (!empty($receivedSellers)): ?>
                                        <?php foreach ($receivedSellers as $index => $seller): ?>
                                            <tr class="seller-row" 
                                                data-business="<?= strtolower(htmlspecialchars($seller['business_name'])) ?>"
                                                data-phone="<?= $seller['phone_number'] ?>"
                                                data-sharer="<?= strtolower(htmlspecialchars($seller['shared_by_name'])) ?>"
                                                data-response="<?= $seller['customer_response'] ?>">
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <div class="fw-bold"><?= htmlspecialchars($seller['business_name']) ?></div>
                                                    <small class="text-muted">ID: <?= htmlspecialchars($seller['seller_id'] ?? '-') ?></small>
                                                    <?php if (!empty($seller['entry_date'])): ?>
                                                        <br><small class="text-muted">Entry: <?= date('d M Y', strtotime($seller['entry_date'])) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($seller['phone_number']) ?></span>
                                                        <?php if (!empty($seller['plans_interested'])): ?>
                                                            <small class="text-muted mt-1">
                                                                <i class="bi bi-star me-1"></i> <?= htmlspecialchars($seller['plans_interested']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $seller['customer_response'] == 'Plan Upgraded' ? 'success' :
                                                        ($seller['customer_response'] == 'Plan Interested' ? 'info' :
                                                        ($seller['customer_response'] == 'CNP' ? 'warning' :
                                                        ($seller['customer_response'] == 'Not interested' ? 'danger' :
                                                        ($seller['customer_response'] == 'Later' ? 'secondary' : 'primary')))) ?>">
                                                        <?= htmlspecialchars($seller['customer_response']) ?>
                                                    </span>
                                                    <?php if (!empty($seller['current_status'])): ?>
                                                        <br><small class="text-muted">Status: <?= htmlspecialchars($seller['current_status']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <strong><?= htmlspecialchars($seller['shared_by_name']) ?></strong>
                                                        <small class="text-muted"><?= htmlspecialchars($seller['shared_by_user_uid']) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small><?= date('d M Y', strtotime($seller['created_at'])) ?></small>
                                                    <br><small class="text-muted"><?= date('h:i A', strtotime($seller['created_at'])) ?></small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary view-seller" 
                                                            data-id="<?= $seller['seller_db_id'] ?>"
                                                            data-business="<?= htmlspecialchars($seller['business_name']) ?>"
                                                            data-phone="<?= $seller['phone_number'] ?>"
                                                            data-response="<?= htmlspecialchars($seller['customer_response']) ?>"
                                                            data-seller-id="<?= htmlspecialchars($seller['seller_id']) ?>"
                                                            data-notes="<?= htmlspecialchars($seller['seller_notes']) ?>"
                                                            data-shared-by="<?= htmlspecialchars($seller['shared_by_name']) ?>"
                                                            data-shared-on="<?= date('d M Y h:i A', strtotime($seller['created_at'])) ?>"
                                                            data-share-notes="<?= htmlspecialchars($seller['notes']) ?>">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                    <?php if ($seller['permission'] == 'edit' || $seller['permission'] == 'both'): ?>
                                                        <button class="btn btn-sm btn-outline-warning edit-seller mt-1 mt-md-0"
                                                                data-id="<?= $seller['seller_db_id'] ?>"
                                                                data-business="<?= htmlspecialchars($seller['business_name']) ?>"
                                                                data-phone="<?= $seller['phone_number'] ?>"
                                                                data-response="<?= htmlspecialchars($seller['customer_response']) ?>"
                                                                data-seller-id="<?= htmlspecialchars($seller['seller_id']) ?>">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                                <p class="text-muted mt-2 mb-0">No sellers shared with you yet</p>
                                                <small class="text-muted">When someone shares seller data with you, it will appear here</small>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Seller Modal -->
    <div class="modal fade" id="viewSellerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-badge me-2"></i>Seller Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewSellerContent">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Seller Modal -->
    <div class="modal fade" id="editSellerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>Edit Seller Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editSellerForm">
                        <input type="hidden" id="edit_seller_id" name="seller_id">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Seller ID</label>
                            <input type="text" class="form-control" id="edit_seller_id_field" name="seller_id_field" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Business Name</label>
                            <input type="text" class="form-control" id="edit_business_name" name="business_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="tel" class="form-control" id="edit_phone_number" name="phone_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Customer Response</label>
                            <select class="form-select" id="edit_customer_response" name="customer_response" required>
                                <option value="Plan Upgraded">Plan Upgraded</option>
                                <option value="Plan Interested">Plan Interested</option>
                                <option value="CNP">CNP</option>
                                <option value="Later">Later</option>
                                <option value="Not interested">Not interested</option>
                                <option value="Switch Off">Switch Off</option>
                                <option value="No Business">No Business</option>
                                <option value="Whatsapp Details sent">Whatsapp Details sent</option>
                                <option value="Out of Service">Out of Service</option>
                                <option value="Testing">Testing</option>
                                <option value="Renewals">Renewals</option>
                                <option value="Schedule">Schedule</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="updateSeller()">Update Seller</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>

    <script>
    $(document).ready(function() {
        // Search and filter functionality
        function filterTable() {
            const searchTerm = $('#searchInput').val().toLowerCase();
            const filterResponse = $('#filterResponse').val();
            let visibleCount = 0;
            
            $('#sellersTableBody tr').each(function() {
                const $row = $(this);
                const business = $row.data('business') || '';
                const phone = $row.data('phone') || '';
                const sharer = $row.data('sharer') || '';
                const response = $row.data('response') || '';
                
                let show = true;
                
                if (searchTerm && !business.includes(searchTerm) && !phone.includes(searchTerm) && !sharer.includes(searchTerm)) {
                    show = false;
                }
                
                if (filterResponse && response !== filterResponse) {
                    show = false;
                }
                
                if (show) {
                    $row.show();
                    visibleCount++;
                } else {
                    $row.hide();
                }
            });
            
            $('#resultCount').text(visibleCount);
        }
        
        $('#searchInput').on('keyup', filterTable);
        $('#filterResponse').on('change', filterTable);
        
        $('#clearFilters').on('click', function() {
            $('#searchInput').val('');
            $('#filterResponse').val('');
            filterTable();
        });
        
        $('#refreshData').on('click', function() {
            location.reload();
        });
        
        // View seller details
        $('.view-seller').on('click', function() {
            const sellerId = $(this).data('id');
            const businessName = $(this).data('business');
            const phoneNumber = $(this).data('phone');
            const customerResponse = $(this).data('response');
            const sellerIdField = $(this).data('seller-id');
            const notes = $(this).data('notes') || 'No notes available';
            const sharedBy = $(this).data('shared-by');
            const sharedOn = $(this).data('shared-on');
            const shareNotes = $(this).data('share-notes');
            
            const html = `
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small fw-semibold">Seller ID</label>
                        <p class="mb-0"><strong>${escapeHtml(sellerIdField || '-')}</strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small fw-semibold">Business Name</label>
                        <p class="mb-0"><strong>${escapeHtml(businessName)}</strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small fw-semibold">Phone Number</label>
                        <p class="mb-0"><strong>${escapeHtml(phoneNumber)}</strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small fw-semibold">Customer Response</label>
                        <p class="mb-0"><span class="badge bg-${getBadgeColor(customerResponse)}">${escapeHtml(customerResponse)}</span></p>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="text-muted small fw-semibold">Seller Notes</label>
                        <p class="mb-0 bg-light p-2 rounded">${escapeHtml(notes).replace(/\n/g, '<br>')}</p>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="alert alert-info mb-0">
                            <strong><i class="bi bi-share"></i> Shared Information:</strong><br>
                            Shared by: ${escapeHtml(sharedBy)}<br>
                            Shared on: ${escapeHtml(sharedOn)}<br>
                            ${shareNotes ? `<strong>Notes:</strong> ${escapeHtml(shareNotes)}` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            $('#viewSellerContent').html(html);
            $('#viewSellerModal').modal('show');
        });
        
        // Edit seller
        $('.edit-seller').on('click', function() {
            const sellerId = $(this).data('id');
            const businessName = $(this).data('business');
            const phoneNumber = $(this).data('phone');
            const customerResponse = $(this).data('response');
            const sellerIdField = $(this).data('seller-id');
            
            $('#edit_seller_id').val(sellerId);
            $('#edit_seller_id_field').val(sellerIdField);
            $('#edit_business_name').val(businessName);
            $('#edit_phone_number').val(phoneNumber);
            $('#edit_customer_response').val(customerResponse);
            $('#edit_notes').val('');
            
            $('#editSellerModal').modal('show');
        });
    });
    
    function updateSeller() {
        const sellerId = $('#edit_seller_id').val();
        const businessName = $('#edit_business_name').val().trim();
        const phoneNumber = $('#edit_phone_number').val().trim();
        const customerResponse = $('#edit_customer_response').val();
        const notes = $('#edit_notes').val().trim();
        
        if (!businessName || !phoneNumber || !customerResponse) {
            Swal.fire('Error', 'Please fill all required fields', 'error');
            return;
        }
        
        if (!/^\d{10}$/.test(phoneNumber)) {
            Swal.fire('Error', 'Please enter a valid 10-digit phone number', 'error');
            return;
        }
        
        Swal.fire({
            title: 'Updating...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: BASE_URL + 'ajax/work-station/share-sellers/update-seller.php',
            type: 'POST',
            data: {
                seller_id: sellerId,
                business_name: businessName,
                phone_number: phoneNumber,
                customer_response: customerResponse,
                notes: notes
            },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.status === 'success') {
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.close();
                Swal.fire('Error!', 'Failed to update seller. Please try again.', 'error');
            }
        });
    }
    
    function getBadgeColor(response) {
        const colors = {
            'Plan Upgraded': 'success',
            'Plan Interested': 'info',
            'CNP': 'warning',
            'Not interested': 'danger',
            'Later': 'secondary',
            'Switch Off': 'dark',
            'No Business': 'secondary',
            'Whatsapp Details sent': 'success',
            'Out of Service': 'danger',
            'Testing': 'info',
            'Renewals': 'primary',
            'Schedule': 'primary'
        };
        return colors[response] || 'primary';
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>

    <style>
        .table-responsive {
            overflow-x: auto;
        }
        
        @media (max-width: 768px) {
            .table td, .table th {
                font-size: 12px;
                padding: 0.5rem;
            }
            
            .btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 11px;
            }
        }
        
        .badge {
            font-size: 11px;
            padding: 5px 10px;
        }
        
        .seller-row {
            transition: background-color 0.2s;
        }
        
        .seller-row:hover {
            background-color: #f8f9fa;
        }
    </style>
</body>
</html>