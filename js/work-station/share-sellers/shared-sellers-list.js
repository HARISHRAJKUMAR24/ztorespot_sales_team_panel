$(document).ready(function () {
    // State variables
    let currentPage = 1;
    let itemsPerPage = 10;
    let totalItems = 0;
    let allShares = [];
    let filteredShares = [];
    
    // Filter variables
    let currentType = 'all';
    let currentStatus = 'all';
    let currentSearch = '';

    // Load shared sellers
    loadSharedSellers();

    // Apply filters
    $('#applyFilters').on('click', function () {
        currentPage = 1;
        currentType = $('#filterType').val();
        currentStatus = $('#filterStatus').val();
        currentSearch = $('#searchInput').val().trim();
        
        filterAndDisplayShares();
        updateActiveFilters();
    });

    // Clear search
    $('#clearSearch').on('click', function () {
        $('#searchInput').val('');
        currentSearch = '';
        currentPage = 1;
        filterAndDisplayShares();
        updateActiveFilters();
    });

    // Clear all filters
    $('#clearAllFilters').on('click', function () {
        $('#filterType').val('all');
        $('#filterStatus').val('all');
        $('#searchInput').val('');
        
        currentType = 'all';
        currentStatus = 'all';
        currentSearch = '';
        currentPage = 1;
        
        filterAndDisplayShares();
        updateActiveFilters();
    });

    // Refresh table
    $('#refreshTable').on('click', function () {
        loadSharedSellers();
    });

    // Search on enter key
    $('#searchInput').on('keypress', function (e) {
        if (e.which === 13) {
            currentPage = 1;
            currentSearch = $(this).val().trim();
            currentType = $('#filterType').val();
            currentStatus = $('#filterStatus').val();
            
            filterAndDisplayShares();
            updateActiveFilters();
        }
    });

    // Load shared sellers function
    function loadSharedSellers() {
        showLoading();
        
        $.ajax({
            url: BASE_URL + 'ajax/work-station/share-sellers/get-shared-sellers.php',
            type: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function (response) {
                console.log('Shared sellers response:', response);
                if (response.status === 'success' && response.data) {
                    allShares = response.data;
                    filterAndDisplayShares();
                    updateSummaryStats();
                } else {
                    showEmptyState(response.message || 'No data found');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading shared sellers:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                let errorMessage = 'Failed to load shared sellers';
                if (xhr.status === 404) {
                    errorMessage = 'API endpoint not found';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error - check PHP error logs';
                }
                
                $('#sharedSellersTable').html(`
                    <tr>
                        <td colspan="8" class="text-center py-5 text-danger">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                            <p class="mt-2">${errorMessage}</p>
                            <small class="text-muted">Check browser console for details</small>
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Filter and display shares
    function filterAndDisplayShares() {
        // Apply filters
        filteredShares = allShares.filter(function (share) {
            let matchesType = true;
            let matchesStatus = true;
            let matchesSearch = true;

            // Filter by type
            if (currentType !== 'all') {
                if (currentType === 'sent') {
                    matchesType = share.share_type === 'sent';
                } else if (currentType === 'received') {
                    matchesType = share.share_type === 'received';
                }
            }

            // Filter by status
            if (currentStatus !== 'all') {
                matchesStatus = share.status === currentStatus;
            }

            // Filter by search
            if (currentSearch) {
                const searchLower = currentSearch.toLowerCase();
                const sellerName = (share.customer_name || '').toLowerCase();
                const phoneNumber = (share.phone_number || '').toLowerCase();
                const sharedByName = (share.shared_by_name || '').toLowerCase();
                const sharedWithName = (share.shared_with_name || '').toLowerCase();
                const sellerId = (share.seller_id || '').toString();
                
                matchesSearch = sellerName.includes(searchLower) ||
                               phoneNumber.includes(searchLower) ||
                               sharedByName.includes(searchLower) ||
                               sharedWithName.includes(searchLower) ||
                               sellerId.includes(searchLower);
            }

            return matchesType && matchesStatus && matchesSearch;
        });

        // Update summary stats
        updateSummaryStats();
        
        // Update total items
        totalItems = filteredShares.length;
        
        // Paginate
        const start = (currentPage - 1) * itemsPerPage;
        const paginatedShares = filteredShares.slice(start, start + itemsPerPage);

        if (paginatedShares.length > 0) {
            renderSharesTable(paginatedShares);
            renderPagination();
            updatePaginationInfo();
        } else {
            showEmptyState('No shares found matching your filters');
        }
        
        // Update showing count
        const end = Math.min(start + itemsPerPage, totalItems);
        $('#showingCount').text(`Showing ${totalItems > 0 ? start + 1 : 0}-${end} of ${totalItems} entries`);
    }

    // Render shares table
    function renderSharesTable(shares) {
        let html = '';

        shares.forEach(function (share) {
            const typeClass = share.share_type === 'sent' ? 'badge-sent' : 'badge-received';
            const typeText = share.share_type === 'sent' ? 'Sent' : 'Received';
            
            const statusClass = 
                share.status === 'accepted' ? 'badge-accepted' :
                share.status === 'rejected' ? 'badge-rejected' : 
                'badge-pending';
            
            const statusText = share.status ? share.status.charAt(0).toUpperCase() + share.status.slice(1) : 'Pending';

            // Determine shared with/by text
            let sharedWithByHtml = '';
            if (share.share_type === 'sent') {
                sharedWithByHtml = `
                    <div class="fw-bold">To: ${escapeHtml(share.shared_with_name || 'N/A')}</div>
                    <small class="text-muted">${escapeHtml(share.shared_with_phone || '')}</small>
                `;
            } else {
                sharedWithByHtml = `
                    <div class="fw-bold">From: ${escapeHtml(share.shared_by_name || 'N/A')}</div>
                    <small class="text-muted">${escapeHtml(share.shared_by_phone || '')}</small>
                `;
            }

            html += `
                <tr>
                    <td>
                        <span class="badge ${typeClass} share-type-badge">${typeText}</span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">${escapeHtml(share.seller_id) || 'N/A'}</span>
                    </td>
                    <td>
                        <div class="fw-bold">${escapeHtml(share.customer_name)}</div>
                        <small class="text-muted">${escapeHtml(share.phone_number)}</small>
                    </td>
                    <td>
                        ${sharedWithByHtml}
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">${escapeHtml(share.customer_response)}</span>
                    </td>
                    <td>
                        <span class="badge ${statusClass} status-badge">${statusText}</span>
                    </td>
                    <td>
                        <small>${formatDate(share.shared_at)}</small>
                    </td>
                    <td>
                        <div class="btn-group action-buttons">
                            <button class="btn btn-sm btn-outline-primary view-share" 
                                    data-id="${share.id}"
                                    title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${share.share_type === 'received' && share.status === 'pending' ? `
                                <button class="btn btn-sm btn-outline-success update-status" 
                                        data-id="${share.id}"
                                        data-status="${share.status}"
                                        title="Update Status">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#sharedSellersTable').html(html);

        // Attach view handlers
        $('.view-share').on('click', function () {
            const shareId = $(this).data('id');
            viewShareDetails(shareId);
        });

        // Attach update status handlers
        $('.update-status').on('click', function () {
            const shareId = $(this).data('id');
            const currentStatus = $(this).data('status');
            showUpdateStatusModal(shareId, currentStatus);
        });
    }

    // Render pagination
    function renderPagination() {
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        let paginationHtml = '';
        
        // Previous button
        if (currentPage > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            `;
        }

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>
            `;
            if (startPage > 2) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                </li>
            `;
        }

        // Next button
        if (currentPage < totalPages) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            `;
        }

        $('#pagination').html(paginationHtml);

        // Attach pagination click handlers
        $('#pagination .page-link').on('click', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                currentPage = parseInt(page);
                filterAndDisplayShares();
            }
        });
    }

    // Update pagination info
    function updatePaginationInfo() {
        const start = (currentPage - 1) * itemsPerPage + 1;
        const end = Math.min(currentPage * itemsPerPage, totalItems);
        $('#paginationInfo').text(`Page ${currentPage} of ${Math.ceil(totalItems / itemsPerPage)}`);
    }

    // Update summary statistics
    function updateSummaryStats() {
        const total = allShares.length;
        const pending = allShares.filter(s => s.status === 'pending').length;
        const accepted = allShares.filter(s => s.status === 'accepted').length;
        const rejected = allShares.filter(s => s.status === 'rejected').length;
        
        $('#totalCount').text(total);
        $('#pendingCount').text(pending);
        $('#acceptedCount').text(accepted);
        $('#rejectedCount').text(rejected);
    }

    // Update active filters display
    function updateActiveFilters() {
        const hasFilters = currentType !== 'all' || currentStatus !== 'all' || currentSearch !== '';
        
        if (hasFilters) {
            $('#activeFilters').show();
            
            $('#activeTypeFilter').text(`Type: ${currentType === 'all' ? 'All' : currentType}`).toggle(currentType !== 'all');
            $('#activeStatusFilter').text(`Status: ${currentStatus === 'all' ? 'All' : currentStatus}`).toggle(currentStatus !== 'all');
            $('#activeSearchFilter').text(`Search: "${currentSearch}"`).toggle(currentSearch !== '');
        } else {
            $('#activeFilters').hide();
        }
    }

    // Show loading state
    function showLoading() {
        $('#sharedSellersTable').html(`
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted">Loading shared sellers...</p>
                </td>
            </tr>
        `);
    }

    // Show empty state
    function showEmptyState(message) {
        $('#sharedSellersTable').html(`
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-2">${message || 'No shared sellers found'}</p>
                </td>
            </tr>
        `);
        $('#pagination').empty();
        $('#paginationInfo').empty();
        $('#showingCount').text('Showing 0-0 of 0 entries');
    }

    // View share details
    function viewShareDetails(shareId) {
        $.ajax({
            url: BASE_URL + 'ajax/work-station/share-sellers/get-share-details.php',
            type: 'GET',
            data: { share_id: shareId },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success' && response.data) {
                    showShareDetailsModal(response.data);
                } else {
                    showToast('danger', 'Error', response.message || 'Failed to load details');
                }
            },
            error: function () {
                showToast('danger', 'Error', 'Failed to load share details');
            }
        });
    }

    // Show share details modal
    function showShareDetailsModal(share) {
        const typeClass = share.share_type === 'sent' ? 'badge-sent' : 'badge-received';
        const typeText = share.share_type === 'sent' ? 'Sent' : 'Received';
        
        const statusClass = 
            share.status === 'accepted' ? 'badge-accepted' :
            share.status === 'rejected' ? 'badge-rejected' : 
            'badge-pending';
        
        const statusText = share.status ? share.status.charAt(0).toUpperCase() + share.status.slice(1) : 'Pending';

        const html = `
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="fw-bold">Share Type:</label>
                        <div><span class="badge ${typeClass}">${typeText}</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Seller ID:</label>
                        <div><span class="badge bg-secondary">${escapeHtml(share.seller_id) || 'N/A'}</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Customer Name:</label>
                        <div>${escapeHtml(share.customer_name)}</div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Phone Number:</label>
                        <div>${escapeHtml(share.phone_number)}</div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Customer Response:</label>
                        <div><span class="badge bg-light text-dark">${escapeHtml(share.customer_response)}</span></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="fw-bold">Shared By:</label>
                        <div>${escapeHtml(share.shared_by_name || 'N/A')}</div>
                        <small class="text-muted">${escapeHtml(share.shared_by_phone || '')}</small>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Shared With:</label>
                        <div>${escapeHtml(share.shared_with_name || 'N/A')}</div>
                        <small class="text-muted">${escapeHtml(share.shared_with_phone || '')}</small>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Status:</label>
                        <div><span class="badge ${statusClass}">${statusText}</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Shared At:</label>
                        <div>${formatDate(share.shared_at)}</div>
                    </div>
                </div>
            </div>
            ${share.notes ? `
            <div class="row">
                <div class="col-12">
                    <div class="mb-3">
                        <label class="fw-bold">Notes:</label>
                        <div class="p-3 bg-light rounded">${escapeHtml(share.notes)}</div>
                    </div>
                </div>
            </div>
            ` : ''}
        `;

        $('#shareDetailsContent').html(html);
        $('#shareDetailsModal').modal('show');
    }

    // Show update status modal
    function showUpdateStatusModal(shareId, currentStatus) {
        $('#updateShareId').val(shareId);
        $('#updateStatus').val(currentStatus);
        $('#updateStatusModal').modal('show');
    }

    // Confirm status update
    $('#confirmUpdate').on('click', function () {
        const shareId = $('#updateShareId').val();
        const newStatus = $('#updateStatus').val();

        $.ajax({
            url: BASE_URL + 'ajax/work-station/share-sellers/update-share-status.php',
            type: 'POST',
            data: {
                share_id: shareId,
                status: newStatus
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', 'Status updated successfully');
                    $('#updateStatusModal').modal('hide');
                    loadSharedSellers(); // Reload the list
                } else {
                    showToast('danger', 'Error!', response.message);
                }
            },
            error: function () {
                showToast('danger', 'Error!', 'Failed to update status');
            }
        });
    });

    // Helper functions
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + 
               date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function showToast(type, title, message) {
        const id = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' :
            type === 'danger' ? 'bg-danger' :
            type === 'warning' ? 'bg-warning' : 'bg-info';

        const html = `
            <div id="${id}" class="toast text-white ${bgClass}" role="alert" 
                 data-bs-autohide="true" data-bs-delay="3000">
                <div class="toast-header ${bgClass} text-white border-0">
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">${message}</div>
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