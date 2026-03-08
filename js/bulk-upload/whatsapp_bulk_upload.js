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

    $.ajax({

        url: BASE_URL + 'ajax/bulk-upload/whatsapp_bulk_upload.php',

        type: 'POST',
        data: formData,

        processData: false,
        contentType: false,
        dataType: 'json',

        success: function (response) {

            if (response.status === 'success') {

                $('#totalRows').text(response.data.total_rows);
                $('#successRows').text(response.data.success_count);
                $('#errorRows').text(response.data.error_count);

                showToast('success', 'Success!', 'Upload completed');

            } else {

                showToast('danger', 'Error!', response.message);

            }

            isUploading = false;

        },

        error: function () {

            showToast('danger', 'Error!', 'Upload failed');
            isUploading = false;

        }

    });

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

    const html = `
    <div id="${id}" class="toast text-white bg-${type}">
        <div class="toast-body">
            <strong>${title}</strong> ${message}
        </div>
    </div>`;

    $('.toast-container').append(html);

    const toast = new bootstrap.Toast(document.getElementById(id));

    toast.show();

}


});
