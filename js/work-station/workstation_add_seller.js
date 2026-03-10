$(document).ready(function() {
    
    // Customer Response Change Handler
    $('#customerResponse').on('change', function() {
        const response = $(this).val();
        const container = $('#dynamicFieldsContainer');
        container.empty(); // Clear previous dynamic fields
        
        let html = '';
        
        switch(response) {
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
                
            case 'Call Back AT':
                html = generateCallBackAtFields();
                break;
                
            case 'Shedule':
                html = generateScheduleFields();
                break;
                
            default:
                // For other responses, no dynamic fields
                break;
        }
        
        container.html(html);
        
        // Initialize the "Other" option handlers for newly added fields
        initializeOtherOptionHandlers();
        
        // Initialize datepicker if schedule fields are shown
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
            
            // Handle date selection
            $('#scheduleDate').on('change', function() {
                const selectedDate = $(this).val();
                if (selectedDate) {
                    // Format: Shedule at dd/mm/yyyy
                    $('#finalScheduleDate').val('Shedule at ' + selectedDate);
                }
            });
            
            // Handle calendar icon click
            $('#calendarIcon').click(function() {
                $('#scheduleDate').datepicker('show');
            });
            
            // Handle clear date
            $('#clearDate').click(function() {
                $('#scheduleDate').val('');
                $('#finalScheduleDate').val('');
            });
        }
    }

    // Initialize "Other" option handlers for all dynamic selects
    function initializeOtherOptionHandlers() {
        // Plan Interested - Custom Plan
        $('#selectedPlan').on('change', function() {
            if ($(this).val() === 'other') {
                $('#customPlanContainer').show();
                $('#customPlan').prop('required', true);
            } else {
                $('#customPlanContainer').hide();
                $('#customPlan').prop('required', false);
                $('#finalSelectedPlan').val($(this).val());
            }
        });

        // Plan Upgraded - Custom Plan
        $('#upgradedPlan').on('change', function() {
            if ($(this).val() === 'other') {
                $('#customUpgradedPlanContainer').show();
                $('#customUpgradedPlan').prop('required', true);
            } else {
                $('#customUpgradedPlanContainer').hide();
                $('#customUpgradedPlan').prop('required', false);
                $('#finalUpgradedPlan').val($(this).val());
            }
        });

        // Plan Upgraded - Custom Duration
        $('#upgradedDuration').on('change', function() {
            if ($(this).val() === 'other') {
                $('#customDurationContainer').show();
                $('#customDuration').prop('required', true);
            } else {
                $('#customDurationContainer').hide();
                $('#customDuration').prop('required', false);
                $('#finalUpgradedDuration').val($(this).val());
            }
        });

        // Later - Custom Call Back Time
        $('#callBackTime').on('change', function() {
            if ($(this).val() === 'other') {
                $('#customCallBackContainer').show();
                $('#customCallBackTime').prop('required', true);
            } else {
                $('#customCallBackContainer').hide();
                $('#customCallBackTime').prop('required', false);
                $('#finalCallBackTime').val($(this).val());
            }
        });

        // Call Back AT - Custom Time
        $('#callBackAt').on('change', function() {
            if ($(this).val() === 'other') {
                $('#customCallBackAtContainer').show();
                $('#customCallBackAt').prop('required', true);
            } else {
                $('#customCallBackAtContainer').hide();
                $('#customCallBackAt').prop('required', false);
                $('#finalCallBackAt').val($(this).val());
            }
        });

        // Update final values when custom fields are filled
        $('#customPlan').on('input', function() {
            $('#finalSelectedPlan').val($(this).val());
        });

        $('#customUpgradedPlan').on('input', function() {
            $('#finalUpgradedPlan').val($(this).val());
        });

        $('#customDuration').on('input', function() {
            $('#finalUpgradedDuration').val($(this).val());
        });

        $('#customCallBackTime').on('input', function() {
            $('#finalCallBackTime').val($(this).val());
        });

        $('#customCallBackAt').on('input', function() {
            $('#finalCallBackAt').val($(this).val());
        });
    }

    // Call Duration Handler
    $('#callDurationSelect').on('change', function() {
        if ($(this).val() === 'other') {
            $('#customCallDurationContainer').show();
            $('#customCallDuration').prop('required', true);
            $('#callDuration').val(''); // Clear hidden field until custom value is entered
        } else {
            $('#customCallDurationContainer').hide();
            $('#customCallDuration').prop('required', false);
            $('#callDuration').val($(this).val());
        }
    });

    $('#customCallDuration').on('input', function() {
        $('#callDuration').val($(this).val());
    });

    // Form Reset Handler
    $('button[type="reset"]').on('click', function(e) {
        e.preventDefault();
        $('#sellerForm')[0].reset();
        $('#dynamicFieldsContainer').empty();
        $('#customCallDurationContainer').hide();
        $('#callDuration').val('');
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
        
        // Process dynamic field values before submission
        processDynamicFieldValues();
        
        // Validate dynamic fields based on response
        if (!validateDynamicFields()) {
            return;
        }
        
        // Collect form data
        const formData = {
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
        
        console.log('Submitting form data:', formData); // For debugging
        
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
                    $('#customCallDurationContainer').hide();
                    
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
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                
                let errorMessage = 'Failed to save seller. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch(e) {
                    errorMessage = 'Server error: ' + xhr.responseText;
                }
                
                showToast('danger', 'Error!', errorMessage);
            },
            complete: function() {
                $submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Process dynamic field values
    function processDynamicFieldValues() {
        // Plan Interested
        if ($('#selectedPlan').length) {
            if ($('#selectedPlan').val() === 'other') {
                $('#finalSelectedPlan').val($('#customPlan').val());
            } else {
                $('#finalSelectedPlan').val($('#selectedPlan').val());
            }
        }
        
        // Plan Upgraded - Plan
        if ($('#upgradedPlan').length) {
            if ($('#upgradedPlan').val() === 'other') {
                $('#finalUpgradedPlan').val($('#customUpgradedPlan').val());
            } else {
                $('#finalUpgradedPlan').val($('#upgradedPlan').val());
            }
        }
        
        // Plan Upgraded - Duration
        if ($('#upgradedDuration').length) {
            if ($('#upgradedDuration').val() === 'other') {
                $('#finalUpgradedDuration').val($('#customDuration').val());
            } else {
                $('#finalUpgradedDuration').val($('#upgradedDuration').val());
            }
        }
        
        // Later - Call Back Time
        if ($('#callBackTime').length) {
            if ($('#callBackTime').val() === 'other') {
                $('#finalCallBackTime').val($('#customCallBackTime').val());
            } else {
                $('#finalCallBackTime').val($('#callBackTime').val());
            }
        }
        
        // Call Back AT
        if ($('#callBackAt').length) {
            if ($('#callBackAt').val() === 'other') {
                $('#finalCallBackAt').val($('#customCallBackAt').val());
            } else {
                $('#finalCallBackAt').val($('#callBackAt').val());
            }
        }
        
        // Schedule Date
        if ($('#scheduleDate').length) {
            const scheduleDate = $('#scheduleDate').val();
            if (scheduleDate) {
                $('#finalScheduleDate').val('Shedule at ' + scheduleDate);
            }
        }
    }

    // Get final plan value
    function getFinalPlanValue() {
        if ($('#selectedPlan').length) {
            return $('#finalSelectedPlan').val() || '';
        }
        return '';
    }

    // Get final upgraded plan value
    function getFinalUpgradedPlanValue() {
        if ($('#upgradedPlan').length) {
            return $('#finalUpgradedPlan').val() || '';
        }
        return '';
    }

    // Get final duration value
    function getFinalDurationValue() {
        if ($('#upgradedDuration').length) {
            return $('#finalUpgradedDuration').val() || '';
        }
        return '';
    }

    // Get final call back value
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

    // Validate dynamic fields
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