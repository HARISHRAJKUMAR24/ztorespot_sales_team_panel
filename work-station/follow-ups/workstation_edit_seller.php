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
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_uid = ?");
$stmt->execute([$user_uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get seller ID from URL
$seller_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$seller = null;

if ($seller_id) {
    $stmt = $pdo->prepare("SELECT * FROM sellers_workstation WHERE id = ? AND user_uid = ?");
    $stmt->execute([$seller_id, $user_uid]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Encode seller data for JavaScript
$seller_json = $seller ? json_encode($seller) : 'null';
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
                        <i class="bi bi-pencil-square text-primary me-2"></i>
                        Edit Seller - Workstation
                    </h1>
                    <div class="d-flex gap-2">
                        <a href="workstation_followup.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Follow Up
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
                                    Edit Seller Information
                                </h5>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <?php if ($seller_id && !$seller): ?>
                                    <div class="alert alert-danger">Seller not found!</div>
                                <?php else: ?>
                                <form id="sellerForm" data-seller-id="<?= $seller_id ?>">
                                    <input type="hidden" id="sellerId" value="<?= $seller_id ?>">
                                    <!-- Add hidden field for seller data -->
                                    <input type="hidden" id="sellerData" value='<?= htmlspecialchars($seller_json, ENT_QUOTES, 'UTF-8') ?>'>
                                    
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
                                                    id="businessName" value="<?= htmlspecialchars($seller['business_name'] ?? '') ?>" required>
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
                                                    <option value="Register Seller" <?= ($seller['seller_type'] ?? '') == 'Register Seller' ? 'selected' : '' ?>>Register Seller</option>
                                                    <option value="Follow up Sellers" <?= ($seller['seller_type'] ?? '') == 'Follow up Sellers' ? 'selected' : '' ?>>Follow up Sellers</option>
                                                    <option value="Aisensy" <?= ($seller['seller_type'] ?? '') == 'Aisensy' ? 'selected' : '' ?>>Aisensy</option>
                                                    <option value="Organic Seller" <?= ($seller['seller_type'] ?? '') == 'Organic Seller' ? 'selected' : '' ?>>Organic Seller</option>
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
                                                    id="phoneNumber" maxlength="10" value="<?= htmlspecialchars($seller['phone_number'] ?? '') ?>" required>
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
                                                    <option value="Plan Upgraded" <?= ($seller['customer_response'] ?? '') == 'Plan Upgraded' ? 'selected' : '' ?>>Plan Upgraded</option>
                                                    <option value="Plan Interested" <?= ($seller['customer_response'] ?? '') == 'Plan Interested' ? 'selected' : '' ?>>Plan Interested</option>
                                                    <option value="CNP" <?= ($seller['customer_response'] ?? '') == 'CNP' ? 'selected' : '' ?>>CNP (Call Not Picked)</option>
                                                    <option value="Later" <?= ($seller['customer_response'] ?? '') == 'Later' ? 'selected' : '' ?>>Later</option>
                                                    <option value="Not interested" <?= ($seller['customer_response'] ?? '') == 'Not interested' ? 'selected' : '' ?>>Not interested</option>
                                                    <option value="Switch Off" <?= ($seller['customer_response'] ?? '') == 'Switch Off' ? 'selected' : '' ?>>Switch Off</option>
                                                    <option value="No Business" <?= ($seller['customer_response'] ?? '') == 'No Business' ? 'selected' : '' ?>>No Business</option>
                                                    <option value="Whatsapp Details sent" <?= ($seller['customer_response'] ?? '') == 'Whatsapp Details sent' ? 'selected' : '' ?>>Whatsapp Details sent</option>
                                                    <option value="Call Back AT" <?= ($seller['customer_response'] ?? '') == 'Call Back AT' ? 'selected' : '' ?>>Call Back AT</option>
                                                    <option value="Out of Service" <?= ($seller['customer_response'] ?? '') == 'Out of Service' ? 'selected' : '' ?>>Out of Service</option>
                                                    <option value="Testing" <?= ($seller['customer_response'] ?? '') == 'Testing' ? 'selected' : '' ?>>Testing</option>
                                                    <option value="Renewals" <?= ($seller['customer_response'] ?? '') == 'Renewals' ? 'selected' : '' ?>>Renewals</option>
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
                                                    id="customerQueries" rows="3"><?= htmlspecialchars($seller['customer_queries'] ?? '') ?></textarea>
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
                                                    <option value="Not yet" <?= ($seller['customer_status'] ?? '') == 'Not yet' ? 'selected' : '' ?>>Not yet</option>
                                                    <option value="Upgraded" <?= ($seller['customer_status'] ?? '') == 'Upgraded' ? 'selected' : '' ?>>Upgraded</option>
                                                </select>
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
                                                        <option value="5 mins" <?= ($seller['call_duration'] ?? '') == '5 mins' ? 'selected' : '' ?>>5 mins</option>
                                                        <option value="10 mins" <?= ($seller['call_duration'] ?? '') == '10 mins' ? 'selected' : '' ?>>10 mins</option>
                                                        <option value="15 mins" <?= ($seller['call_duration'] ?? '') == '15 mins' ? 'selected' : '' ?>>15 mins</option>
                                                        <option value="20 mins" <?= ($seller['call_duration'] ?? '') == '20 mins' ? 'selected' : '' ?>>20 mins</option>
                                                        <option value="25 mins" <?= ($seller['call_duration'] ?? '') == '25 mins' ? 'selected' : '' ?>>25 mins</option>
                                                        <option value="30 mins" <?= ($seller['call_duration'] ?? '') == '30 mins' ? 'selected' : '' ?>>30 mins</option>
                                                        <option value="45 mins" <?= ($seller['call_duration'] ?? '') == '45 mins' ? 'selected' : '' ?>>45 mins</option>
                                                        <option value="1 hour" <?= ($seller['call_duration'] ?? '') == '1 hour' ? 'selected' : '' ?>>1 hour</option>
                                                        <option value="1.5 hours" <?= ($seller['call_duration'] ?? '') == '1.5 hours' ? 'selected' : '' ?>>1.5 hours</option>
                                                        <option value="2 hours" <?= ($seller['call_duration'] ?? '') == '2 hours' ? 'selected' : '' ?>>2 hours</option>
                                                        <option value="other">Other (Custom)</option>
                                                    </select>
                                                </div>
                                                <div id="customCallDurationContainer" style="display: none;" class="mt-2">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light">
                                                            <i class="bi bi-pencil"></i>
                                                        </span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Enter custom call duration"
                                                            id="customCallDuration" value="<?= !in_array($seller['call_duration'] ?? '', ['5 mins','10 mins','15 mins','20 mins','25 mins','30 mins','45 mins','1 hour','1.5 hours','2 hours']) ? htmlspecialchars($seller['call_duration'] ?? '') : '' ?>">
                                                    </div>
                                                </div>
                                                <input type="hidden" id="callDuration" name="call_duration" value="<?= htmlspecialchars($seller['call_duration'] ?? '') ?>">
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
                                                    id="additionalNotes" rows="2"><?= htmlspecialchars($seller['additional_notes'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 gap-sm-3 pt-3 border-top">
                                        <a href="workstation_followup.php" class="btn btn-outline-secondary px-5 py-2">
                                            <i class="bi bi-x-circle me-2"></i>
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary px-5 py-2">
                                            <i class="bi bi-save me-2"></i>
                                            Update Seller
                                        </button>
                                    </div>
                                </form>
                                <?php endif; ?>
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
        .dynamic-field {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
        }
        .custom-field {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
        }
        @media (min-width: 992px) {
            .card-body {
                padding: 2rem !important;
            }
        }
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/workstation_edit_seller.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>