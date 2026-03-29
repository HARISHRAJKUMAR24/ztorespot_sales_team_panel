$(document).ready(function () {

    // Store subscription plans data
    let subscriptionPlans = [];
    let currentPlanDurations = [];

    // Load subscription plans from database
    function loadSubscriptionPlans() {
        $.ajax({
            url: BASE_URL + 'ajax/work-station/get_subscription_plans.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    subscriptionPlans = response.data;
                    console.log('Loaded subscription plans:', subscriptionPlans);
                }
            },
            error: function () {
                console.error('Failed to load subscription plans');
                showToast('danger', 'Error!', 'Failed to load subscription plans');
            }
        });
    }

    // Load plans on page load
    loadSubscriptionPlans();

    // Get durations for a specific plan
    function getPlanDurations(planName) {
        const durations = subscriptionPlans
            .filter(plan => plan.plan_name === planName)
            .map(plan => ({
                duration: plan.duration,
                amount: parseFloat(plan.total_amount),
                plan_id: plan.id
            }));
        return durations;
    }

    // Customer Response Change Handler
    $('#customerResponse').on('change', function () {
        const response = $(this).val();
        const container = $('#dynamicFieldsContainer');
        container.empty(); // Clear previous dynamic fields

        let html = '';

        switch (response) {
            case 'Plan Interested':
                html = generatePlanInterestedFields();
                break;

            case 'Plan Upgraded':
                html = generatePlanUpgradedFields();
                // Auto set customer status to "Upgraded"
                $('#customerStatus').val('Upgraded');
                break;

            case 'Later':
                html = generateLaterFields();
                break;

            case 'Schedule':
                html = generateScheduleFields();
                break;

            default:
                // For other responses, no dynamic fields
                break;
        }

        container.html(html);

        // Initialize handlers
        initializePlanHandlers();

        // Initialize datepicker if schedule fields are shown
        if (response === 'Schedule') {
            initializeDatepicker();
        }
    });

    // Initialize plan selection handlers
    function initializePlanHandlers() {
        // For Plan Interested
        $(document).on('change', '#selectedPlan', function () {
            const planName = $(this).val();
            const durationSelect = $('#selectedDuration');
            
            if (planName && planName !== 'other') {
                const durations = getPlanDurations(planName);
                currentPlanDurations = durations;
                
                // Populate duration dropdown
                let durationHtml = '<option value="" selected disabled>Select duration</option>';
                durations.forEach(dur => {
                    durationHtml += `<option value="${dur.duration}" data-amount="${dur.amount}" data-plan-id="${dur.plan_id}">${dur.duration} - ₹${dur.amount.toFixed(2)}</option>`;
                });
                durationHtml += '<option value="other">Other (Custom Duration)</option>';
                durationSelect.html(durationHtml).prop('disabled', false);
                
                // Reset amount field
                $('#planAmount').val('');
                $('#planAmountDisplay').text('₹0.00');
                $('#selectedPlanId').val('');
            } else if (planName === 'other') {
                // Custom plan
                durationSelect.html('<option value="" selected disabled>Select duration</option><option value="other">Custom Duration</option>').prop('disabled', false);
                $('#customPlanContainer').show();
                $('#planAmount').val('').prop('readonly', false);
                $('#planAmountDisplay').text('₹0.00');
            } else {
                durationSelect.html('<option value="" selected disabled>First select a plan</option>').prop('disabled', true);
            }
        });

        $(document).on('change', '#selectedDuration', function () {
            const selectedOption = $(this).find('option:selected');
            const duration = $(this).val();
            
            if (duration && duration !== 'other') {
                const amount = parseFloat(selectedOption.data('amount'));
                const planId = parseInt(selectedOption.data('plan-id'));
                $('#planAmount').val(amount.toFixed(2));
                $('#planAmountDisplay').text('₹' + amount.toFixed(2));
                $('#selectedPlanId').val(planId);
                $('#selectedDurationValue').val(duration);
                $('#isCustomDuration').val(0);
            } else if (duration === 'other') {
                $('#planAmount').val('').prop('readonly', false);
                $('#planAmountDisplay').text('₹0.00');
                $('#customDurationContainerPlan').show();
                $('#selectedPlanId').val(0);
                $('#isCustomDuration').val(1);
            } else {
                $('#planAmount').val('');
                $('#planAmountDisplay').text('₹0.00');
                $('#selectedPlanId').val('');
            }
        });

        // For Plan Upgraded
        $(document).on('change', '#upgradedPlan', function () {
            const planName = $(this).val();
            const durationSelect = $('#upgradedDuration');
            
            if (planName && planName !== 'other') {
                const durations = getPlanDurations(planName);
                currentPlanDurations = durations;
                
                // Populate duration dropdown
                let durationHtml = '<option value="" selected disabled>Select duration</option>';
                durations.forEach(dur => {
                    durationHtml += `<option value="${dur.duration}" data-amount="${dur.amount}" data-plan-id="${dur.plan_id}">${dur.duration} - ₹${dur.amount.toFixed(2)}</option>`;
                });
                durationHtml += '<option value="other">Other (Custom Duration)</option>';
                durationSelect.html(durationHtml).prop('disabled', false);
                
                // Reset amount field
                $('#upgradedPlanAmount').val('');
                $('#upgradedPlanAmountDisplay').text('₹0.00');
                $('#upgradedPlanId').val('');
            } else if (planName === 'other') {
                durationSelect.html('<option value="" selected disabled>Select duration</option><option value="other">Custom Duration</option>').prop('disabled', false);
                $('#customUpgradedPlanContainer').show();
                $('#upgradedPlanAmount').val('').prop('readonly', false);
                $('#upgradedPlanAmountDisplay').text('₹0.00');
            } else {
                durationSelect.html('<option value="" selected disabled>First select a plan</option>').prop('disabled', true);
            }
        });

        $(document).on('change', '#upgradedDuration', function () {
            const selectedOption = $(this).find('option:selected');
            const duration = $(this).val();
            
            if (duration && duration !== 'other') {
                const amount = parseFloat(selectedOption.data('amount'));
                const planId = parseInt(selectedOption.data('plan-id'));
                $('#upgradedPlanAmount').val(amount.toFixed(2));
                $('#upgradedPlanAmountDisplay').text('₹' + amount.toFixed(2));
                $('#upgradedPlanId').val(planId);
                $('#upgradedDurationValue').val(duration);
                $('#isCustomDurationUpgraded').val(0);
            } else if (duration === 'other') {
                $('#upgradedPlanAmount').val('').prop('readonly', false);
                $('#upgradedPlanAmountDisplay').text('₹0.00');
                $('#customDurationContainer').show();
                $('#upgradedPlanId').val(0);
                $('#isCustomDurationUpgraded').val(1);
            } else {
                $('#upgradedPlanAmount').val('');
                $('#upgradedPlanAmountDisplay').text('₹0.00');
                $('#upgradedPlanId').val('');
            }
        });

        // Manual amount entry handlers
        $(document).on('input', '#planAmount', function () {
            const amount = $(this).val();
            if (amount) {
                $('#planAmountDisplay').text('₹' + parseFloat(amount).toFixed(2));
            } else {
                $('#planAmountDisplay').text('₹0.00');
            }
        });

        $(document).on('input', '#upgradedPlanAmount', function () {
            const amount = $(this).val();
            if (amount) {
                $('#upgradedPlanAmountDisplay').text('₹' + parseFloat(amount).toFixed(2));
            } else {
                $('#upgradedPlanAmountDisplay').text('₹0.00');
            }
        });

        // Custom duration handlers
        $(document).on('input', '#customDurationPlan', function () {
            $('#selectedDurationValue').val($(this).val());
        });

        $(document).on('input', '#customDuration', function () {
            $('#upgradedDurationValue').val($(this).val());
        });

        $(document).on('input', '#customPlan', function () {
            $('#selectedPlanName').val($(this).val());
        });

        $(document).on('input', '#customUpgradedPlan', function () {
            $('#upgradedPlanName').val($(this).val());
        });
    }

    // Generate Plan Interested Fields
    function generatePlanInterestedFields() {
        // Get unique plan names from subscription plans
        const uniquePlans = [];
        const planMap = new Map();
        
        subscriptionPlans.forEach(plan => {
            if (!planMap.has(plan.plan_name)) {
                planMap.set(plan.plan_name, plan);
                uniquePlans.push(plan);
            }
        });
        
        const planOptions = uniquePlans.map(plan => `<option value="${plan.plan_name}">${plan.plan_name}</option>`).join('');
        
        return `
            <div class="dynamic-field">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            Select Plan <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="selectedPlan" required>
                            <option value="" selected disabled>Choose a plan</option>
                            ${planOptions}
                            <option value="other">Other (Custom Plan)</option>
                        </select>
                        <div id="customPlanContainer" style="display: none;" class="mt-2 custom-field">
                            <label class="form-label">Enter Custom Plan Name:</label>
                            <input type="text" class="form-control" id="customPlan" 
                                placeholder="e.g., Premium Plan, Gold Plan etc.">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-check-fill text-info me-1"></i>
                            Duration <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="selectedDuration" disabled required>
                            <option value="" selected>First select a plan</option>
                        </select>
                        <div id="customDurationContainerPlan" style="display: none;" class="mt-2 custom-field">
                            <label class="form-label">Enter Custom Duration:</label>
                            <input type="text" class="form-control" id="customDurationPlan" 
                                placeholder="e.g., 45 days, 18 months, etc.">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-currency-rupee text-success me-1"></i>
                            Plan Amount <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" class="form-control" id="planAmount" 
                                placeholder="Enter plan amount" step="0.01" required>
                            <span class="input-group-text bg-light" id="planAmountDisplay">₹0.00</span>
                        </div>
                        <div class="form-text text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Amount will be auto-filled for standard plans
                        </div>
                    </div>
                </div>
                <input type="hidden" id="selectedPlanId" value="0">
                <input type="hidden" id="selectedPlanName" value="">
                <input type="hidden" id="selectedDurationValue" value="">
                <input type="hidden" id="isCustomDuration" value="0">
            </div>
        `;
    }

    // Generate Plan Upgraded Fields
    function generatePlanUpgradedFields() {
        // Get unique plan names from subscription plans
        const uniquePlans = [];
        const planMap = new Map();
        
        subscriptionPlans.forEach(plan => {
            if (!planMap.has(plan.plan_name)) {
                planMap.set(plan.plan_name, plan);
                uniquePlans.push(plan);
            }
        });
        
        const planOptions = uniquePlans.map(plan => `<option value="${plan.plan_name}">${plan.plan_name}</option>`).join('');
        
        return `
            <div class="dynamic-field">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-arrow-up-circle-fill text-success me-1"></i>
                            Upgraded Plan <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="upgradedPlan" required>
                            <option value="" selected disabled>Choose upgraded plan</option>
                            ${planOptions}
                            <option value="other">Other (Custom Plan)</option>
                        </select>
                        <div id="customUpgradedPlanContainer" style="display: none;" class="mt-2 custom-field">
                            <label class="form-label">Enter Custom Plan Name:</label>
                            <input type="text" class="form-control" id="customUpgradedPlan" 
                                placeholder="e.g., Premium Plan, Gold Plan etc.">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-check-fill text-info me-1"></i>
                            Duration <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="upgradedDuration" disabled required>
                            <option value="" selected>First select a plan</option>
                        </select>
                        <div id="customDurationContainer" style="display: none;" class="mt-2 custom-field">
                            <label class="form-label">Enter Custom Duration:</label>
                            <input type="text" class="form-control" id="customDuration" 
                                placeholder="e.g., 45 days, 18 months, etc.">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-currency-rupee text-success me-1"></i>
                            Plan Amount <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" class="form-control" id="upgradedPlanAmount" 
                                placeholder="Enter plan amount" step="0.01" required>
                            <span class="input-group-text bg-light" id="upgradedPlanAmountDisplay">₹0.00</span>
                        </div>
                        <div class="form-text text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Amount will be auto-filled for standard plans
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-cloud-upload text-primary me-1"></i>
                            Number of Products Uploaded
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-files"></i>
                            </span>
                            <input type="number" class="form-control border-start-0" 
                                id="productsUploaded" 
                                placeholder="Enter number of products (0-999)"
                                min="0" max="999" value="0">
                        </div>
                        <div class="form-text text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Enter 0 if no products uploaded yet
                        </div>
                    </div>
                </div>
                <input type="hidden" id="upgradedPlanId" value="0">
                <input type="hidden" id="upgradedPlanName" value="">
                <input type="hidden" id="upgradedDurationValue" value="">
                <input type="hidden" id="isCustomDurationUpgraded" value="0">
            </div>
        `;
    }

    // Generate Later Fields
    function generateLaterFields() {
        return `
            <div class="dynamic-field">
                <div class="row">
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-clock-fill text-primary me-1"></i>
                            Call Back Time <span class="text-danger">*</span>
                        </label>
                        <div class="callback-wrapper">
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
                                <option value="other">Other (Custom Time)</option>
                            </select>
                            <div id="customCallBackContainer" style="display: none;" class="mt-2 custom-field">
                                <label class="form-label">Enter Custom Call Back Time:</label>
                                <input type="text" class="form-control" id="customCallBackTime" 
                                    placeholder="e.g., After 4 hours, Next week, After 15 days, etc.">
                            </div>
                            <input type="hidden" id="finalCallBackTime">
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Generate Schedule Fields with Datepicker
    function generateScheduleFields() {
        return `
            <div class="dynamic-field">
                <div class="row">
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-date-fill text-primary me-1"></i>
                            Schedule Date <span class="text-danger">*</span>
                        </label>
                        <div class="schedule-wrapper">
                            <div class="input-group date" id="scheduleDatePicker">
                                <span class="input-group-text bg-light" id="calendarIcon">
                                    <i class="bi bi-calendar3"></i>
                                </span>
                                <input type="text" class="form-control" id="scheduleDate" 
                                    placeholder="Select schedule date" readonly required>
                                <span class="input-group-text bg-light" id="clearDate" style="cursor: pointer;">
                                    <i class="bi bi-x-lg"></i>
                                </span>
                            </div>
                            <div class="mt-2 text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                Select a date for follow-up schedule
                            </div>
                            <input type="hidden" id="finalScheduleDate">
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Initialize Datepicker
    function initializeDatepicker() {
        if ($('#scheduleDate').length) {
            $('#scheduleDate').datepicker({
                format: 'dd/mm/yyyy',
                startDate: new Date(),
                autoclose: true,
                todayHighlight: true,
                orientation: 'bottom'
            });

            $('#scheduleDate').on('change', function () {
                const selectedDate = $(this).val();
                if (selectedDate) {
                    $('#finalScheduleDate').val('Schedule at ' + selectedDate);
                }
            });

            $('#calendarIcon').click(function () {
                $('#scheduleDate').datepicker('show');
            });

            $('#clearDate').click(function () {
                $('#scheduleDate').val('');
                $('#finalScheduleDate').val('');
            });
        }
    }

    // Call Duration Handler
    $('#callDurationSelect').on('change', function () {
        if ($(this).val() === 'other') {
            $('#customCallDurationContainer').show();
            $('#customCallDuration').prop('required', true);
            $('#callDuration').val('');
        } else {
            $('#customCallDurationContainer').hide();
            $('#customCallDuration').prop('required', false);
            $('#callDuration').val($(this).val());
        }
    });

    $('#customCallDuration').on('input', function () {
        $('#callDuration').val($(this).val());
    });

    // Form Reset Handler
    $('button[type="reset"]').on('click', function (e) {
        e.preventDefault();
        $('#sellerForm')[0].reset();
        $('#dynamicFieldsContainer').empty();
        $('#customCallDurationContainer').hide();
        $('#callDuration').val('');
    });

    // Form Submit Handler
    $('#sellerForm').on('submit', function (e) {
        e.preventDefault();

        // Validate required fields
        const businessName = $('#businessName').val().trim();
        const phoneNumber = $('#phoneNumber').val().trim();
        const customerResponse = $('#customerResponse').val();
        const sellerID = $('#sellerID').val().trim() || '';

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

        if (!/^\d{10}$/.test(phoneNumber)) {
            showToast('warning', 'Warning!', 'Please enter a valid 10-digit phone number');
            $('#phoneNumber').focus();
            return;
        }

        // Prepare form data based on response
        let formData = {
            business_name: businessName,
            seller_type: $('#sellerType').val() || '',
            phone_number: phoneNumber,
            seller_id: sellerID,
            customer_response: customerResponse,
            products_uploaded: $('#productsUploaded').length ? $('#productsUploaded').val() || '0' : '0',
            customer_queries: $('#customerQueries').val().trim() || '',
            customer_status: $('#customerStatus').val() || '',
            call_duration: $('#callDuration').val() || '',
            additional_notes: $('#additionalNotes').val().trim() || ''
        };

        // Add plan data based on response
        if (customerResponse === 'Plan Interested') {
            const planId = $('#selectedPlanId').val();
            const planName = $('#selectedPlanName').val() || $('#selectedPlan').val();
            const planDuration = $('#selectedDurationValue').val() || $('#selectedDuration').val();
            const planAmount = $('#planAmount').val();
            const isCustomDuration = $('#isCustomDuration').val();

            if (!planName || planName === '') {
                showToast('warning', 'Warning!', 'Please select a plan');
                return;
            }

            if (!planDuration || planDuration === '') {
                showToast('warning', 'Warning!', 'Please select a duration');
                return;
            }

            if (!planAmount || parseFloat(planAmount) <= 0) {
                showToast('warning', 'Warning!', 'Please enter a valid plan amount');
                return;
            }

            formData.plan_id = planId;
            formData.plan_name = planName;
            formData.plan_duration = planDuration;
            formData.plan_amount = parseFloat(planAmount);
            formData.is_custom_duration = isCustomDuration;
            
            console.log('Plan Interested Data:', formData);
        }

        if (customerResponse === 'Plan Upgraded') {
            const planId = $('#upgradedPlanId').val();
            const planName = $('#upgradedPlanName').val() || $('#upgradedPlan').val();
            const planDuration = $('#upgradedDurationValue').val() || $('#upgradedDuration').val();
            const planAmount = $('#upgradedPlanAmount').val();
            const isCustomDuration = $('#isCustomDurationUpgraded').val();

            if (!planName || planName === '') {
                showToast('warning', 'Warning!', 'Please select a plan');
                return;
            }

            if (!planDuration || planDuration === '') {
                showToast('warning', 'Warning!', 'Please select a duration');
                return;
            }

            if (!planAmount || parseFloat(planAmount) <= 0) {
                showToast('warning', 'Warning!', 'Please enter a valid plan amount');
                return;
            }

            formData.plan_id = planId;
            formData.plan_name = planName;
            formData.plan_duration = planDuration;
            formData.plan_amount = parseFloat(planAmount);
            formData.is_custom_duration = isCustomDuration;
            
            console.log('Plan Upgraded Data:', formData);
        }

        if (customerResponse === 'Later') {
            const callBackTime = $('#finalCallBackTime').val() || $('#callBackTime').val();
            if (!callBackTime) {
                showToast('warning', 'Warning!', 'Please select or enter call back time');
                return;
            }
            formData.call_back_time = callBackTime;
        }

        if (customerResponse === 'Schedule') {
            const scheduleDate = $('#finalScheduleDate').val();
            if (!scheduleDate) {
                showToast('warning', 'Warning!', 'Please select a schedule date');
                return;
            }
            formData.call_back_time = scheduleDate;
        }

        console.log('Final Form Data:', formData);

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
            success: function (response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', 'Seller added successfully');
                    $('#sellerForm')[0].reset();
                    $('#dynamicFieldsContainer').empty();
                    $('#customCallDurationContainer').hide();
                } else {
                    showToast('danger', 'Error!', response.message);
                }
            },
            error: function (xhr, status, error) {
                let errorMessage = 'Failed to save seller. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    errorMessage = 'Server error: ' + xhr.responseText;
                }
                showToast('danger', 'Error!', errorMessage);
            },
            complete: function () {
                $submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Phone number validation
    $('#phoneNumber').on('input', function () {
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

        $(`#${id}`).on('hidden.bs.toast', function () {
            $(this).remove();
        });
    }
});