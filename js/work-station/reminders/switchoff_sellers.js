$(document).ready(function () {
    let currentPage = 1;
    let perPage = 10;
    let totalPages = 1;
    let sortColumn = 'id';
    let sortOrder = 'DESC';
    let searchTerm = '';
    let dateFilter = '';
    let sortFilter = 'id_desc';

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
        sortFilter = $('#sortFilter').val();
        
        // Parse sort filter
        switch(sortFilter) {
            case 'id_desc':
                sortColumn = 'id';
                sortOrder = 'DESC';
                break;
            case 'id_asc':
                sortColumn = 'id';
                sortOrder = 'ASC';
                break;
            case 'name_asc':
                sortColumn = 'work_details_update';
                sortOrder = 'ASC';
                break;
            case 'name_desc':
                sortColumn = 'work_details_update';
                sortOrder = 'DESC';
                break;
        }
        
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
            url: BASE_URL + 'ajax/work-station/reminders/switchoff_sellers.php',
            type: 'POST',
            data: {
                action: 'get_data',
                page: currentPage,
                per_page: perPage,
                sort_column: sortColumn,
                sort_order: sortOrder,
                search: searchTerm,
                date_filter: dateFilter
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
        
        rows.forEach(function (row) {
            let dateDisplay = row.entry_date ? new Date(row.entry_date + 'T00:00:00').toLocaleDateString('en-GB') : '-';
            
            let statusBadge = 'bg-secondary';
            if (row.current_status === 'Not yet') statusBadge = 'bg-warning';
            else if (row.current_status === 'In Progress') statusBadge = 'bg-info';
            else if (row.current_status === 'Upgraded') statusBadge = 'bg-success';
            else if (row.current_status === 'Deleted') statusBadge = 'bg-danger';
            
            html += '<tr>';
            html += `<td>${escapeHtml(row.id)}</td>`;
            html += `<td>${dateDisplay}</td>`;
            html += `<td>${escapeHtml(row.work_details_update || '-')}</td>`;
            html += `<td>${escapeHtml(row.phone_number || '-')}</td>`;
            html += `<td><span class="badge bg-secondary bg-opacity-25 text-dark">${escapeHtml(row.source_type || 'N/A')}</span></td>`;
            html += `<td><span class="badge ${statusBadge}">${escapeHtml(row.current_status || 'Not yet')}</span></td>`;
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
    }

    // Render pagination
    function renderPagination(total, page, perPage) {
        totalPages = Math.ceil(total / perPage);
        let html = '';
        
        if (totalPages > 1) {
            html += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
                <a class="page-link bg-secondary text-white border-secondary" href="#" data-page="${page - 1}">&laquo;</a>
            </li>`;
            
            let startPage = Math.max(1, page - 2);
            let endPage = Math.min(totalPages, page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === page ? 'active' : ''}">
                    <a class="page-link ${i === page ? 'bg-secondary text-white border-secondary' : 'text-secondary'}" 
                       href="#" data-page="${i}">${i}</a>
                </li>`;
            }
            
            html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
                <a class="page-link bg-secondary text-white border-secondary" href="#" data-page="${page + 1}">&raquo;</a>
            </li>`;
        }
        
        $('#pagination').html(html);
        
        const start = (page - 1) * perPage + 1;
        const end = Math.min(page * perPage, total);
        $('#paginationInfo').html(`Showing ${start} to ${end} of ${total} entries`);
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
        
        const fields = [
            ['ID', seller.id],
            ['Entry Date', dateDisplay],
            ['Business Name', seller.work_details_update],
            ['Source Type', seller.source_type],
            ['Registration Status', seller.registration_status],
            ['Phone Number', seller.phone_number],
            ['Plans Interested', seller.plans_interested],
            ['Customer Response', seller.customer_response],
            ['Remembering Notes', seller.remembering_notes],
            ['Latest Update', seller.latest_update],
            ['Current Status', seller.current_status],
            ['Customer Queries', seller.customer_queries],
            ['Call Timing', seller.call_timing],
            ['Remarks', seller.remarks],
            ['Import Batch', seller.import_batch],
            ['Created At', createdDisplay],
            ['Updated At', updatedDisplay]
        ];
        
        fields.forEach(function (field) {
            if (field[1] && field[1] !== '' && field[1] !== null && field[1] !== '-') {
                html += `
                    <div class="col-12 col-sm-6 mb-3">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">${field[0]}</small>
                            <strong class="d-block" style="word-break: break-word;">${escapeHtml(field[1].toString())}</strong>
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
        
        if (confirm('Are you sure you want to delete this record?')) {
            $.ajax({
                url: BASE_URL + 'ajax/work-station/reminders/switchoff_sellers.php',
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
        let bgClass = 'bg-secondary';
        
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