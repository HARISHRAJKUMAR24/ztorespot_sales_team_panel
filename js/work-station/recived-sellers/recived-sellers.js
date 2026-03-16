$(document).ready(function () {
    let currentPage = 1;
    let totalPages = 1;
    let allShares = [];

    // Load shared sellers
    loadSharedSellers();

    // Apply filters
    $('#applyFilters').on('click', function () {
        currentPage = 1;
        loadSharedSellers();
    });

    // Search on enter key
    $('#searchInput').on('keypress', function (e) {
        if (e.which === 13) {
            currentPage = 1;
            loadSharedSellers();
        }
    });

    // Load shared sellers function
    function loadSharedSellers() {
        const filterType = $('#filterType').val();
        const filterStatus = $('#filterStatus').val();
        const search = $('#searchInput').val();

        $.ajax({
            url: BASE_URL + 'ajax/work-station/recived-sellers/get-recent-shares.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log('Shares response:', response);
                if (response.status === 'success' && response.data) {
                    allShares = response.data;
                    filterAndDisplayShares(filterType, filterStatus, search);
                } else {
                    showEmptyState();
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading shares:', error);
                $('#sharedSellersTable').html(`
                    <tr>
                        <td colspan="8" class="text-center py-4 text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Failed to load shared sellers
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Filter and display shares
    function filterAndDisplayShares(type, status, search) {
        let filteredShares = [...allShares];

        // Filter by type
        if (type !== 'all') {
            filteredShares = filteredShares.filter(share => share.share_type === type);
        }

        // Filter by status
        if (status !== 'all') {
            filteredShares = filteredShares.filter(share => share.status === status);
        }

        // Filter by search
        if (search) {
            const searchLower = search.toLowerCase();
            filteredShares = filteredShares.filter(share => 
                (share.customer_name && share.customer_name.toLowerCase().includes(searchLower)) ||
                (share.phone_number && share.phone_number.includes(search))
            );
        }

        // Update total count
        $('#totalCount').text(filteredShares.length);

        // Paginate
        const itemsPerPage = 10;
        totalPages = Math.ceil(filteredShares.length / itemsPerPage);
        const start = (currentPage - 1) * itemsPerPage;
        const paginatedShares = filteredShares.slice(start, start + itemsPerPage);

        if (paginatedShares.length > 0) {
            renderSharesTable(paginatedShares);
            renderPagination();
        } else {
            $('#sharedSellersTable').html(`
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox me-2"></i>
                        No shares found matching your filters
                    </td>
                </tr>
            `);
            $('#pagination').empty();
        }
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
            
            const statusText = share.status.charAt(0).toUpperCase() + share.status.slice(1);

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
                        ${share.share_type === 'sent' 
                            ? `<div class="fw-bold">To: ${escapeHtml(share.shared_with_name)}</div>
                               <small class="text-muted">${escapeHtml(share.shared_with_phone)}</small>`
                            : `<div class="fw-bold">From: ${escapeHtml(share.shared_by_name)}</div>
                               <small class="text-muted">${escapeHtml(share.shared_by_phone)}</small>`
                        }
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">${escapeHtml(share.customer_response)}</span>
                    </td>
                    <td>
                        <span class="badge ${statusClass}">${statusText}</span>
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
        let paginationHtml = '';
        
        if (currentPage > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
                </li>
            `;
        }

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        if (currentPage < totalPages) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
                </li>
            `;
        }

        $('#pagination').html(paginationHtml);

        // Attach pagination click handlers
        $('#pagination .page-link').on('click', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                currentPage = page;
                const filterType = $('#filterType').val();
                const filterStatus = $('#filterStatus').val();
                const search = $('#searchInput').val();
                filterAndDisplayShares(filterType, filterStatus, search);
            }
        });
    }

    // Show empty state
    function showEmptyState() {
        $('#sharedSellersTable').html(`
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox me-2"></i>
                    No shared sellers found
                </td>
            </tr>
        `);
        $('#totalCount').text('0');
        $('#pagination').empty();
    }

    // View share details
    function viewShareDetails(shareId) {
        $.ajax({
            url: BASE_URL + 'ajax/work-station/recived-sellers/get-share-details.php',
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
        
        const statusText = share.status.charAt(0).toUpperCase() + share.status.slice(1);

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
                        <div>${escapeHtml(share.shared_by_name)}</div>
                        <small class="text-muted">${escapeHtml(share.shared_by_phone)}</small>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Shared With:</label>
                        <div>${escapeHtml(share.shared_with_name)}</div>
                        <small class="text-muted">${escapeHtml(share.shared_with_phone)}</small>
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
            url: BASE_URL + 'ajax/work-station/recived-sellers/update-share-status.php',
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