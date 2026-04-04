$(document).ready(function () {
    let currentPage = 1;
    let perPage = 10;
    let totalPages = 1;
    let sortColumn = 'id';
    let sortOrder = 'DESC';
    let searchTerm = '';
    let statusFilter = 'all';
    
    // Get plan filter from URL
    const urlParams = new URLSearchParams(window.location.search);
    const planFilter = urlParams.get('plan') || 'all';

    // Load initial data
    loadData();

    // Tab click handlers
    $('#renewalTabs .nav-link').on('click', function () {
        $('#renewalTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        statusFilter = $(this).data('filter');
        currentPage = 1;
        loadData();
    });

    // Search input with debounce
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

    // Sorting
    $(document).on('click', '.sortable', function () {
        const column = $(this).data('sort');
        if (sortColumn === column) {
            sortOrder = sortOrder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            sortColumn = column;
            sortOrder = 'ASC';
        }
        
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

        $.ajax({
           url: BASE_URL + 'ajax/work-station/reminders/renewal_sellers.php',
            type: 'POST',
            data: {
                action: 'get_data',
                page: currentPage,
                per_page: perPage,
                sort_column: sortColumn,
                sort_order: sortOrder,
                search: searchTerm,
                status_filter: statusFilter,
                plan_filter: planFilter
            },
            dataType: 'json',
            success: function (response) {
                $('#loadingSpinner').hide();
                
                if (response.status === 'success') {
                    if (response.data.rows.length > 0) {
                        renderTable(response.data.rows);
                        renderPagination(response.data.total, response.data.page, response.data.per_page);
                        updateStats(response.data.stats);
                        $('#dataTable').show();
                    } else {
                        $('#noData').show();
                        updateStats(response.data.stats);
                    }
                } else {
                    showToast('danger', 'Error', response.message);
                }
            },
            error: function (xhr, status, error) {
                $('#loadingSpinner').hide();
                $('#noData').show();
                showToast('danger', 'Error', 'Failed to load data');
                console.error('AJAX Error:', error);
            }
        });
    }

    // Render table
    function renderTable(rows) {
        let html = '';
        let activeCount = 0, alertCount = 0, nearExpiryCount = 0, expiredCount = 0;
        
        rows.forEach(function (row) {
            const renewalInfo = row.renewal_info || {
                days_remaining: null,
                status: 'unknown',
                formatted_date: 'N/A',
                start_date: 'N/A',
                duration: 'N/A',
                alert_days: 0
            };
            
            const daysRemaining = renewalInfo.days_remaining;
            const status = renewalInfo.status;
            
            // Update counters
            if (status === 'active') activeCount++;
            else if (status === 'renewal_alert') alertCount++;
            else if (status === 'near_expiry') nearExpiryCount++;
            else if (status === 'expired') expiredCount++;
            
            // Get status badge
            let statusBadge = '';
            let daysText = '';
            let badgeClass = '';
            
            if (daysRemaining === null) {
                statusBadge = '<span class="badge bg-secondary">Unknown</span>';
                daysText = 'N/A';
                badgeClass = 'bg-secondary';
            } else if (daysRemaining < 0) {
                statusBadge = '<span class="badge bg-danger">Expired</span>';
                daysText = Math.abs(daysRemaining) + ' days ago';
                badgeClass = 'bg-danger';
            } else if (daysRemaining === 0) {
                statusBadge = '<span class="badge bg-warning text-dark">Due Today</span>';
                daysText = 'Today';
                badgeClass = 'bg-warning';
            } else if (daysRemaining <= renewalInfo.alert_days) {
                statusBadge = '<span class="badge bg-warning">Renewal Alert</span>';
                daysText = daysRemaining + ' days left';
                badgeClass = 'bg-warning';
            } else if (daysRemaining <= 30) {
                statusBadge = '<span class="badge bg-info">Near Expiry</span>';
                daysText = daysRemaining + ' days left';
                badgeClass = 'bg-info';
            } else {
                statusBadge = '<span class="badge bg-success">Active</span>';
                daysText = daysRemaining + ' days left';
                badgeClass = 'bg-success';
            }
            
            // Get plan badge
            let planBadge = '';
            const plan = (row.plans_interested || '').toLowerCase();
            if (plan.includes('welcome')) {
                planBadge = '<span class="badge bg-success">Welcome Plan</span>';
            } else if (plan.includes('starter')) {
                planBadge = '<span class="badge bg-info">Starter Plan</span>';
            } else if (plan.includes('intermediate')) {
                planBadge = '<span class="badge bg-warning text-dark">Intermediate Plan</span>';
            } else if (plan.includes('professional')) {
                planBadge = '<span class="badge bg-primary">Professional Plan</span>';
            } else {
                planBadge = '<span class="badge bg-secondary">' + escapeHtml(row.plans_interested || 'N/A') + '</span>';
            }
            
            html += '<tr>';
            html += `<td>${escapeHtml(row.id)}</td>`;
            html += `<td>${escapeHtml(row.work_details_update || '-')}</td>`;
            html += `<td>${escapeHtml(row.phone_number || '-')}</td>`;
            html += `<td>${planBadge}</td>`;
            html += `<td>${escapeHtml(renewalInfo.start_date || 'N/A')}</td>`;
            html += `<td>${escapeHtml(renewalInfo.duration || 'N/A')}</td>`;
            html += `<td><span class="badge bg-secondary">${escapeHtml(renewalInfo.formatted_date || 'N/A')}</span></td>`;
            html += `<td><span class="badge days-badge ${badgeClass}">${escapeHtml(daysText)}</span></td>`;
            html += `<td>${statusBadge}</td>`;
            html += `<td class="text-center text-nowrap">
                <button class="btn btn-sm btn-outline-info view-btn" data-id="${row.id}" title="View Details">
                    <i class="bi bi-eye"></i>
                </button>
                <a href="../sheets_edit_seller.php?id=${row.id}" class="btn btn-sm btn-outline-warning" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
            </td>`;
            html += '</tr>';
        });
        
        $('#tableBody').html(html);
        
        // Update stats
        $('#activeCount').text(activeCount);
        $('#alertCount').text(alertCount);
        $('#nearExpiryCount').text(nearExpiryCount);
        $('#expiredCount').text(expiredCount);
    }

    // Render pagination
    function renderPagination(total, page, perPage) {
        totalPages = Math.ceil(total / perPage);
        let html = '';
        
        if (totalPages > 1) {
            html += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
                <a class="page-link bg-primary text-white border-primary" href="#" data-page="${page - 1}">&laquo;</a>
            </li>`;
            
            let startPage = Math.max(1, page - 2);
            let endPage = Math.min(totalPages, page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === page ? 'active' : ''}">
                    <a class="page-link ${i === page ? 'bg-primary text-white border-primary' : 'text-primary'}" 
                       href="#" data-page="${i}">${i}</a>
                </li>`;
            }
            
            html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
                <a class="page-link bg-primary text-white border-primary" href="#" data-page="${page + 1}">&raquo;</a>
            </li>`;
        }
        
        $('#pagination').html(html);
        
        const start = (page - 1) * perPage + 1;
        const end = Math.min(page * perPage, total);
        $('#paginationInfo').html(`Showing ${start} to ${end} of ${total} entries`);
    }

    // Update stats (fallback)
    function updateStats(stats) {
        // Stats are now calculated in renderTable
    }

    // View button click
    $(document).on('click', '.view-btn', function () {
        const id = $(this).data('id');
        
        $.ajax({
            url: BASE_URL + 'ajax/work-station/reminders/renewal_sellers.php',
            type: 'POST',
            data: { action: 'get_details', id: id },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    showSellerDetails(response.data);
                    new bootstrap.Modal(document.getElementById('viewModal')).show();
                } else {
                    showToast('danger', 'Error', response.message);
                }
            }
        });
    });

    // Show seller details
    function showSellerDetails(seller) {
        let html = '';
        
        let dateDisplay = seller.entry_date ? new Date(seller.entry_date + 'T00:00:00').toLocaleDateString('en-GB') : '-';
        let createdDisplay = seller.created_at ? new Date(seller.created_at).toLocaleString('en-GB') : '-';
        let updatedDisplay = seller.updated_at ? new Date(seller.updated_at).toLocaleString('en-GB') : '-';
        
        // Get renewal info
        const renewalInfo = seller.renewal_info || {
            days_remaining: null,
            status: 'unknown',
            formatted_date: 'N/A',
            start_date: 'N/A',
            duration: 'N/A',
            alert_days: 0
        };
        
        const fields = [
            ['ID', seller.id],
            ['Entry Date', dateDisplay],
            ['Business Name', seller.work_details_update],
            ['Source Type', seller.source_type],
            ['Registration Status', seller.registration_status],
            ['Phone Number', seller.phone_number],
            ['Plan', seller.plans_interested],
            ['Start Date', renewalInfo.start_date],
            ['Duration', renewalInfo.duration],
            ['Renewal Date', renewalInfo.formatted_date],
            ['Days Remaining', renewalInfo.days_remaining !== null ? renewalInfo.days_remaining + ' days' : 'N/A'],
            ['Alert Days', renewalInfo.alert_days + ' days'],
            ['Renewal Status', renewalInfo.status],
            ['Customer Response', seller.customer_response],
            ['Remembering Notes', seller.remembering_notes],
            ['Latest Update', seller.latest_update],
            ['Current Status', seller.current_status],
            ['Customer Queries', seller.customer_queries],
            ['Call Timing', seller.call_timing],
            ['Products Uploaded', seller.products_uploaded || 0],
            ['Remarks', seller.remarks],
            ['Created At', createdDisplay],
            ['Updated At', updatedDisplay]
        ];
        
        fields.forEach(function (field) {
            if (field[1] && field[1] !== '' && field[1] !== null && field[1] !== '-') {
                let value = field[1].toString();
                if (field[0] === 'Renewal Status') {
                    if (value === 'active') value = '<span class="badge bg-success">Active</span>';
                    else if (value === 'renewal_alert') value = '<span class="badge bg-warning">Renewal Alert</span>';
                    else if (value === 'near_expiry') value = '<span class="badge bg-info">Near Expiry</span>';
                    else if (value === 'expired') value = '<span class="badge bg-danger">Expired</span>';
                    else value = '<span class="badge bg-secondary">Unknown</span>';
                }
                html += `
                    <div class="col-12 col-sm-6 mb-3">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">${field[0]}</small>
                            <strong class="d-block" style="word-break: break-word;">${value}</strong>
                        </div>
                    </div>
                `;
            }
        });
        
        $('#sellerDetails').html(html);
    }

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
        let bgClass = 'bg-primary';
        
        if (type === 'success') bgClass = 'bg-success';
        else if (type === 'danger') bgClass = 'bg-danger';
        else if (type === 'warning') bgClass = 'bg-warning';
        else if (type === 'info') bgClass = 'bg-info';

        const html = `
            <div id="${id}" class="toast text-white ${bgClass}" role="alert" data-bs-autohide="true" data-bs-delay="3000">
                <div class="toast-body d-flex justify-content-between align-items-center">
                    <div><strong>${title}</strong> ${message}</div>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="toast"></button>
                </div>
            </div>`;

        $('.toast-container').append(html);
        new bootstrap.Toast(document.getElementById(id)).show();
        $(`#${id}`).on('hidden.bs.toast', function () { $(this).remove(); });
    }
});


// Delete seller function
$(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    
    Swal.fire({
        title: 'Delete Seller?',
        text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: BASE_URL + 'ajax/work-station/reminders/renewal_sellers.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function (response) {
                    Swal.close();
                    if (response.status === 'success') {
                        Swal.fire('Deleted!', response.message, 'success').then(() => {
                            loadData(); // Reload the table
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.close();
                    Swal.fire('Error!', 'Failed to delete seller. Please try again.', 'error');
                }
            });
        }
    });
});

// Update the renderTable function to include delete button
function renderTable(rows) {
    let html = '';
    let activeCount = 0, alertCount = 0, nearExpiryCount = 0, expiredCount = 0;
    
    rows.forEach(function (row) {
        const renewalInfo = row.renewal_info || {
            days_remaining: null,
            status: 'unknown',
            formatted_date: 'N/A',
            start_date: 'N/A',
            duration: 'N/A',
            alert_days: 0
        };
        
        const daysRemaining = renewalInfo.days_remaining;
        const status = renewalInfo.status;
        
        // Update counters
        if (status === 'active') activeCount++;
        else if (status === 'renewal_alert') alertCount++;
        else if (status === 'near_expiry') nearExpiryCount++;
        else if (status === 'expired') expiredCount++;
        
        // Get status badge
        let statusBadge = '';
        let daysText = '';
        let badgeClass = '';
        
        if (daysRemaining === null) {
            statusBadge = '<span class="badge bg-secondary">Unknown</span>';
            daysText = 'N/A';
            badgeClass = 'bg-secondary';
        } else if (daysRemaining < 0) {
            statusBadge = '<span class="badge bg-danger">Expired</span>';
            daysText = Math.abs(daysRemaining) + ' days ago';
            badgeClass = 'bg-danger';
        } else if (daysRemaining === 0) {
            statusBadge = '<span class="badge bg-warning text-dark">Due Today</span>';
            daysText = 'Today';
            badgeClass = 'bg-warning';
        } else if (daysRemaining <= renewalInfo.alert_days) {
            statusBadge = '<span class="badge bg-warning">Renewal Alert</span>';
            daysText = daysRemaining + ' days left';
            badgeClass = 'bg-warning';
        } else if (daysRemaining <= 30) {
            statusBadge = '<span class="badge bg-info">Near Expiry</span>';
            daysText = daysRemaining + ' days left';
            badgeClass = 'bg-info';
        } else {
            statusBadge = '<span class="badge bg-success">Active</span>';
            daysText = daysRemaining + ' days left';
            badgeClass = 'bg-success';
        }
        
        // Get plan badge
        let planBadge = '';
        const plan = (row.plans_interested || '').toLowerCase();
        if (plan.includes('welcome')) {
            planBadge = '<span class="badge bg-success">Welcome Plan</span>';
        } else if (plan.includes('starter')) {
            planBadge = '<span class="badge bg-info">Starter Plan</span>';
        } else if (plan.includes('intermediate')) {
            planBadge = '<span class="badge bg-warning text-dark">Intermediate Plan</span>';
        } else if (plan.includes('professional')) {
            planBadge = '<span class="badge bg-primary">Professional Plan</span>';
        } else {
            planBadge = '<span class="badge bg-secondary">' + escapeHtml(row.plans_interested || 'N/A') + '</span>';
        }
        
        html += '<tr>';
        html += `<td>${escapeHtml(row.id)}</td>`;
        html += `<td><a href="../sheets_edit_seller.php?id=${row.id}" class="text-decoration-none fw-semibold">${escapeHtml(row.work_details_update || '-')}</a></td>`;
        html += `<td>${escapeHtml(row.phone_number || '-')}</td>`;
        html += `<td>${planBadge}</td>`;
        html += `<td>${escapeHtml(renewalInfo.start_date || 'N/A')}</td>`;
        html += `<td>${escapeHtml(renewalInfo.duration || 'N/A')}</td>`;
        html += `<td><span class="badge bg-secondary">${escapeHtml(renewalInfo.formatted_date || 'N/A')}</span></td>`;
        html += `<td><span class="badge days-badge ${badgeClass}">${escapeHtml(daysText)}</span></td>`;
        html += `<td>${statusBadge}</td>`;
        html += `<td class="text-center text-nowrap">
            <button class="btn btn-sm btn-outline-info view-btn" data-id="${row.id}" title="View Details">
                <i class="bi bi-eye"></i>
            </button>
            <a href="../sheets_edit_seller.php?id=${row.id}" class="btn btn-sm btn-outline-warning" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>
            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${row.id}" data-name="${escapeHtml(row.work_details_update)}" title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </td>`;
        html += '</tr>';
    });
    
    $('#tableBody').html(html);
    
    // Update stats
    $('#activeCount').text(activeCount);
    $('#alertCount').text(alertCount);
    $('#nearExpiryCount').text(nearExpiryCount);
    $('#expiredCount').text(expiredCount);
}