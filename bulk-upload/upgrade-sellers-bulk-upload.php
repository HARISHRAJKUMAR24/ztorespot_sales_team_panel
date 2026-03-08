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
    <div class="toast-container"></div>

    <!-- SVG Icons -->
    <?php template('svg-icons'); ?>

    <!-- Navigation -->
    <?php template('top-navbar'); ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php template('side-navbar'); ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-arrow-up-circle me-2"></i>
                        Upgrade Sellers - Bulk Upload
                    </h1>
                    <div>
                        <a href="upgrade_sellers.php" class="btn btn-outline-primary me-2">
                            <i class="bi bi-arrow-left me-1"></i>Back to List
                        </a>
                        <button class="btn btn-success" id="downloadSampleBtn">
                            <i class="bi bi-download me-1"></i>Download Sample
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="card-title">Total Upgrade Sellers</h6>
                                <h2 class="mb-0" id="totalCount">-</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-title">Active</h6>
                                <h2 class="mb-0" id="activeCount">-</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h6 class="card-title">Pending</h6>
                                <h2 class="mb-0" id="pendingCount">-</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6 class="card-title">Last Upload</h6>
                                <h2 class="mb-0" id="lastUpload">-</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <!-- Upload Card -->
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-cloud-upload me-2 text-primary"></i>
                                    Upload Excel File
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="bulkUploadForm" enctype="multipart/form-data">
                                    <div class="upload-area" id="uploadArea">
                                        <i class="bi bi-cloud-upload fs-1 text-primary"></i>
                                        <h5 class="mt-3">Drag & Drop or Click to Upload</h5>
                                        <p class="text-muted mb-3">Supported formats: .xlsx, .xls, .csv (Max 10MB)</p>
                                        <input type="file" name="excel_file" id="excelFile" accept=".xlsx,.xls,.csv" style="display: none;">
                                        <button type="button" class="btn btn-primary" id="selectFileBtn">
                                            <i class="bi bi-folder2-open me-1"></i>Select File
                                        </button>
                                    </div>
                                    
                                    <div id="fileInfo" class="mt-3 d-none">
                                        <div class="alert alert-info d-flex align-items-center">
                                            <i class="bi bi-file-earmark-excel fs-4 me-3"></i>
                                            <div class="flex-grow-1">
                                                <strong id="fileName"></strong>
                                                <br>
                                                <small id="fileSize" class="text-muted"></small>
                                            </div>
                                            <button type="button" class="btn-close" id="removeFileBtn"></button>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary w-100" id="uploadBtn" disabled>
                                            <i class="bi bi-upload me-1"></i>Upload & Import Data
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Progress Card -->
                        <div class="card mb-4 shadow-sm d-none" id="progressCard">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-hourglass-split me-2 text-warning"></i>
                                    Import Progress
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="progress mb-3" style="height: 30px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                         id="uploadProgress" role="progressbar" style="width: 0%">0%</div>
                                </div>
                                <div class="row text-center">
                                    <div class="col-3">
                                        <h4 class="mb-0" id="totalRows">0</h4>
                                        <small class="text-muted">Total</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 class="mb-0 text-success" id="successRows">0</h4>
                                        <small class="text-muted">Success</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 class="mb-0 text-danger" id="errorRows">0</h4>
                                        <small class="text-muted">Errors</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 class="mb-0 text-warning" id="duplicateRows">0</h4>
                                        <small class="text-muted">Duplicates</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Results Card -->
                        <div class="card mb-4 shadow-sm d-none" id="resultsCard">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-check-circle me-2 text-success"></i>
                                    Import Results
                                </h5>
                                <button class="btn btn-sm btn-outline-danger" id="viewErrorsBtn" style="display: none;">
                                    <i class="bi bi-exclamation-triangle me-1"></i>View Errors
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="resultsContent"></div>
                            </div>
                        </div>

                        <!-- Instructions Card -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-info-circle me-2 text-info"></i>
                                    Excel Column Mapping
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Excel Column</th>
                                                <th>Database Field</th>
                                                <th>Required</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td>1</td><td>Date</td><td><code>date</code></td><td>No</td><td>Upgrade date</td></tr>
                                            <tr><td>2</td><td>Seller Name/ID</td><td><code>seller_name_id</code></td><td><span class="text-danger">Yes</span></td><td>Seller identifier</td></tr>
                                            <tr><td>3</td><td>Work Details Update</td><td><code>work_details_update</code></td><td>No</td><td>Work update details</td></tr>
                                            <tr><td>4</td><td>Aiseny/Organic/Direct</td><td><code>source_type</code></td><td>No</td><td>Source type</td></tr>
                                            <tr><td>5</td><td>Reg/Not Reg</td><td><code>registration_status</code></td><td>No</td><td>Yes/No</td></tr>
                                            <tr><td>6</td><td>CS Mobile Number</td><td><code>cs_mobile</code></td><td>No</td><td>CS contact number</td></tr>
                                            <tr><td>7</td><td>Plans Interested</td><td><code>plans_interested</code></td><td>No</td><td>Plan type</td></tr>
                                            <tr><td>8</td><td>Customer Responses</td><td><code>customer_responses</code></td><td>No</td><td>Response text</td></tr>
                                            <tr><td>9</td><td>Remembering</td><td><code>remembering</code></td><td>No</td><td>Remembering notes</td></tr>
                                            <tr><td>10</td><td>Latest Update</td><td><code>latest_update</code></td><td>No</td><td>Latest update</td></tr>
                                            <tr><td>11</td><td>Current Status</td><td><code>current_status</code></td><td>No</td><td>Current status</td></tr>
                                            <tr><td>12</td><td>Customer Queries</td><td><code>customer_queries</code></td><td>No</td><td>Customer questions</td></tr>
                                            <tr><td>13</td><td>Video/Canva</td><td><code>video_canva</code></td><td>No</td><td>Video/Canva details</td></tr>
                                            <tr><td>14</td><td>Timings</td><td><code>timings</code></td><td>No</td><td>Call timings</td></tr>
                                            <tr><td>15</td><td>Remarks</td><td><code>remarks</code></td><td>No</td><td>Additional remarks</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="bi bi-lightbulb-fill me-2"></i>
                                    <strong>Tip:</strong> Download the sample file to see the exact format. The first row must contain headers matching the column names above.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Import Errors
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-danger">
                                <tr>
                                    <th>Row</th>
                                    <th>Seller</th>
                                    <th>Error Message</th>
                                </tr>
                            </thead>
                            <tbody id="errorList"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Import Complete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                    <h3 class="mt-3" id="successModalMessage">Import completed successfully!</h3>
                    <p id="successModalDetails" class="text-muted"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #0d6efd;
            background-color: #e9ecef;
        }
        .upload-area.dragover {
            border-color: #0d6efd;
            background-color: #cfe2ff;
        }
        .card {
            border: none;
            border-radius: 10px;
        }
        .progress-bar {
            transition: width 0.3s ease;
            border-radius: 15px;
        }
        .progress {
            border-radius: 15px;
            background-color: #e9ecef;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pass BASE_URL to JavaScript
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
    <script src="<?= BASE_URL ?>js/bulk-upload/upgrade-sellers-bulk-upload.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>