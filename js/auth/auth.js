document.addEventListener("DOMContentLoaded", function() {
    console.log("Auth.js loaded");
    
    // ========== PASSWORD VISIBILITY TOGGLE ==========
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener("click", function() {
            // Toggle password type
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);
            
            // Toggle icon
            const icon = this.querySelector("i");
            if (icon) {
                icon.classList.toggle("bi-eye");
                icon.classList.toggle("bi-eye-slash");
            }
        });
    }
    
    // ========== LOGIN FORM HANDLER ==========
    const loginForm = document.getElementById("loginForm");
    
    if (loginForm) {
        loginForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            console.log("Login form submitted");
            
            // Clear previous message div if it exists (for backward compatibility)
            const messageDiv = document.getElementById("loginMessage");
            if (messageDiv) {
                messageDiv.innerHTML = '';
            }
            
            // Clear any existing toasts - check if container exists first
            const toastContainer = document.querySelector('.toast-container');
            if (toastContainer) {
                toastContainer.innerHTML = '';
            } else {
                // Create toast container if it doesn't exist
                const newContainer = document.createElement('div');
                newContainer.className = 'toast-container';
                document.body.appendChild(newContainer);
            }
            
            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';
            
            let formData = new FormData(this);
            
            // Log form data for debugging
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            // Use absolute URL with .php extension
            fetch(BASE_URL + "ajax/auth/login.php", {
                method: "POST",
                body: formData
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
                    showToast('success', 'Success!', 'Login successful! Redirecting...');
                    
                    setTimeout(() => {
                        window.location.href = BASE_URL + "index.php";
                    }, 1500);
                } else {
                    // Show error toast
                    showToast('danger', 'Error!', data.message || "Login failed");
                    
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error("Error:", error);
                
                // Show error toast
                showToast('danger', 'Error!', 'An error occurred. Please try again.');
                
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
    
    // ========== TOAST FUNCTION ==========
    function showToast(type, title, message) {
        // Get or create toast container
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }
        
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