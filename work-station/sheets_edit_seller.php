<?php
// sheets_edit_seller.php
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

// Get subscription plans for dropdown
$plans_stmt = $pdo->prepare("SELECT id, plan_name, duration, total_amount FROM subscription_plans WHERE status = 1 ORDER BY plan_name, total_amount");
$plans_stmt->execute();
$subscription_plans = $plans_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    'seller_id' => $seller['seller_id'] ?? '',
    'plans_interested' => $seller['plans_interested'] ?? '',
    'plan_duration' => $seller['plan_duration'] ?? '',
    'plan_data' => $seller['plan_data'] ?? '',
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

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Seller - Work Station</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Bootstrap Datepicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">

    <style>
        :root {
            --success-color: #10b981;
            --success-hover: #059669;
            --primary-color: #4f46e5;
            --warning-color: #f59e0b;
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
        }

        .card-header {
            border-bottom: 1px solid #eef2ff;
            background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
            padding: 0.625rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .dynamic-field {
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
            border-left: 4px solid var(--warning-color);
            padding: 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
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

        .list-group-item {
            border-left: none;
            border-right: none;
            transition: background 0.2s;
        }

        .list-group-item:hover {
            background-color: #f9fafb;
        }

        .toast {
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .datepicker {
            z-index: 9999 !important;
        }
    </style>
</head>

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
                            Edit Seller
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="bi bi-clock me-1"></i>
                            Last updated: <?= $current_indian_time ?>
                        </p>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-person-gear text-success me-2"></i>
                                    Edit Seller Information
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <form id="sellerForm" data-seller-id="<?= $seller_id ?>">
                                    <input type="hidden" id="sellerId" value="<?= $seller_id ?>">
                                    <input type="hidden" id="sellerData" value='<?= htmlspecialchars($seller_json, ENT_QUOTES, 'UTF-8') ?>'>

                                    <!-- Row 1: Business Name (Full Width) -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-shop text-success me-1"></i>
                                                Business / Store Name <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-building"></i>
                                                </span>
                                                <input type="text" class="form-control border-start-0"
                                                    placeholder="Enter business name, store name or company name"
                                                    id="businessName" value="<?= htmlspecialchars($form_data['business_name']) ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Seller Type and Seller ID -->
                                    <div class="row mb-4">
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
                                                    <option value="Own Chat" <?= $form_data['seller_type'] == 'Own Chat' ? 'selected' : '' ?>>Own Chat</option>
                                                    <option value="Organic" <?= $form_data['seller_type'] == 'Organic' ? 'selected' : '' ?>>Organic</option>
                                                    <option value="Direct" <?= $form_data['seller_type'] == 'Direct' ? 'selected' : '' ?>>Direct</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-upc-scan text-success me-1"></i>
                                                Seller ID
                                                <span class="badge bg-light text-muted ms-1">Optional</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-upc-scan"></i>
                                                </span>
                                                <input type="text" class="form-control border-start-0"
                                                    placeholder="Enter seller ID (optional)"
                                                    id="sellerID" value="<?= htmlspecialchars($seller['seller_id'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 3: Phone Number and Customer Response -->
                                    <div class="row mb-4">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
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
                                        <div class="col-12 col-md-6">
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
                                                    <option value="other">Other (Custom Response)</option>
                                                </select>
                                            </div>
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

                                    <!-- Dynamic Fields Container -->
                                    <div id="dynamicFieldsContainer" class="mb-4"></div>

                                    <!-- Row 4: Remembering Notes and Latest Update -->
                                    <div class="row mb-4">
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
                                                    id="rememberingNotes" rows="3"><?= htmlspecialchars($form_data['remembering_notes']) ?></textarea>
                                            </div>
                                            <div class="form-text text-muted small">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Your custom notes will be saved here
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
                                                    id="latestUpdate" rows="3"><?= htmlspecialchars($form_data['latest_update']) ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 5: Customer Queries and Additional Notes -->
                                    <div class="row mb-4">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
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
                                                    id="customerQueries" rows="3"><?= htmlspecialchars($form_data['customer_queries']) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-journal-text text-success me-1"></i>
                                                Additional Notes / Remarks
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0" style="align-items: flex-start; padding-top: 0.75rem;">
                                                    <i class="bi bi-pencil"></i>
                                                </span>
                                                <textarea class="form-control border-start-0"
                                                    placeholder="Enter any additional notes or remarks..."
                                                    id="additionalNotes" rows="3"><?= htmlspecialchars($form_data['remarks']) ?></textarea>
                                            </div>
                                            <div class="form-text text-muted small">
                                                <i class="bi bi-info-circle me-1"></i>
                                                These notes will be saved in the remarks field
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 6: Call Timing and Current Status -->
                                    <div class="row mb-4">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
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
                                                        <option value="5 mins" <?= $form_data['call_timing'] == '5 mins' ? 'selected' : '' ?>>5 mins</option>
                                                        <option value="10 mins" <?= $form_data['call_timing'] == '10 mins' ? 'selected' : '' ?>>10 mins</option>
                                                        <option value="15 mins" <?= $form_data['call_timing'] == '15 mins' ? 'selected' : '' ?>>15 mins</option>
                                                        <option value="20 mins" <?= $form_data['call_timing'] == '20 mins' ? 'selected' : '' ?>>20 mins</option>
                                                        <option value="25 mins" <?= $form_data['call_timing'] == '25 mins' ? 'selected' : '' ?>>25 mins</option>
                                                        <option value="30 mins" <?= $form_data['call_timing'] == '30 mins' ? 'selected' : '' ?>>30 mins</option>
                                                        <option value="45 mins" <?= $form_data['call_timing'] == '45 mins' ? 'selected' : '' ?>>45 mins</option>
                                                        <option value="1 hour" <?= $form_data['call_timing'] == '1 hour' ? 'selected' : '' ?>>1 hour</option>
                                                        <option value="1.5 hours" <?= $form_data['call_timing'] == '1.5 hours' ? 'selected' : '' ?>>1.5 hours</option>
                                                        <option value="2 hours" <?= $form_data['call_timing'] == '2 hours' ? 'selected' : '' ?>>2 hours</option>
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
                                                            id="customCallTiming" value="<?= !in_array($form_data['call_timing'], ['5 mins', '10 mins', '15 mins', '20 mins', '25 mins', '30 mins', '45 mins', '1 hour', '1.5 hours', '2 hours']) ? htmlspecialchars($form_data['call_timing']) : '' ?>">
                                                    </div>
                                                </div>
                                                <input type="hidden" id="callTiming" name="call_timing" value="<?= htmlspecialchars($form_data['call_timing']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
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
                                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 pt-3 border-top">
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
                                        Update History
                                    </h5>
                                </div>
                                <div class="card-body p-3">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($update_history as $index => $entry):
                                            $display_time = $entry['timestamp_formatted'] ?? date('d M Y, h:i A', strtotime($entry['timestamp']));
                                        ?>
                                            <div class="list-group-item px-0 <?= $index === 0 ? 'bg-light' : '' ?>">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">
                                                        <?php if ($index === 0): ?>
                                                            <span class="badge bg-success me-2">Latest</span>
                                                        <?php endif; ?>
                                                        <small class="text-muted"><?= $display_time ?></small>
                                                    </h6>
                                                </div>

                                                <?php if (!empty($entry['changes'])): ?>
                                                    <ul class="mb-1 ps-3">
                                                        <?php foreach ($entry['changes'] as $change):
                                                            $old = $change['old'] ?? '-';
                                                            $new = $change['new'] ?? '-';
                                                        ?>
                                                            <li>
                                                                <strong><?= htmlspecialchars($change['field']) ?>:</strong>
                                                                <span class="text-muted"><?= htmlspecialchars($old) ?></span>
                                                                <i class="bi bi-arrow-right mx-1 text-primary"></i>
                                                                <span class="text-success"><?= htmlspecialchars($new) ?></span>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">No changes recorded</p>
                                                <?php endif; ?>
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

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <!-- WhatsApp Modal -->
    <div class="modal fade" id="whatsappModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-whatsapp me-2"></i>
                        WhatsApp Message
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Template Type</label>
                            <select class="form-select" id="templateType">
                                <option value="register">Register Seller</option>
                                <option value="aisensy">Aisensy / WP Chat Seller</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Phone Number</label>
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
                    <button type="button" class="btn btn-success" id="sendWhatsappMsgBtn">
                        <i class="bi bi-whatsapp me-2"></i> Send WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SCRIPTS - CORRECT ORDER IS CRITICAL -->
    <!-- ============================================ -->

    <!-- 1. jQuery FIRST -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- 2. Bootstrap JS (depends on jQuery) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- 3. Bootstrap Datepicker (depends on jQuery) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>

    <!-- 4. Pass PHP variables to JavaScript -->
    <script>
        const subscriptionPlans = <?= json_encode($subscription_plans) ?>;
        window.currentUser = {
            name: '<?= addslashes($user['name'] ?? 'Barani tharan') ?>',
            phone: '<?= $user['phone'] ?? '9952852208' ?>'
        };
        console.log('Subscription Plans loaded:', subscriptionPlans);
        console.log('Current User:', window.currentUser);
    </script>

    <!-- 5. Your custom JavaScript LAST -->
    <script src="<?= BASE_URL ?>js/work-station/sheets_edit_seller.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>

</body>

</html>