$(document).ready(function () {

let selectedFile = null;
let isUploading = false;

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
    if (file.size > 10 * 1024 * 1024) {
        showToast('danger', 'Error!', 'File must be under 10MB');
        return;
    }

    const valid = ['xlsx', 'xls', 'csv'];
    const ext = file.name.split('.').pop().toLowerCase();

    if (!valid.includes(ext)) {
        showToast('danger', 'Error!', 'Invalid file format');
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
    window.location.href = BASE_URL + 'ajax/bulk-upload/download_sample_registered_sellers.php';
});

/* -----------------------------
   UPLOAD FORM SUBMIT
----------------------------- */
$('#bulkUploadForm').on('submit', function (e) {
    e.preventDefault();

    if (!selectedFile) {
        showToast('warning', 'Warning!', 'Select a file first');
        return;
    }

    // Show progress card
    $('#progressCard').removeClass('d-none');
    $('#uploadProgress').css('width', '10%').text('10%');
    $('#totalRows').text('0');
    $('#successRows').text('0');
    $('#errorRows').text('0');

    const formData = new FormData();
    formData.append('action', 'bulk_upload');
    formData.append('excel_file', selectedFile);

    isUploading = true;

    $.ajax({
        url: BASE_URL + 'ajax/bulk-upload/register-sellers-bulk-upload.php',
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
                $('#totalRows').text(response.data.total_rows);
                $('#successRows').text(response.data.success_count);
                $('#errorRows').text(response.data.error_count);
                
                $('#resultsCard').removeClass('d-none');
                
                let resultsHtml = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Successfully imported ${response.data.success_count} records.
                    </div>`;
                
                if (response.data.duplicates_count > 0) {
                    resultsHtml += `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            ${response.data.duplicates_count} duplicate entries were skipped.
                        </div>`;
                }
                
                if (response.data.error_count > 0) {
                    $('#viewErrorsBtn').show();
                    resultsHtml += `
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle-fill me-2"></i>
                            ${response.data.error_count} errors occurred during import.
                        </div>`;
                    
                    // Populate error modal
                    let errorHtml = '';
                    response.data.errors.forEach(function(error) {
                        errorHtml += `<tr><td>${error.row}</td><td>${error.error}</td></tr>`;
                    });
                    $('#errorList').html(errorHtml);
                }
                
                $('#resultsContent').html(resultsHtml);
                
                showToast('success', 'Success!', 'Upload completed');
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
            showToast('danger', 'Error!', 'Upload failed: ' + error);
            $('#uploadProgress').css('width', '0%').text('0%');
            isUploading = false;
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
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
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
    <div id="${id}" class="toast text-white ${bgClass}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
        <div class="toast-body">
            <strong>${title}</strong> ${message}
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

});