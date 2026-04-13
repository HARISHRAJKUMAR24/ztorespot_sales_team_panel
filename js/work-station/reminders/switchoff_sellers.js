$(document).ready(function () {
    let currentPage = 1;
    let perPage = 10;
    let totalPages = 1;
    let sortColumn = 'id';
    let sortOrder = 'DESC';
    let searchTerm = '';
    let dateFilter = '';
    
    // Load initial data
    loadData();
    
    // Search input
    let searchTimer;
    $('#searchInput').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTerm = $(this).val();
        searchTimer = setTimeout(function () {
            currentPage = 1;
            loadData();
        }, 500);
    });
    
    // Per page change
    $('#perPage').on('change', function () {
        perPage = parseInt($(this).val());
        currentPage = 1;
        loadData();
    });
    
    // Apply filters button
    $('#applyFilters').on('click', function () {
        dateFilter = $('#dateFilter').val();
        currentPage = 1;
        loadData();
    });
    
    // Sort column click (for table headers)
    $(document).on('click', '.sortable', function () {
        const column = $(this).data('sort');
        if (sortColumn === column) {
            sortOrder = sortOrder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            sortColumn = column;
            sortOrder = 'ASC';
        }
        
        // Update sorting icons
        $('.sortable i').removeClass('bi-arrow-up bi-arrow-down').addClass('bi-arrow-down-up');
        const icon = $(this).find('i');
        icon.removeClass('bi-arrow-down-up').addClass(sortOrder === 'ASC' ? 'bi-arrow-up' : 'bi-arrow-down');
        
        loadData();
    });
    
    // Pagination click
    $(document).on('click', '.page-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadData();
        }
    });
    
    // Load data function
    function loadData() {
        $('#loadingSpinner').show();
        $('#dataTable, #noData').hide();
        
        let sortBy = sortColumn;
        if (sortColumn === 'work_details_update') {
            sortBy = 'work_details_update';
        } else if (sortColumn === 'phone_number') {
            sortBy = 'phone_number';
        } else if (sortColumn === 'entry_date') {
            sortBy = 'entry_date';
        }
        
        $.ajax({
            url: BASE_URL + 'ajax/work-station/reminders/switchoff_sellers.php',
            type: 'POST',
            data: {
                action: 'get_data',
                page: currentPage,
                per_page: perPage,
                sort_column: sortBy,
                sort_order: sortOrder,
                search: searchTerm,
                date_filter: dateFilter
            },
            dataType: 'json',
            success: function (response) {
                $('#loadingSpinner').hide();
                
                if (response.status === 'success') {
                    if (response.data.rows && response.data.rows.length > 0) {
                        renderTable(response.data.rows);
                        renderPagination(response.data.total, response.data.page, response.data.per_page);
                        updateStats(response.data.stats);
                        $('#dataTable').show();
                    } else {
                        $('#noData').show();
                        if (response.data.stats) {
                            updateStats(response.data.stats);
                        } else {
                            $('#totalCount').text('0');
                            $('#weekCount').text('0');
                            $('#monthCount').text('0');
                        }
                    }
                } else {
                    showToast('danger', 'Error', response.message || 'Failed to load data');
                    $('#noData').show();
                }
            },
            error: function (xhr, status, error) {
                $('#loadingSpinner').hide();
                $('#noData').show();
                console.error('AJAX Error:', error);
                showToast('danger', 'Error', 'Failed to load data. Please try again.');
            }
        });
    }
    
    // Render table
    function renderTable(rows) {
        let html = '';
        
        rows.forEach(function (row) {
            let dateDisplay = row.entry_date ? new Date(row.entry_date + 'T00:00:00').toLocaleDateString('en-GB') : '-';
            
            let statusBadge = 'bg-secondary';
            if (row.current_status === 'Upgraded') statusBadge = 'bg-success';
            else if (row.current_status === 'In Progress') statusBadge = 'bg-info';
            else if (row.current_status === 'Not yet') statusBadge = 'bg-warning';
            else if (row.current_status === 'Deleted') statusBadge = 'bg-danger';
            
            html += '<tr>';
            html += '<td class="px-3">' + escapeHtml(row.id) + '</td>';
            html += '<td class="px-3">' + dateDisplay + '</td>';
            html += '<td class="px-3"><div class="fw-semibold">' + escapeHtml(row.work_details_update || '-') + '</div>';
            if (row.customer_queries) {
                html += '<small class="text-muted d-block text-truncate" style="max-width: 200px;">';
                html += '<i class="bi bi-chat-text me-1"></i>' + escapeHtml(row.customer_queries.substring(0, 30)) + '...';
                html += '</small>';
            }
            html += '</td>';
            html += '<td class="px-3"><span class="badge bg-light text-dark"><i class="bi bi-telephone me-1"></i>' + escapeHtml(row.phone_number || '-') + '</span></td>';
            html += '<td class="px-3"><span class="badge bg-info bg-opacity-10 text-info">' + escapeHtml(row.source_type || 'N/A') + '</span></td>';
            html += '<td class="px-3"><span class="badge ' + statusBadge + ' bg-opacity-10 text-' + statusBadge.replace('bg-', '') + '">' + escapeHtml(row.current_status || 'Pending') + '</span></td>';
            html += '<td class="px-3 text-center"><div class="btn-group btn-group-sm">';
            html += '<button class="btn btn-sm btn-outline-info view-btn" data-id="' + row.id + '" title="View"><i class="bi bi-eye"></i></button>';
            html += '<a href="' + BASE_URL + 'work-station/sheets_edit_seller.php?id=' + row.id + '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>';
            html += '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' + row.id + '" title="Delete"><i class="bi bi-trash"></i></button>';
            html += '</div></td>';
            html += '</tr>';
        });
        
        $('#tableBody').html(html);
    }
    
    // Render pagination
    function renderPagination(total, page, perPage) {
        totalPages = Math.ceil(total / perPage);
        let html = '';
        
        if (totalPages > 1) {
            html += '<li class="page-item ' + (page === 1 ? 'disabled' : '') + '">';
            html += '<a class="page-link bg-secondary text-white border-secondary" href="#" data-page="' + (page - 1) + '">&laquo;</a>';
            html += '</li>';
            
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                    html += '<li class="page-item ' + (i === page ? 'active' : '') + '">';
                    html += '<a class="page-link ' + (i === page ? 'bg-secondary text-white border-secondary' : 'text-secondary') + '" href="#" data-page="' + i + '">' + i + '</a>';
                    html += '</li>';
                } else if (i === page - 3 || i === page + 3) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            
            html += '<li class="page-item ' + (page === totalPages ? 'disabled' : '') + '">';
            html += '<a class="page-link bg-secondary text-white border-secondary" href="#" data-page="' + (page + 1) + '">&raquo;</a>';
            html += '</li>';
        }
        
        $('#pagination').html(html);
        
        const start = (page - 1) * perPage + 1;
        const end = Math.min(page * perPage, total);
        $('#paginationInfo').html('Showing ' + start + ' to ' + end + ' of ' + total + ' entries');
    }
    
    // Update stats
    function updateStats(stats) {
        $('#totalCount').text(stats.total || 0);
        $('#weekCount').text(stats.week_count || 0);
        $('#monthCount').text(stats.month_count || 0);
    }
    
    // View button click
    $(document).on('click', '.view-btn', function () {
        const id = $(this).data('id');
        
        $.ajax({
            url: BASE_URL + 'ajax/work-station/reminders/switchoff_sellers.php',
            type: 'POST',
            data: { action: 'get_details', id: id },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    showSellerDetails(response.data);
                    new bootstrap.Modal(document.getElementById('viewModal')).show();
                } else {
                    showToast('danger', 'Error', response.message || 'Failed to load details');
                }
            },
            error: function () {
                showToast('danger', 'Error', 'Failed to load seller details');
            }
        });
    });
    
    // Show seller details
    function showSellerDetails(seller) {
        let html = '';
        
        let dateDisplay = seller.entry_date ? new Date(seller.entry_date + 'T00:00:00').toLocaleDateString('en-GB') : '-';
        let createdDisplay = seller.created_at ? new Date(seller.created_at).toLocaleString('en-GB') : '-';
        let updatedDisplay = seller.updated_at ? new Date(seller.updated_at).toLocaleString('en-GB') : '-';
        
        const fields = [
            ['ID', seller.id],
            ['Entry Date', dateDisplay],
            ['Business Name', seller.work_details_update],
            ['Source Type', seller.source_type],
            ['Registration Status', seller.registration_status],
            ['Phone Number', seller.phone_number],
            ['Seller ID', seller.seller_id],
            ['Plans Interested', seller.plans_interested],
            ['Customer Response', seller.customer_response],
            ['Call Timing', seller.call_timing],
            ['Current Status', seller.current_status],
            ['Products Uploaded', seller.products_uploaded],
            ['Remembering Notes', seller.remembering_notes],
            ['Latest Update', seller.latest_update],
            ['Customer Queries', seller.customer_queries],
            ['Customer Doubts', seller.customer_doubts],
            ['Remarks', seller.remarks],
            ['Created At', createdDisplay],
            ['Last Updated', updatedDisplay]
        ];
        
        fields.forEach(function (field) {
            if (field[1] && field[1] !== '' && field[1] !== null && field[1] !== '-') {
                html += `
                    <div class="col-12 col-sm-6 mb-3">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">${field[0]}</small>
                            <strong class="d-block">${escapeHtml(field[1].toString())}</strong>
                        </div>
                    </div>
                `;
            }
        });
        
        $('#sellerDetails').html(html);
    }
    
    // Delete button click
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this seller?')) {
            $.ajax({
                url: BASE_URL + 'ajax/work-station/reminders/switchoff_sellers.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        showToast('success', 'Success', response.message || 'Seller deleted successfully');
                        loadData();
                    } else {
                        showToast('danger', 'Error', response.message || 'Failed to delete seller');
                    }
                },
                error: function () {
                    showToast('danger', 'Error', 'Failed to delete seller');
                }
            });
        }
    });
    
    // Escape HTML
    function escapeHtml(text) {
        if (!text || text === null || text === '') return '-';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Toast message
    function showToast(type, title, message) {
        const id = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : type === 'danger' ? 'bg-danger' : 'bg-secondary';
        
        const html = `
            <div id="${id}" class="toast text-white ${bgClass}" role="alert" data-bs-autohide="true" data-bs-delay="3000">
                <div class="toast-body d-flex justify-content-between align-items-center">
                    <div><strong>${title}</strong> ${message}</div>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="toast"></button>
                </div>
            </div>`;
        
        $('.toast-container').append(html);
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