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
                    <h1 class="h2">WhatsApp Customers - Bulk Upload</h1>
                    <div>
                        <a href="whatsapp_customers.php" class="btn btn-outline-primary me-2">
                            <i class="bi bi-arrow-left me-1"></i>Back to List
                        </a>
                        <button class="btn btn-success" id="downloadSampleBtn">
                            <i class="bi bi-download me-1"></i>Download Sample
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <!-- Upload Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">Upload Excel File</h5>
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
                        <div class="card mb-4 d-none" id="progressCard">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">Import Progress</h5>
                            </div>
                            <div class="card-body">
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         id="uploadProgress" role="progressbar" style="width: 0%">0%</div>
                                </div>
                                <div class="row text-center">
                                    <div class="col-3">
                                        <h4 class="mb-0" id="totalRows">0</h4>
                                        <small class="text-muted">Total Rows</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 class="mb-0" id="validRows">0</h4>
                                        <small class="text-muted">Valid Data</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 class="mb-0 text-success" id="successRows">0</h4>
                                        <small class="text-muted">Imported</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 class="mb-0 text-danger" id="errorRows">0</h4>
                                        <small class="text-muted">Errors</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Results Card -->
                        <div class="card d-none" id="resultsCard">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Import Results</h5>
                                <button class="btn btn-sm btn-outline-primary" id="viewErrorsBtn" style="display: none;">
                                    <i class="bi bi-exclamation-triangle me-1"></i>View Errors
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="resultsContent"></div>
                            </div>
                        </div>

                        <!-- Column Mapping Info -->
                        <div class="card">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">Excel Format Guide</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-primary">
                                    <h6><i class="bi bi-calendar-event me-2"></i>Date Handling</h6>
                                    <p class="mb-0">Dates can appear anywhere in the sheet. All data rows after a date will be associated with that date until a new date is found.</p>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Excel Column</th>
                                                <th>Database Field</th>
                                                <th>Example</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td><strong>Date Row</strong></td><td><code>entry_date</code></td><td>1/2/2024 or 01.02.2024 or 1 February 2024</td></tr>
                                            <tr><td>Seller Name</td><td><code>seller_name</code></td><td>SMS, SHARUK, Balaji</td></tr>
                                            <tr><td>Phone Number</td><td><code>phone_number</code></td><td>8526133467</td></tr>
                                            <tr><td>Assigned By</td><td><code>assigned_by</code></td><td>Gowsika (Prabha)</td></tr>
                                            <tr><td>Update 1</td><td><code>update_1</code></td><td>CNP, CNA, not interest</td></tr>
                                            <tr><td>Update 2</td><td><code>update_2</code></td><td>Website need ila</td></tr>
                                            <tr><td>Update 3</td><td><code>update_3</code></td><td>Swiggy/Zomato maari venum</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    <strong>Example Format:</strong><br>
                                    <code>1/2/2024</code> (date row - not imported as data)<br>
                                    <code>SMS | 8526133467 | Gowsika | | Website need ila |</code> (data row - will get date 1/2/2024)<br>
                                    <code>SHARUK | 9629229928 | Gowsika | | 5 maniku mela call |</code> (data row - will get date 1/2/2024)<br>
                                    <code>3/2/2024</code> (new date row)<br>
                                    <code>RAJIV S | 9789307337 | Prabha | | No business |</code> (data row - will get date 3/2/2024)
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Errors</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-danger">
                                <tr>
                                    <th>Row</th>
                                    <th>Seller Name</th>
                                    <th>Error</th>
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
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/bulk-upload/whatsapp_bulk_upload.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>
</html>