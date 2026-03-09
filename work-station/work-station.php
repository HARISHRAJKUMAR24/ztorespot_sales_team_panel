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
            
            <!-- Main Content - WIDER DESKTOP LAYOUT -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-3 px-md-4 py-4">
                <!-- Header -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center flex-wrap flex-md-nowrap pt-3 pb-2 mb-4 border-bottom gap-2">
                    <h1 class="h2 mb-2 mb-sm-0">Add New Seller</h1>
                    <div class="d-flex gap-2">
                        <a href="sales_person_sellers_list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to List
                        </a>
                    </div>
                </div>

                <!-- Form Card - WIDER ON DESKTOP -->
                <div class="row">
                    <!-- Changed from col-12 col-lg-10 col-xl-8 mx-auto to full width columns -->
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
                                    <!-- Row 1: Name/Store/Business - Full width -->
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
                                                       id="businessName">
                                            </div>
                                            <div class="form-text text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Example: Ezhil Gardens, SRI BALAN WINDOWS, delta dairy
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Seller Type and Phone Number - Side by side on larger screens -->
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
                                                       id="phoneNumber" maxlength="10">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 3: Plans Interested and Customer Response - Side by side -->
                                    <div class="row mb-3">
                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-star text-primary me-1"></i>
                                                Plans Interested
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-box"></i>
                                                </span>
                                                <select class="form-select border-start-0" id="plansInterested">
                                                    <option value="" selected disabled>Select plan</option>
                                                    <option value="Welcome">Welcome Plan</option>
                                                    <option value="Starter">Starter Plan</option>
                                                    <option value="Professional">Professional Plan</option>
                                                    <option value="None">None</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-chat-dots text-primary me-1"></i>
                                                Customer Response
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-megaphone"></i>
                                                </span>
                                                <select class="form-select border-start-0" id="customerResponse">
                                                    <option value="" selected disabled>Select response type</option>
                                                    <option value="Plan Upgraded">Plan Upgraded</option>
                                                    <option value="CNP">CNP (Call Not Picked)</option>
                                                    <option value="Later">Later</option>
                                                    <option value="Not interested">Not interested</option>
                                                    <option value="Switch Off">Switch Off</option>
                                                    <option value="No Business">No Business</option>
                                                    <option value="Whatsapp Details sent">Whatsapp Details sent</option>
                                                    <option value="Call Back AT">Call Back AT</option>
                                                    <option value="Out of Service">Out of Service</option>
                                                    <option value="Customer Responses">Customer Responses</option>
                                                    <option value="Testing">Testing</option>
                                                    <option value="Renewals">Renewals</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 4: Customer Queries - Full width -->
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
                                            <div class="form-text text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Example: product uploading verification call, already website created, etc.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 5: Customer Status and Call Duration - Side by side -->
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
                                            <div class="form-text text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Example: 15 mins, 30 mins, 45 mins, 1 hour
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Information Section - Full width accordion -->
                                    <div class="accordion mb-4" id="additionalInfoAccordion">
                                        <div class="accordion-item border-0">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed bg-light" type="button" 
                                                        data-bs-toggle="collapse" data-bs-target="#additionalInfo">
                                                    <i class="bi bi-three-dots me-2"></i>
                                                    Additional Information (Optional)
                                                </button>
                                            </h2>
                                            <div id="additionalInfo" class="accordion-collapse collapse" 
                                                 data-bs-parent="#additionalInfoAccordion">
                                                <div class="accordion-body px-0">
                                                    <!-- Remembering Notes - Full width -->
                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <label class="form-label fw-semibold">
                                                                <i class="bi bi-pencil text-secondary me-1"></i>
                                                                Remembering Notes
                                                            </label>
                                                            <textarea class="form-control" 
                                                                      placeholder="Additional notes to remember about this seller..."
                                                                      id="rememberingNotes" rows="2"></textarea>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Latest Update - Full width -->
                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <label class="form-label fw-semibold">
                                                                <i class="bi bi-arrow-up-circle text-secondary me-1"></i>
                                                                Latest Update
                                                            </label>
                                                            <input type="text" class="form-control" 
                                                                   placeholder="Latest update from conversation"
                                                                   id="latestUpdate">
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Row: Registration Status and Call Timing -->
                                                    <div class="row mb-3">
                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label fw-semibold">
                                                                <i class="bi bi-check-square text-secondary me-1"></i>
                                                                Registration Status
                                                            </label>
                                                            <select class="form-select" id="registrationStatus">
                                                                <option value="">Select status</option>
                                                                <option value="Yes">Yes (Registered)</option>
                                                                <option value="No">No (Not Registered)</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label fw-semibold">
                                                                <i class="bi bi-calendar text-secondary me-1"></i>
                                                                Call Timing
                                                            </label>
                                                            <input type="text" class="form-control" 
                                                                   placeholder="When to call back (e.g., tomorrow 5pm)"
                                                                   id="callTiming">
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Row: Video/Canva and Remarks -->
                                                    <div class="row">
                                                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                                                            <label class="form-label fw-semibold">
                                                                <i class="bi bi-youtube text-secondary me-1"></i>
                                                                Video/Canva Link
                                                            </label>
                                                            <input type="url" class="form-control" 
                                                                   placeholder="https://..."
                                                                   id="videoCanva">
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label fw-semibold">
                                                                <i class="bi bi-chat-text text-secondary me-1"></i>
                                                                Remarks
                                                            </label>
                                                            <input type="text" class="form-control" 
                                                                   placeholder="Any additional remarks"
                                                                   id="remarks">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Entry Date - Left aligned -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-calendar-date text-primary me-1"></i>
                                                Entry Date
                                            </label>
                                            <div class="input-group" style="max-width: 300px;">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-calendar"></i>
                                                </span>
                                                <input type="date" class="form-control border-start-0" 
                                                       id="entryDate" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions - Centered and larger buttons -->
                                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 gap-sm-3 pt-3 border-top">
                                        <button type="submit" class="btn btn-primary px-5 py-2">
                                            <i class="bi bi-save me-2"></i>
                                            Save Seller
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary px-5 py-2">
                                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                                            Reset
                                        </button>
                                        <a href="sales_person_sellers_list.php" class="btn btn-light px-5 py-2">
                                            <i class="bi bi-x-circle me-2"></i>
                                            Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </main>
        </div>
    </div>

    <style>
        /* Custom styles for better UI */
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
        
        .input-group .form-control:focus + .input-group-text,
        .input-group .form-select:focus + .input-group-text {
            border-color: #86b7fe;
        }
        
        .accordion-button:not(.collapsed) {
            background-color: #e7f1ff;
            color: #0d6efd;
        }
        
        .accordion-button:focus {
            box-shadow: none;
            border-color: rgba(0,0,0,.125);
        }
        
        /* Desktop specific styles - WIDER LAYOUT */
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
            
            .input-group-text, 
            .form-control, 
            .form-select {
                font-size: 1rem;
                padding: 0.6rem 1rem;
            }
            
            .accordion-button {
                font-size: 1rem;
                padding: 1rem;
            }
        }
        
        /* Extra large screens - even wider */
        @media (min-width: 1400px) {
            .card-body {
                padding: 2.5rem !important;
            }
            
            .col-12 {
                padding-left: 2rem;
                padding-right: 2rem;
            }
        }
        
        @media (max-width: 576px) {
            .card-body {
                padding: 1rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
            }
            
            .form-label {
                font-size: 0.85rem;
            }
            
            .input-group-text {
                padding: 0.375rem 0.5rem;
            }
        }
        
        /* Hover effects */
        .card {
            transition: all 0.2s ease-in-out;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }
        
        .btn {
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        }
        
        /* Input focus effects */
        .form-control:focus,
        .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
        }
        
        /* Toast container for future notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>