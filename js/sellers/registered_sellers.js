$(document).ready(function () {

let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let sortColumn = 's_no';
let sortOrder = 'DESC';
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
        error: function () {
            $('#loadingSpinner').hide();
            $('#noData').show();
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
        
        html += '<tr>';
        html += `<td>${row.s_no || '-'}</td>`;
        html += `<td>${date}</td>`;
        html += `<td>${escapeHtml(row.store_name || '-')}</td>`;
        html += `<td>${escapeHtml(row.customer_name || '-')}</td>`;
        html += `<td>${row.phone_number || '-'}</td>`;
        html += `<td><span class="status-badge ${statusClass}">${row.status || 'Unknown'}</span></td>`;
        html += `<td>${escapeHtml(row.lead_source || '-')}</td>`;
        html += `<td>${escapeHtml(row.assigned_by || '-')}</td>`;
        html += `<td><span class="badge bg-secondary">${row.call_attempts || 0}</span></td>`;
        html += `<td>
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
        }
    });
});

/* -----------------------------
   SHOW SELLER DETAILS
----------------------------- */
function showSellerDetails(seller) {
    let html = '<div class="row">';
    
    const fields = [
        ['S.No', seller.s_no],
        ['Date', seller.date ? new Date(seller.date).toLocaleDateString('en-IN') : '-'],
        ['Store Name', seller.store_name],
        ['Customer Name', seller.customer_name],
        ['Phone Number', seller.phone_number],
        ['Status', seller.status],
        ['Lead Source Link', seller.lead_source_link],
        ['Assigned By', seller.assigned_by],
        ['Deleted By', seller.deleted_by],
        ['Lead Source', seller.lead_source],
        ['Before/After Registered', seller.before_after_registered],
        ['Store Status', seller.store_status],
        ['Major Reasons', seller.major_reasons],
        ['Call Attempts', seller.call_attempts],
        ['Follow Up Date', seller.follow_up_date ? new Date(seller.follow_up_date).toLocaleDateString('en-IN') : '-'],
        ['Created At', seller.created_at ? new Date(seller.created_at).toLocaleString() : '-'],
        ['Notes', seller.notes]
    ];
    
    fields.forEach(function (field) {
        if (field[1]) {
            html += `
                <div class="col-md-6 mb-3">
                    <strong>${field[0]}:</strong><br>
                    <span class="text-muted">${escapeHtml(field[1])}</span>
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
                $('#editNotes').val(seller.notes || '');
                
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
        store_name: $('#editStoreName').val(),
        customer_name: $('#editCustomerName').val(),
        phone_number: $('#editPhone').val(),
        status: $('#editStatus').val(),
        notes: $('#editNotes').val()
    };
    
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
        }
    });
});

/* -----------------------------
   DELETE BUTTON CLICK
----------------------------- */
$(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    
    if (confirm('Are you sure you want to delete this seller?')) {
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
                }
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
        <div class="toast-body">
            <strong>${title}</strong> ${message}
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