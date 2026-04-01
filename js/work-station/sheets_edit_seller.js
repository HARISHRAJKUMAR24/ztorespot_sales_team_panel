$(document).ready(function () {
    const sellerId = $('#sellerId').val();

    // Get seller data from PHP
    let sellerData = null;
    try {
        const sellerDataStr = $('#sellerData').val();
        if (sellerDataStr && sellerDataStr !== 'null') {
            sellerData = JSON.parse(sellerDataStr);
            console.log('Seller data loaded:', sellerData);
        }
    } catch (e) {
        console.error('Error parsing seller data:', e);
    }

    let sellerID = '';
    if ($('#sellerID').length > 0) {
        sellerID = $('#sellerID').val() || '';
    }

    // Store original values
    let originalValues = {};
    if (sellerData) {
        originalValues = {
            customerResponse: sellerData.customer_response,
            plansInterested: sellerData.plans_interested,
            planDuration: sellerData.plan_duration || '',
            planAmount: sellerData.plan_amount || 0,
            callTiming: sellerData.call_timing,
            rememberingNotes: sellerData.remembering_notes,
            latestUpdate: sellerData.latest_update
        };

        // Set call timing hidden field
        $('#callTiming').val(sellerData.call_timing || '');

        // Handle call timing select
        const callTiming = sellerData.call_timing || '';
        if (callTiming) {
            const standardTimings = ['Morning 9-11 AM', 'Late Morning 11-1 PM', 'Afternoon 2-4 PM', 'Evening 4-6 PM', 'Night 7-9 PM'];
            if (!standardTimings.includes(callTiming)) {
                $('#callTimingSelect').val('other');
                $('#customCallTimingContainer').show();
                $('#customCallTiming').val(callTiming);
            } else {
                $('#callTimingSelect').val(callTiming);
                $('#customCallTimingContainer').hide();
            }
        }
    }

    // Initialize form after page load
    setTimeout(function () {
        initializeFormWithData();
    }, 100);

    // Customer Response Change Handler
    $('#customerResponse').on('change', function () {
        const response = $(this).val();
        const container = $('#dynamicFieldsContainer');

        container.empty();

        let html = '';

        switch (response) {
            case 'Plan Interested':
                html = generatePlanInterestedFields();
                break;
            case 'Plan Upgraded':
                html = generatePlanUpgradedFields();
                $('#currentStatus').val('Upgraded');
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
                $('#currentStatus').val('Refunded');
                break;
            default:
                break;
        }

        container.html(html);
        initializeOtherOptionHandlers();
        initializePlanDropdowns();

        // Set existing values
        setTimeout(function () {
            setExistingDynamicValues(response);
        }, 100);

        // Initialize datepickers
        if (response === 'Schedule') {
            initializeDatepicker();
        }
        if (response === 'Refund') {
            initializeRefundDatepicker();
        }
    });

    // Generate Plan Interested Fields with Amount Dropdown
    function generatePlanInterestedFields() {
        // Build plan options from subscription plans
        let planOptions = '<option value="" selected disabled>Choose a plan</option>';
        
        // Group plans by name
        const groupedPlans = {};
        subscriptionPlans.forEach(plan => {
            if (!groupedPlans[plan.plan_name]) {
                groupedPlans[plan.plan_name] = [];
            }
            groupedPlans[plan.plan_name].push(plan);
        });
        
        // Create optgroups for each plan
        for (const [planName, durations] of Object.entries(groupedPlans)) {
            planOptions += `<optgroup label="${planName}">`;
            durations.forEach(plan => {
                planOptions += `<option value="${plan.id}" data-duration="${plan.duration}" data-amount="${plan.total_amount}">
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
                        <div class="form-text text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Amount will be auto-filled when you select a plan
                        </div>
                    </div>
                </div>
                <input type="hidden" id="selectedPlanId" value="0">
                <input type="hidden" id="selectedPlanName" value="">
                <input type="hidden" id="selectedDuration" value="">
            </div>
        `;
    }

    // Generate Plan Upgraded Fields with Amount Dropdown
    function generatePlanUpgradedFields() {
        // Build plan options from subscription plans
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
                planOptions += `<option value="${plan.id}" data-duration="${plan.duration}" data-amount="${plan.total_amount}">
                    ${plan.duration} - ₹${parseFloat(plan.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}
                </option>`;
            });
            planOptions += `</optgroup>`;
        }
        
        planOptions += '<option value="other">Other (Custom Plan)</option>';
        
        const productsValue = (sellerData && sellerData.products_uploaded !== undefined && sellerData.products_uploaded !== null)
            ? sellerData.products_uploaded
            : 0;
        
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
                        <div class="form-text text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Amount will be auto-filled when you select a plan
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-check-fill text-info me-1"></i>
                            Duration <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="upgradedDurationDisplay" readonly>
                        <input type="hidden" id="upgradedDuration" value="">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-cloud-upload text-primary me-1"></i>
                            Number of Products Uploaded
                        </label>
                        <input type="number" class="form-control" id="productsUploaded" 
                            placeholder="Enter number of products (0-999)"
                            min="0" max="999" value="${productsValue}">
                    </div>
                </div>
                <div id="customDurationContainer" style="display: none;" class="mt-2 custom-field">
                    <label class="form-label">Enter Custom Duration:</label>
                    <input type="text" class="form-control" id="customDuration" 
                        placeholder="e.g., 45 days, 18 months, etc.">
                </div>
                <input type="hidden" id="upgradedPlanId" value="0">
                <input type="hidden" id="upgradedPlanName" value="">
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
                $('#planAmount').val('').prop('readonly', false);
                $('#planAmountDisplay').text('₹0.00');
                $('#selectedPlanId').val('0');
                $('#selectedPlanName').val('');
                $('#selectedDuration').val('');
            } else if (selectedValue && selectedValue !== '') {
                $('#customPlanContainer').hide();
                const selectedOption = $(this).find('option:selected');
                const planId = selectedValue;
                const planName = selectedOption.parent().attr('label');
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
                $('#upgradedDurationDisplay').val('');
                $('#upgradedDuration').val('');
                $('#upgradedPlanId').val('0');
                $('#upgradedPlanName').val('');
            } else if (selectedValue && selectedValue !== '') {
                $('#customUpgradedPlanContainer').hide();
                $('#customDurationContainer').hide();
                const selectedOption = $(this).find('option:selected');
                const planId = selectedValue;
                const planName = selectedOption.parent().attr('label');
                const duration = selectedOption.data('duration');
                const amount = selectedOption.data('amount');
                
                $('#upgradedPlanId').val(planId);
                $('#upgradedPlanName').val(planName);
                $('#upgradedDurationDisplay').val(duration);
                $('#upgradedDuration').val(duration);
                $('#upgradedPlanAmount').val(amount);
                $('#upgradedPlanAmountDisplay').text('₹' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2}));
            }
        });

        // Custom duration handler
        $('#customDuration').off('input').on('input', function () {
            $('#upgradedDuration').val($(this).val());
            $('#upgradedDurationDisplay').val($(this).val());
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

    // Initialize Datepicker for Schedule
    function initializeDatepicker() {
        if ($('#scheduleDate').length) {
            if ($('#scheduleDate').data('datepicker')) {
                $('#scheduleDate').datepicker('destroy');
            }

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
                    $('#callTiming').val('Schedule at ' + selectedDate);
                }
            });

            $('#calendarIcon').click(function () {
                $('#scheduleDate').datepicker('show');
            });

            $('#clearDate').click(function () {
                $('#scheduleDate').val('');
                $('#finalScheduleDate').val('');
                $('#callTiming').val('');
            });

            // Set existing schedule date
            setTimeout(function () {
                if (sellerData && sellerData.call_timing) {
                    const callTiming = sellerData.call_timing;
                    if (callTiming && callTiming.startsWith('Schedule at ')) {
                        const datePart = callTiming.replace('Schedule at ', '');
                        $('#scheduleDate').val(datePart);
                        $('#finalScheduleDate').val(callTiming);

                        try {
                            const dateParts = datePart.split('/');
                            if (dateParts.length === 3) {
                                const day = parseInt(dateParts[0], 10);
                                const month = parseInt(dateParts[1], 10) - 1;
                                const year = parseInt(dateParts[2], 10);
                                const date = new Date(year, month, day);
                                if ($('#scheduleDate').data('datepicker')) {
                                    $('#scheduleDate').datepicker('setDate', date);
                                }
                            }
                        } catch (e) {
                            console.error('Error parsing date:', e);
                        }
                    }
                }
            }, 200);
        }
    }

    // Initialize Refund Datepicker
    function initializeRefundDatepicker() {
        if ($('#refundDate').length) {
            if ($('#refundDate').data('datepicker')) {
                $('#refundDate').datepicker('destroy');
            }

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

            // Set existing refund date
            setTimeout(function () {
                if (sellerData && sellerData.customer_response === 'Refund') {
                    const refundInfo = sellerData.remembering_notes || '';
                    const dateMatch = refundInfo.match(/Date: (\d{2}\/\d{2}\/\d{4})/);
                    if (dateMatch && dateMatch[1]) {
                        $('#refundDate').val(dateMatch[1]);
                    }
                }
            }, 200);
        }
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

    // Initialize form with existing data
    function initializeFormWithData() {
        if (!sellerData) return;

        const response = sellerData.customer_response || '';
        if (response) {
            $('#customerResponse').val(response);
            $('#customerResponse').trigger('change');
        }
    }

    // Set existing dynamic field values
    function setExistingDynamicValues(currentResponse) {
        if (!sellerData) return;

        if (currentResponse === 'Plan Interested') {
            const planId = sellerData.plan_id || 0;
            const planName = sellerData.plans_interested || '';
            const duration = sellerData.plan_duration || '';
            const amount = sellerData.plan_amount || 0;
            
            if (planId > 0) {
                $(`#selectedPlan option[value="${planId}"]`).prop('selected', true).trigger('change');
            } else if (planName && planName !== 'None' && planName !== '') {
                $('#selectedPlan').val('other').trigger('change');
                $('#customPlan').val(planName);
                $('#selectedPlanName').val(planName);
                if (duration) $('#selectedDuration').val(duration);
                if (amount > 0) {
                    $('#planAmount').val(amount);
                    $('#planAmountDisplay').text('₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2}));
                }
            }
        }

        if (currentResponse === 'Plan Upgraded') {
            const planId = sellerData.plan_id || 0;
            const planName = sellerData.plans_interested || '';
            const duration = sellerData.plan_duration || '';
            const amount = sellerData.plan_amount || 0;
            
            // Set products uploaded
            if (sellerData && sellerData.products_uploaded !== undefined && sellerData.products_uploaded !== null && sellerData.products_uploaded > 0) {
                setTimeout(function () {
                    if ($('#productsUploaded').length) {
                        $('#productsUploaded').val(sellerData.products_uploaded);
                    }
                }, 150);
            }
            
            if (planId > 0) {
                $(`#upgradedPlan option[value="${planId}"]`).prop('selected', true).trigger('change');
            } else if (planName && planName !== 'None' && planName !== '') {
                $('#upgradedPlan').val('other').trigger('change');
                $('#customUpgradedPlan').val(planName);
                $('#upgradedPlanName').val(planName);
                if (duration) {
                    $('#upgradedDuration').val(duration);
                    $('#upgradedDurationDisplay').val(duration);
                }
                if (amount > 0) {
                    $('#upgradedPlanAmount').val(amount);
                    $('#upgradedPlanAmountDisplay').text('₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2}));
                }
            }
        }

        if (currentResponse === 'Later' || currentResponse === 'Call Back AT') {
            const callTiming = sellerData.call_timing || '';
            if (callTiming && callTiming !== '') {
                const standardTimes = currentResponse === 'Later'
                    ? ['After 1 hour', 'After 2 hours', 'After 3 hours', 'After 6 hours', 'Tomorrow', 'After 2 days', 'After 1 week', 'Next month']
                    : ['Morning 9-11 AM', 'Late Morning 11-1 PM', 'Afternoon 2-4 PM', 'Evening 4-6 PM', 'Night 7-9 PM'];

                if (!standardTimes.includes(callTiming)) {
                    if (currentResponse === 'Later') {
                        $('#callBackTime').val('other').trigger('change');
                        $('#customCallBackTime').val(callTiming);
                        $('#finalCallBackTime').val(callTiming);
                    } else {
                        $('#callBackAt').val('other').trigger('change');
                        $('#customCallBackAt').val(callTiming);
                        $('#finalCallBackAt').val(callTiming);
                    }
                } else {
                    if (currentResponse === 'Later') {
                        $('#callBackTime').val(callTiming);
                        $('#finalCallBackTime').val(callTiming);
                    } else {
                        $('#callBackAt').val(callTiming);
                        $('#finalCallBackAt').val(callTiming);
                    }
                }

                $('#callTiming').val(callTiming);
            }
        }

        if (currentResponse === 'Schedule') {
            const callTiming = sellerData.call_timing || '';
            if (callTiming && callTiming.startsWith('Schedule at ') && callTiming !== '') {
                const datePart = callTiming.replace('Schedule at ', '');
                setTimeout(function () {
                    $('#scheduleDate').val(datePart);
                    $('#finalScheduleDate').val(callTiming);
                }, 200);
            }
        }

        if (currentResponse === 'Refund') {
            const refundInfo = sellerData.remembering_notes || '';
            const refundPlan = sellerData.plans_interested || '';

            if (refundPlan && refundPlan !== '' && refundPlan !== 'None') {
                const standardPlans = ['Welcome', 'Starter', 'Professional', 'Intermediate'];
                if (!standardPlans.includes(refundPlan)) {
                    $('#refundPlan').val('other').trigger('change');
                    $('#customRefundPlan').val(refundPlan);
                    $('#finalRefundPlan').val(refundPlan);
                } else {
                    $('#refundPlan').val(refundPlan);
                    $('#finalRefundPlan').val(refundPlan);
                }
            }

            if (refundInfo && refundInfo !== '') {
                const amountMatch = refundInfo.match(/Amount: ₹?([0-9.]+)/);
                if (amountMatch && amountMatch[1]) {
                    $('#refundAmount').val(amountMatch[1]);
                }

                const dateMatch = refundInfo.match(/Date: (\d{2}\/\d{2}\/\d{4})/);
                if (dateMatch && dateMatch[1]) {
                    $('#refundDate').val(dateMatch[1]);
                }

                const reasonMatch = refundInfo.match(/Reason: ([^.]+)/);
                if (reasonMatch && reasonMatch[1]) {
                    $('#refundReason').val(reasonMatch[1].trim());
                }
            }

            setTimeout(function () {
                updateRefundInfo();
            }, 200);
        }
    }

    // Initialize "Other" option handlers
    function initializeOtherOptionHandlers() {
        $('#callBackTime').off('change').on('change', function () {
            const val = $(this).val();
            if (val === 'other') {
                $('#customCallBackContainer').show();
                $('#customCallBackTime').prop('required', true);
                $('#callTiming').val('');
            } else {
                $('#customCallBackContainer').hide();
                $('#customCallBackTime').prop('required', false);
                $('#finalCallBackTime').val(val);
                $('#callTiming').val(val);
            }
        });

        $('#callBackAt').off('change').on('change', function () {
            const val = $(this).val();
            if (val === 'other') {
                $('#customCallBackAtContainer').show();
                $('#customCallBackAt').prop('required', true);
                $('#callTiming').val('');
            } else {
                $('#customCallBackAtContainer').hide();
                $('#customCallBackAt').prop('required', false);
                $('#finalCallBackAt').val(val);
                $('#callTiming').val(val);
            }
        });

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

        $('#customCallBackTime').off('input').on('input', function () {
            const val = $(this).val();
            $('#finalCallBackTime').val(val);
            $('#callTiming').val(val);
        });

        $('#customCallBackAt').off('input').on('input', function () {
            const val = $(this).val();
            $('#finalCallBackAt').val(val);
            $('#callTiming').val(val);
        });
        
        $('#customDuration').off('input').on('input', function () {
            $('#upgradedDuration').val($(this).val());
            $('#upgradedDurationDisplay').val($(this).val());
        });
        
        $('#customPlan').off('input').on('input', function () {
            $('#selectedPlanName').val($(this).val());
        });
        
        $('#customUpgradedPlan').off('input').on('input', function () {
            $('#upgradedPlanName').val($(this).val());
        });
    }

    // Call Timing Handler
    $('#callTimingSelect').off('change').on('change', function () {
        if ($(this).val() === 'other') {
            $('#customCallTimingContainer').show();
            $('#customCallTiming').prop('required', true);
            $('#callTiming').val('');
        } else {
            $('#customCallTimingContainer').hide();
            $('#customCallTiming').prop('required', false);
            $('#callTiming').val($(this).val());
        }
    });

    $('#customCallTiming').off('input').on('input', function () {
        $('#callTiming').val($(this).val());
    });

    // Phone number validation
    $('#phoneNumber').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });

    // Form Submit Handler
    $('#sellerForm').on('submit', function (e) {
        e.preventDefault();

        // Get basic required fields
        const businessName = $('#businessName').val();
        const phoneNumber = $('#phoneNumber').val();
        const customerResponse = $('#customerResponse').val();

        if (!businessName || businessName.trim() === '') {
            showToast('warning', 'Warning!', 'Business Name is required');
            $('#businessName').focus();
            return;
        }

        if (!phoneNumber || phoneNumber.trim() === '') {
            showToast('warning', 'Warning!', 'Phone Number is required');
            $('#phoneNumber').focus();
            return;
        }

        if (!customerResponse || customerResponse === '') {
            showToast('warning', 'Warning!', 'Customer Response is required');
            $('#customerResponse').focus();
            return;
        }

        const cleanPhone = phoneNumber.replace(/\D/g, '');
        if (!/^\d{10}$/.test(cleanPhone)) {
            showToast('warning', 'Warning!', 'Please enter a valid 10-digit phone number');
            $('#phoneNumber').focus();
            return;
        }

        // Process dynamic field values
        processDynamicFieldValues();

        // Validate dynamic fields
        if (!validateDynamicFields()) {
            return;
        }

        // Get form values
        let rememberingNotes = $('#rememberingNotes').val() || '';
        let callTiming = $('#callTiming').val() || '';
        let latestUpdate = $('#latestUpdate').val() || customerResponse;
        let currentStatus = $('#currentStatus').val() || '';
        let customerQueries = $('#customerQueries').val() || '';
        let entryDate = $('#entryDate').val() || '';
        let sellerType = $('#sellerType').val() || '';
        let sellerID = $('#sellerID').val() || '';
        let productsUploaded = $('#productsUploaded').length ? $('#productsUploaded').val() || '0' : '0';
        let refundInfo = $('#finalRefundInfo').length ? $('#finalRefundInfo').val() || '' : '';

        // Get plan data
        let planId = 0;
        let planName = '';
        let planDuration = '';
        let planAmount = 0;

        if (customerResponse === 'Plan Interested') {
            planId = $('#selectedPlanId').val() || 0;
            planName = $('#selectedPlanName').val() || $('#selectedPlan option:selected').parent().attr('label') || '';
            planDuration = $('#selectedDuration').val() || '';
            planAmount = parseFloat($('#planAmount').val()) || 0;
        }

        if (customerResponse === 'Plan Upgraded') {
            planId = $('#upgradedPlanId').val() || 0;
            planName = $('#upgradedPlanName').val() || $('#upgradedPlan option:selected').parent().attr('label') || '';
            planDuration = $('#upgradedDuration').val() || '';
            planAmount = parseFloat($('#upgradedPlanAmount').val()) || 0;
        }

        // Clean remembering notes
        let cleanNotes = rememberingNotes;
        cleanNotes = cleanNotes.replace(/Call Duration: [^\n]*(\n|$)/g, '');
        cleanNotes = cleanNotes.replace(/Upgraded Duration: [^\n]*(\n|$)/g, '');
        cleanNotes = cleanNotes.replace(/Plan Duration: [^\n]*(\n|$)/g, '');
        cleanNotes = cleanNotes.replace(/Plan Amount: ₹[^\n]*(\n|$)/g, '');
        cleanNotes = cleanNotes.replace(/Refund - Plan:.*?(\n|$)/g, '');
        cleanNotes = cleanNotes.replace(/\n\s*\n/g, '\n');
        cleanNotes = cleanNotes.trim();

        let finalNotes = cleanNotes;

        if (refundInfo && customerResponse === 'Refund') {
            if (finalNotes) {
                finalNotes = refundInfo + '\n' + finalNotes;
            } else {
                finalNotes = refundInfo;
            }
        }

        // Prepare form data
        const formData = {
            id: sellerId,
            business_name: businessName.trim(),
            seller_type: sellerType,
            phone_number: cleanPhone,
            seller_id: sellerID,
            customer_response: customerResponse,
            plan_id: planId,
            plan_name: planName,
            plan_duration: planDuration,
            plan_amount: planAmount,
            products_uploaded: productsUploaded,
            refund_info: refundInfo,
            call_back_time: getFinalCallBackValue(),
            remembering_notes: finalNotes,
            latest_update: latestUpdate,
            current_status: currentStatus,
            customer_queries: customerQueries,
            call_timing: callTiming,
            entry_date: entryDate
        };

        console.log('Submitting form data:', formData);

        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.html();
        $submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'ajax/work-station/update_sheets_seller.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', response.message);
                    setTimeout(function () {
                        window.location.href = 'sheets_followup_list.php';
                    }, 2000);
                } else {
                    showToast('danger', 'Error!', response.message || 'Unknown error occurred');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                showToast('danger', 'Error!', 'Failed to update seller. Please try again.');
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
            } else {
                const selectedOption = $('#selectedPlan').find('option:selected');
                $('#selectedPlanName').val(selectedOption.parent().attr('label') || '');
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
                $('#upgradedPlanName').val(selectedOption.parent().attr('label') || '');
                $('#upgradedDuration').val(selectedOption.data('duration') || '');
            }
        }

        // Process Later/Call Back AT
        if ($('#callBackTime').length > 0) {
            if ($('#callBackTime').val() === 'other') {
                if ($('#customCallBackTime').length > 0) {
                    $('#finalCallBackTime').val($('#customCallBackTime').val());
                }
            } else {
                $('#finalCallBackTime').val($('#callBackTime').val());
            }
        }

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

    function getFinalCallBackValue() {
        if ($('#finalCallBackTime').length > 0 && $('#finalCallBackTime').val()) {
            return $('#finalCallBackTime').val();
        }
        if ($('#finalCallBackAt').length > 0 && $('#finalCallBackAt').val()) {
            return $('#finalCallBackAt').val();
        }
        if ($('#finalScheduleDate').length > 0 && $('#finalScheduleDate').val()) {
            return $('#finalScheduleDate').val();
        }
        return '';
    }

    function validateDynamicFields() {
        const response = $('#customerResponse').val();

        if (response === 'Plan Interested') {
            const planId = $('#selectedPlanId').val();
            const customPlan = $('#customPlan').val();
            const planAmount = $('#planAmount').val();
            
            if ((!planId || planId == 0) && (!customPlan || customPlan === '')) {
                showToast('warning', 'Warning!', 'Please select or enter a plan');
                return false;
            }
            if (!planAmount || parseFloat(planAmount) <= 0) {
                showToast('warning', 'Warning!', 'Please enter a valid plan amount');
                return false;
            }
        }

        if (response === 'Plan Upgraded') {
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

        if (response === 'Later' || response === 'Call Back AT' || response === 'Schedule') {
            const callBack = getFinalCallBackValue();
            if (!callBack || callBack === '') {
                const fieldName = response === 'Schedule' ? 'schedule date' : 'call back time';
                showToast('warning', 'Warning!', 'Please select or enter ' + fieldName);
                return false;
            }
        }

        if (response === 'Refund') {
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
        let bgClass = 'bg-info';

        if (type === 'success') bgClass = 'bg-success';
        else if (type === 'danger') bgClass = 'bg-danger';
        else if (type === 'warning') bgClass = 'bg-warning';

        const toastHtml = `
            <div id="${id}" class="toast text-white ${bgClass}" role="alert" aria-live="assertive" 
                 aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                <div class="toast-header ${bgClass} text-white border-0">
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        $('.toast-container').append(toastHtml);

        const toastElement = document.getElementById(id);
        if (toastElement) {
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            $(toastElement).on('hidden.bs.toast', function () {
                $(this).remove();
            });
        }
    }
});