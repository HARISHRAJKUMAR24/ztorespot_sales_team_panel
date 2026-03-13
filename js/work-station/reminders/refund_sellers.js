$(document).ready(function () {
    let currentPage = 1;
    let perPage = 10;
    let totalPages = 1;
    let sortColumn = 'id';
    let sortOrder = 'DESC';
    let searchTerm = '';
    let dateFilter = '';
    let planFilter = '';
    let amountFilter = '';

    // Load initial data
    loadData();

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

    // Apply filters
    $('#applyFilters').on('click', function () {
        dateFilter = $('#dateFilter').val();
        planFilter = $('#planFilter').val();
        amountFilter = $('#amountFilter').val();
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
           url: BASE_URL + 'ajax/work-station/reminders/refund_sellers.php',
            type: 'POST',
            data: {
                action: 'get_data',
                page: currentPage,
                per_page: perPage,
                sort_column: sortColumn,
                sort_order: sortOrder,
                search: searchTerm,
                date_filter: dateFilter,
                plan_filter: planFilter,
                amount_filter: amountFilter
            },
            dataType: 'json',
            success: function (response) {
                $('#loadingSpinner').hide();
                
                if (response.status === 'success') {
                    if (response.data.rows.length > 0) {
                        renderTable(response.data.rows);
                        renderPagination(response.data.total, response.data.page, response.data.per_page);
                        updateStats(response.data.stats, response.data.rows);
                        $('#dataTable').show();
                    } else {
                        $('#noData').show();
                        updateStats(response.data.stats, []);
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
        let totalAmount = 0;
        
        rows.forEach(function (row) {
            let dateDisplay = row.entry_date ? new Date(row.entry_date + 'T00:00:00').toLocaleDateString('en-GB') : '-';
            
            // Extract refund details from remembering_notes
            let refundPlan = row.plans_interested || 'N/A';
            let refundAmount = '0';
            let refundDate = '-';
            let refundReason = '-';
            
            if (row.remembering_notes && row.remembering_notes.includes('Refund')) {
                const notes = row.remembering_notes;
                
                // Extract amount
                const amountMatch = notes.match(/Amount: ₹?([0-9.]+)/);
                if (amountMatch && amountMatch[1]) {
                    refundAmount = amountMatch[1];
                    totalAmount += parseFloat(refundAmount);
                }
                
                // Extract date
                const dateMatch = notes.match(/Date: (\d{2}\/\d{2}\/\d{4})/);
                if (dateMatch && dateMatch[1]) {
                    refundDate = dateMatch[1];
                }
                
                // Extract reason
                const reasonMatch = notes.match(/Reason: ([^.]+)/);
                if (reasonMatch && reasonMatch[1]) {
                    refundReason = reasonMatch[1].trim();
                    if (refundReason.length > 30) {
                        refundReason = refundReason.substring(0, 30) + '...';
                    }
                }
            }
            
            html += '<tr>';
            html += `<td>${escapeHtml(row.id)}</td>`;
            html += `<td>${dateDisplay}</td>`;
            html += `<td>${escapeHtml(row.work_details_update || '-')}</td>`;
            html += `<td>${escapeHtml(row.phone_number || '-')}</td>`;
            html += `<td><span class="badge bg-danger">${escapeHtml(refundPlan)}</span></td>`;
            html += `<td><span class="badge amount-badge"><i class="bi bi-currency-rupee me-1"></i>${escapeHtml(refundAmount)}</span></td>`;
            html += `<td>${escapeHtml(refundDate)}</td>`;
            html += `<td><small class="text-muted">${escapeHtml(refundReason)}</small></td>`;
            html += `<td class="text-center text-nowrap">
                <button class="btn btn-sm btn-outline-info view-btn" data-id="${row.id}" title="View Details">
                    <i class="bi bi-eye"></i>
                </button>
                <a href="../sheets_edit_seller.php?id=${row.id}" class="btn btn-sm btn-outline-warning" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${row.id}" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </td>`;
            html += '</tr>';
        });
        
        $('#tableBody').html(html);
        
        // Update total amount in stats
        $('#totalAmount').text('₹' + totalAmount.toFixed(2));
    }

    // Render pagination
    function renderPagination(total, page, perPage) {
        totalPages = Math.ceil(total / perPage);
        let html = '';
        
        if (totalPages > 1) {
            html += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
                <a class="page-link bg-danger text-white border-danger" href="#" data-page="${page - 1}">&laquo;</a>
            </li>`;
            
            let startPage = Math.max(1, page - 2);
            let endPage = Math.min(totalPages, page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === page ? 'active' : ''}">
                    <a class="page-link ${i === page ? 'bg-danger text-white border-danger' : 'text-danger'}" 
                       href="#" data-page="${i}">${i}</a>
                </li>`;
            }
            
            html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
                <a class="page-link bg-danger text-white border-danger" href="#" data-page="${page + 1}">&raquo;</a>
            </li>`;
        }
        
        $('#pagination').html(html);
        
        const start = (page - 1) * perPage + 1;
        const end = Math.min(page * perPage, total);
        $('#paginationInfo').html(`Showing ${start} to ${end} of ${total} entries`);
    }

    // Update stats
    function updateStats(stats, rows) {
        $('#totalCount').text(stats.total || 0);
        $('#weekCount').text(stats.week_count || 0);
        $('#monthCount').text(stats.month_count || 0);
    }

    // View button click
    $(document).on('click', '.view-btn', function () {
        const id = $(this).data('id');
        
        $.ajax({
            url: BASE_URL + 'ajax/work-station/reminders/refund_sellers.php',
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
        
        // Extract refund details
        let refundPlan = seller.plans_interested || 'N/A';
        let refundAmount = '0';
        let refundDate = '-';
        let refundReason = '-';
        
        if (seller.remembering_notes && seller.remembering_notes.includes('Refund')) {
            const notes = seller.remembering_notes;
            
            const amountMatch = notes.match(/Amount: ₹?([0-9.]+)/);
            if (amountMatch && amountMatch[1]) {
                refundAmount = amountMatch[1];
            }
            
            const dateMatch = notes.match(/Date: (\d{2}\/\d{2}\/\d{4})/);
            if (dateMatch && dateMatch[1]) {
                refundDate = dateMatch[1];
            }
            
            const reasonMatch = notes.match(/Reason: ([^.]+)/);
            if (reasonMatch && reasonMatch[1]) {
                refundReason = reasonMatch[1].trim();
            }
        }
        
        const fields = [
            ['ID', seller.id],
            ['Entry Date', dateDisplay],
            ['Business Name', seller.work_details_update],
            ['Source Type', seller.source_type],
            ['Registration Status', seller.registration_status],
            ['Phone Number', seller.phone_number],
            ['Refund Plan', refundPlan],
            ['Refund Amount', '₹' + refundAmount],
            ['Refund Date', refundDate],
            ['Refund Reason', refundReason],
            ['Customer Response', seller.customer_response],
            ['Remembering Notes', seller.remembering_notes],
            ['Latest Update', seller.latest_update],
            ['Current Status', seller.current_status],
            ['Customer Queries', seller.customer_queries],
            ['Call Timing', seller.call_timing],
            ['Remarks', seller.remarks],
            ['Created At', createdDisplay],
            ['Updated At', updatedDisplay]
        ];
        
        fields.forEach(function (field) {
            if (field[1] && field[1] !== '' && field[1] !== null && field[1] !== '-') {
                let value = field[1].toString();
                if (field[0] === 'Refund Amount') {
                    value = `<span class="badge amount-badge fs-6">${value}</span>`;
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

    // Delete button click
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this refund record?')) {
            $.ajax({
                url: BASE_URL + 'ajax/work-station/reminders/refund_sellers.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        showToast('success', 'Success', 'Record deleted successfully');
                        loadData();
                    } else {
                        showToast('danger', 'Error', response.message);
                    }
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
        let bgClass = 'bg-danger';
        
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