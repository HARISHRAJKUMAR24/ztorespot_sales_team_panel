// js/logout.js
document.addEventListener("DOMContentLoaded", function() {
    console.log("Logout.js loaded");
    
    const logoutLink = document.getElementById("logoutLink");
    
    if (logoutLink) {
        logoutLink.addEventListener("click", function(e) {
            e.preventDefault();
            
            console.log("Logout clicked");
            
            // Check if BASE_URL is defined
            if (typeof BASE_URL === 'undefined') {
                console.error("BASE_URL is not defined!");
                showToast('danger', 'Error!', 'Configuration error. Please refresh the page.');
                return;
            }
            
            // Show beautiful Bootstrap confirmation modal
            showLogoutConfirmation();
        });
    }
    
    // Function to show logout confirmation modal
    function showLogoutConfirmation() {
        // Remove existing modal if any
        const existingModal = document.getElementById('logoutConfirmModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Create beautiful responsive modal HTML with proper accessibility
        const modalHTML = `
            <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content border-0 shadow-lg">
                        <!-- Header with proper labeling -->
                        <div class="modal-header border-0 pb-0">
                            <h5 id="logoutConfirmModalTitle" class="visually-hidden">Sign Out Confirmation</h5>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <div class="modal-body text-center p-4 pt-0">
                            <!-- Animated Icon -->
                            <div class="mb-4">
                                <div class="rounded-circle bg-danger bg-opacity-10 p-3 d-inline-block">
                                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-danger" aria-hidden="true">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                        <polyline points="16 17 21 12 16 7" />
                                        <line x1="21" y1="12" x2="9" y2="12" />
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Title (visible for sighted users) -->
                            <h5 class="fw-bold mb-2">Sign Out?</h5>
                            
                            <!-- Message -->
                            <p class="text-muted small mb-4">
                                Are you sure you want to sign out? You'll need to login again to access your Panel.
                            </p>
                            
                            <!-- Buttons Stack -->
                            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                                <button type="button" class="btn btn-light fw-semibold py-2 px-4" data-bs-dismiss="modal">
                                    Cancel
                                </button>
                                <button type="button" class="btn btn-danger fw-semibold py-2 px-4" id="confirmLogoutBtn">
                                    Sign Out
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Get modal element
        const modalElement = document.getElementById('logoutConfirmModal');
        
        // Initialize modal with options
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false,
            focus: true // Ensures focus is properly managed
        });
        
        // Handle focus management when modal is shown
        modalElement.addEventListener('shown.bs.modal', function() {
            // Focus on the cancel button by default (safer than close button)
            const cancelBtn = modalElement.querySelector('.btn-light');
            if (cancelBtn) {
                cancelBtn.focus();
            }
        });
        
        // Show modal
        modal.show();
        
        // Handle confirm button click
        document.getElementById('confirmLogoutBtn').addEventListener('click', function() {
            // Hide modal
            modal.hide();
            
            // Disable the logout link
            const logoutLink = document.getElementById('logoutLink');
            if (logoutLink) {
                logoutLink.style.pointerEvents = 'none';
                logoutLink.style.opacity = '0.6';
            }
            
            // Send logout request
            performLogout();
        });
        
        // Clean up modal after hidden
        modalElement.addEventListener('hidden.bs.modal', function() {
            modalElement.remove();
            
            // Return focus to logout link
            const logoutLink = document.getElementById('logoutLink');
            if (logoutLink) {
                logoutLink.focus();
            }
        });
    }
    
    // Function to perform logout
    function performLogout() {
        // Send AJAX request
        fetch(BASE_URL + "ajax/logout.php", {
            method: "POST",
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log("Response status:", response.status);
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }
            return response.json();
        })
        .then(data => {
            console.log("Response data:", data);
            
            if (data.status === "success") {
                // Show success toast
                showToast('success', 'Success!', 'Logged out successfully');
                
                // Redirect to login page after 1 second
                setTimeout(() => {
                    window.location.href = BASE_URL + "auth/login.php";
                }, 1000);
            } else {
                // Show error toast
                showToast('danger', 'Error!', data.message || "Logout failed");
                
                // Re-enable the link
                const logoutLink = document.getElementById('logoutLink');
                if (logoutLink) {
                    logoutLink.style.pointerEvents = 'auto';
                    logoutLink.style.opacity = '1';
                }
            }
        })
        .catch(error => {
            console.error("Error:", error);
            
            // Show error toast
            showToast('danger', 'Error!', 'An error occurred. Please try again.');
            
            // Re-enable the link
            const logoutLink = document.getElementById('logoutLink');
            if (logoutLink) {
                logoutLink.style.pointerEvents = 'auto';
                logoutLink.style.opacity = '1';
            }
        });
    }
    
    // Function to show Bootstrap toast
    function showToast(type, title, message) {
        // Get or create toast container
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '1060';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'toast-' + Date.now();
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'} me-2" aria-hidden="true"></i>
                        <strong>${title}</strong> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        if (toastElement) {
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Remove toast after it's hidden
            toastElement.addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }
    }
});