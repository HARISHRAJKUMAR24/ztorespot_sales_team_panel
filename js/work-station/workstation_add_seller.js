$(document).ready(function () {
    // Store subscription plans data - FIXED: Use the global variable from PHP
    let subscriptionPlans = window.subscriptionPlans || [];
    let currentPlanDurations = [];

    console.log('Subscription Plans loaded in JS:', subscriptionPlans);

    // Customer Response Change Handler
    $('#customerResponse').on('change', function () {
        const response = $(this).val();
        const container = $('#dynamicFieldsContainer');
        const customResponseContainer = $('#customResponseContainer');
        
        // Handle custom response
        if (response === 'other') {
            customResponseContainer.show();
            $('#customResponse').prop('required', true);
        } else {
            customResponseContainer.hide();
            $('#customResponse').prop('required', false);
        }

        container.empty();

        let html = '';

        switch (response) {
            case 'Plan Interested':
                html = generatePlanInterestedFields();
                break;
            case 'Plan Upgraded':
                html = generatePlanUpgradedFields();
                $('#customerStatus').val('Upgraded');
                break;
            case 'Later':
                html = generateLaterFields();
                break;
            case 'Call Back AT':
                html = generateCallBackAtFields();
                break;
            case 'Schedule':
                html = generateScheduleFields();
                break;
            case 'Refund':
                html = generateRefundFields();
                $('#customerStatus').val('Refunded');
                break;
            case 'other':
                // No dynamic fields for custom response
                break;
            default:
                break;
        }

        container.html(html);
        initializePlanDropdowns();
        initializeOtherOptionHandlers();

        // Initialize datepicker if schedule fields are shown
        if (response === 'Schedule') {
            initializeDatepicker();
        }
        if (response === 'Refund') {
            initializeRefundDatepicker();
        }
    });

    // Generate Plan Interested Fields with subscription plans dropdown
    function generatePlanInterestedFields() {
        let planOptions = '<option value="" selected disabled>Choose a plan</option>';
        
        // Group plans by name
        const groupedPlans = {};
        subscriptionPlans.forEach(plan => {
            if (!groupedPlans[plan.plan_name]) {
                groupedPlans[plan.plan_name] = [];
            }
            groupedPlans[plan.plan_name].push(plan);
        });
        
        // Create optgroups for each plan with duration and amount
        for (const [planName, durations] of Object.entries(groupedPlans)) {
            planOptions += `<optgroup label="${planName}">`;
            durations.forEach(plan => {
                planOptions += `<option value="${plan.id}" data-duration="${plan.duration}" data-amount="${plan.total_amount}" data-plan-name="${plan.plan_name}">
                    ${plan.duration} - ₹${parseFloat(plan.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}
                </option>`;
            });
            planOptions += `</optgroup>`;
        }
        
        planOptions += '<option value="other">Other (Custom Plan)</option>';
        
        return `
            <div class="dynamic-field">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            Select Plan <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="selectedPlan" required>
                            ${planOptions}
                        </select>
                        <div id="customPlanContainer" style="display: none;" class="mt-2 custom-field">
                            <label class="form-label">Enter Custom Plan Name:</label>
                            <input type="text" class="form-control" id="customPlan" 
                                placeholder="e.g., Premium Plan, Gold Plan etc.">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-currency-rupee text-success me-1"></i>
                            Plan Amount <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" class="form-control" id="planAmount" 
                                placeholder="Plan amount" step="0.01" required>
                            <span class="input-group-text bg-light" id="planAmountDisplay">₹0.00</span>
                        </div>
                    </div>
                </div>
                
                <!-- Custom Duration - Only visible for "other" plan -->
                <div id="customDurationPlanContainer" style="display: none;" class="row mt-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-check-fill text-info me-1"></i>
                            Custom Duration <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="customDurationPlan" 
                            placeholder="e.g., 45 days, 18 months, 2 weeks, etc.">
                        <small class="text-muted">Enter custom duration for your plan</small>
                    </div>
                </div>
                
                <!-- Hidden field for duration -->
                <input type="hidden" id="selectedDuration" value="">
                
                <!-- DOUBTS FIELD -->
                <div class="row mt-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-question-circle text-warning me-1"></i>
                            Customer Doubts / Questions
                        </label>
                        <textarea class="form-control" id="customerDoubts" rows="3" 
                            placeholder="Enter any doubts, questions, or concerns raised by the customer about the plan..."></textarea>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            These will be stored in JSON format and visible in update history
                        </small>
                    </div>
                </div>
                
                <input type="hidden" id="selectedPlanId" value="0">
                <input type="hidden" id="selectedPlanName" value="">
            </div>
        `;
    }

    // Generate Plan Upgraded Fields with subscription plans dropdown
    function generatePlanUpgradedFields() {
        let planOptions = '<option value="" selected disabled>Choose upgraded plan</option>';
        
        const groupedPlans = {};
        subscriptionPlans.forEach(plan => {
            if (!groupedPlans[plan.plan_name]) {
                groupedPlans[plan.plan_name] = [];
            }
            groupedPlans[plan.plan_name].push(plan);
        });
        
        for (const [planName, durations] of Object.entries(groupedPlans)) {
            planOptions += `<optgroup label="${planName}">`;
            durations.forEach(plan => {
                planOptions += `<option value="${plan.id}" data-duration="${plan.duration}" data-amount="${plan.total_amount}" data-plan-name="${plan.plan_name}">
                    ${plan.duration} - ₹${parseFloat(plan.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}
                </option>`;
            });
            planOptions += `</optgroup>`;
        }
        
        planOptions += '<option value="other">Other (Custom Plan)</option>';
        
        return `
            <div class="dynamic-field">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-arrow-up-circle-fill text-success me-1"></i>
                            Upgraded Plan <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="upgradedPlan" required>
                            ${planOptions}
                        </select>
                        <div id="customUpgradedPlanContainer" style="display: none;" class="mt-2 custom-field">
                            <label class="form-label">Enter Custom Plan Name:</label>
                            <input type="text" class="form-control" id="customUpgradedPlan" 
                                placeholder="e.g., Premium Plan, Gold Plan etc.">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-currency-rupee text-success me-1"></i>
                            Plan Amount <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" class="form-control" id="upgradedPlanAmount" 
                                placeholder="Plan amount" step="0.01" required>
                            <span class="input-group-text bg-light" id="upgradedPlanAmountDisplay">₹0.00</span>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-cloud-upload text-primary me-1"></i>
                            Number of Products Uploaded
                        </label>
                        <input type="number" class="form-control" id="productsUploaded" 
                            placeholder="Enter number of products (0-999)"
                            min="0" max="999" value="0">
                    </div>
                    <div class="col-12 col-md-6">
                        <!-- Custom Duration - Only visible for "other" plan -->
                        <div id="customDurationContainer" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-calendar-check-fill text-info me-1"></i>
                                Custom Duration <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="customDuration" 
                                placeholder="e.g., 45 days, 18 months, 2 weeks, etc.">
                            <small class="text-muted">Enter custom duration for your plan</small>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden field for duration -->
                <input type="hidden" id="upgradedDuration" value="">
                
                <!-- DOUBTS FIELD -->
                <div class="row mt-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-question-circle text-warning me-1"></i>
                            Customer Doubts / Questions
                        </label>
                        <textarea class="form-control" id="customerDoubts" rows="3" 
                            placeholder="Enter any doubts, questions, or concerns raised by the customer about the plan upgrade..."></textarea>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            These will be stored in JSON format and visible in update history
                        </small>
                    </div>
                </div>
                
                <input type="hidden" id="upgradedPlanId" value="0">
                <input type="hidden" id="upgradedPlanName" value="">
            </div>
        `;
    }

    // Generate Call Back AT Fields
    function generateCallBackAtFields() {
        return `
            <div class="dynamic-field">
                <div class="row">
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-telephone-forward-fill text-primary me-1"></i>
                            Call Back At <span class="text-danger">*</span>
                        </label>
                        <div class="callback-at-wrapper">
                            <select class="form-select" id="callBackAt" required>
                                <option value="" selected disabled>Select call back time</option>
                                <option value="Morning 9-11 AM">Morning 9-11 AM</option>
                                <option value="Late Morning 11-1 PM">Late Morning 11-1 PM</option>
                                <option value="Afternoon 2-4 PM">Afternoon 2-4 PM</option>
                                <option value="Evening 4-6 PM">Evening 4-6 PM</option>
                                <option value="Night 7-9 PM">Night 7-9 PM</option>
                                <option value="other">Other (Custom Time)</option>
                            </select>
                            <div id="customCallBackAtContainer" style="display: none;" class="mt-2 custom-field">
                                <label class="form-label">Enter Custom Call Back Time:</label>
                                <input type="text" class="form-control" id="customCallBackAt" 
                                    placeholder="e.g., 10-12 AM, 3-5 PM, etc.">
                            </div>
                            <input type="hidden" id="finalCallBackAt">
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Generate Refund Fields
    function generateRefundFields() {
        return `
            <div class="dynamic-field">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-arrow-return-left text-danger me-1"></i>
                            Refund For Plan <span class="text-danger">*</span>
                        </label>
                        <div class="refund-plan-wrapper">
                            <select class="form-select" id="refundPlan" required>
                                <option value="" selected disabled>Select plan to refund</option>
                                <option value="Welcome">Welcome Plan</option>
                                <option value="Starter">Starter Plan</option>
                                <option value="Professional">Professional Plan</option>
                                <option value="Intermediate">Intermediate Plan</option>
                                <option value="other">Other (Custom Plan)</option>
                            </select>
                            <div id="customRefundPlanContainer" style="display: none;" class="mt-2 custom-field">
                                <label class="form-label">Enter Custom Plan Name:</label>
                                <input type="text" class="form-control" id="customRefundPlan" 
                                    placeholder="e.g., Premium Plan, Gold Plan etc.">
                            </div>
                            <input type="hidden" id="finalRefundPlan">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-cash-stack text-warning me-1"></i>
                            Refund Amount <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-currency-rupee"></i>
                            </span>
                            <input type="number" class="form-control border-start-0" 
                                id="refundAmount" 
                                placeholder="Enter refund amount"
                                min="0" step="0.01" value="0" required>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-event text-info me-1"></i>
                            Refund Date
                        </label>
                        <div class="input-group date" id="refundDatePicker">
                            <span class="input-group-text bg-light" id="refundCalendarIcon">
                                <i class="bi bi-calendar3"></i>
                            </span>
                            <input type="text" class="form-control" id="refundDate" 
                                placeholder="Select refund date" readonly>
                            <span class="input-group-text bg-light" id="clearRefundDate" style="cursor: pointer;">
                                <i class="bi bi-x-lg"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-chat-quote text-secondary me-1"></i>
                            Refund Reason
                        </label>
                        <textarea class="form-control" id="refundReason" 
                            placeholder="Enter reason for refund..." rows="2"></textarea>
                    </div>
                </div>
                <input type="hidden" id="finalRefundInfo">
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

    // Generate Schedule Fields
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

    // Initialize plan dropdown handlers
    function initializePlanDropdowns() {
        // For Plan Interested
        $('#selectedPlan').off('change').on('change', function () {
            const selectedValue = $(this).val();
            if (selectedValue === 'other') {
                $('#customPlanContainer').show();
                $('#customDurationPlanContainer').show();
                $('#planAmount').val('').prop('readonly', false);
                $('#planAmountDisplay').text('₹0.00');
                $('#selectedPlanId').val('0');
                $('#selectedPlanName').val('');
                $('#selectedDuration').val('');
            } else if (selectedValue && selectedValue !== '') {
                $('#customPlanContainer').hide();
                $('#customDurationPlanContainer').hide();
                const selectedOption = $(this).find('option:selected');
                const planId = selectedValue;
                const planName = selectedOption.data('plan-name') || selectedOption.parent().attr('label');
                const duration = selectedOption.data('duration');
                const amount = selectedOption.data('amount');
                
                $('#selectedPlanId').val(planId);
                $('#selectedPlanName').val(planName);
                $('#selectedDuration').val(duration);
                $('#planAmount').val(amount);
                $('#planAmountDisplay').text('₹' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2}));
            }
        });

        // For Plan Upgraded
        $('#upgradedPlan').off('change').on('change', function () {
            const selectedValue = $(this).val();
            if (selectedValue === 'other') {
                $('#customUpgradedPlanContainer').show();
                $('#customDurationContainer').show();
                $('#upgradedPlanAmount').val('').prop('readonly', false);
                $('#upgradedPlanAmountDisplay').text('₹0.00');
                $('#upgradedDuration').val('');
                $('#upgradedPlanId').val('0');
                $('#upgradedPlanName').val('');
            } else if (selectedValue && selectedValue !== '') {
                $('#customUpgradedPlanContainer').hide();
                $('#customDurationContainer').hide();
                const selectedOption = $(this).find('option:selected');
                const planId = selectedValue;
                const planName = selectedOption.data('plan-name') || selectedOption.parent().attr('label');
                const duration = selectedOption.data('duration');
                const amount = selectedOption.data('amount');
                
                $('#upgradedPlanId').val(planId);
                $('#upgradedPlanName').val(planName);
                $('#upgradedDuration').val(duration);
                $('#upgradedPlanAmount').val(amount);
                $('#upgradedPlanAmountDisplay').text('₹' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2}));
            }
        });

        // Custom duration handlers
        $('#customDurationPlan').off('input').on('input', function () {
            $('#selectedDuration').val($(this).val());
        });

        $('#customDuration').off('input').on('input', function () {
            $('#upgradedDuration').val($(this).val());
        });

        // Custom plan amount handlers
        $('#planAmount').off('input').on('input', function () {
            const amount = $(this).val();
            $('#planAmountDisplay').text(amount ? '₹' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '₹0.00');
        });

        $('#upgradedPlanAmount').off('input').on('input', function () {
            const amount = $(this).val();
            $('#upgradedPlanAmountDisplay').text(amount ? '₹' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '₹0.00');
        });
        
        // Custom plan name handlers
        $('#customPlan').off('input').on('input', function () {
            $('#selectedPlanName').val($(this).val());
        });
        
        $('#customUpgradedPlan').off('input').on('input', function () {
            $('#upgradedPlanName').val($(this).val());
        });
    }

    // Initialize "Other" option handlers
    function initializeOtherOptionHandlers() {
        // For Later
        $('#callBackTime').off('change').on('change', function () {
            const val = $(this).val();
            if (val === 'other') {
                $('#customCallBackContainer').show();
                $('#customCallBackTime').prop('required', true);
                $('#finalCallBackTime').val('');
            } else {
                $('#customCallBackContainer').hide();
                $('#customCallBackTime').prop('required', false);
                $('#finalCallBackTime').val(val);
            }
        });

        // For Call Back AT
        $('#callBackAt').off('change').on('change', function () {
            const val = $(this).val();
            if (val === 'other') {
                $('#customCallBackAtContainer').show();
                $('#customCallBackAt').prop('required', true);
                $('#finalCallBackAt').val('');
            } else {
                $('#customCallBackAtContainer').hide();
                $('#customCallBackAt').prop('required', false);
                $('#finalCallBackAt').val(val);
            }
        });

        // For Refund
        $('#refundPlan').off('change').on('change', function () {
            if ($(this).val() === 'other') {
                $('#customRefundPlanContainer').show();
                $('#customRefundPlan').prop('required', true);
            } else {
                $('#customRefundPlanContainer').hide();
                $('#customRefundPlan').prop('required', false);
                $('#finalRefundPlan').val($(this).val());
                updateRefundInfo();
            }
        });

        // Custom input handlers
        $('#customCallBackTime').off('input').on('input', function () {
            $('#finalCallBackTime').val($(this).val());
        });

        $('#customCallBackAt').off('input').on('input', function () {
            $('#finalCallBackAt').val($(this).val());
        });

        $('#customRefundPlan').off('input').on('input', function () {
            $('#finalRefundPlan').val($(this).val());
            updateRefundInfo();
        });

        $('#refundAmount').off('input').on('input', function () {
            updateRefundInfo();
        });

        $('#refundReason').off('input').on('input', function () {
            updateRefundInfo();
        });
    }

    // Update refund info hidden field
    function updateRefundInfo() {
        const plan = $('#finalRefundPlan').val() || $('#refundPlan').val() || '';
        const amount = $('#refundAmount').val() || '0';
        const date = $('#refundDate').val() || '';
        const reason = $('#refundReason').val() || '';

        let refundInfo = `Refund - Plan: ${plan}`;
        if (amount && amount !== '0') refundInfo += `, Amount: ₹${amount}`;
        if (date) refundInfo += `, Date: ${date}`;
        if (reason) refundInfo += `, Reason: ${reason}`;

        $('#finalRefundInfo').val(refundInfo);
    }

    // Initialize Refund Datepicker
    function initializeRefundDatepicker() {
        if ($('#refundDate').length) {
            $('#refundDate').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                orientation: 'bottom'
            });

            $('#refundDate').on('change', function () {
                const selectedDate = $(this).val();
                if (selectedDate) {
                    updateRefundInfo();
                }
            });

            $('#refundCalendarIcon').click(function () {
                $('#refundDate').datepicker('show');
            });

            $('#clearRefundDate').click(function () {
                $('#refundDate').val('');
                updateRefundInfo();
            });
        }
    }

    // Initialize Datepicker for Schedule
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

    // Phone number validation
    $('#phoneNumber').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });

    // Form Reset Handler
    $('button[type="reset"]').on('click', function (e) {
        e.preventDefault();
        $('#sellerForm')[0].reset();
        $('#dynamicFieldsContainer').empty();
        $('#customCallDurationContainer').hide();
        $('#callDuration').val('');
        $('#customResponseContainer').hide();
        $('#customerResponse').val('');
    });

    // Form Submit Handler
    $('#sellerForm').on('submit', function (e) {
        e.preventDefault();

        const businessName = $('#businessName').val().trim();
        const phoneNumber = $('#phoneNumber').val().trim();
        let customerResponse = $('#customerResponse').val();
        const sellerID = $('#sellerID').val().trim() || '';
        
        // Handle custom response
        if (customerResponse === 'other') {
            const customResponse = $('#customResponse').val().trim();
            if (!customResponse) {
                showToast('warning', 'Warning!', 'Please enter a custom response');
                $('#customResponse').focus();
                return;
            }
            customerResponse = customResponse;
        }

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

        // Process dynamic field values
        processDynamicFieldValues();

        // Validate dynamic fields
        if (!validateDynamicFields(customerResponse)) {
            return;
        }

        // Prepare form data
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
            additional_notes: $('#additionalNotes').val().trim() || '',
            customer_doubts: $('#customerDoubts').length ? $('#customerDoubts').val() || '' : ''
        };

        // Add plan data based on response
        if (customerResponse === 'Plan Interested') {
            const planId = $('#selectedPlanId').val();
            const planName = $('#selectedPlanName').val() || '';
            const planDuration = $('#selectedDuration').val() || '';
            const planAmount = $('#planAmount').val();

            if (!planName || planName === '') {
                showToast('warning', 'Warning!', 'Please select a plan');
                return;
            }

            if (!planDuration || planDuration === '') {
                showToast('warning', 'Warning!', 'Please enter the duration');
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
            formData.is_custom_duration = ($('#selectedPlan').val() === 'other') ? 1 : 0;
        }

        if (customerResponse === 'Plan Upgraded') {
            const planId = $('#upgradedPlanId').val();
            const planName = $('#upgradedPlanName').val() || '';
            const planDuration = $('#upgradedDuration').val() || '';
            const planAmount = $('#upgradedPlanAmount').val();

            if (!planName || planName === '') {
                showToast('warning', 'Warning!', 'Please select a plan');
                return;
            }

            if (!planDuration || planDuration === '') {
                showToast('warning', 'Warning!', 'Please enter the duration');
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
            formData.is_custom_duration = ($('#upgradedPlan').val() === 'other') ? 1 : 0;
        }

        if (customerResponse === 'Later') {
            const callBackTime = $('#finalCallBackTime').val() || $('#callBackTime').val();
            if (!callBackTime) {
                showToast('warning', 'Warning!', 'Please select or enter call back time');
                return;
            }
            formData.call_back_time = callBackTime;
        }

        if (customerResponse === 'Call Back AT') {
            const callBackAt = $('#finalCallBackAt').val() || $('#callBackAt').val();
            if (!callBackAt) {
                showToast('warning', 'Warning!', 'Please select or enter call back time');
                return;
            }
            formData.call_back_time = callBackAt;
        }

        if (customerResponse === 'Schedule') {
            const scheduleDate = $('#finalScheduleDate').val();
            if (!scheduleDate) {
                showToast('warning', 'Warning!', 'Please select a schedule date');
                return;
            }
            formData.call_back_time = scheduleDate;
        }

        if (customerResponse === 'Refund') {
            const refundInfo = $('#finalRefundInfo').val();
            if (!refundInfo) {
                showToast('warning', 'Warning!', 'Please fill refund details');
                return;
            }
            formData.refund_info = refundInfo;
            formData.plan_name = $('#finalRefundPlan').val() || $('#refundPlan').val();
            formData.plan_amount = parseFloat($('#refundAmount').val()) || 0;
        }

        console.log('Final Form Data:', formData);

        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.html();
        $submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'ajax/work-station/workstation_add_seller.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', response.message);
                    $('#sellerForm')[0].reset();
                    $('#dynamicFieldsContainer').empty();
                    $('#customCallDurationContainer').hide();
                    $('#customResponseContainer').hide();
                    $('#customerResponse').val('');
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

    function processDynamicFieldValues() {
        // Process Plan Interested
        if ($('#selectedPlan').length > 0) {
            if ($('#selectedPlan').val() === 'other') {
                if ($('#customPlan').length > 0) {
                    $('#selectedPlanName').val($('#customPlan').val());
                }
                if ($('#customDurationPlan').length > 0) {
                    $('#selectedDuration').val($('#customDurationPlan').val());
                }
            } else {
                const selectedOption = $('#selectedPlan').find('option:selected');
                $('#selectedPlanName').val(selectedOption.data('plan-name') || selectedOption.parent().attr('label') || '');
                $('#selectedDuration').val(selectedOption.data('duration') || '');
            }
        }

        // Process Plan Upgraded
        if ($('#upgradedPlan').length > 0) {
            if ($('#upgradedPlan').val() === 'other') {
                if ($('#customUpgradedPlan').length > 0) {
                    $('#upgradedPlanName').val($('#customUpgradedPlan').val());
                }
                if ($('#customDuration').length > 0) {
                    $('#upgradedDuration').val($('#customDuration').val());
                }
            } else {
                const selectedOption = $('#upgradedPlan').find('option:selected');
                $('#upgradedPlanName').val(selectedOption.data('plan-name') || selectedOption.parent().attr('label') || '');
                $('#upgradedDuration').val(selectedOption.data('duration') || '');
            }
        }

        // Process Later
        if ($('#callBackTime').length > 0) {
            if ($('#callBackTime').val() === 'other') {
                if ($('#customCallBackTime').length > 0) {
                    $('#finalCallBackTime').val($('#customCallBackTime').val());
                }
            } else {
                $('#finalCallBackTime').val($('#callBackTime').val());
            }
        }

        // Process Call Back AT
        if ($('#callBackAt').length > 0) {
            if ($('#callBackAt').val() === 'other') {
                if ($('#customCallBackAt').length > 0) {
                    $('#finalCallBackAt').val($('#customCallBackAt').val());
                }
            } else {
                $('#finalCallBackAt').val($('#callBackAt').val());
            }
        }

        // Process Schedule
        if ($('#scheduleDate').length > 0 && $('#scheduleDate').val()) {
            $('#finalScheduleDate').val('Schedule at ' + $('#scheduleDate').val());
        }

        // Process Refund
        if ($('#refundPlan').length > 0) {
            if ($('#refundPlan').val() === 'other') {
                if ($('#customRefundPlan').length > 0) {
                    $('#finalRefundPlan').val($('#customRefundPlan').val());
                }
            } else {
                $('#finalRefundPlan').val($('#refundPlan').val());
            }
            updateRefundInfo();
        }
    }

    function validateDynamicFields(customerResponse) {
        if (customerResponse === 'Plan Interested') {
            const planId = $('#selectedPlanId').val();
            const customPlan = $('#customPlan').val();
            const planAmount = $('#planAmount').val();
            const duration = $('#selectedDuration').val();
            
            if ((!planId || planId == 0) && (!customPlan || customPlan === '')) {
                showToast('warning', 'Warning!', 'Please select or enter a plan');
                return false;
            }
            if (!planAmount || parseFloat(planAmount) <= 0) {
                showToast('warning', 'Warning!', 'Please enter a valid plan amount');
                return false;
            }
            if (!duration || duration === '') {
                showToast('warning', 'Warning!', 'Please enter the duration');
                return false;
            }
        }

        if (customerResponse === 'Plan Upgraded') {
            const planId = $('#upgradedPlanId').val();
            const customPlan = $('#customUpgradedPlan').val();
            const planAmount = $('#upgradedPlanAmount').val();
            const duration = $('#upgradedDuration').val();
            
            if ((!planId || planId == 0) && (!customPlan || customPlan === '')) {
                showToast('warning', 'Warning!', 'Please select or enter the upgraded plan');
                return false;
            }
            if (!planAmount || parseFloat(planAmount) <= 0) {
                showToast('warning', 'Warning!', 'Please enter a valid plan amount');
                return false;
            }
            if (!duration || duration === '') {
                showToast('warning', 'Warning!', 'Please enter the duration');
                return false;
            }
        }

        if (customerResponse === 'Later') {
            const callBack = $('#finalCallBackTime').val() || $('#callBackTime').val();
            if (!callBack || callBack === '') {
                showToast('warning', 'Warning!', 'Please select or enter call back time');
                return false;
            }
        }

        if (customerResponse === 'Call Back AT') {
            const callBack = $('#finalCallBackAt').val() || $('#callBackAt').val();
            if (!callBack || callBack === '') {
                showToast('warning', 'Warning!', 'Please select or enter call back time');
                return false;
            }
        }

        if (customerResponse === 'Schedule') {
            const scheduleDate = $('#finalScheduleDate').val();
            if (!scheduleDate || scheduleDate === '') {
                showToast('warning', 'Warning!', 'Please select a schedule date');
                return false;
            }
        }

        if (customerResponse === 'Refund') {
            const plan = $('#finalRefundPlan').val() || $('#refundPlan').val();
            const amount = $('#refundAmount').val();

            if (!plan || plan === '') {
                showToast('warning', 'Warning!', 'Please select or enter the plan to refund');
                return false;
            }
            if (!amount || amount === '' || parseFloat(amount) <= 0) {
                showToast('warning', 'Warning!', 'Please enter a valid refund amount');
                return false;
            }
        }

        return true;
    }

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