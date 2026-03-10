$(document).ready(function() {
    
    // Customer Response Change Handler
    $('#customerResponse').on('change', function() {
        const response = $(this).val();
        const container = $('#dynamicFieldsContainer');
        container.empty(); // Clear previous dynamic fields
        
        let html = '';
        
        switch(response) {
            case 'Plan Interested':
                html = `
                    <div class="dynamic-field">
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-star-fill text-warning me-1"></i>
                                    Select Plan
                                </label>
                                <select class="form-select" id="selectedPlan" required>
                                    <option value="" selected disabled>Choose a plan</option>
                                    <option value="Welcome Plan">Welcome Plan</option>
                                    <option value="Starter Plan">Starter Plan</option>
                                    <option value="Professional Plan">Professional Plan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                `;
                break;
                
            case 'Plan Upgraded':
                html = `
                    <div class="dynamic-field">
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-arrow-up-circle-fill text-success me-1"></i>
                                    Upgraded Plan
                                </label>
                                <select class="form-select" id="upgradedPlan" required>
                                    <option value="" selected disabled>Choose upgraded plan</option>
                                    <option value="Welcome Plan">Welcome Plan</option>
                                    <option value="Starter Plan">Starter Plan</option>
                                    <option value="Professional Plan">Professional Plan</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-calendar-check-fill text-info me-1"></i>
                                    Duration
                                </label>
                                <select class="form-select" id="upgradedDuration" required>
                                    <option value="" selected disabled>Select duration</option>
                                    <option value="1 Month">1 Month</option>
                                    <option value="3 Months">3 Months</option>
                                    <option value="6 Months">6 Months</option>
                                    <option value="1 Year">1 Year</option>
                                    <option value="2 Years">2 Years</option>
                                </select>
                            </div>
                        </div>
                    </div>
                `;
                break;
                
            case 'Later':
                html = `
                    <div class="dynamic-field">
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-clock-fill text-primary me-1"></i>
                                    Call Back Time
                                </label>
                                <select class="form-select" id="callBackTime" required>
                                    <option value="" selected disabled>Select when to call back</option>
                                    <option value="After 1 hour">After 1 hour</option>
                                    <option value="After 2 hours">After 2 hours</option>
                                    <option value="After 3 hours">After 3 hours</option>
                                    <option value="After 6 hours">After 6 hours</option>
                                    <option value="Tomorrow">Tomorrow</option>
                                    <option value="After 2 days">After 2 days</option>
                                    <option value="After 1 week">After 1 week</option>
                                    <option value="Next month">Next month</option>
                                </select>
                            </div>
                        </div>
                    </div>
                `;
                break;
                
            case 'CNP':
            case 'Switch Off':
            case 'Out of Service':
            case 'Testing':
            case 'Renewals':
            case 'Not interested':
            case 'No Business':
            case 'Whatsapp Details sent':
            case 'Call Back AT':
                // No additional fields needed for these options
                break;
        }
        
        container.html(html);
    });

    // Form Reset Handler
    $('button[type="reset"]').on('click', function(e) {
        e.preventDefault();
        $('#sellerForm')[0].reset();
        $('#dynamicFieldsContainer').empty();
    });

    // Form Submit Handler
    $('#sellerForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate required fields
        const businessName = $('#businessName').val().trim();
        const phoneNumber = $('#phoneNumber').val().trim();
        const customerResponse = $('#customerResponse').val();
        
        if (!businessName) {
            showToast('warning', 'Warning!', 'Business Name is required');
            $('#businessName').focus();
            return;
        }
        
        if (!phoneNumber) {
            showToast('warning', 'Warning!', 'Phone Number is required');
            $('#phoneNumber').focus();
            return;
        }
        
        if (!customerResponse) {
            showToast('warning', 'Warning!', 'Customer Response is required');
            $('#customerResponse').focus();
            return;
        }
        
        // Validate phone number format
        if (!/^\d{10}$/.test(phoneNumber)) {
            showToast('warning', 'Warning!', 'Please enter a valid 10-digit phone number');
            $('#phoneNumber').focus();
            return;
        }
        
        // Validate dynamic fields based on response
        if (customerResponse === 'Plan Interested') {
            const selectedPlan = $('#selectedPlan').val();
            if (!selectedPlan) {
                showToast('warning', 'Warning!', 'Please select a plan');
                return;
            }
        }
        
        if (customerResponse === 'Plan Upgraded') {
            const upgradedPlan = $('#upgradedPlan').val();
            const upgradedDuration = $('#upgradedDuration').val();
            
            if (!upgradedPlan) {
                showToast('warning', 'Warning!', 'Please select the upgraded plan');
                return;
            }
            
            if (!upgradedDuration) {
                showToast('warning', 'Warning!', 'Please select the duration');
                return;
            }
        }
        
        if (customerResponse === 'Later') {
            const callBackTime = $('#callBackTime').val();
            if (!callBackTime) {
                showToast('warning', 'Warning!', 'Please select call back time');
                return;
            }
        }
        
        // Collect form data
        const formData = {
            business_name: businessName,
            seller_type: $('#sellerType').val() || '',
            phone_number: phoneNumber,
            customer_response: customerResponse,
            selected_plan: $('#selectedPlan').val() || '',
            upgraded_plan: $('#upgradedPlan').val() || '',
            upgraded_duration: $('#upgradedDuration').val() || '',
            call_back_time: $('#callBackTime').val() || '',
            customer_queries: $('#customerQueries').val().trim() || '',
            customer_status: $('#customerStatus').val() || '',
            call_duration: $('#callDuration').val().trim() || '',
            additional_notes: $('#additionalNotes').val().trim() || ''
        };
        
        // Show loading state
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.html();
        $submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...').prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: BASE_URL + 'ajax/work-station/workstation_add_seller.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', 'Seller added successfully');
                    $('#sellerForm')[0].reset();
                    $('#dynamicFieldsContainer').empty();
                    
                    // Optional: Redirect after 2 seconds
                    setTimeout(function() {
                       // window.location.href = BASE_URL + 'sellers/workstation_sellers_list.php';
                    }, 2000);
                } else {
                    showToast('danger', 'Error!', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                showToast('danger', 'Error!', 'Failed to save seller. Please try again.');
            },
            complete: function() {
                $submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Phone number validation
    $('#phoneNumber').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });

    // Toast notification function
    function showToast(type, title, message) {
        const id = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' :
                       type === 'danger' ? 'bg-danger' :
                       type === 'warning' ? 'bg-warning' : 'bg-info';
        
        const html = `
            <div id="${id}" class="toast text-white ${bgClass}" role="alert" aria-live="assertive" 
                 aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                <div class="toast-header ${bgClass} text-white border-0">
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        $('.toast-container').append(html);
        const toast = new bootstrap.Toast(document.getElementById(id));
        toast.show();
        
        $(`#${id}`).on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});