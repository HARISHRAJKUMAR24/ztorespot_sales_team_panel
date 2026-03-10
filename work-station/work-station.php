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
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_uid = ?");
$stmt->execute([$user_uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
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
                        <i class="bi bi-person-workspace text-primary me-2"></i>
                        Add New Seller - Workstation
                    </h1>
                    <div class="d-flex gap-2">
                        <a href="workstation_sellers_list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to List
                        </a>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-person-plus-fill text-primary me-2"></i>
                                    Seller Information
                                </h5>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <form id="sellerForm">
                                    <!-- Row 1: Business Name -->
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-shop text-primary me-1"></i>
                                                Name / Store Name / Business Name <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-building"></i>
                                                </span>
                                                <input type="text" class="form-control border-start-0"
                                                    placeholder="Enter seller name, store name or business name"
                                                    id="businessName" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Seller Type and Phone Number -->
                                    <div class="row mb-3">
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
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
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
                                    </div>

                                    <!-- Row 3: Customer Response -->
                                    <div class="row mb-3">
                                        <div class="col-12">
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
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dynamic Fields Container -->
                                    <div id="dynamicFieldsContainer" class="mb-3"></div>

                                    <!-- Row 4: Customer Queries -->
                                    <div class="row mb-3">
                                        <div class="col-12">
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
                                    </div>

                                    <!-- Row 5: Customer Status and Call Duration -->
                                    <div class="row mb-4">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-flag text-primary me-1"></i>
                                                Customer Status
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-check2-circle"></i>
                                                </span>
                                                <select class="form-select border-start-0" id="customerStatus">
                                                    <option value="" selected disabled>Select current status</option>
                                                    <option value="Not yet">Not yet</option>
                                                    <option value="Upgraded">Upgraded</option>
                                                    <option value="In Active">In Active</option>
                                                    <option value="To be deleted">To be deleted</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-clock-history text-primary me-1"></i>
                                                Call Duration
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-stopwatch"></i>
                                                </span>
                                                <input type="text" class="form-control border-start-0"
                                                    placeholder="e.g., 15 mins, 30 mins, 1 hour"
                                                    id="callDuration">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 6: Additional Notes -->
                                    <div class="row mb-4">
                                        <div class="col-12">
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
                                                    id="additionalNotes" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 gap-sm-3 pt-3 border-top">
                                        <button type="reset" class="btn btn-outline-secondary px-5 py-2">
                                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                                            Reset
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

    <style>
        .form-label {
            font-size: 0.9rem;
            margin-bottom: 0.35rem;
        }

        .input-group-text {
            background-color: #f8f9fa;
        }

        .input-group .form-control:focus,
        .input-group .form-select:focus {
            border-color: #86b7fe;
            box-shadow: none;
        }

        .dynamic-field {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
        }

        @media (min-width: 992px) {
            .container-fluid {
                max-width: 100%;
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }

            .card-body {
                padding: 2rem !important;
            }

            .form-label {
                font-size: 1rem;
            }

            .btn {
                padding: 0.6rem 2rem !important;
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 1rem;
            }

            .btn {
                padding: 0.5rem 1rem;
            }
        }

        .card {
            transition: all 0.2s ease-in-out;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        }

        .dynamic-field label {
            font-weight: 600;
            color: #0d6efd;
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/workstation_add_seller.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>