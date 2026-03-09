$(document).ready(function () {

let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let sortColumn = 'id';
let sortOrder = 'DESC';
let searchTerm = '';
let filters = {
    assigned_by: '',
    plan_status: '',
    plan_name: '',
    has_products: '',
    month: '',
    year: ''
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
    perPage = parseInt($(this).val());
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
        assigned_by: $('#filterAssigned').val(),
        plan_status: $('#filterStatus').val(),
        plan_name: $('#filterPlan').val(),
        has_products: $('#filterProducts').val(),
        month: $('#filterMonth').val(),
        year: $('#filterYear').val()
    };
    currentPage = 1;
    loadData();
    
    if (window.innerWidth < 768) {
        $('#filterCollapse').collapse('hide');
    }
});

/* -----------------------------
   CLEAR FILTERS
----------------------------- */
$('#clearFilters').on('click', function () {
    $('#filterAssigned').val('');
    $('#filterStatus').val('');
    $('#filterPlan').val('');
    $('#filterProducts').val('');
    $('#filterMonth').val('');
    $('#filterYear').val('');
    
    filters = {
        assigned_by: '',
        plan_status: '',
        plan_name: '',
        has_products: '',
        month: '',
        year: ''
    };
    currentPage = 1;
    loadData();
});

/* -----------------------------
   PAGINATION CLICK HANDLER
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
        url: BASE_URL + 'ajax/upgrade_sellers/get_sellers.php',
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
        error: function (xhr, status, error) {
            $('#loadingSpinner').hide();
            $('#noData').show();
            showToast('danger', 'Error!', 'Failed to load data');
            console.error('AJAX Error:', error);
        }
    });
}

/* -----------------------------
   RENDER TABLE
----------------------------- */
function renderTable(rows) {
    let html = '';
    const isMobile = window.innerWidth < 768;
    
    rows.forEach(function (row) {
        let sellerName = row.seller_name || '-';
        if (sellerName.length > 20 && isMobile) {
            sellerName = sellerName.substring(0, 17) + '...';
        }
        
        // Status badge color
        let statusBadge = '';
        if (row.plan_status) {
            const status = row.plan_status.toLowerCase();
            if (status.includes('active')) {
                statusBadge = '<span class="badge bg-success">Active</span>';
            } else if (status.includes('inactive') || status.includes('not')) {
                statusBadge = '<span class="badge bg-secondary">Inactive</span>';
            } else if (status.includes('dis') || status.includes('pending')) {
                statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
            } else {
                statusBadge = `<span class="badge bg-info">${escapeHtml(row.plan_status)}</span>`;
            }
        } else {
            statusBadge = '<span class="badge bg-light text-dark">-</span>';
        }
        
        html += '<tr>';
        html += `<td>${escapeHtml(row.id)}</td>`;
        html += `<td class="d-none d-lg-table-cell">${escapeHtml(row.store_id || '-')}</td>`;
        html += `<td title="${escapeHtml(row.seller_name || '')}">${escapeHtml(sellerName)}</td>`;
        html += `<td class="d-none d-xl-table-cell" title="${escapeHtml(row.seller_contact || '')}">${escapeHtml(row.seller_contact || '-')}</td>`;
        html += `<td>${escapeHtml(row.phone_number || '-')}</td>`;
        html += `<td class="d-none d-lg-table-cell">${escapeHtml(row.plan_name || '-')}</td>`;
        html += `<td class="d-none d-md-table-cell">${statusBadge}</td>`;
        html += `<td class="d-none d-xl-table-cell text-center">${escapeHtml(row.product_uploads || '0')}</td>`;
        html += `<td class="d-none d-xxl-table-cell">${escapeHtml(row.assigned_by || '-')}</td>`;
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
   RENDER PAGINATION
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
        
        // Calculate page range to show
        let maxVisible = window.innerWidth < 576 ? 3 : 5;
        let startPage = Math.max(1, page - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);
        
        // Adjust if we're near the end
        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }
        
        // First page indicator
        if (startPage > 1) {
            html += `<li class="page-item d-none d-sm-block"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled d-none d-sm-block"><span class="page-link">...</span></li>`;
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }
        
        // Last page indicator
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
    
    // Update pagination info
    const start = (page - 1) * perPage + 1;
    const end = Math.min(page * perPage, total);
    $('#paginationInfo').html(`Showing ${start} to ${end} of ${total} entries`);
}

/* -----------------------------
   UPDATE STATS CARDS
----------------------------- */
function updateStats(stats) {
    $('#totalCount').text(stats.total || 0);
    $('#activeCount').text(stats.active_count || 0);
    $('#monthCount').text(stats.month_count || 0);
    $('#productsCount').text(stats.products_count || 0);
}

/* -----------------------------
   VIEW BUTTON CLICK
----------------------------- */
$(document).on('click', '.view-btn', function () {
    const id = $(this).data('id');
    
    $.ajax({
        url: BASE_URL + 'ajax/upgrade_sellers/get_seller_details.php',
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
    let html = '';
    
    let createdDisplay = '-';
    if (seller.created_at) {
        const date = new Date(seller.created_at);
        createdDisplay = date.toLocaleString('en-GB');
    }
    
    let updatedDisplay = '-';
    if (seller.updated_at) {
        const date = new Date(seller.updated_at);
        updatedDisplay = date.toLocaleString('en-GB');
    }
    
    const fields = [
        ['ID', seller.id],
        ['Store ID', seller.store_id],
        ['Seller Name', seller.seller_name],
        ['Seller Contact', seller.seller_contact],
        ['Phone Number', seller.phone_number],
        ['Seller Response', seller.seller_response],
        ['Product Uploads', seller.product_uploads],
        ['Plan Name', seller.plan_name],
        ['Plan Status', seller.plan_status],
        ['Assigned By', seller.assigned_by],
        ['Platform Come', seller.platform_come],
        ['Platform Known', seller.platform_known],
        ['Month Name', seller.month_name],
        ['Created At', createdDisplay],
        ['Updated At', updatedDisplay]
    ];
    
    fields.forEach(function (field) {
        if (field[1] && field[1] !== '' && field[1] !== null && field[1] !== '-') {
            html += `
                <div class="col-12 col-sm-6 mb-3">
                    <div class="p-2 bg-light rounded">
                        <small class="text-muted d-block">${field[0]}</small>
                        <strong class="d-block" style="white-space: pre-wrap; word-break: break-word;">${escapeHtml(field[1].toString())}</strong>
                    </div>
                </div>
            `;
        }
    });
    
    $('#sellerDetails').html(html);
}

/* -----------------------------
   EDIT BUTTON CLICK
----------------------------- */
$(document).on('click', '.edit-btn', function () {
    const id = $(this).data('id');
    
    $.ajax({
        url: BASE_URL + 'ajax/upgrade_sellers/get_seller_details.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                const seller = response.data;
                $('#editId').val(seller.id);
                $('#editStoreId').val(seller.store_id || '');
                $('#editSellerName').val(seller.seller_name || '');
                $('#editSellerContact').val(seller.seller_contact || '');
                $('#editPhone').val(seller.phone_number || '');
                $('#editSellerResponse').val(seller.seller_response || '');
                $('#editProductUploads').val(seller.product_uploads || '0');
                $('#editPlanName').val(seller.plan_name || '');
                $('#editPlanStatus').val(seller.plan_status || '');
                $('#editAssignedBy').val(seller.assigned_by || '');
                $('#editPlatformCome').val(seller.platform_come || '');
                $('#editPlatformKnown').val(seller.platform_known || '');
                $('#editMonthName').val(seller.month_name || '');
                
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
        store_id: $('#editStoreId').val() || null,
        seller_name: $('#editSellerName').val().trim(),
        seller_contact: $('#editSellerContact').val().trim() || null,
        phone_number: $('#editPhone').val().trim(),
        seller_response: $('#editSellerResponse').val().trim() || null,
        product_uploads: $('#editProductUploads').val() || 0,
        plan_name: $('#editPlanName').val().trim() || null,
        plan_status: $('#editPlanStatus').val().trim() || null,
        assigned_by: $('#editAssignedBy').val().trim() || null,
        platform_come: $('#editPlatformCome').val().trim() || null,
        platform_known: $('#editPlatformKnown').val().trim() || null,
        month_name: $('#editMonthName').val().trim() || null
    };
    
    if (!data.seller_name) {
        showToast('warning', 'Warning!', 'Seller Name is required');
        return;
    }
    
    if (!data.phone_number) {
        showToast('warning', 'Warning!', 'Phone Number is required');
        return;
    }
    
    $.ajax({
        url: BASE_URL + 'ajax/upgrade_sellers/update_seller.php',
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
            showToast('danger', 'Error!', 'Failed to update: ' + error);
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
            url: BASE_URL + 'ajax/upgrade_sellers/delete_seller.php',
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
    window.location.href = BASE_URL + 'ajax/upgrade_sellers/export_sellers.php';
});

/* -----------------------------
   ESCAPE HTML
----------------------------- */
function escapeHtml(text) {
    if (!text) return '-';
    if (text === null) return '-';
    if (text === '') return '-';
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
        if ($('#dataTable').is(':visible')) {
            loadData();
        }
    }, 250);
});

});