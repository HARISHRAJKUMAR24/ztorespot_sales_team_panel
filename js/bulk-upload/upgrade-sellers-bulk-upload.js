$(document).ready(function () {

let selectedFile = null;
let isUploading = false;

/* -----------------------------
   LOAD INITIAL STATS
----------------------------- */
function loadStats() {
    $.ajax({
        url: BASE_URL + 'ajax/bulk-upload/get_upgrade_sellers_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#totalCount').text(response.data.total);
                $('#activeCount').text(response.data.active);
                $('#pendingCount').text(response.data.pending);
                $('#lastUpload').text(response.data.last_upload || '-');
            }
        }
    });
}
loadStats();

/* -----------------------------
   CLICK SELECT FILE BUTTON
----------------------------- */
$('#selectFileBtn').on('click', function (e) {
    e.preventDefault();
    $('#excelFile')[0].click();
});

/* -----------------------------
   DRAG & DROP
----------------------------- */
$('#uploadArea').on('dragover', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass('dragover');
});

$('#uploadArea').on('dragleave', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('dragover');
});

$('#uploadArea').on('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('dragover');

    const files = e.originalEvent.dataTransfer.files;
    if (files.length > 0) {
        handleFileSelect(files[0]);
    }
});

/* -----------------------------
   REMOVE FILE BUTTON
----------------------------- */
$('#removeFileBtn').on('click', function () {
    selectedFile = null;
    $('#excelFile').val('');
    $('#fileInfo').addClass('d-none');
    $('#uploadBtn').prop('disabled', true);
});

/* -----------------------------
   FILE INPUT CHANGE
----------------------------- */
$('#excelFile').on('change', function () {
    if (this.files.length > 0) {
        handleFileSelect(this.files[0]);
    }
});

/* -----------------------------
   HANDLE FILE SELECT
----------------------------- */
function handleFileSelect(file) {
    // Check file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
        showToast('danger', 'Error!', 'File size must be under 10MB');
        return;
    }

    // Check file extension
    const valid = ['xlsx', 'xls', 'csv'];
    const ext = file.name.split('.').pop().toLowerCase();

    if (!valid.includes(ext)) {
        showToast('danger', 'Error!', 'Invalid file format. Please upload .xlsx, .xls, or .csv files only.');
        return;
    }

    selectedFile = file;

    $('#fileName').text(file.name);
    $('#fileSize').text(formatFileSize(file.size));

    $('#fileInfo').removeClass('d-none');
    $('#uploadBtn').prop('disabled', false);
}

/* -----------------------------
   DOWNLOAD SAMPLE BUTTON
----------------------------- */
$('#downloadSampleBtn').on('click', function () {
    window.location.href = BASE_URL + 'ajax/bulk-upload/download_sample_upgrade_sellers.php';
});

/* -----------------------------
   UPLOAD FORM SUBMIT
----------------------------- */
$('#bulkUploadForm').on('submit', function (e) {
    e.preventDefault();

    if (!selectedFile) {
        showToast('warning', 'Warning!', 'Please select a file first');
        return;
    }

    if (isUploading) {
        showToast('warning', 'Warning!', 'Upload already in progress');
        return;
    }

    // Show progress card
    $('#progressCard').removeClass('d-none');
    $('#uploadProgress').css('width', '10%').text('10%');
    $('#totalRows').text('0');
    $('#successRows').text('0');
    $('#errorRows').text('0');
    $('#duplicateRows').text('0');
    
    // Hide previous results
    $('#resultsCard').addClass('d-none');
    $('#viewErrorsBtn').hide();

    const formData = new FormData();
    formData.append('action', 'bulk_upload');
    formData.append('excel_file', selectedFile);

    isUploading = true;

    $.ajax({
        url: BASE_URL + 'ajax/bulk-upload/upgrade-sellers-bulk-upload.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    $('#uploadProgress').css('width', percent + '%').text(percent + '%');
                }
            }, false);
            return xhr;
        },
        
        success: function (response) {
            $('#uploadProgress').css('width', '100%').text('100%');
            
            if (response.status === 'success') {
                const data = response.data;
                
                $('#totalRows').text(data.total_rows);
                $('#successRows').text(data.success_count);
                $('#errorRows').text(data.error_count);
                $('#duplicateRows').text(data.duplicates_count || 0);
                
                $('#resultsCard').removeClass('d-none');
                
                // Build results HTML
                let resultsHtml = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Success!</strong> ${data.success_count} records imported successfully.
                    </div>`;
                
                if (data.duplicates_count > 0) {
                    resultsHtml += `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>${data.duplicates_count} duplicate entries</strong> were skipped.
                        </div>`;
                }
                
                if (data.error_count > 0) {
                    $('#viewErrorsBtn').show();
                    resultsHtml += `
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle-fill me-2"></i>
                            <strong>${data.error_count} errors</strong> occurred during import.
                        </div>`;
                    
                    // Populate error modal
                    let errorHtml = '';
                    data.errors.forEach(function(error) {
                        errorHtml += `<tr>
                            <td>${error.row}</td>
                            <td>${error.seller || '-'}</td>
                            <td>${error.error}</td>
                        </tr>`;
                    });
                    $('#errorList').html(errorHtml);
                } else {
                    // Show success modal
                    $('#successModalMessage').text('All records imported successfully!');
                    $('#successModalDetails').text(`${data.success_count} records were added to the database.`);
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                }
                
                $('#resultsContent').html(resultsHtml);
                
                showToast('success', 'Success!', `Upload completed. ${data.success_count} records imported.`);
                
                // Refresh stats
                loadStats();
                
            } else {
                showToast('danger', 'Error!', response.message);
                $('#resultsCard').removeClass('d-none');
                $('#resultsContent').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-x-circle-fill me-2"></i>
                        ${response.message}
                    </div>
                `);
            }
            
            isUploading = false;
            
            // Reset form after 3 seconds
            setTimeout(function() {
                selectedFile = null;
                $('#excelFile').val('');
                $('#fileInfo').addClass('d-none');
                $('#uploadBtn').prop('disabled', true);
                $('#progressCard').addClass('d-none');
            }, 3000);
        },
        
        error: function (xhr, status, error) {
            let errorMsg = 'Upload failed: ' + error;
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    errorMsg = response.message;
                }
            } catch (e) {
                // Use default error message
            }
            
            showToast('danger', 'Error!', errorMsg);
            $('#uploadProgress').css('width', '0%').text('0%');
            isUploading = false;
            
            $('#resultsCard').removeClass('d-none');
            $('#resultsContent').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    ${errorMsg}
                </div>
            `);
        }
    });
});

/* -----------------------------
   VIEW ERRORS BUTTON
----------------------------- */
$('#viewErrorsBtn').on('click', function () {
    const modal = new bootstrap.Modal(document.getElementById('errorModal'));
    modal.show();
});

/* -----------------------------
   FILE SIZE FORMAT
----------------------------- */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/* -----------------------------
   TOAST MESSAGE
----------------------------- */
function showToast(type, title, message) {
    const id = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 
                    type === 'danger' ? 'bg-danger' : 
                    type === 'warning' ? 'bg-warning' : 'bg-info';

    const html = `
    <div id="${id}" class="toast text-white ${bgClass}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
        <div class="toast-header ${bgClass} text-white">
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    </div>`;

    $('.toast-container').append(html);
    const toast = new bootstrap.Toast(document.getElementById(id));
    toast.show();
    
    // Remove from DOM after hidden
    $(`#${id}`).on('hidden.bs.toast', function () {
        $(this).remove();
    });
}

// Auto-refresh stats every 30 seconds
setInterval(loadStats, 30000);

});