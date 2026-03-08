$(document).ready(function () {

let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let sortColumn = 'seller_name';
let sortOrder = 'ASC';
let searchTerm = '';
let filters = {
    status: '',
    assigned_by: '',
    lead_source: '',
    date_range: ''
};

/* -----------------------------
   LOAD INITIAL DATA
----------------------------- */
loadData();

/* -----------------------------
   SEARCH INPUT
----------------------------- */
let searchTimer;
$('#searchInput').on('keyup', function () {
    clearTimeout(searchTimer);
    searchTerm = $(this).val();
    searchTimer = setTimeout(function () {
        currentPage = 1;
        loadData();
    }, 500);
});

/* -----------------------------
   PER PAGE CHANGE
----------------------------- */
$('#perPage').on('change', function () {
    perPage = $(this).val();
    currentPage = 1;
    loadData();
});

/* -----------------------------
   SORTING
----------------------------- */
$(document).on('click', '.sortable', function () {
    const column = $(this).data('sort');
    if (sortColumn === column) {
        sortOrder = sortOrder === 'ASC' ? 'DESC' : 'ASC';
    } else {
        sortColumn = column;
        sortOrder = 'ASC';
    }
    
    // Update sort icons
    $('.sortable i').removeClass('bi-arrow-up bi-arrow-down').addClass('bi-arrow-down-up');
    const icon = $(this).find('i');
    icon.removeClass('bi-arrow-down-up').addClass(sortOrder === 'ASC' ? 'bi-arrow-up' : 'bi-arrow-down');
    
    loadData();
});

/* -----------------------------
   APPLY FILTERS
----------------------------- */
$('#applyFilters').on('click', function () {
    filters = {
        status: $('#filterStatus').val(),
        assigned_by: $('#filterAssigned').val(),
        lead_source: $('#filterSource').val(),
        date_range: $('#filterDate').val()
    };
    currentPage = 1;
    loadData();
    
    // Close collapse on mobile after applying filters
    if (window.innerWidth < 768) {
        $('#filterCollapse').collapse('hide');
    }
});

/* -----------------------------
   CLEAR FILTERS
----------------------------- */
$('#clearFilters').on('click', function () {
    $('#filterStatus').val('');
    $('#filterAssigned').val('');
    $('#filterSource').val('');
    $('#filterDate').val('');
    
    filters = {
        status: '',
        assigned_by: '',
        lead_source: '',
        date_range: ''
    };
    currentPage = 1;
    loadData();
});

/* -----------------------------
   PAGINATION
----------------------------- */
$(document).on('click', '.page-link', function (e) {
    e.preventDefault();
    const page = $(this).data('page');
    if (page && page !== currentPage) {
        currentPage = page;
        loadData();
        // Scroll to top of table on mobile
        if (window.innerWidth < 768) {
            $('html, body').animate({
                scrollTop: $('#dataTable').offset().top - 70
            }, 300);
        }
    }
});

/* -----------------------------
   LOAD DATA FUNCTION
----------------------------- */
function loadData() {
    $('#loadingSpinner').show();
    $('#dataTable').hide();
    $('#noData').hide();

    $.ajax({
        url: BASE_URL + 'ajax/whatsapp_customers/get_customers.php',
        type: 'POST',
        data: {
            page: currentPage,
            per_page: perPage,
            sort_column: sortColumn,
            sort_order: sortOrder,
            search: searchTerm,
            filters: filters
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
                }
            } else {
                showToast('danger', 'Error!', response.message);
            }
        },
        error: function () {
            $('#loadingSpinner').hide();
            $('#noData').show();
            showToast('danger', 'Error!', 'Failed to load data');
        }
    });
}

/* -----------------------------
   RENDER TABLE (Responsive)
----------------------------- */
function renderTable(rows) {
    let html = '';
    const isMobile = window.innerWidth < 768;
    
    rows.forEach(function (row) {
        let statusClass = '';
        if (row.status === 'Active') statusClass = 'status-active';
        else if (row.status === 'Inactive') statusClass = 'status-inactive';
        else if (row.status === 'Pending') statusClass = 'status-pending';
        
        // Count non-empty updates
        let updateCount = 0;
        if (row.update_1) updateCount++;
        if (row.update_2) updateCount++;
        if (row.update_3) updateCount++;
        
        // Truncate long names
        let sellerName = row.seller_name || '-';
        if (sellerName.length > 20 && isMobile) {
            sellerName = sellerName.substring(0, 17) + '...';
        }
        
        let storeName = row.store_name || '-';
        if (storeName.length > 15 && isMobile) {
            storeName = storeName.substring(0, 12) + '...';
        }
        
        html += '<tr>';
        
        // Seller Name
        html += `<td title="${row.seller_name || ''}">${escapeHtml(sellerName)}</td>`;
        
        // Phone
        html += `<td>${row.phone_number || '-'}</td>`;
        
        // Seller ID - hidden on md
        html += `<td class="d-none d-md-table-cell">${escapeHtml(row.seller_id || '-')}</td>`;
        
        // Store Name - hidden on lg
        html += `<td class="d-none d-lg-table-cell" title="${row.store_name || ''}">${escapeHtml(storeName)}</td>`;
        
        // Status with badge
        html += `<td><span class="status-badge ${statusClass}">${row.status || 'Unknown'}</span></td>`;
        
        // Lead Source - hidden on xl
        html += `<td class="d-none d-xl-table-cell">${escapeHtml(row.lead_source || '-')}</td>`;
        
        // Assigned By - hidden on sm
        html += `<td class="d-none d-sm-table-cell">${escapeHtml(row.assigned_by || '-')}</td>`;
        
        // Updates count with tooltip
        let updates = [];
        if (row.update_1) updates.push(`Update 1: ${row.update_1}`);
        if (row.update_2) updates.push(`Update 2: ${row.update_2}`);
        if (row.update_3) updates.push(`Update 3: ${row.update_3}`);
        const tooltipText = updates.length > 0 ? updates.join('\n') : 'No updates';
        
        html += `<td class="text-center">
            <span class="update-badge" title="${tooltipText}">${updateCount}</span>
        </td>`;
        
        // Actions
        html += `<td class="text-center text-nowrap">
            <button class="btn btn-sm btn-outline-primary view-btn" data-id="${row.id}" title="View">
                <i class="bi bi-eye"></i>
            </button>
            <button class="btn btn-sm btn-outline-warning edit-btn" data-id="${row.id}" title="Edit">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${row.id}" title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </td>`;
        
        html += '</tr>';
    });
    
    $('#tableBody').html(html);
}

/* -----------------------------
   RENDER PAGINATION (Responsive)
----------------------------- */
function renderPagination(total, page, perPage) {
    totalPages = Math.ceil(total / perPage);
    let html = '';
    
    if (totalPages > 1) {
        // Previous button
        html += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${page - 1}" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>`;
        
        // Page numbers (show fewer on mobile)
        let maxVisible = window.innerWidth < 576 ? 3 : 5;
        let startPage = Math.max(1, page - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);
        
        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }
        
        if (startPage > 1) {
            html += `<li class="page-item d-none d-sm-block"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled d-none d-sm-block"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<li class="page-item disabled d-none d-sm-block"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item d-none d-sm-block"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
        }
        
        // Next button
        html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${page + 1}" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>`;
    }
    
    $('#pagination').html(html);
    
    // Show pagination info
    const start = (page - 1) * perPage + 1;
    const end = Math.min(page * perPage, total);
    $('#paginationInfo').html(`Showing ${start} to ${end} of ${total} entries`);
}

/* -----------------------------
   UPDATE STATS CARDS
----------------------------- */
function updateStats(stats) {
    $('#totalCount').text(stats.total || 0);
    $('#activeCount').text(stats.active || 0);
    $('#inactiveCount').text(stats.inactive || 0);
    $('#pendingCount').text(stats.pending || 0);
}

/* -----------------------------
   VIEW BUTTON CLICK
----------------------------- */
$(document).on('click', '.view-btn', function () {
    const id = $(this).data('id');
    
    $.ajax({
        url: BASE_URL + 'ajax/whatsapp_customers/get_customer_details.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                showCustomerDetails(response.data);
                const modal = new bootstrap.Modal(document.getElementById('viewModal'));
                modal.show();
            } else {
                showToast('danger', 'Error!', response.message);
            }
        }
    });
});

/* -----------------------------
   SHOW CUSTOMER DETAILS (Responsive)
----------------------------- */
function showCustomerDetails(customer) {
    let html = '';
    
    const fields = [
        ['Seller Name', customer.seller_name],
        ['Phone Number', customer.phone_number],
        ['Assigned By', customer.assigned_by],
        ['Update 1', customer.update_1],
        ['Update 2', customer.update_2],
        ['Update 3', customer.update_3],
        ['Seller ID', customer.seller_id],
        ['Store Name', customer.store_name],
        ['Lead Link', customer.lead_link],
        ['Lead Source', customer.lead_source],
        ['Before/After Registered', customer.before_after_registered],
        ['Store Status', customer.store_status],
        ['Major Reasons', customer.major_reasons],
        ['Created At', customer.created_at ? new Date(customer.created_at).toLocaleString() : '-']
    ];
    
    fields.forEach(function (field) {
        if (field[1] && field[1] !== '') {
            html += `
                <div class="col-12 col-sm-6 mb-3">
                    <div class="p-2 bg-light rounded">
                        <small class="text-muted d-block">${field[0]}</small>
                        <strong class="d-block">${escapeHtml(field[1])}</strong>
                    </div>
                </div>
            `;
        }
    });
    
    $('#customerDetails').html(html);
}

/* -----------------------------
   EDIT BUTTON CLICK
----------------------------- */
$(document).on('click', '.edit-btn', function () {
    const id = $(this).data('id');
    
    $.ajax({
        url: BASE_URL + 'ajax/whatsapp_customers/get_customer_details.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                const customer = response.data;
                $('#editId').val(customer.id);
                $('#editSellerName').val(customer.seller_name || '');
                $('#editPhone').val(customer.phone_number || '');
                $('#editStoreName').val(customer.store_name || '');
                $('#editSellerId').val(customer.seller_id || '');
                $('#editStatus').val(customer.status || 'Active');
                $('#editUpdate1').val(customer.update_1 || '');
                $('#editUpdate2').val(customer.update_2 || '');
                $('#editUpdate3').val(customer.update_3 || '');
                
                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            } else {
                showToast('danger', 'Error!', response.message);
            }
        }
    });
});

/* -----------------------------
   SAVE EDIT
----------------------------- */
$('#saveEdit').on('click', function () {
    const data = {
        id: $('#editId').val(),
        seller_name: $('#editSellerName').val(),
        phone_number: $('#editPhone').val(),
        store_name: $('#editStoreName').val(),
        seller_id: $('#editSellerId').val(),
        status: $('#editStatus').val(),
        update_1: $('#editUpdate1').val(),
        update_2: $('#editUpdate2').val(),
        update_3: $('#editUpdate3').val()
    };
    
    $.ajax({
        url: BASE_URL + 'ajax/whatsapp_customers/update_customer.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                showToast('success', 'Success!', 'Customer updated successfully');
                $('#editModal').modal('hide');
                loadData();
            } else {
                showToast('danger', 'Error!', response.message);
            }
        }
    });
});

/* -----------------------------
   DELETE BUTTON CLICK
----------------------------- */
$(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    
    if (confirm('Are you sure you want to delete this customer?')) {
        $.ajax({
            url: BASE_URL + 'ajax/whatsapp_customers/delete_customer.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', 'Customer deleted successfully');
                    loadData();
                } else {
                    showToast('danger', 'Error!', response.message);
                }
            }
        });
    }
});

/* -----------------------------
   EXPORT BUTTON
----------------------------- */
$('#exportBtn').on('click', function () {
    window.location.href = BASE_URL + 'ajax/whatsapp_customers/export_customers.php';
});

/* -----------------------------
   ESCAPE HTML
----------------------------- */
function escapeHtml(text) {
    if (!text) return '-';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/* -----------------------------
   TOAST MESSAGE (Mobile Optimized)
----------------------------- */
function showToast(type, title, message) {
    const id = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 
                    type === 'danger' ? 'bg-danger' : 
                    type === 'warning' ? 'bg-warning' : 'bg-info';

    const html = `
    <div id="${id}" class="toast text-white ${bgClass}" role="alert" aria-live="assertive" 
         aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000" style="min-width: 200px;">
        <div class="toast-body d-flex justify-content-between align-items-center">
            <div>
                <strong>${title}</strong> ${message}
            </div>
            <button type="button" class="btn-close btn-close-white btn-sm ms-2" data-bs-dismiss="toast"></button>
        </div>
    </div>`;

    $('.toast-container').append(html);
    const toast = new bootstrap.Toast(document.getElementById(id));
    toast.show();
    
    $(`#${id}`).on('hidden.bs.toast', function () {
        $(this).remove();
    });
}

/* -----------------------------
   WINDOW RESIZE HANDLER
----------------------------- */
let resizeTimer;
$(window).on('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
        // Reload table with new responsive layout
        if ($('#dataTable').is(':visible')) {
            loadData();
        }
    }, 250);
});

});