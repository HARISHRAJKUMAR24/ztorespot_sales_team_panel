// js/forgot-password.js
$(document).ready(function() {
    let timerInterval;
    let currentEmail = '';
    
    // Email Form Submit
    $('#emailForm').on('submit', function(e) {
        e.preventDefault();
        
        const email = $('#email').val();
        currentEmail = email;
        
        if (!validateEmail(email)) {
            showToast('danger', 'Error!', 'Please enter a valid email');
            return;
        }
        
        const $btn = $('#sendOtpBtn');
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('Sending...');
        
        $.ajax({
            url: BASE_URL + 'ajax/forgot-password.php',
            type: 'POST',
            data: {
                action: 'send_otp',
                email: email
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', response.message);
                    
                    // Show debug OTP if available
                    if (response.debug_otp) {
                        $('#otpValue').text(response.debug_otp);
                        $('#debugOtp').slideDown();
                    }
                    
                    // Move to step 2
                    $('#step1').removeClass('active').addClass('completed');
                    $('#step2').addClass('active');
                    $('#step1Form').slideUp();
                    $('#step2Form').slideDown();
                    
                    // Start timer
                    startTimer(600);
                } else {
                    showToast('danger', 'Error!', response.message);
                }
                $btn.prop('disabled', false).text(originalText);
            },
            error: function(xhr, status, error) {
                showToast('danger', 'Error!', 'Failed to send OTP');
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Verify OTP
    $('#verifyOtpBtn').on('click', function() {
        const otp = $('#otp').val();
        
        if (!otp || otp.length !== 6) {
            showToast('danger', 'Error!', 'Please enter 6-digit OTP');
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('Verifying...');
        
        $.ajax({
            url: BASE_URL + 'ajax/forgot-password.php',
            type: 'POST',
            data: {
                action: 'verify_otp',
                email: currentEmail,
                otp: otp
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', response.message);
                    
                    // Hide debug OTP
                    $('#debugOtp').slideUp();
                    
                    // Move to step 3
                    $('#step2').removeClass('active').addClass('completed');
                    $('#step3').addClass('active');
                    $('#step2Form').slideUp();
                    $('#step3Form').slideDown();
                    $('#resetEmail').val(currentEmail);
                    $('#otpVerified').val('yes');
                    
                    clearInterval(timerInterval);
                } else {
                    showToast('danger', 'Error!', response.message);
                }
                $btn.prop('disabled', false).text(originalText);
            },
            error: function() {
                showToast('danger', 'Error!', 'Verification failed');
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Resend OTP
    $('#resendOtpBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Resending...');
        
        $.ajax({
            url: BASE_URL + 'ajax/forgot-password.php',
            type: 'POST',
            data: {
                action: 'send_otp',
                email: currentEmail
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', 'OTP resent successfully');
                    
                    if (response.debug_otp) {
                        $('#otpValue').text(response.debug_otp);
                        $('#debugOtp').slideDown();
                    }
                    
                    resetTimer();
                } else {
                    showToast('danger', 'Error!', response.message);
                }
                $btn.prop('disabled', false).text('Resend OTP');
            },
            error: function() {
                showToast('danger', 'Error!', 'Failed to resend OTP');
                $btn.prop('disabled', false).text('Resend OTP');
            }
        });
    });
    
    // Change Email
    $('#changeEmailBtn').on('click', function() {
        $('#step2').removeClass('active');
        $('#step1').addClass('active').removeClass('completed');
        $('#step2Form').slideUp();
        $('#step1Form').slideDown();
        $('#otp').val('');
        $('#debugOtp').slideUp();
        clearInterval(timerInterval);
    });
    
    // Reset Password Form Submit
// Reset Password Form Submit
$('#resetPasswordForm').on('submit', function(e) {
    e.preventDefault();
    
    const password = $('#newPassword').val();
    const confirm = $('#confirmPassword').val();
    
    if (password.length < 6) {
        showToast('danger', 'Error!', 'Password must be at least 6 characters');
        return;
    }
    
    if (password !== confirm) {
        showToast('danger', 'Error!', 'Passwords do not match');
        return;
    }
    
    const $btn = $('#resetPasswordBtn');
    const originalText = $btn.text();
    $btn.prop('disabled', true).text('Resetting...');
    
    $.ajax({
        url: BASE_URL + 'ajax/forgot-password.php',
        type: 'POST',
        data: {
            action: 'reset_password',
            email: currentEmail,
            password: password,
            confirm_password: confirm
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Show success message
                $('#step3').removeClass('active').addClass('completed');
                $('#step3Form').slideUp();
                
                // Show success message with auto redirect
                const successHtml = `
                    <div class="text-center">
                        <div class="mb-3">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M8 12L11 15L16 9"/>
                            </svg>
                        </div>
                        <h5 class="text-success">Password Reset Successfully!</h5>
                        <p class="text-muted mb-3">Your password has been changed.</p>
                        <p class="text-muted small">Redirecting to login page in <span id="countdown">3</span> seconds...</p>
                    </div>
                `;
                
                $('#successMessage').html(successHtml).slideDown();
                
                // Countdown and redirect
                let countdown = 3;
                const timer = setInterval(function() {
                    countdown--;
                    $('#countdown').text(countdown);
                    
                    if (countdown <= 0) {
                        clearInterval(timer);
                        window.location.href = BASE_URL + 'auth/login.php';
                    }
                }, 1000);
                
            } else {
                showToast('danger', 'Error!', response.message);
                $btn.prop('disabled', false).text(originalText);
            }
        },
        error: function() {
            showToast('danger', 'Error!', 'Password reset failed');
            $btn.prop('disabled', false).text(originalText);
        }
    });
});
    
    // Timer functions
    function startTimer(seconds) {
        const timerDisplay = $('#timer');
        let remaining = seconds;
        
        clearInterval(timerInterval);
        timerInterval = setInterval(function() {
            const minutes = Math.floor(remaining / 60);
            const secs = remaining % 60;
            timerDisplay.text(`OTP expires in: ${minutes}:${secs < 10 ? '0' : ''}${secs}`);
            
            if (remaining <= 0) {
                clearInterval(timerInterval);
                timerDisplay.text('OTP expired');
                $('#resendOtpBtn').show();
                $('#verifyOtpBtn').prop('disabled', true);
            }
            remaining--;
        }, 1000);
    }
    
    function resetTimer() {
        clearInterval(timerInterval);
        startTimer(600);
        $('#verifyOtpBtn').prop('disabled', false);
    }
    
    // Email validation
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Toast function
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
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});