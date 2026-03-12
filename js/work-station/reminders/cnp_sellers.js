$(document).ready(function() {
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // View Seller Details
    $('.view-seller').on('click', function() {
        const sellerId = $(this).data('id');
        
        if (!sellerId) {
            showToast('warning', 'Warning!', 'Invalid seller ID');
            return;
        }
        
        // Show modal with loading
        $('#sellerDetails').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading seller details...</p>
            </div>
        `);
        
        $('#viewSellerModal').modal('show');
        
        // Set edit button link
        $('#editFromModal').attr('href', 'sheets_edit_seller.php?id=' + sellerId);
        
        // Fetch seller details
        $.ajax({
            url: BASE_URL + 'ajax/work-station/reminders/get_seller_details.php',
            type: 'POST',
            data: { id: sellerId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    displaySellerDetails(response.data);
                } else {
                    $('#sellerDetails').html(`
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            ${response.message || 'Failed to load seller details'}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#sellerDetails').html(`
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Error loading seller details. Please try again.
                    </div>
                `);
            }
        });
    });

    // Display seller details in modal
    function displaySellerDetails(seller) {
        if (!seller) return;
        
        const registrationStatus = seller.registration_status || 'No';
        const statusClass = registrationStatus === 'Yes' ? 'success' : 'secondary';
        
        const currentStatus = seller.current_status || 'Not yet';
        let statusBadgeClass = 'secondary';
        if (currentStatus === 'Upgraded') statusBadgeClass = 'success';
        else if (currentStatus === 'In Progress') statusBadgeClass = 'warning';
        else if (currentStatus === 'Deleted') statusBadgeClass = 'danger';
        
        const entryDate = seller.entry_date ? new Date(seller.entry_date).toLocaleDateString('en-GB') : 'Not set';
        const createdDate = seller.created_at ? new Date(seller.created_at).toLocaleString() : 'Unknown';
        const updatedDate = seller.updated_at ? new Date(seller.updated_at).toLocaleString() : 'Never';
        
        const html = `
            <div class="container-fluid px-0">
                <!-- Basic Information -->
                <div class="card mb-3 border-0 bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-success mb-3">
                            <i class="bi bi-info-circle-fill me-2"></i>Basic Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small mb-1">Business Name</label>
                                <div class="fw-semibold">${escapeHtml(seller.work_details_update || 'N/A')}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small mb-1">Phone Number</label>
                                <div class="fw-semibold">
                                    <span class="badge bg-light text-dark fs-6">
                                        <i class="bi bi-telephone me-1"></i>
                                        ${escapeHtml(seller.phone_number || 'N/A')}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small mb-1">Seller Type</label>
                                <div><span class="badge bg-info">${escapeHtml(seller.source_type || 'N/A')}</span></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small mb-1">Registration Status</label>
                                <div><span class="badge bg-${statusClass}">${registrationStatus}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Response Information -->
                <div class="card mb-3 border-0 bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-success mb-3">
                            <i class="bi bi-chat-dots-fill me-2"></i>Response Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small mb-1">Customer Response</label>
                                <div><span class="badge bg-danger">${escapeHtml(seller.customer_response || 'N/A')}</span></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small mb-1">Plans Interested</label>
                                <div>${escapeHtml(seller.plans_interested || 'None')}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small mb-1">Call Timing</label>
                                <div>${escapeHtml(seller.call_timing || 'Not set')}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small mb-1">Current Status</label>
                                <div><span class="badge bg-${statusBadgeClass}">${escapeHtml(currentStatus)}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notes and Updates -->
                <div class="card mb-3 border-0 bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-success mb-3">
                            <i class="bi bi-journal-text me-2"></i>Notes & Updates
                        </h6>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="text-muted small mb-1">Remembering Notes</label>
                                <div class="p-2 bg-white rounded">${escapeHtml(seller.remembering_notes || 'No notes')}</div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="text-muted small mb-1">Latest Update</label>
                                <div class="p-2 bg-white rounded">${escapeHtml(seller.latest_update || 'No updates')}</div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="text-muted small mb-1">Customer Queries</label>
                                <div class="p-2 bg-white rounded">${escapeHtml(seller.customer_queries || 'No queries')}</div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small mb-1">Remarks</label>
                                <div class="p-2 bg-white rounded">${escapeHtml(seller.remarks || 'No remarks')}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Dates -->
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-success mb-3">
                            <i class="bi bi-calendar3 me-2"></i>Dates
                        </h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="text-muted small mb-1">Entry Date</label>
                                <div><i class="bi bi-calendar-check me-1"></i>${entryDate}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="text-muted small mb-1">Created At</label>
                                <div><i class="bi bi-clock me-1"></i>${createdDate}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="text-muted small mb-1">Last Updated</label>
                                <div><i class="bi bi-arrow-repeat me-1"></i>${updatedDate}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#sellerDetails').html(html);
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Toast notification function
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
            
            $(toastElement).on('hidden.bs.toast', function() {
                $(this).remove();
            });
        }
    }

    // Handle delete confirmation if needed
    $('.delete-seller').on('click', function(e) {
        e.preventDefault();
        const sellerId = $(this).data('id');
        const sellerName = $(this).data('name');
        
        if (confirm(`Are you sure you want to delete "${sellerName || 'this seller'}"?`)) {
            window.location.href = 'delete_seller.php?id=' + sellerId;
        }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').alert('close');
    }, 5000);
});