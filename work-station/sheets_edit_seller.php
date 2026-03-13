<?php
// Set Indian timezone
date_default_timezone_set('Asia/Kolkata');

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

// Get seller ID from URL
$seller_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$seller = null;

if ($seller_id) {
    $stmt = $pdo->prepare("SELECT * FROM sales_person_sellers WHERE id = ? AND user_uid = ?");
    $stmt->execute([$seller_id, $user_uid]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);
}

// If no seller found, redirect
if (!$seller) {
    header("Location: sheets_followup_list.php?error=notfound");
    exit;
}

// Decode update history
$update_history = [];
if (!empty($seller['update_history'])) {
    $update_history = json_decode($seller['update_history'], true);
    if (!is_array($update_history)) {
        $update_history = [];
    }
}

// Map database fields to form fields
$form_data = [
    'business_name' => $seller['work_details_update'] ?? '',
    'seller_type' => $seller['source_type'] ?? '',
    'registration_status' => $seller['registration_status'] ?? '',
    'phone_number' => $seller['phone_number'] ?? '',
    'plans_interested' => $seller['plans_interested'] ?? '',
    'plan_duration' => $seller['plan_duration'] ?? '',
    'products_uploaded' => $seller['products_uploaded'] ?? 0,
    'customer_response' => $seller['customer_response'] ?? '',
    'remembering_notes' => $seller['remembering_notes'] ?? '',
    'latest_update' => $seller['latest_update'] ?? '',
    'current_status' => $seller['current_status'] ?? '',
    'customer_queries' => $seller['customer_queries'] ?? '',
    'video_canva' => $seller['video_canva'] ?? '',
    'call_timing' => $seller['call_timing'] ?? '',
    'remarks' => $seller['remarks'] ?? '',
    'entry_date' => $seller['entry_date'] ?? ''
];

// Encode seller data for JavaScript
$seller_json = json_encode($seller);

// Get current Indian time for display
$current_indian_time = date('d M Y, h:i A');
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
                            <i class="bi bi-pencil-square text-success me-2"></i>
                            Edit Seller - Follow Up
                        </h1>
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>
                            Indian Time: <?= $current_indian_time ?>
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="sheets_followup_list.php" class="btn btn-outline-secondary">
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
                                    <i class="bi bi-person-plus-fill text-success me-2"></i>
                                    Edit Seller Information
                                </h5>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <form id="sellerForm" data-seller-id="<?= $seller_id ?>">
                                    <input type="hidden" id="sellerId" value="<?= $seller_id ?>">
                                    <input type="hidden" id="sellerData" value='<?= htmlspecialchars($seller_json, ENT_QUOTES, 'UTF-8') ?>'>

                                    <!-- Row 1: Business Name -->
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-shop text-success me-1"></i>
                                                Name / Store Name / Business Name <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-building"></i>
                                                </span>
                                                <input type="text" class="form-control border-start-0"
                                                    placeholder="Enter seller name, store name or business name"
                                                    id="businessName" value="<?= htmlspecialchars($form_data['business_name']) ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Seller Type and Phone Number -->
                                    <div class="row mb-3">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-tag text-success me-1"></i>
                                                Seller Type
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-grid"></i>
                                                </span>
                                                <select class="form-select border-start-0" id="sellerType">
                                                    <option value="" selected disabled>Select seller type</option>
                                                    <option value="Register Seller" <?= $form_data['seller_type'] == 'Register Seller' ? 'selected' : '' ?>>Register Seller</option>
                                                    <option value="Follow up Sellers" <?= $form_data['seller_type'] == 'Follow up Sellers' ? 'selected' : '' ?>>Follow up Sellers</option>
                                                    <option value="Aisensy" <?= $form_data['seller_type'] == 'Aisensy' ? 'selected' : '' ?>>Aisensy</option>
                                                    <option value="Organic" <?= $form_data['seller_type'] == 'Organic' ? 'selected' : '' ?>>Organic</option>
                                                    <option value="Direct" <?= $form_data['seller_type'] == 'Direct' ? 'selected' : '' ?>>Direct</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-telephone text-success me-1"></i>
                                                Phone Number <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-phone"></i>
                                                </span>
                                                <input type="tel" class="form-control border-start-0"
                                                    placeholder="10 digit mobile number"
                                                    id="phoneNumber" maxlength="10" value="<?= htmlspecialchars($form_data['phone_number']) ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 3: Customer Response -->
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-chat-dots text-success me-1"></i>
                                                Customer Response <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-megaphone"></i>
                                                </span>
                                                <select class="form-select border-start-0" id="customerResponse" required>
                                                    <option value="" selected disabled>Select response type</option>
                                                    <option value="Plan Upgraded" <?= $form_data['customer_response'] == 'Plan Upgraded' ? 'selected' : '' ?>>Plan Upgraded</option>
                                                    <option value="Plan Interested" <?= $form_data['customer_response'] == 'Plan Interested' ? 'selected' : '' ?>>Plan Interested</option>
                                                    <option value="CNP" <?= $form_data['customer_response'] == 'CNP' ? 'selected' : '' ?>>CNP (Call Not Picked)</option>
                                                    <option value="Later" <?= $form_data['customer_response'] == 'Later' ? 'selected' : '' ?>>Later</option>
                                                    <option value="Not interested" <?= $form_data['customer_response'] == 'Not interested' ? 'selected' : '' ?>>Not interested</option>
                                                    <option value="Switch Off" <?= $form_data['customer_response'] == 'Switch Off' ? 'selected' : '' ?>>Switch Off</option>
                                                    <option value="No Business" <?= $form_data['customer_response'] == 'No Business' ? 'selected' : '' ?>>No Business</option>
                                                    <option value="Whatsapp Details sent" <?= $form_data['customer_response'] == 'Whatsapp Details sent' ? 'selected' : '' ?>>Whatsapp Details sent</option>
                                                    <option value="Call Back AT" <?= $form_data['customer_response'] == 'Call Back AT' ? 'selected' : '' ?>>Call Back AT</option>
                                                    <option value="Out of Service" <?= $form_data['customer_response'] == 'Out of Service' ? 'selected' : '' ?>>Out of Service</option>
                                                    <option value="Testing" <?= $form_data['customer_response'] == 'Testing' ? 'selected' : '' ?>>Testing</option>
                                                    <option value="Renewals" <?= $form_data['customer_response'] == 'Renewals' ? 'selected' : '' ?>>Renewals</option>
                                                    <option value="Schedule" <?= ($form_data['customer_response'] == 'Schedule' || $form_data['customer_response'] == 'Shedule') ? 'selected' : '' ?>>Schedule (Select Date)</option>
                                                    <option value="Refund" <?= $form_data['customer_response'] == 'Refund' ? 'selected' : '' ?>>Refund</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dynamic Fields Container -->
                                    <div id="dynamicFieldsContainer" class="mb-3"></div>

                                    <!-- Row 4: Remembering Notes and Latest Update -->
                                    <div class="row mb-3">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-journal-bookmark-fill text-success me-1"></i>
                                                Remembering Notes
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0" style="align-items: flex-start; padding-top: 0.75rem;">
                                                    <i class="bi bi-bookmark"></i>
                                                </span>
                                                <textarea class="form-control border-start-0"
                                                    placeholder="Enter remembering notes..."
                                                    id="rememberingNotes" rows="2"><?= htmlspecialchars($form_data['remembering_notes']) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-arrow-up-circle text-success me-1"></i>
                                                Latest Update
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0" style="align-items: flex-start; padding-top: 0.75rem;">
                                                    <i class="bi bi-clock-history"></i>
                                                </span>
                                                <textarea class="form-control border-start-0"
                                                    placeholder="Enter latest update..."
                                                    id="latestUpdate" rows="2"><?= htmlspecialchars($form_data['latest_update']) ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 5: Customer Queries -->
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-question-circle text-success me-1"></i>
                                                Customer Queries
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0" style="align-items: flex-start; padding-top: 0.75rem;">
                                                    <i class="bi bi-pencil-square"></i>
                                                </span>
                                                <textarea class="form-control border-start-0"
                                                    placeholder="Enter customer questions or queries..."
                                                    id="customerQueries" rows="2"><?= htmlspecialchars($form_data['customer_queries']) ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 6: Current Status and Call Timing -->
                                    <div class="row mb-3">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-flag text-success me-1"></i>
                                                Current Status
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-check2-circle"></i>
                                                </span>
                                                <select class="form-select border-start-0" id="currentStatus">
                                                    <option value="" selected disabled>Select current status</option>
                                                    <option value="Not yet" <?= $form_data['current_status'] == 'Not yet' ? 'selected' : '' ?>>Not yet</option>
                                                    <option value="Upgraded" <?= $form_data['current_status'] == 'Upgraded' ? 'selected' : '' ?>>Upgraded</option>
                                                    <option value="In Progress" <?= $form_data['current_status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                    <option value="Deleted" <?= $form_data['current_status'] == 'Deleted' ? 'selected' : '' ?>>Deleted</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-clock text-success me-1"></i>
                                                Call Timing
                                            </label>
                                            <div class="call-timing-wrapper">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="bi bi-telephone"></i>
                                                    </span>
                                                    <select class="form-select border-start-0" id="callTimingSelect">
                                                        <option value="" selected disabled>Select call timing</option>
                                                        <option value="Morning 9-11 AM" <?= $form_data['call_timing'] == 'Morning 9-11 AM' ? 'selected' : '' ?>>Morning 9-11 AM</option>
                                                        <option value="Late Morning 11-1 PM" <?= $form_data['call_timing'] == 'Late Morning 11-1 PM' ? 'selected' : '' ?>>Late Morning 11-1 PM</option>
                                                        <option value="Afternoon 2-4 PM" <?= $form_data['call_timing'] == 'Afternoon 2-4 PM' ? 'selected' : '' ?>>Afternoon 2-4 PM</option>
                                                        <option value="Evening 4-6 PM" <?= $form_data['call_timing'] == 'Evening 4-6 PM' ? 'selected' : '' ?>>Evening 4-6 PM</option>
                                                        <option value="Night 7-9 PM" <?= $form_data['call_timing'] == 'Night 7-9 PM' ? 'selected' : '' ?>>Night 7-9 PM</option>
                                                        <option value="other">Other (Custom)</option>
                                                    </select>
                                                </div>
                                                <div id="customCallTimingContainer" style="display: none;" class="mt-2">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light">
                                                            <i class="bi bi-pencil"></i>
                                                        </span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Enter custom call timing"
                                                            id="customCallTiming" value="<?= !in_array($form_data['call_timing'], ['Morning 9-11 AM', 'Late Morning 11-1 PM', 'Afternoon 2-4 PM', 'Evening 4-6 PM', 'Night 7-9 PM']) ? htmlspecialchars($form_data['call_timing']) : '' ?>">
                                                    </div>
                                                </div>
                                                <input type="hidden" id="callTiming" name="call_timing" value="<?= htmlspecialchars($form_data['call_timing']) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 7: Entry Date -->
                                    <div class="row mb-4">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-calendar-date text-success me-1"></i>
                                                Entry Date
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-calendar3"></i>
                                                </span>
                                                <input type="date" class="form-control border-start-0"
                                                    id="entryDate" value="<?= htmlspecialchars($form_data['entry_date']) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 gap-sm-3 pt-3 border-top">
                                        <a href="sheets_followup_list.php" class="btn btn-outline-secondary px-5 py-2">
                                            <i class="bi bi-x-circle me-2"></i>
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-success px-5 py-2">
                                            <i class="bi bi-save me-2"></i>
                                            Update Seller
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

<!-- Update History Section -->
<?php if (!empty($update_history)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history text-primary me-2"></i>
                    Update History & Tracking (Indian Standard Time)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="timeline">
                    <?php foreach ($update_history as $index => $entry): 
                        // Use formatted timestamp if available, otherwise format it
                        $display_time = $entry['timestamp_formatted'] ?? date('d M Y, h:i A', strtotime($entry['timestamp']));
                    ?>
                        <div class="timeline-item <?= $index === 0 ? 'latest' : '' ?>">
                            <div class="timeline-badge">
                                <i class="bi bi-<?= $index === 0 ? 'star-fill' : 'record-circle' ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-date">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?= $display_time ?> IST
                                    </span>
                                    <?php if ($index === 0): ?>
                                        <span class="badge bg-success ms-2">Latest</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($entry['changes'])): ?>
                                    <div class="timeline-changes">
                                        <table class="table table-sm table-borderless mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="30%">Field</th>
                                                    <th width="35%">Previous Value</th>
                                                    <th width="35%">New Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($entry['changes'] as $change): ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?= htmlspecialchars($change['field']) ?></td>
                                                        <td class="text-muted">
                                                            <?php 
                                                            $old_value = $change['old'] ?? '';
                                                            if ($old_value !== '' && $old_value !== null && $old_value !== '0'): 
                                                            ?>
                                                                <span class="badge bg-light text-dark"><?= htmlspecialchars($old_value) ?></span>
                                                            <?php else: ?>
                                                                <span class="text-muted fw-bold">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-success">
                                                            <?php 
                                                            $new_value = $change['new'] ?? '';
                                                            if ($new_value !== '' && $new_value !== null && $new_value !== '0'): 
                                                            ?>
                                                                <span class="badge bg-success-subtle text-success">
                                                                    <?= htmlspecialchars($new_value) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted fw-bold">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0">No specific field changes recorded</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Toast Container for Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <!-- Bootstrap Datepicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">

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
            border-left: 4px solid #198754;
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

        .date-field {
            margin-top: 10px;
            padding: 15px;
            background-color: #fff;
            border: 1px solid #198754;
            border-radius: 0.5rem;
        }

        .input-group.date .input-group-text {
            cursor: pointer;
        }

        /* Timeline Styles */
        .timeline {
            position: relative;
            padding: 1rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 2.2rem;
            top: 1.5rem;
            bottom: 1.5rem;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            padding-left: 3.5rem;
            margin-bottom: 1.5rem;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-badge {
            position: absolute;
            left: 1.5rem;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            background: white;
            border: 2px solid #198754;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #198754;
            font-size: 0.8rem;
            z-index: 1;
        }

        .timeline-item.latest .timeline-badge {
            background: #198754;
            color: white;
            border-color: #198754;
        }

        .timeline-content {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .timeline-header {
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .timeline-date {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }

        .timeline-changes table {
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .timeline-changes td {
            padding: 0.5rem 0.25rem;
            vertical-align: middle;
        }

        .badge.bg-success-subtle {
            background-color: #d1e7dd !important;
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
    <!-- Bootstrap Datepicker JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="<?= BASE_URL ?>js/work-station/sheets_edit_seller.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>

</html>