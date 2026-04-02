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
                                    <!-- Row 1: Business Name (Full Width) -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-shop text-primary me-1"></i>
                                                Business / Store Name <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-building"></i>
                                                </span>
                                                <input type="text" class="form-control border-start-0"
                                                    placeholder="Enter business name, store name or company name"
                                                    id="businessName" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Seller Type and Seller ID (2x2 Grid) -->
                                    <div class="row mb-4">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-tag text-primary me-1"></i>
                                                Seller Type
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-grid"></i>
                                                </span>
                                                <select class="form-select border-start-0" id="sellerType">
                                                    <option value="" selected disabled>Select seller type</option>
                                                    <option value="Register Seller">Register Seller</option>
                                                    <option value="Follow up Sellers">Follow up Sellers</option>
                                                    <option value="Aisensy">Aisensy</option>
                                                    <option value="Organic Seller">Organic Seller</option>
                                                    <option value="Direct Seller">Direct Seller</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-upc-scan text-primary me-1"></i>
                                                Seller ID
                                                <span class="badge bg-light text-muted ms-1">Optional</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-upc-scan"></i>
                                                </span>
                                                <input type="text" class="form-control border-start-0"
                                                    placeholder="Enter seller ID (optional)"
                                                    id="sellerID">
                                            </div>
                                            <div class="form-text text-muted small">
                                                <i class="bi bi-info-circle me-1"></i>
                                                You can enter any seller ID or leave it empty
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 3: Phone Number and Customer Response (2x2 Grid) -->
                                    <div class="row mb-4">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-telephone text-primary me-1"></i>
                                                Phone Number <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-phone"></i>
                                                </span>
                                                <input type="tel" class="form-control border-start-0"
                                                    placeholder="10 digit mobile number"
                                                    id="phoneNumber" maxlength="10" required>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-chat-dots text-primary me-1"></i>
                                                Customer Response <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-megaphone"></i>
                                                </span>
                                                <select class="form-select border-start-0" id="customerResponse" required>
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
                                            </div>
                                            <!-- Custom Response Text Field (hidden by default) -->
                                            <div id="customResponseContainer" style="display: none;" class="mt-2">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="bi bi-pencil"></i>
                                                    </span>
                                                    <input type="text" class="form-control" id="customResponse"
                                                        placeholder="Enter your custom response...">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dynamic Fields Container (Plan Details, Call Back, etc.) -->
                                    <div id="dynamicFieldsContainer" class="mb-4"></div>

                                    <!-- Row 4: Customer Queries and Call Duration (2x2 Grid) -->
                                    <div class="row mb-4">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-journal-bookmark-fill text-primary me-1"></i>
                                                Remembering Notes
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-bookmark"></i>
                                                </span>
                                                <input type="text" class="form-control border-start-0"
                                                    placeholder="Enter remembering notes..."
                                                    id="rememberingNotes">
                                            </div>
                                            <div class="form-text text-muted small">
                                                <i class="bi bi-info-circle me-1"></i>
                                                These notes will be stored in JSON format
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-clock-history text-primary me-1"></i>
                                                Call Duration
                                            </label>
                                            <div class="call-duration-wrapper">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="bi bi-stopwatch"></i>
                                                    </span>
                                                    <select class="form-select border-start-0" id="callDurationSelect">
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
                                                </div>
                                                <div id="customCallDurationContainer" style="display: none;" class="mt-2">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light">
                                                            <i class="bi bi-pencil"></i>
                                                        </span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Enter custom call duration (e.g., 40 mins, 2.5 hours)"
                                                            id="customCallDuration">
                                                    </div>
                                                </div>
                                                <input type="hidden" id="callDuration" name="call_duration">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 5: Remembering Notes and Additional Notes (2x2 Grid) -->
                                    <div class="row mb-4">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-question-circle text-primary me-1"></i>
                                                Customer Queries
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0" style="align-items: flex-start; padding-top: 0.75rem;">
                                                    <i class="bi bi-pencil-square"></i>
                                                </span>
                                                <textarea class="form-control border-start-0"
                                                    placeholder="Enter customer questions or queries..."
                                                    id="customerQueries" rows="3"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-journal-text text-primary me-1"></i>
                                                Additional Notes
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0" style="align-items: flex-start; padding-top: 0.75rem;">
                                                    <i class="bi bi-pencil"></i>
                                                </span>
                                                <textarea class="form-control border-start-0"
                                                    placeholder="Any additional notes or remarks..."
                                                    id="additionalNotes" rows="3"></textarea>
                                            </div>
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

    <!-- Bootstrap Datepicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">

    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
        }

        body {
            background: #f3f4f6;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .form-label {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        .input-group-text {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            color: #6b7280;
        }

        .form-control,
        .form-select {
            border: 1px solid #e5e7eb;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .card {
            border-radius: 1rem;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.05) !important;
        }

        .card-header {
            border-bottom: 1px solid #eef2ff;
            background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #7c3aed);
            border: none;
            padding: 0.625rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            background: linear-gradient(135deg, var(--primary-hover), #6d28d9);
        }

        .btn-outline-secondary {
            border: 1px solid #e5e7eb;
            color: #4b5563;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-outline-secondary:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }

        .dynamic-field {
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
            border-left: 4px solid var(--warning-color);
            padding: 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            animation: fadeIn 0.3s ease;
        }

        .custom-field {
            margin-top: 0.75rem;
            padding: 0.875rem;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
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

        .badge-optional {
            background-color: #f3f4f6;
            color: #6b7280;
            font-weight: 500;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
        }

        .text-muted {
            color: #6b7280 !important;
        }

        .form-text {
            font-size: 0.7rem;
            margin-top: 0.375rem;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1.25rem !important;
            }

            .btn {
                padding: 0.5rem 1rem;
            }
        }

        /* Plan amount display */
        .plan-amount-preview {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--primary-color);
        }

        /* Callback options */
        .callback-wrapper,
        .callback-at-wrapper,
        .schedule-wrapper {
            width: 100%;
        }

        /* Toast styling */
        .toast {
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Datepicker JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/workstation_add_seller.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>

    <!-- Pass subscription plans to JavaScript -->
    <script>
        const subscriptionPlans = <?= json_encode($subscription_plans) ?>;
        console.log('Subscription Plans loaded:', subscriptionPlans);
    </script>
</body>

</html>