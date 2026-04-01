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
    SELECT id, work_details_update as business_name, phone_number, customer_response, seller_id
    FROM sales_person_sellers 
    WHERE user_uid = ? 
    ORDER BY created_at DESC
");
$sellers_stmt->execute([$user_uid]);
$sellers = $sellers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ALL users for sharing dropdown
$users_stmt = $pdo->prepare("
    SELECT id, user_uid, name, phone, email 
    FROM users 
    WHERE user_uid != ? 
    ORDER BY name
");
$users_stmt->execute([$user_uid]);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center flex-wrap flex-md-nowrap pt-3 pb-2 mb-4 border-bottom gap-2">
                    <h1 class="h2 mb-2 mb-sm-0">
                        <i class="bi bi-share-fill text-primary me-2"></i>
                        Share Seller Information
                    </h1>
                </div>

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

                                    <!-- Existing Seller Section -->
                                    <div id="existingSellerSection" style="display: none;">
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-person-badge text-primary me-1"></i>
                                                    Select Sellers to Share <span class="text-danger">*</span>
                                                </label>

                                                <div class="mb-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light">
                                                            <i class="bi bi-search"></i>
                                                        </span>
                                                        <input type="text" class="form-control" id="sellerSearch"
                                                            placeholder="Search sellers by ID, name or phone...">
                                                    </div>
                                                </div>

                                                <div class="mb-2">
                                                    <small class="text-muted">Hold Ctrl (Cmd on Mac) to select multiple sellers</small>
                                                </div>

                                                <select class="form-select" id="sellerIds" name="seller_ids[]" multiple size="8">
                                                    <option value="" disabled>Choose sellers to share</option>
                                                    <?php foreach ($sellers as $seller): ?>
                                                        <option value="<?= $seller['id'] ?>"
                                                            data-business="<?= htmlspecialchars($seller['business_name']) ?>"
                                                            data-phone="<?= htmlspecialchars($seller['phone_number']) ?>"
                                                            data-response="<?= htmlspecialchars($seller['customer_response']) ?>"
                                                            data-seller-id="<?= htmlspecialchars($seller['seller_id']) ?>">
                                                            ID: <?= $seller['id'] ?> -
                                                            <?= htmlspecialchars($seller['business_name']) ?> -
                                                            <?= htmlspecialchars($seller['phone_number']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <div class="mt-2 d-flex gap-2 flex-wrap">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllSellers">
                                                        <i class="bi bi-check-all"></i> Select All
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllSellers">
                                                        <i class="bi bi-x"></i> Deselect All
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

                                        <div id="sellersPreview" class="row mb-4" style="display: none;">
                                            <div class="col-12">
                                                <div class="card bg-light border">
                                                    <div class="card-body py-3">
                                                        <h6 class="card-subtitle mb-2 text-muted">
                                                            Selected Sellers (<span id="selectedCount">0</span>)
                                                        </h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-borderless mb-0">
                                                                <tbody id="previewTableBody"></tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- New Seller Section -->
                                    <div id="newSellerSection">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-hash text-primary me-1"></i>
                                                    Seller ID <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="newSellerId"
                                                    placeholder="Enter seller ID" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    <i class="bi bi-person-circle text-primary me-1"></i>
                                                    Business Name <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="newBusinessName"
                                                    placeholder="Enter business name" required>
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
                                    </div>

                                    <!-- Share With Users Section -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-people-fill text-primary me-1"></i>
                                                Share With Users <span class="text-danger">*</span>
                                            </label>

                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="shareToAll">
                                                <label class="form-check-label" for="shareToAll">
                                                    <i class="bi bi-send-check-fill text-success me-1"></i>
                                                    <strong>Send to All Users</strong>
                                                </label>
                                            </div>

                                            <div id="userSelectionSection">
                                                <div class="mb-2">
                                                    <small class="text-muted">Hold Ctrl (Cmd on Mac) to select multiple users</small>
                                                </div>
                                                <select class="form-select" id="sharedWithUser" name="shared_with_users[]" multiple size="5">
                                                    <option value="" disabled>Select users to share with</option>
                                                    <?php foreach ($users as $shareUser): ?>
                                                        <option value="<?= $shareUser['user_uid'] ?>" data-name="<?= htmlspecialchars($shareUser['name']) ?>">
                                                            <?= htmlspecialchars($shareUser['name']) ?> (<?= $shareUser['user_uid'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllUsers">
                                                    <i class="bi bi-check-all"></i> Select All Users
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllUsers">
                                                    <i class="bi bi-x"></i> Deselect All
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notes -->
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
                                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary px-5 py-2">
                                            <i class="bi bi-send me-2"></i>Share Seller(s)
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>

    <script>
        $(document).ready(function() {
            // Toggle between existing and new seller
            $('input[name="shareOption"]').on('change', function() {
                if ($(this).val() === 'existing') {
                    $('#existingSellerSection').show();
                    $('#newSellerSection').hide();
                    $('#newSellerId').prop('required', false);
                    $('#newBusinessName').prop('required', false);
                    $('#newPhoneNumber').prop('required', false);
                    $('#newCustomerResponse').prop('required', false);
                } else {
                    $('#existingSellerSection').hide();
                    $('#newSellerSection').show();
                    $('#newSellerId').prop('required', true);
                    $('#newBusinessName').prop('required', true);
                    $('#newPhoneNumber').prop('required', true);
                    $('#newCustomerResponse').prop('required', true);
                    $('#sellersPreview').hide();
                    $('#sellerIds').val([]);
                }
            });

            // Share to All toggle
            $('#shareToAll').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#sharedWithUser').prop('disabled', true).hide();
                    $('#sharedWithUser').val([]);
                } else {
                    $('#sharedWithUser').prop('disabled', false).show();
                }
            });

            // Seller selection change
            $('#sellerIds').on('change', function() {
                const selectedOptions = $(this).find('option:selected');
                const selectedCount = selectedOptions.length;

                if (selectedCount > 0) {
                    let previewHtml = '';
                    selectedOptions.each(function() {
                        previewHtml += `
                        <tr>
                            <td><strong>ID:</strong> ${$(this).val()}</td>
                            <td><strong>Business:</strong> ${$(this).data('business') || '-'}</td>
                            <td><strong>Phone:</strong> ${$(this).data('phone') || '-'}</td>
                            <td><strong>Response:</strong> ${$(this).data('response') || '-'}</td>
                        </tr>
                    `;
                    });
                    $('#selectedCount').text(selectedCount);
                    $('#previewTableBody').html(previewHtml);
                    $('#sellersPreview').show();
                } else {
                    $('#sellersPreview').hide();
                }
            });

            // Select/Deselect all sellers
            $('#selectAllSellers').on('click', function() {
                $('#sellerIds option').prop('selected', true);
                $('#sellerIds').trigger('change');
            });
            $('#deselectAllSellers').on('click', function() {
                $('#sellerIds option').prop('selected', false);
                $('#sellerIds').trigger('change');
            });

            // Select/Deselect all users
            $('#selectAllUsers').on('click', function() {
                if (!$('#shareToAll').is(':checked')) {
                    $('#sharedWithUser option').prop('selected', true);
                }
            });
            $('#deselectAllUsers').on('click', function() {
                if (!$('#shareToAll').is(':checked')) {
                    $('#sharedWithUser option').prop('selected', false);
                }
            });

            // Phone number validation
            $('#newPhoneNumber').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            });

            // Form submit
            $('#shareSellerForm').on('submit', function(e) {
                e.preventDefault();

                const shareOption = $('input[name="shareOption"]:checked').val();
                const shareToAll = $('#shareToAll').is(':checked');
                const sharedWithUsers = shareToAll ? [] : $('#sharedWithUser').val();
                const notes = $('#shareNotes').val().trim();

                if (!shareToAll && (!sharedWithUsers || sharedWithUsers.length === 0)) {
                    Swal.fire('Error', 'Please select at least one user to share with', 'error');
                    return;
                }

                const formData = {
                    share_option: shareOption,
                    notes: notes,
                    share_to_all: shareToAll,
                    shared_with_users: sharedWithUsers
                };

                if (shareOption === 'existing') {
                    const sellerIds = $('#sellerIds').val();
                    if (!sellerIds || sellerIds.length === 0) {
                        Swal.fire('Error', 'Please select at least one seller to share', 'error');
                        return;
                    }
                    formData.seller_ids = sellerIds;
                } else {
                    const sellerId = $('#newSellerId').val().trim();
                    const businessName = $('#newBusinessName').val().trim();
                    const phoneNumber = $('#newPhoneNumber').val().trim();
                    const customerResponse = $('#newCustomerResponse').val();

                    if (!sellerId || !businessName || !phoneNumber || !customerResponse) {
                        Swal.fire('Error', 'Please fill all seller details', 'error');
                        return;
                    }
                    if (!/^\d{10}$/.test(phoneNumber)) {
                        Swal.fire('Error', 'Please enter a valid 10-digit phone number', 'error');
                        return;
                    }
                    formData.seller_id = sellerId;
                    formData.business_name = businessName;
                    formData.phone_number = phoneNumber;
                    formData.customer_response = customerResponse;
                }

                const $btn = $(this).find('button[type="submit"]');
                const originalText = $btn.html();
                $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Sharing...').prop('disabled', true);

                $.ajax({
                    url: BASE_URL + 'ajax/work-station/share-sellers/share-sellers.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    traditional: true,
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('Success!', response.message, 'success').then(() => {
                                $('#shareSellerForm')[0].reset();
                                $('#sellersPreview').hide();
                                $('#sharedWithUser').val([]);
                                $('#shareToAll').prop('checked', false);
                                $('#sharedWithUser').prop('disabled', false).show();
                                $('input[name="shareOption"][value="new"]').prop('checked', true);
                                $('#existingSellerSection').hide();
                                $('#newSellerSection').show();
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Failed to share seller';
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.message) msg = res.message;
                        } catch (e) {}
                        Swal.fire('Error!', msg, 'error');
                    },
                    complete: function() {
                        $btn.html(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Toggle between existing and new seller
            $('input[name="shareOption"]').on('change', function() {
                if ($(this).val() === 'existing') {
                    $('#existingSellerSection').show();
                    $('#newSellerSection').hide();
                    $('#newSellerId').prop('required', false);
                    $('#newBusinessName').prop('required', false);
                    $('#newPhoneNumber').prop('required', false);
                    $('#newCustomerResponse').prop('required', false);
                } else {
                    $('#existingSellerSection').hide();
                    $('#newSellerSection').show();
                    $('#newSellerId').prop('required', true);
                    $('#newBusinessName').prop('required', true);
                    $('#newPhoneNumber').prop('required', true);
                    $('#newCustomerResponse').prop('required', true);
                    $('#sellersPreview').hide();
                    $('#sellerIds').val([]);
                }
            });

            // Share to All toggle
            $('#shareToAll').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#sharedWithUser').prop('disabled', true);
                    $('#sharedWithUser').val([]);
                } else {
                    $('#sharedWithUser').prop('disabled', false);
                }
            });

            // Seller selection change
            $('#sellerIds').on('change', function() {
                const selectedOptions = $(this).find('option:selected');
                const selectedCount = selectedOptions.length;

                if (selectedCount > 0) {
                    let previewHtml = '';
                    selectedOptions.each(function() {
                        previewHtml += `
                    <tr>
                        <td><strong>ID:</strong> ${$(this).val()}</td>
                        <td><strong>Business:</strong> ${$(this).data('business') || '-'}</td>
                        <td><strong>Phone:</strong> ${$(this).data('phone') || '-'}</td>
                        <td><strong>Response:</strong> ${$(this).data('response') || '-'}</td>
                    </tr>
                `;
                    });
                    $('#selectedCount').text(selectedCount);
                    $('#previewTableBody').html(previewHtml);
                    $('#sellersPreview').show();
                } else {
                    $('#sellersPreview').hide();
                }
            });

            // Select/Deselect all sellers
            $('#selectAllSellers').on('click', function() {
                $('#sellerIds option').prop('selected', true);
                $('#sellerIds').trigger('change');
            });
            $('#deselectAllSellers').on('click', function() {
                $('#sellerIds option').prop('selected', false);
                $('#sellerIds').trigger('change');
            });

            // Select/Deselect all users
            $('#selectAllUsers').on('click', function() {
                if (!$('#shareToAll').is(':checked')) {
                    $('#sharedWithUser option').prop('selected', true);
                }
            });
            $('#deselectAllUsers').on('click', function() {
                if (!$('#shareToAll').is(':checked')) {
                    $('#sharedWithUser option').prop('selected', false);
                }
            });

            // Seller search functionality
            $('#sellerSearch').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('#sellerIds option').each(function() {
                    const text = $(this).text().toLowerCase();
                    if (text.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Phone number validation
            $('#newPhoneNumber').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            });

            // Form submit
            $('#shareSellerForm').on('submit', function(e) {
                e.preventDefault();

                const shareOption = $('input[name="shareOption"]:checked').val();
                const shareToAll = $('#shareToAll').is(':checked');
                const sharedWithUsers = $('#sharedWithUser').val();
                const notes = $('#shareNotes').val().trim();

                if (!shareToAll && (!sharedWithUsers || sharedWithUsers.length === 0)) {
                    Swal.fire('Error', 'Please select at least one user to share with', 'error');
                    return;
                }

                const formData = {
                    share_option: shareOption,
                    notes: notes,
                    share_to_all: shareToAll,
                    shared_with_users: sharedWithUsers || []
                };

                if (shareOption === 'existing') {
                    const sellerIds = $('#sellerIds').val();
                    if (!sellerIds || sellerIds.length === 0) {
                        Swal.fire('Error', 'Please select at least one seller to share', 'error');
                        return;
                    }
                    formData.seller_ids = sellerIds;
                } else {
                    const sellerId = $('#newSellerId').val().trim();
                    const businessName = $('#newBusinessName').val().trim();
                    const phoneNumber = $('#newPhoneNumber').val().trim();
                    const customerResponse = $('#newCustomerResponse').val();

                    if (!sellerId || !businessName || !phoneNumber || !customerResponse) {
                        Swal.fire('Error', 'Please fill all seller details', 'error');
                        return;
                    }
                    if (!/^\d{10}$/.test(phoneNumber)) {
                        Swal.fire('Error', 'Please enter a valid 10-digit phone number', 'error');
                        return;
                    }
                    formData.seller_id = sellerId;
                    formData.business_name = businessName;
                    formData.phone_number = phoneNumber;
                    formData.customer_response = customerResponse;
                }

                const $btn = $(this).find('button[type="submit"]');
                const originalText = $btn.html();
                $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Sharing...').prop('disabled', true);

                console.log('Sending data:', formData);

                $.ajax({
                    url: BASE_URL + 'ajax/work-station/share-sellers/share-sellers.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    traditional: true,
                    success: function(response) {
                        console.log('Response:', response);
                        if (response.status === 'success') {
                            Swal.fire('Success!', response.message, 'success').then(() => {
                                $('#shareSellerForm')[0].reset();
                                $('#sellersPreview').hide();
                                $('#sharedWithUser').val([]);
                                $('#shareToAll').prop('checked', false);
                                $('#sharedWithUser').prop('disabled', false);
                                $('input[name="shareOption"][value="new"]').prop('checked', true);
                                $('#existingSellerSection').hide();
                                $('#newSellerSection').show();
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        console.error('Response:', xhr.responseText);
                        let msg = 'Failed to share seller. Please try again.';
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.message) msg = res.message;
                        } catch (e) {}
                        Swal.fire('Error!', msg, 'error');
                    },
                    complete: function() {
                        $btn.html(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>

</html>