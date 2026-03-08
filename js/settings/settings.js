// js/settings.js
$(document).ready(function() {
    
    // Preview profile image before upload
    $('#profileImage').on('change', function() {
        const file = this.files[0];
        if (file) {
            // Check file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                showToast('danger', 'Error!', 'Image size must be less than 2MB');
                this.value = '';
                return;
            }
            
            // Check file type
            if (!file.type.match('image.*')) {
                showToast('danger', 'Error!', 'Please select an image file');
                this.value = '';
                return;
            }
            
            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#profilePreview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
            
            // Upload image
            uploadProfileImage(file);
        }
    });
    
    // Upload profile image
    function uploadProfileImage(file) {
        const formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('profile_image', file);
        
        $.ajax({
            url: BASE_URL + 'ajax/settings/settings.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function() {
                $('#imageMessage').html('<div class="alert alert-info py-2">Uploading...</div>');
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('#imageMessage').html('<div class="alert alert-success py-2">' + response.message + '</div>');
                    showToast('success', 'Success!', response.message);
                    
                    // Update image source with new path
                    if (response.image_path) {
                        $('#profilePreview').attr('src', response.image_path);
                    }
                } else {
                    $('#imageMessage').html('<div class="alert alert-danger py-2">' + response.message + '</div>');
                    showToast('danger', 'Error!', response.message);
                }
                setTimeout(function() {
                    $('#imageMessage').empty();
                }, 3000);
            },
            error: function(xhr, status, error) {
                console.error('Upload error:', error);
                console.log('Response:', xhr.responseText);
                $('#imageMessage').html('<div class="alert alert-danger py-2">Upload failed</div>');
                showToast('danger', 'Error!', 'Upload failed');
            }
        });
    }
    
    // Save settings form
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'update_profile');
        
        const $btn = $('#saveBtn');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        
        $.ajax({
            url: BASE_URL + 'ajax/settings/settings.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', response.message);
                    
                    // Update displayed name if changed
                    if (response.name) {
                        $('.user-name').text(response.name);
                    }
                } else {
                    showToast('danger', 'Error!', response.message);
                }
                $btn.prop('disabled', false).html(originalText);
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error);
                showToast('danger', 'Error!', 'Failed to save changes');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Toast function (top right)
    function showToast(type, title, message) {
        const toastId = 'toast-' + Date.now();
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'} me-2"></i>
                        <strong>${title}</strong> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        $('.toast-container').append(toastHTML);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});