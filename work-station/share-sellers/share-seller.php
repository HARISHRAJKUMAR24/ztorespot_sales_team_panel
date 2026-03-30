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

// Get all sellers for multi-select dropdown
$sellers_stmt = $pdo->prepare("
    SELECT id, work_details_update as business_name, phone_number, customer_response 
    FROM sales_person_sellers 
    WHERE user_uid = ? 
    ORDER BY created_at DESC
");
$sellers_stmt->execute([$user_uid]);
$sellers = $sellers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ALL users for sharing dropdown (including dummy users)
$users_stmt = $pdo->prepare("
    SELECT user_uid, name, phone, email 
    FROM users 
    WHERE user_uid != ? 
    ORDER BY name
");
$users_stmt->execute([$user_uid]);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Set profile image path
$profile_image = !empty($user['profile_image'])
    ? BASE_URL . $user['profile_image']
    : 'https://via.placeholder.com/150';
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
                    <h1 class="h2 mb-2 mb-sm-0">
                        <i class="bi bi-share-fill text-primary me-2"></i>
                        Share Seller Information
                    </h1>
                    <div class="d-flex gap-2">
                        <a href="shared-sellers-list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-list me-1"></i>View Shared Sellers
                        </a>
                    </div>
                </div>

                <!-- Share Form Card -->
                <div class="row">
                    <div class="col-12 col-lg-8 mx-auto">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-send-fill text-primary me-2"></i>
                                    Share Seller Details
                                </h5>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <form id="shareSellerForm">
                                    <!-- Option to Select Existing or Create New -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="shareOption" id="newSeller" value="new" checked>
                                                <label class="form-check-label" for="newSeller">
                                                    <i class="bi bi-plus-circle me-1"></i>Add New Seller Details
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="shareOption" id="existingSeller" value="existing">
                                                <label class="form-check-label" for="existingSeller">
                                                    <i class="bi bi-shop me-1"></i>Select Existing Seller(s)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Existing Seller Selection (Multi-select) - Hidden by default -->
                                    <div id="existingSellerSection" style="display: none;">
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-person-badge text-primary me-1"></i>
                                                    Select Sellers to Share <span class="text-danger">*</span>
                                                </label>
                                                
                                                <!-- Search Box for Sellers -->
                                                <div class="mb-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light">
                                                            <i class="bi bi-search"></i>
                                                        </span>
                                                        <input type="text" class="form-control" id="sellerSearch" 
                                                               placeholder="Search sellers by ID, name or phone...">
                                                        <button class="btn btn-outline-secondary" type="button" id="clearSellerSearch">
                                                            <i class="bi bi-x"></i> Clear
                                                        </button>
                                                    </div>
                                                    <small class="text-muted" id="searchResultsCount"></small>
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-info-circle me-1"></i>
                                                        Hold Ctrl (Cmd on Mac) to select multiple sellers
                                                    </small>
                                                </div>
                                                
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="bi bi-shop"></i>
                                                    </span>
                                                    <select class="form-select border-start-0" id="sellerIds" name="seller_ids[]" multiple size="8">
                                                        <option value="" disabled>Choose sellers to share</option>
                                                        <?php foreach ($sellers as $seller): ?>
                                                            <option value="<?= htmlspecialchars($seller['id']) ?>"
                                                                    data-business="<?= htmlspecialchars($seller['business_name']) ?>"
                                                                    data-phone="<?= htmlspecialchars($seller['phone_number']) ?>"
                                                                    data-response="<?= htmlspecialchars($seller['customer_response']) ?>">
                                                                ID: <?= htmlspecialchars($seller['id']) ?> - 
                                                                <?= htmlspecialchars($seller['business_name']) ?> - 
                                                                <?= htmlspecialchars($seller['phone_number']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <!-- Quick Select Buttons -->
                                                <div class="mt-2 d-flex gap-2 flex-wrap">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllSellers">
                                                        <i class="bi bi-check-all"></i> Select All
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllSellers">
                                                        <i class="bi bi-x"></i> Deselect All
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info" id="showSelectedOnly">
                                                        <i class="bi bi-eye"></i> Show Selected Only
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="showAllSellers">
                                                        <i class="bi bi-list"></i> Show All
                                                    </button>
                                                </div>

                                                <?php if (empty($sellers)): ?>
                                                    <div class="alert alert-warning mt-2 mb-0 py-2">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                                        No sellers found. Please add new seller details.
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Selected Sellers Preview -->
                                        <div id="sellersPreview" class="row mb-4" style="display: none;">
                                            <div class="col-12">
                                                <div class="card bg-light border">
                                                    <div class="card-body py-3">
                                                        <h6 class="card-subtitle mb-2 text-muted">
                                                            <i class="bi bi-info-circle me-1"></i>
                                                            Selected Sellers (<span id="selectedCount">0</span>)
                                                        </h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-borderless mb-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th>ID</th>
                                                                        <th>Business Name</th>
                                                                        <th>Phone</th>
                                                                        <th>Response</th>
                                                                        <th>Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="previewTableBody">
                                                                    <!-- Dynamically filled -->
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- New Seller Section (Visible by default) -->
                                    <div id="newSellerSection">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-hash text-primary me-1"></i>
                                                    Seller ID <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control" id="newSellerId"
                                                    placeholder="Enter seller ID" min="1" required>
                                                <small class="text-muted">Enter a unique numeric ID for the seller</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-person-circle text-primary me-1"></i>
                                                    Customer / Business Name <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="newCustomerName"
                                                    placeholder="Enter customer or business name" required>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-telephone text-primary me-1"></i>
                                                    Phone Number <span class="text-danger">*</span>
                                                </label>
                                                <input type="tel" class="form-control" id="newPhoneNumber"
                                                    placeholder="10 digit mobile number" maxlength="10" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-chat-dots text-primary me-1"></i>
                                                    Customer Response <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="newCustomerResponse" required>
                                                    <option value="" selected disabled>Select response type</option>
                                                    <option value="Plan Upgraded">Plan Upgraded</option>
                                                    <option value="Plan Interested">Plan Interested</option>
                                                    <option value="CNP">CNP (Call Not Picked)</option>
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
                                        </div>
                                        <!-- New Seller Preview -->
                                        <div id="newSellerPreview" class="row mb-3" style="display: none;">
                                            <div class="col-12">
                                                <div class="card bg-info bg-opacity-10 border-info">
                                                    <div class="card-body py-2">
                                                        <h6 class="card-subtitle mb-2 text-info">
                                                            <i class="bi bi-eye me-1"></i>
                                                            New Seller Preview
                                                        </h6>
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <small class="text-muted d-block">Seller ID:</small>
                                                                <strong id="previewNewSellerId">-</strong>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <small class="text-muted d-block">Business Name:</small>
                                                                <strong id="previewNewBusinessName">-</strong>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <small class="text-muted d-block">Phone Number:</small>
                                                                <strong id="previewNewPhoneNumber">-</strong>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <small class="text-muted d-block">Response:</small>
                                                                <strong id="previewNewResponse">-</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Select Users to Share With (Multi-select) -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-people-fill text-primary me-1"></i>
                                                Share With Users <span class="text-danger">*</span>
                                            </label>

                                            <!-- Send to All Option -->
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="shareToAll">
                                                <label class="form-check-label" for="shareToAll">
                                                    <i class="bi bi-send-check-fill text-success me-1"></i>
                                                    <strong>Send to All Users</strong>
                                                    <small class="text-muted d-block">Share this seller with all registered users</small>
                                                </label>
                                            </div>

                                            <!-- Individual User Selection -->
                                            <div id="userSelectionSection">
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-info-circle me-1"></i>
                                                        Hold Ctrl (Cmd on Mac) to select multiple users
                                                    </small>
                                                </div>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="bi bi-person-plus"></i>
                                                    </span>
                                                    <select class="form-select border-start-0" id="sharedWithUser" name="shared_with_users[]" multiple size="5">
                                                        <option value="" disabled>Select users to share with</option>
                                                        <?php foreach ($users as $shareUser): ?>
                                                            <option value="<?= htmlspecialchars($shareUser['user_uid']) ?>">
                                                                <?= htmlspecialchars($shareUser['name']) ?>
                                                                (<?= htmlspecialchars($shareUser['phone']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Quick Select Buttons for Users -->
                                            <div class="mt-2 d-flex gap-2 flex-wrap" id="userQuickButtons">
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllUsers">
                                                    <i class="bi bi-check-all"></i> Select All Users
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllUsers">
                                                    <i class="bi bi-x"></i> Deselect All
                                                </button>
                                            </div>

                                            <!-- Note when "Send to All" is selected -->
                                            <div class="alert alert-info mt-2 mb-0 py-2 select-all-note" style="display: none;">
                                                <i class="bi bi-send-check me-2"></i>
                                                You have selected "Send to All". This will share with all registered users.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Notes -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-journal-text text-primary me-1"></i>
                                                Share Notes / Message
                                            </label>
                                            <textarea class="form-control" id="shareNotes" name="notes" rows="3"
                                                placeholder="Add any notes or message for the recipient..."></textarea>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 gap-sm-3 pt-3 border-top">
                                        <button type="reset" class="btn btn-outline-secondary px-5 py-2">
                                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                                            Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary px-5 py-2">
                                            <i class="bi bi-send me-2"></i>
                                            Share Seller(s)
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Shares Card -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-clock-history text-primary me-2"></i>
                                    Recent Shares
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Seller ID</th>
                                                <th>Seller</th>
                                                <th>Shared With</th>
                                                <th>Response</th>
                                                <th>Status</th>
                                                <th>Shared At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recentSharesTable">
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                                    Loading recent shares...
                                                </td>
                                            </tr>
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

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <!-- Share Details Modal -->
    <div class="modal fade" id="shareDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Share Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="shareDetailsContent">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .form-label {
            font-size: 0.9rem;
            margin-bottom: 0.35rem;
        }

        .input-group-text {
            background-color: #f8f9fa;
        }

        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-accepted {
            background-color: #198754;
            color: #fff;
        }

        .badge-rejected {
            background-color: #dc3545;
            color: #fff;
        }

        /* Multi-select styling */
        #sellerIds[multiple],
        #sharedWithUser[multiple] {
            min-height: 150px;
            padding: 8px;
        }

        #sellerIds[multiple] option,
        #sharedWithUser[multiple] option {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }

        #sellerIds[multiple] option:checked,
        #sharedWithUser[multiple] option:checked {
            background: #0d6efd linear-gradient(0deg, #0d6efd 0%, #0d6efd 100%);
            color: white;
        }

        /* Preview table styling */
        #previewTableBody tr td {
            padding: 4px 8px;
            font-size: 0.9rem;
        }

        /* Send to all note animation */
        .select-all-note {
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Quick select buttons */
        .btn-sm {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }

        /* New seller preview animation */
        #newSellerPreview {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/share-sellers/share-sellers.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>