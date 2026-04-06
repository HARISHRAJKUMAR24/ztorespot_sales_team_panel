<?php
require_once '../lib/functions.php';
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// Get current user data using user_uid from session
$user_uid = $_SESSION['user_uid'];
$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_uid = ?");
$stmt->execute([$user_uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get subscription plans for dropdown
$plans_stmt = $pdo->prepare("SELECT id, plan_name, duration, total_amount FROM subscription_plans WHERE status = 1 ORDER BY plan_name, total_amount");
$plans_stmt->execute();
$subscription_plans = $plans_stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <div>
                        <h1 class="h2 mb-2 mb-sm-0">
                            <i class="bi bi-person-workspace text-primary me-2"></i>
                            Add New Seller
                        </h1>
                        <p class="text-muted mb-0">Enter seller details to add to your workstation</p>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-person-plus-fill text-primary me-2"></i>
                                    Seller Information Form
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <form id="sellerForm">
                                    <!-- 3x3 Grid Layout - Row 1 -->
                                    <div class="row mb-4">
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-shop text-primary me-1"></i>
                                                Business Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter business name"
                                                id="businessName" required>
                                        </div>
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-tag text-primary me-1"></i>
                                                Seller Type
                                            </label>
                                            <select class="form-select" id="sellerType">
                                                <option value="" selected disabled>Select seller type</option>
                                                <option value="Register Seller">Register Seller</option>
                                                <option value="Follow up Sellers">Follow up Sellers</option>
                                                <option value="Aisensy">Aisensy</option>
                                                <option value="Organic Seller">Organic Seller</option>
                                                <option value="Direct Seller">Direct Seller</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-upc-scan text-primary me-1"></i>
                                                Seller ID
                                                <span class="badge bg-light text-muted ms-1">Optional</span>
                                            </label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter seller ID (optional)"
                                                id="sellerID">
                                        </div>
                                    </div>

                                    <!-- 3x3 Grid Layout - Row 2 -->
                                    <div class="row mb-4">
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-telephone text-primary me-1"></i>
                                                Phone Number <span class="text-danger">*</span>
                                            </label>
                                            <input type="tel" class="form-control"
                                                placeholder="10 digit mobile number"
                                                id="phoneNumber" maxlength="10" required>
                                        </div>
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-chat-dots text-primary me-1"></i>
                                                Customer Response <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="customerResponse" required>
                                                <option value="" selected disabled>Select response type</option>
                                                <option value="Plan Upgraded">Plan Upgraded</option>
                                                <option value="Plan Interested">Plan Interested</option>
                                                <option value="CNP">CNP (Call Not Picked)</option>
                                                <option value="Later">Later</option>
                                                <option value="Not interested">Not interested</option>
                                                <option value="Switch Off">Switch Off</option>
                                                <option value="No Business">No Business</option>
                                                <option value="Whatsapp Details sent">Whatsapp Details sent</option>
                                                <option value="Call Back AT">Call Back AT</option>
                                                <option value="Out of Service">Out of Service</option>
                                                <option value="Testing">Testing</option>
                                                <option value="Renewals">Renewals</option>
                                                <option value="Schedule">Schedule (Select Date)</option>
                                                <option value="Refund">Refund</option>
                                                <option value="other">Custom Response</option>
                                            </select>
                                            <!-- Custom Response Text Field (hidden by default) -->
                                            <div id="customResponseContainer" style="display: none;" class="mt-2">
                                                <input type="text" class="form-control" id="customResponse"
                                                    placeholder="Enter your custom response...">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-clock-history text-primary me-1"></i>
                                                Call Duration
                                            </label>
                                            <select class="form-select" id="callDurationSelect">
                                                <option value="" selected disabled>Select duration</option>
                                                <option value="5 mins">5 mins</option>
                                                <option value="10 mins">10 mins</option>
                                                <option value="15 mins">15 mins</option>
                                                <option value="20 mins">20 mins</option>
                                                <option value="25 mins">25 mins</option>
                                                <option value="30 mins">30 mins</option>
                                                <option value="45 mins">45 mins</option>
                                                <option value="1 hour">1 hour</option>
                                                <option value="1.5 hours">1.5 hours</option>
                                                <option value="2 hours">2 hours</option>
                                                <option value="other">Other (Custom)</option>
                                            </select>
                                            <div id="customCallDurationContainer" style="display: none;" class="mt-2">
                                                <input type="text" class="form-control" id="customCallDuration"
                                                    placeholder="Enter custom call duration">
                                            </div>
                                            <input type="hidden" id="callDuration" name="call_duration">
                                        </div>
                                    </div>

                                    <!-- Dynamic Fields Container (Plan Details, Call Back, Schedule, Refund) - BETWEEN ROW 2 AND ROW 3 -->
                                    <div id="dynamicFieldsContainer" class="mb-4"></div>

                                    <!-- 3x3 Grid Layout - Row 3 -->
                                    <div class="row mb-4">
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-journal-bookmark-fill text-primary me-1"></i>
                                                Remembering Notes
                                            </label>
                                            <textarea class="form-control" rows="3"
                                                placeholder="Enter remembering notes..."
                                                id="rememberingNotes"></textarea>
                                        </div>
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-question-circle text-primary me-1"></i>
                                                Customer Queries
                                            </label>
                                            <textarea class="form-control" rows="3"
                                                placeholder="Enter customer questions or queries..."
                                                id="customerQueries"></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-journal-text text-primary me-1"></i>
                                                Additional Notes
                                            </label>
                                            <textarea class="form-control" rows="3"
                                                placeholder="Any additional notes or remarks..."
                                                id="additionalNotes"></textarea>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 pt-3 border-top">
                                        <button type="reset" class="btn btn-outline-secondary px-5 py-2">
                                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                                            Reset Form
                                        </button>
                                        <button type="submit" class="btn btn-primary px-5 py-2">
                                            <i class="bi bi-save me-2"></i>
                                            Save Seller
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Toast Container for Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <!-- WhatsApp Modal -->
    <div class="modal fade" id="whatsappModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-whatsapp me-2"></i>
                        WhatsApp Message Preview
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Select Template Type</label>
                            <select class="form-select" id="templateType">
                                <option value="register">Register Seller Template</option>
                                <option value="aisensy">Aisensy / WP Chat Seller Ads</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Recipient Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">+91</span>
                                <input type="text" class="form-control" id="whatsappPhoneNumber" readonly>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Message Preview</label>
                            <div class="border rounded p-3 bg-light" id="messagePreview" style="min-height: 300px; white-space: pre-wrap; font-family: monospace; font-size: 13px;">
                                Loading...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="sendWhatsappBtn">
                        <i class="bi bi-whatsapp me-2"></i>Open WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Datepicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">

    <style>
        /* Only minimal custom styles - keep Bootstrap mostly */
        .dynamic-field {
            background-color: #fefce8;
            border-left: 4px solid #f59e0b;
            padding: 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .custom-field {
            margin-top: 0.75rem;
            padding: 0.875rem;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Datepicker JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/workstation_add_seller.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>

    <!-- Pass subscription plans and user data to JavaScript -->
    <script>
        // Make sure subscriptionPlans is defined globally
        window.subscriptionPlans = <?= json_encode($subscription_plans) ?>;
        window.currentUser = {
            name: '<?= addslashes($user['name'] ?? 'Barani tharan') ?>',
            phone: '<?= $user['phone'] ?? '9952852208' ?>'
        };
        console.log('Subscription Plans loaded:', window.subscriptionPlans);
        console.log('Current User:', window.currentUser);
    </script>
</body>

</html>