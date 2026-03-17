$(document).ready(function () {
    let currentPage = 1;
    let totalPages = 1;
    let allReceivedSellers = [];

    // Load received sellers
    loadReceivedSellers();

    // Apply filters
    $('#applyFilters').on('click', function () {
        currentPage = 1;
        filterAndDisplaySellers();
    });

    // Search on enter key
    $('#searchInput').on('keypress', function (e) {
        if (e.which === 13) {
            currentPage = 1;
            filterAndDisplaySellers();
        }
    });

    // Load received sellers function - ONLY shows sellers shared WITH current user
    function loadReceivedSellers() {
        $.ajax({
            url: BASE_URL + 'ajax/work-station/received-sellers/get-received-sellers.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log('Received sellers response:', response);
                if (response.status === 'success' && response.data) {
                    allReceivedSellers = response.data;
                    filterAndDisplaySellers();
                } else {
                    showEmptyState('No sellers have been shared with you yet');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading received sellers:', error);
                $('#receivedSellersTable').html(`
                    <tr>
                        <td colspan="7" class="text-center py-4 text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Failed to load received sellers
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Filter and display sellers
    function filterAndDisplaySellers() {
        const status = $('#filterStatus').val();
        const sharedBy = $('#filterSharedBy').val();
        const search = $('#searchInput').val().toLowerCase();

        let filteredSellers = [...allReceivedSellers];

        // Filter by status
        if (status !== 'all') {
            filteredSellers = filteredSellers.filter(seller => seller.status === status);
        }

        // Filter by shared by user
        if (sharedBy !== 'all') {
            filteredSellers = filteredSellers.filter(seller => seller.shared_by_user_uid === sharedBy);
        }

        // Filter by search
        if (search) {
            filteredSellers = filteredSellers.filter(seller => 
                (seller.customer_name && seller.customer_name.toLowerCase().includes(search)) ||
                (seller.phone_number && seller.phone_number.includes(search))
            );
        }

        // Update total count
        $('#totalCount').text(filteredSellers.length);

        // Paginate
        const itemsPerPage = 10;
        totalPages = Math.ceil(filteredSellers.length / itemsPerPage);
        const start = (currentPage - 1) * itemsPerPage;
        const paginatedSellers = filteredSellers.slice(start, start + itemsPerPage);

        if (paginatedSellers.length > 0) {
            renderSellersTable(paginatedSellers);
            renderPagination();
        } else {
            showEmptyState('No sellers match your filters');
        }
    }

    // Render sellers table
    function renderSellersTable(sellers) {
        let html = '';

        sellers.forEach(function (seller) {
            const statusClass = 
                seller.status === 'accepted' ? 'badge-accepted' :
                seller.status === 'rejected' ? 'badge-rejected' : 
                'badge-pending';
            
            const statusText = seller.status.charAt(0).toUpperCase() + seller.status.slice(1);

            html += `
                <tr>
                    <td>
                        <span class="badge bg-secondary">${escapeHtml(seller.seller_id) || 'N/A'}</span>
                    </td>
                    <td>
                        <div class="fw-bold">${escapeHtml(seller.customer_name)}</div>
                        <small class="text-muted">${escapeHtml(seller.phone_number)}</small>
                    </td>
                    <td>
                        <div class="fw-bold">${escapeHtml(seller.shared_by_name)}</div>
                        <small class="text-muted">${escapeHtml(seller.shared_by_phone)}</small>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">${escapeHtml(seller.customer_response)}</span>
                    </td>
                    <td>
                        <span class="badge ${statusClass}">${statusText}</span>
                    </td>
                    <td>
                        <small>${formatDate(seller.shared_at)}</small>
                    </td>
                    <td>
                        <div class="btn-group action-buttons">
                            <button class="btn btn-sm btn-outline-success view-share" 
                                    data-id="${seller.id}"
                                    title="View Details">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <button class="btn btn-sm btn-outline-warning update-status" 
                                    data-id="${seller.id}"
                                    data-status="${seller.status}"
                                    title="Update Status">
                                <i class="bi bi-arrow-repeat"></i> Update
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#receivedSellersTable').html(html);

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

    // Show empty state
    function showEmptyState(message) {
        $('#receivedSellersTable').html(`
            <tr>
                <td colspan="7" class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">${message || 'No sellers shared with you'}</h5>
                    <p class="text-muted">When someone shares a seller with you, it will appear here</p>
                </td>
            </tr>
        `);
        $('#totalCount').text('0');
        $('#pagination').empty();
    }

    // Render pagination
    function renderPagination() {
        let paginationHtml = '';
        
        if (currentPage > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link text-success" href="#" data-page="${currentPage - 1}">Previous</a>
                </li>
            `;
        }

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link ${i === currentPage ? 'bg-success border-success' : 'text-success'}" 
                           href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        if (currentPage < totalPages) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link text-success" href="#" data-page="${currentPage + 1}">Next</a>
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
                filterAndDisplaySellers();
            }
        });
    }

    // View share details
    function viewShareDetails(shareId) {
        $.ajax({
            url: BASE_URL + 'ajax/work-station/received-sellers/get-share-details.php',
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
        const statusClass = 
            share.status === 'accepted' ? 'badge-accepted' :
            share.status === 'rejected' ? 'badge-rejected' : 
            'badge-pending';
        
        const statusText = share.status.charAt(0).toUpperCase() + share.status.slice(1);

        const html = `
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="fw-bold text-success">Seller ID:</label>
                        <div><span class="badge bg-secondary fs-6">${escapeHtml(share.seller_id) || 'N/A'}</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold text-success">Customer Name:</label>
                        <div class="fs-5">${escapeHtml(share.customer_name)}</div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold text-success">Phone Number:</label>
                        <div>${escapeHtml(share.phone_number)}</div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold text-success">Customer Response:</label>
                        <div><span class="badge bg-light text-dark fs-6">${escapeHtml(share.customer_response)}</span></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="fw-bold text-success">Shared By:</label>
                        <div class="fw-bold">${escapeHtml(share.shared_by_name)}</div>
                        <small class="text-muted">${escapeHtml(share.shared_by_phone)}</small>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold text-success">Status:</label>
                        <div><span class="badge ${statusClass} fs-6">${statusText}</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold text-success">Shared At:</label>
                        <div>${formatDate(share.shared_at)}</div>
                    </div>
                </div>
            </div>
            ${share.notes ? `
            <div class="row">
                <div class="col-12">
                    <div class="mb-3">
                        <label class="fw-bold text-success">Notes from sharer:</label>
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
            url: BASE_URL + 'ajax/work-station/received-sellers/update-share-status.php',
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
                    loadReceivedSellers(); // Reload the list
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