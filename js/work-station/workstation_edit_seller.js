$(document).ready(function() {
    const sellerId = $('#sellerId').val();
    
    // Get seller data from PHP
    let sellerData = null;
    try {
        // Try to get data from data attribute if available
        const sellerDataStr = $('#sellerData').val();
        if (sellerDataStr && sellerDataStr !== 'null') {
            sellerData = JSON.parse(sellerDataStr);
            console.log('Seller data loaded:', sellerData); // Debug log
        }
    } catch (e) {
        console.error('Error parsing seller data:', e);
    }
    
    // Store original values
    let originalValues = {};
    if (sellerData) {
        originalValues = {
            customerResponse: sellerData.customer_response,
            selectedPlan: sellerData.selected_plan,
            upgradedPlan: sellerData.upgraded_plan,
            upgradedDuration: sellerData.upgraded_duration,
            callBackTime: sellerData.call_back_time
        };
        
        // Set call duration hidden field
        $('#callDuration').val(sellerData.call_duration || '');
        
        // Handle call duration select
        const callDuration = sellerData.call_duration || '';
        if (callDuration) {
            const standardDurations = ['5 mins','10 mins','15 mins','20 mins','25 mins','30 mins','45 mins','1 hour','1.5 hours','2 hours'];
            if (!standardDurations.includes(callDuration)) {
                $('#callDurationSelect').val('other');
                $('#customCallDurationContainer').show();
                $('#customCallDuration').val(callDuration);
            } else {
                $('#callDurationSelect').val(callDuration);
                $('#customCallDurationContainer').hide();
            }
        }
    }
    
    // Initialize form after page load
    setTimeout(function() {
        initializeFormWithData();
    }, 100);
    
    // Customer Response Change Handler
    $('#customerResponse').on('change', function() {
        const response = $(this).val();
        const container = $('#dynamicFieldsContainer');
        
        container.empty();
        
        let html = '';
        
        switch(response) {
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
            case 'Shedule':
                html = generateScheduleFields();
                break;
        }
        
        container.html(html);
        initializeOtherOptionHandlers();
        
        // Set existing values
        setTimeout(function() {
            setExistingDynamicValues(response);
        }, 100);
        
        // Initialize datepicker for schedule
        if (response === 'Shedule') {
            initializeDatepicker();
        }
    });

    // Generate Plan Interested Fields
    function generatePlanInterestedFields() {
        return `
            <div class="dynamic-field">
                <div class="row">
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            Select Plan <span class="text-danger">*</span>
                        </label>
                        <div class="plan-select-wrapper">
                            <select class="form-select" id="selectedPlan" required>
                                <option value="" selected disabled>Choose a plan</option>
                                <option value="Welcome Plan">Welcome Plan</option>
                                <option value="Starter Plan">Starter Plan</option>
                                <option value="Professional Plan">Professional Plan</option>
                                <option value="Enterprise Plan">Enterprise Plan</option>
                                <option value="other">Other (Custom Plan)</option>
                            </select>
                            <div id="customPlanContainer" style="display: none;" class="mt-2 custom-field">
                                <label class="form-label">Enter Custom Plan Name:</label>
                                <input type="text" class="form-control" id="customPlan" 
                                    placeholder="e.g., Premium Plan, Gold Plan etc.">
                            </div>
                            <input type="hidden" id="finalSelectedPlan">
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Generate Plan Upgraded Fields
    function generatePlanUpgradedFields() {
        return `
            <div class="dynamic-field">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-arrow-up-circle-fill text-success me-1"></i>
                            Upgraded Plan <span class="text-danger">*</span>
                        </label>
                        <div class="upgraded-plan-wrapper">
                            <select class="form-select" id="upgradedPlan" required>
                                <option value="" selected disabled>Choose upgraded plan</option>
                                <option value="Welcome Plan">Welcome Plan</option>
                                <option value="Starter Plan">Starter Plan</option>
                                <option value="Professional Plan">Professional Plan</option>
                                <option value="Enterprise Plan">Enterprise Plan</option>
                                <option value="other">Other (Custom Plan)</option>
                            </select>
                            <div id="customUpgradedPlanContainer" style="display: none;" class="mt-2 custom-field">
                                <label class="form-label">Enter Custom Plan Name:</label>
                                <input type="text" class="form-control" id="customUpgradedPlan" 
                                    placeholder="e.g., Premium Plan, Gold Plan etc.">
                            </div>
                            <input type="hidden" id="finalUpgradedPlan">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-check-fill text-info me-1"></i>
                            Duration <span class="text-danger">*</span>
                        </label>
                        <div class="duration-wrapper">
                            <select class="form-select" id="upgradedDuration" required>
                                <option value="" selected disabled>Select duration</option>
                                <option value="1 Month">1 Month</option>
                                <option value="3 Months">3 Months</option>
                                <option value="6 Months">6 Months</option>
                                <option value="1 Year">1 Year</option>
                                <option value="2 Years">2 Years</option>
                                <option value="other">Other (Custom Duration)</option>
                            </select>
                            <div id="customDurationContainer" style="display: none;" class="mt-2 custom-field">
                                <label class="form-label">Enter Custom Duration:</label>
                                <input type="text" class="form-control" id="customDuration" 
                                    placeholder="e.g., 45 days, 18 months, etc.">
                            </div>
                            <input type="hidden" id="finalUpgradedDuration">
                        </div>
                    </div>
                </div>
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

    // Generate Schedule Fields (match exactly with add form)
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

    // Initialize Datepicker (match with add form)
    function initializeDatepicker() {
        if ($('#scheduleDate').length) {
            // Destroy existing datepicker if any
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
            
            // Handle date selection
            $('#scheduleDate').on('change', function() {
                const selectedDate = $(this).val();
                if (selectedDate) {
                    $('#finalScheduleDate').val('Shedule at ' + selectedDate);
                }
            });
            
            $('#calendarIcon').click(function() {
                $('#scheduleDate').datepicker('show');
            });
            
            $('#clearDate').click(function() {
                $('#scheduleDate').val('');
                $('#finalScheduleDate').val('');
            });
            
            // Set existing schedule date if any
            setTimeout(function() {
                if (sellerData && sellerData.call_back_time) {
                    const callBackTime = sellerData.call_back_time;
                    console.log('Setting schedule date in datepicker:', callBackTime);
                    
                    if (callBackTime && callBackTime.startsWith('Shedule at ')) {
                        const datePart = callBackTime.replace('Shedule at ', '');
                        console.log('Extracted date part:', datePart);
                        
                        $('#scheduleDate').val(datePart);
                        $('#finalScheduleDate').val(callBackTime);
                        
                        // Set date in datepicker
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
                    } else if (callBackTime && callBackTime !== 'null' && callBackTime !== '') {
                        // If it's just a date without prefix
                        $('#scheduleDate').val(callBackTime);
                        $('#finalScheduleDate').val('Shedule at ' + callBackTime);
                    }
                }
            }, 200);
        }
    }

    // Initialize form with existing data
    function initializeFormWithData() {
        if (!sellerData) return;
        
        const response = sellerData.customer_response || '';
        console.log('Initializing form with response:', response);
        
        if (response) {
            $('#customerResponse').val(response);
            $('#customerResponse').trigger('change');
        }
    }

    // Set existing dynamic field values
    function setExistingDynamicValues(currentResponse) {
        if (!sellerData) return;
        
        console.log('Setting existing values for response:', currentResponse);
        console.log('Seller data:', sellerData);
        
        if (currentResponse === 'Plan Interested') {
            const selectedPlan = sellerData.selected_plan || '';
            if (selectedPlan) {
                const standardPlans = ['Welcome Plan','Starter Plan','Professional Plan','Enterprise Plan'];
                if (!standardPlans.includes(selectedPlan)) {
                    $('#selectedPlan').val('other').trigger('change');
                    $('#customPlan').val(selectedPlan);
                    $('#finalSelectedPlan').val(selectedPlan);
                } else {
                    $('#selectedPlan').val(selectedPlan);
                    $('#finalSelectedPlan').val(selectedPlan);
                }
            }
        }
        
        if (currentResponse === 'Plan Upgraded') {
            const upgradedPlan = sellerData.upgraded_plan || '';
            const upgradedDuration = sellerData.upgraded_duration || '';
            
            if (upgradedPlan) {
                const standardPlans = ['Welcome Plan','Starter Plan','Professional Plan','Enterprise Plan'];
                if (!standardPlans.includes(upgradedPlan)) {
                    $('#upgradedPlan').val('other').trigger('change');
                    $('#customUpgradedPlan').val(upgradedPlan);
                    $('#finalUpgradedPlan').val(upgradedPlan);
                } else {
                    $('#upgradedPlan').val(upgradedPlan);
                    $('#finalUpgradedPlan').val(upgradedPlan);
                }
            }
            
            if (upgradedDuration) {
                const standardDurations = ['1 Month','3 Months','6 Months','1 Year','2 Years'];
                if (!standardDurations.includes(upgradedDuration)) {
                    $('#upgradedDuration').val('other').trigger('change');
                    $('#customDuration').val(upgradedDuration);
                    $('#finalUpgradedDuration').val(upgradedDuration);
                } else {
                    $('#upgradedDuration').val(upgradedDuration);
                    $('#finalUpgradedDuration').val(upgradedDuration);
                }
            }
        }
        
        if (currentResponse === 'Later') {
            const callBackTime = sellerData.call_back_time || '';
            if (callBackTime) {
                const standardTimes = ['After 1 hour','After 2 hours','After 3 hours','After 6 hours','Tomorrow','After 2 days','After 1 week','Next month'];
                if (!standardTimes.includes(callBackTime)) {
                    $('#callBackTime').val('other').trigger('change');
                    $('#customCallBackTime').val(callBackTime);
                    $('#finalCallBackTime').val(callBackTime);
                } else {
                    $('#callBackTime').val(callBackTime);
                    $('#finalCallBackTime').val(callBackTime);
                }
            }
        }
        
        if (currentResponse === 'Call Back AT') {
            const callBackAt = sellerData.call_back_time || '';
            if (callBackAt) {
                const standardTimes = ['Morning 9-11 AM','Late Morning 11-1 PM','Afternoon 2-4 PM','Evening 4-6 PM','Night 7-9 PM'];
                if (!standardTimes.includes(callBackAt)) {
                    $('#callBackAt').val('other').trigger('change');
                    $('#customCallBackAt').val(callBackAt);
                    $('#finalCallBackAt').val(callBackAt);
                } else {
                    $('#callBackAt').val(callBackAt);
                    $('#finalCallBackAt').val(callBackAt);
                }
            }
        }
        
        if (currentResponse === 'Shedule') {
            const callBackTime = sellerData.call_back_time || '';
            console.log('Schedule data from DB:', callBackTime);
            
            if (callBackTime && callBackTime.startsWith('Shedule at ')) {
                const datePart = callBackTime.replace('Shedule at ', '');
                console.log('Extracted date:', datePart);
                
                // Small delay to ensure DOM is ready
                setTimeout(function() {
                    $('#scheduleDate').val(datePart);
                    $('#finalScheduleDate').val(callBackTime);
                    
                    // Also set the datepicker date if available
                    try {
                        if ($('#scheduleDate').data('datepicker')) {
                            const dateParts = datePart.split('/');
                            if (dateParts.length === 3) {
                                const day = parseInt(dateParts[0], 10);
                                const month = parseInt(dateParts[1], 10) - 1;
                                const year = parseInt(dateParts[2], 10);
                                const date = new Date(year, month, day);
                                $('#scheduleDate').datepicker('setDate', date);
                            }
                        }
                    } catch (e) {
                        console.error('Error setting datepicker date:', e);
                    }
                }, 200);
            } else if (callBackTime && callBackTime !== 'null' && callBackTime !== '') {
                setTimeout(function() {
                    $('#scheduleDate').val(callBackTime);
                    $('#finalScheduleDate').val('Shedule at ' + callBackTime);
                }, 200);
            }
        }
    }

    // Initialize "Other" option handlers
    function initializeOtherOptionHandlers() {
        $('#selectedPlan').off('change').on('change', function() {
            if ($(this).val() === 'other') {
                $('#customPlanContainer').show();
                $('#customPlan').prop('required', true);
            } else {
                $('#customPlanContainer').hide();
                $('#customPlan').prop('required', false);
                $('#finalSelectedPlan').val($(this).val());
            }
        });

        $('#upgradedPlan').off('change').on('change', function() {
            if ($(this).val() === 'other') {
                $('#customUpgradedPlanContainer').show();
                $('#customUpgradedPlan').prop('required', true);
            } else {
                $('#customUpgradedPlanContainer').hide();
                $('#customUpgradedPlan').prop('required', false);
                $('#finalUpgradedPlan').val($(this).val());
            }
        });

        $('#upgradedDuration').off('change').on('change', function() {
            if ($(this).val() === 'other') {
                $('#customDurationContainer').show();
                $('#customDuration').prop('required', true);
            } else {
                $('#customDurationContainer').hide();
                $('#customDuration').prop('required', false);
                $('#finalUpgradedDuration').val($(this).val());
            }
        });

        $('#callBackTime').off('change').on('change', function() {
            if ($(this).val() === 'other') {
                $('#customCallBackContainer').show();
                $('#customCallBackTime').prop('required', true);
            } else {
                $('#customCallBackContainer').hide();
                $('#customCallBackTime').prop('required', false);
                $('#finalCallBackTime').val($(this).val());
            }
        });

        $('#callBackAt').off('change').on('change', function() {
            if ($(this).val() === 'other') {
                $('#customCallBackAtContainer').show();
                $('#customCallBackAt').prop('required', true);
            } else {
                $('#customCallBackAtContainer').hide();
                $('#customCallBackAt').prop('required', false);
                $('#finalCallBackAt').val($(this).val());
            }
        });

        $('#customPlan').off('input').on('input', function() {
            $('#finalSelectedPlan').val($(this).val());
        });

        $('#customUpgradedPlan').off('input').on('input', function() {
            $('#finalUpgradedPlan').val($(this).val());
        });

        $('#customDuration').off('input').on('input', function() {
            $('#finalUpgradedDuration').val($(this).val());
        });

        $('#customCallBackTime').off('input').on('input', function() {
            $('#finalCallBackTime').val($(this).val());
        });

        $('#customCallBackAt').off('input').on('input', function() {
            $('#finalCallBackAt').val($(this).val());
        });
    }

    // Call Duration Handler
    $('#callDurationSelect').off('change').on('change', function() {
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

    $('#customCallDuration').off('input').on('input', function() {
        $('#callDuration').val($(this).val());
    });

    // Phone number validation
    $('#phoneNumber').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });

    // Form Submit Handler
    $('#sellerForm').on('submit', function(e) {
        e.preventDefault();
        
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
        
        if (!/^\d{10}$/.test(phoneNumber)) {
            showToast('warning', 'Warning!', 'Please enter a valid 10-digit phone number');
            $('#phoneNumber').focus();
            return;
        }
        
        processDynamicFieldValues();
        
        if (!validateDynamicFields()) {
            return;
        }
        
        const formData = {
            id: sellerId,
            business_name: businessName,
            seller_type: $('#sellerType').val() || '',
            phone_number: phoneNumber,
            customer_response: customerResponse,
            selected_plan: getFinalPlanValue(),
            upgraded_plan: getFinalUpgradedPlanValue(),
            upgraded_duration: getFinalDurationValue(),
            call_back_time: getFinalCallBackValue(),
            customer_queries: $('#customerQueries').val().trim() || '',
            customer_status: $('#customerStatus').val() || '',
            call_duration: $('#callDuration').val() || '',
            additional_notes: $('#additionalNotes').val().trim() || ''
        };
        
        console.log('Submitting form data:', formData);
        
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.html();
        $submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...').prop('disabled', true);
        
        $.ajax({
            url: BASE_URL + 'ajax/work-station/workstation_update_seller.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', 'Seller updated successfully');
                    setTimeout(function() {
                        window.location.href = 'workstation_followup.php';
                    }, 1500);
                } else {
                    showToast('danger', 'Error!', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('danger', 'Error!', 'Failed to update seller. Please try again.');
            },
            complete: function() {
                $submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    function processDynamicFieldValues() {
        if ($('#selectedPlan').length) {
            if ($('#selectedPlan').val() === 'other') {
                $('#finalSelectedPlan').val($('#customPlan').val());
            } else {
                $('#finalSelectedPlan').val($('#selectedPlan').val());
            }
        }
        
        if ($('#upgradedPlan').length) {
            if ($('#upgradedPlan').val() === 'other') {
                $('#finalUpgradedPlan').val($('#customUpgradedPlan').val());
            } else {
                $('#finalUpgradedPlan').val($('#upgradedPlan').val());
            }
        }
        
        if ($('#upgradedDuration').length) {
            if ($('#upgradedDuration').val() === 'other') {
                $('#finalUpgradedDuration').val($('#customDuration').val());
            } else {
                $('#finalUpgradedDuration').val($('#upgradedDuration').val());
            }
        }
        
        if ($('#callBackTime').length) {
            if ($('#callBackTime').val() === 'other') {
                $('#finalCallBackTime').val($('#customCallBackTime').val());
            } else {
                $('#finalCallBackTime').val($('#callBackTime').val());
            }
        }
        
        if ($('#callBackAt').length) {
            if ($('#callBackAt').val() === 'other') {
                $('#finalCallBackAt').val($('#customCallBackAt').val());
            } else {
                $('#finalCallBackAt').val($('#callBackAt').val());
            }
        }
        
        if ($('#scheduleDate').length) {
            const scheduleDate = $('#scheduleDate').val();
            if (scheduleDate) {
                $('#finalScheduleDate').val('Shedule at ' + scheduleDate);
            }
        }
    }

    function getFinalPlanValue() {
        return $('#selectedPlan').length ? ($('#finalSelectedPlan').val() || '') : '';
    }

    function getFinalUpgradedPlanValue() {
        return $('#upgradedPlan').length ? ($('#finalUpgradedPlan').val() || '') : '';
    }

    function getFinalDurationValue() {
        return $('#upgradedDuration').length ? ($('#finalUpgradedDuration').val() || '') : '';
    }

    function getFinalCallBackValue() {
        if ($('#callBackTime').length) {
            return $('#finalCallBackTime').val() || '';
        }
        if ($('#callBackAt').length) {
            return $('#finalCallBackAt').val() || '';
        }
        if ($('#finalScheduleDate').length) {
            return $('#finalScheduleDate').val() || '';
        }
        return '';
    }

    function validateDynamicFields() {
        const response = $('#customerResponse').val();
        
        if (response === 'Plan Interested') {
            const plan = getFinalPlanValue();
            if (!plan) {
                showToast('warning', 'Warning!', 'Please select or enter a plan');
                return false;
            }
        }
        
        if (response === 'Plan Upgraded') {
            const upgradedPlan = getFinalUpgradedPlanValue();
            const duration = getFinalDurationValue();
            
            if (!upgradedPlan) {
                showToast('warning', 'Warning!', 'Please select or enter the upgraded plan');
                return false;
            }
            
            if (!duration) {
                showToast('warning', 'Warning!', 'Please select or enter the duration');
                return false;
            }
        }
        
        if (response === 'Later' || response === 'Call Back AT' || response === 'Shedule') {
            const callBack = getFinalCallBackValue();
            if (!callBack) {
                const fieldName = response === 'Shedule' ? 'schedule date' : 'call back time';
                showToast('warning', 'Warning!', `Please select or enter ${fieldName}`);
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
        
        $(`#${id}`).on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});