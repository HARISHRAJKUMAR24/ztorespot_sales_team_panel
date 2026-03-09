$(document).ready(function () {
    let selectedFile = null;
    let isUploading = false;

    /* -----------------------------
       DOWNLOAD SAMPLE FILE
    ----------------------------- */
    $('#downloadSampleBtn').on('click', function (e) {
        e.preventDefault();
        window.location.href = BASE_URL + 'bulk-upload/download_sales_person_sample.php';
    });

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
       FILE INPUT CHANGE
    ----------------------------- */
    $('#excelFile').on('change', function () {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    /* -----------------------------
       REMOVE FILE
    ----------------------------- */
    $('#removeFileBtn').on('click', function () {
        selectedFile = null;
        $('#excelFile').val('');
        $('#fileInfo').addClass('d-none');
        $('#uploadBtn').prop('disabled', true);
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
       UPLOAD FORM
    ----------------------------- */
    $('#bulkUploadForm').on('submit', function (e) {
        e.preventDefault();

        if (!selectedFile) {
            showToast('warning', 'Warning!', 'Select a file first');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'bulk_upload');
        formData.append('excel_file', selectedFile);

        isUploading = true;
        $('#uploadBtn').prop('disabled', true);
        $('#progressCard').removeClass('d-none');
        $('#resultsCard').addClass('d-none');
        $('#viewErrorsBtn').hide();

        // Reset progress
        $('#uploadProgress').css('width', '0%').text('0%');
        $('#totalRows').text('0');
        $('#validRows').text('0');
        $('#successRows').text('0');
        $('#errorRows').text('0');

        $.ajax({
            url: BASE_URL + 'ajax/bulk-upload/sales_person_sellers_bulk_upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',

            xhr: function () {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        $('#uploadProgress').css('width', percentComplete + '%').text(percentComplete + '%');
                    }
                }, false);
                return xhr;
            },

            success: function (response) {
                if (response.status === 'success') {
                    $('#totalRows').text(response.data.total_rows);
                    $('#validRows').text(response.data.valid_rows);
                    $('#successRows').text(response.data.success_count);
                    $('#errorRows').text(response.data.error_count);
                    
                    $('#uploadProgress').css('width', '100%').text('100%');
                    
                    let resultsHtml = '<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>';
                    resultsHtml += '<strong>Import completed!</strong><br>';
                    resultsHtml += 'Total rows in file: ' + response.data.total_rows + '<br>';
                    resultsHtml += 'Valid data rows: ' + response.data.valid_rows + '<br>';
                    resultsHtml += 'Successfully imported: ' + response.data.success_count + '<br>';
                    resultsHtml += 'Failed: ' + response.data.error_count;
                    
                    if (response.data.batch_id) {
                        resultsHtml += '<br><small class="text-muted">Batch ID: ' + response.data.batch_id + '</small>';
                    }
                    
                    resultsHtml += '</div>';
                    
                    $('#resultsContent').html(resultsHtml);
                    $('#resultsCard').removeClass('d-none');
                    
                    if (response.data.errors && response.data.errors.length > 0) {
                        $('#viewErrorsBtn').show();
                        displayErrors(response.data.errors);
                    } else {
                        // Auto redirect after 3 seconds if no errors
                        setTimeout(function() {
                          //  window.location.href = BASE_URL + 'bulk-upload/sales_person_sellers_list.php';
                        }, 3000);
                    }

                    showToast('success', 'Success!', 'Upload completed successfully');
                    
                    // Reset form
                    $('#removeFileBtn').click();
                    
                } else {
                    showToast('danger', 'Error!', response.message);
                }

                isUploading = false;
                $('#uploadBtn').prop('disabled', false);
            },

            error: function (xhr, status, error) {
                console.error('Upload error:', error);
                console.error('Response:', xhr.responseText);
                showToast('danger', 'Error!', 'Upload failed: ' + error);
                isUploading = false;
                $('#uploadBtn').prop('disabled', false);
            }
        });
    });

    /* -----------------------------
       DISPLAY ERRORS
    ----------------------------- */
    function displayErrors(errors) {
        let html = '';
        errors.forEach(function (error) {
            html += '<tr>';
            html += '<td>' + error.row + '</td>';
            html += '<td>' + (error.seller || '') + '</td>';
            html += '<td>' + error.error + '</td>';
            html += '</tr>';
        });
        $('#errorList').html(html);
    }

    /* -----------------------------
       VIEW ERRORS BUTTON
    ----------------------------- */
    $('#viewErrorsBtn').on('click', function () {
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        errorModal.show();
    });

    /* -----------------------------
       SAMPLE FORMAT LINK
    ----------------------------- */
    $('#sampleFormatLink').on('click', function (e) {
        e.preventDefault();
        $('#downloadSampleBtn').click();
    });

    /* -----------------------------
       FILE SIZE FORMAT
    ----------------------------- */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
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
        const html = `
        <div id="${id}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;

        $('.toast-container').append(html);
        const toast = new bootstrap.Toast(document.getElementById(id), { delay: 5000 });
        toast.show();

        setTimeout(function () {
            $('#' + id).remove();
        }, 5000);
    }
});