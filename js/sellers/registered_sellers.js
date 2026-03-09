$(document).ready(function () {

let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let sortColumn = 'id';
let sortOrder = 'DESC';
let searchTerm = '';
let filters = {
    status: '',
    assigned_by: '',
    lead_source_link: '', // Changed to match database column
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
        lead_source_link: $('#filterSource').val(), // Changed to match database
        date_range: $('#filterDate').val()
    };
    currentPage = 1;
    loadData();
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
        lead_source_link: '', // Changed to match database
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
        url: BASE_URL + 'ajax/registered_sellers/get_sellers.php',
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
        error: function(xhr, status, error) {
            $('#loadingSpinner').hide();
            $('#noData').show();
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            showToast('danger', 'Error!', 'Failed to load data');
        }
    });
}

/* -----------------------------
   RENDER TABLE
----------------------------- */
function renderTable(rows) {
    let html = '';
    
    rows.forEach(function (row) {
        const statusClass = row.status === 'Active' ? 'status-active' : 'status-inactive';
        const date = row.date ? new Date(row.date).toLocaleDateString('en-IN') : '-';
        const created_at = row.created_at ? new Date(row.created_at).toLocaleString('en-IN') : '-';
        
        html += '<tr>';
        html += `<td class="fw-bold">${row.id || '-'}</td>`;
        html += `<td>${date}</td>`;
        html += `<td>${escapeHtml(row.store_name || '-')}</td>`;
        html += `<td class="d-none d-lg-table-cell">${escapeHtml(row.customer_name || '-')}</td>`;
        html += `<td>${row.phone_number || '-'}</td>`;
        html += `<td><span class="status-badge ${statusClass}">${row.status || 'Unknown'}</span></td>`;
        html += `<td class="d-none d-xl-table-cell">${escapeHtml(row.lead_source_link || '-')}</td>`;
        html += `<td class="d-none d-sm-table-cell">${escapeHtml(row.assigned_by || '-')}</td>`;
        html += `<td class="text-center"><small>${created_at}</small></td>`;
        html += `<td class="text-center">
            <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-outline-primary view-btn" data-id="${row.id}" title="View">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-outline-warning edit-btn" data-id="${row.id}" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger delete-btn" data-id="${row.id}" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>`;
        html += '</tr>';
    });
    
    $('#tableBody').html(html);
}

/* -----------------------------
   RENDER PAGINATION
----------------------------- */
function renderPagination(total, page, perPage) {
    totalPages = Math.ceil(total / perPage);
    let html = '';
    
    if (totalPages > 1) {
        // Previous button
        html += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${page - 1}">Previous</a>
        </li>`;
        
        // Page numbers
        let startPage = Math.max(1, page - 2);
        let endPage = Math.min(totalPages, page + 2);
        
        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
        }
        
        // Next button
        html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${page + 1}">Next</a>
        </li>`;
    }
    
    $('#pagination').html(html);
    $('#paginationInfo').text(`Showing ${((page-1)*perPage)+1} to ${Math.min(page*perPage, total)} of ${total} entries`);
}

/* -----------------------------
   UPDATE STATS CARDS
----------------------------- */
function updateStats(stats) {
    $('#totalCount').text(stats.total || 0);
    $('#activeCount').text(stats.active || 0);
    $('#inactiveCount').text(stats.inactive || 0);
    $('#followupCount').text(stats.followup || 0);
}

/* -----------------------------
   VIEW BUTTON CLICK
----------------------------- */
$(document).on('click', '.view-btn', function () {
    const id = $(this).data('id');
    
    $.ajax({
        url: BASE_URL + 'ajax/registered_sellers/get_seller_details.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                showSellerDetails(response.data);
                const modal = new bootstrap.Modal(document.getElementById('viewModal'));
                modal.show();
            } else {
                showToast('danger', 'Error!', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            showToast('danger', 'Error!', 'Failed to load seller details');
        }
    });
});

/* -----------------------------
   SHOW SELLER DETAILS
----------------------------- */
function showSellerDetails(seller) {
    let html = '<div class="row">';
    
    const fields = [
        ['ID', seller.id],
        ['Date', seller.date ? new Date(seller.date).toLocaleDateString('en-IN') : '-'],
        ['Store Name', seller.store_name],
        ['Customer Name', seller.customer_name],
        ['Phone Number', seller.phone_number],
        ['Status', seller.status],
        ['Lead Source', seller.lead_source_link], // Using lead_source_link
        ['Assigned By', seller.assigned_by],
        ['Created At', seller.created_at ? new Date(seller.created_at).toLocaleString() : '-']
    ];
    
    fields.forEach(function (field) {
        if (field[1] && field[1] !== '' && field[1] !== null) {
            html += `
                <div class="col-md-6 mb-3">
                    <strong>${field[0]}:</strong><br>
                    <span class="text-muted">${escapeHtml(field[1].toString())}</span>
                </div>
            `;
        }
    });
    
    html += '</div>';
    $('#sellerDetails').html(html);
}

/* -----------------------------
   EDIT BUTTON CLICK
----------------------------- */
$(document).on('click', '.edit-btn', function () {
    const id = $(this).data('id');
    
    $.ajax({
        url: BASE_URL + 'ajax/registered_sellers/get_seller_details.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                const seller = response.data;
                $('#editId').val(seller.id);
                $('#editStoreName').val(seller.store_name || '');
                $('#editCustomerName').val(seller.customer_name || '');
                $('#editPhone').val(seller.phone_number || '');
                $('#editStatus').val(seller.status || 'Active');
                $('#editLeadSourceLink').val(seller.lead_source_link || ''); // Using lead_source_link
                $('#editAssignedBy').val(seller.assigned_by || '');
                
                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            } else {
                showToast('danger', 'Error!', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            showToast('danger', 'Error!', 'Failed to load seller data');
        }
    });
});

/* -----------------------------
   SAVE EDIT
----------------------------- */
$('#saveEdit').on('click', function () {
    // Validate required fields
    if (!$('#editStoreName').val().trim()) {
        showToast('warning', 'Warning!', 'Store name is required');
        return;
    }
    
    const data = {
        id: $('#editId').val(),
        store_name: $('#editStoreName').val().trim(),
        customer_name: $('#editCustomerName').val().trim(),
        phone_number: $('#editPhone').val().trim(),
        status: $('#editStatus').val(),
        lead_source_link: $('#editLeadSourceLink').val().trim(), // Using lead_source_link
        assigned_by: $('#editAssignedBy').val()
    };
    
    // Show loading state
    const $btn = $(this);
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
    
    $.ajax({
        url: BASE_URL + 'ajax/registered_sellers/update_seller.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                showToast('success', 'Success!', 'Seller updated successfully');
                $('#editModal').modal('hide');
                loadData();
            } else {
                showToast('danger', 'Error!', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            showToast('danger', 'Error!', 'Failed to update seller');
        },
        complete: function() {
            $btn.prop('disabled', false).text('Save Changes');
        }
    });
});

/* -----------------------------
   DELETE BUTTON CLICK
----------------------------- */
$(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    
    if (confirm('Are you sure you want to delete this seller?')) {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: BASE_URL + 'ajax/registered_sellers/delete_seller.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    showToast('success', 'Success!', 'Seller deleted successfully');
                    loadData();
                } else {
                    showToast('danger', 'Error!', response.message);
                    $btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                }
            },
            error: function() {
                showToast('danger', 'Error!', 'Failed to delete seller');
                $btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
            }
        });
    }
});

/* -----------------------------
   EXPORT BUTTON
----------------------------- */
$('#exportBtn').on('click', function () {
    window.location.href = BASE_URL + 'ajax/registered_sellers/export_sellers.php';
});

/* -----------------------------
   ESCAPE HTML
----------------------------- */
function escapeHtml(text) {
    if (!text) return '-';
    if (text === null) return '-';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/* -----------------------------
   TOAST MESSAGE
----------------------------- */
function showToast(type, title, message) {
    const id = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 
                    type === 'danger' ? 'bg-danger' : 
                    type === 'warning' ? 'bg-warning' : 'bg-info';

    const html = `
    <div id="${id}" class="toast text-white ${bgClass}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
        <div class="toast-header ${bgClass} text-white">
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    </div>`;

    $('.toast-container').append(html);
    const toast = new bootstrap.Toast(document.getElementById(id));
    toast.show();
    
    $(`#${id}`).on('hidden.bs.toast', function () {
        $(this).remove();
    });
}

});