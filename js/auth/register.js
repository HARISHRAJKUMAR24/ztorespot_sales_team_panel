$(document).ready(function() {
    
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear any existing toasts
        $('.toast-container').empty();
        
        // Disable submit button
        const $submitBtn = $('#submitBtn');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Creating Account...');
        
        // Get form data
        const formData = new FormData(this);
        
        // Send AJAX request
        $.ajax({
            url: BASE_URL + 'ajax/auth/register.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);
                
                if (response.status === 'success') {
                    // Show success toast
                    showToast('success', 'Success!', response.msg);
                    
                    // Reset form
                    $('#registerForm')[0].reset();
                    
                    // Redirect after 2 seconds
                    setTimeout(function() {
                        window.location.href = BASE_URL + 'auth/login.php';
                    }, 2000);
                } else {
                    // Show error toast
                    showToast('danger', response.msg);
                    
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.log('Response:', xhr.responseText);
                
                // Show error toast
                showToast('danger', 'Error!', 'Something went wrong. Please try again.');
                
                // Re-enable submit button
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Function to show Bootstrap toast
    function showToast(type, title, message) {
        const toastId = 'toast-' + Date.now();
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                <div class="d-flex">
                    <div class="toast-body">
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
        
        // Remove toast after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});