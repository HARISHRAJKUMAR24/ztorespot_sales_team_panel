$(document).ready(function () {

let currentPage = 1;
let perPage = 10;
let totalPages = 1;
let sortColumn = 'id';
let sortOrder = 'DESC';
let searchTerm = '';
let filters = {
    source_type: '',
    reg_status: '',
    current_status: '',
    plan: '',
    has_response: '',
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
        source_type: $('#filterSource').val(),
        reg_status: $('#filterRegStatus').val(),
        current_status: $('#filterCurrentStatus').val(),
        plan: $('#filterPlan').val(),
        has_response: $('#filterHasResponse').val(),
        date_range: $('#filterDate').val()
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
    $('#filterSource').val('');
    $('#filterRegStatus').val('');
    $('#filterCurrentStatus').val('');
    $('#filterPlan').val('');
    $('#filterHasResponse').val('');
    $('#filterDate').val('');
    
    filters = {
        source_type: '',
        reg_status: '',
        current_status: '',
        plan: '',
        has_response: '',
        date_range: ''
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
        url: BASE_URL + 'ajax/sellers/get_sales_person_sellers.php',
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
        // Format date
        let dateDisplay = '-';
        if (row.entry_date) {
            const date = new Date(row.entry_date + 'T00:00:00');
            dateDisplay = date.toLocaleDateString('en-GB');
        }
        
        // Format fields for display
        const workDetails = row.work_details_update || '-';
        const sourceType = row.source_type || '-';
        const phoneNumber = row.phone_number || '-';
        const plan = row.plans_interested || '-';
        const response = row.customer_response || '-';
        const status = row.current_status || '-';
        
        function truncateText(text, maxLength) {
            if (!text || text === '-') return '-';
            if (text.length > maxLength) {
                return text.substring(0, maxLength) + '...';
            }
            return text;
        }
        
        let workDetailsDisplay = workDetails;
        if (workDetails.length > 20 && isMobile) {
            workDetailsDisplay = workDetails.substring(0, 17) + '...';
        }
        
        html += '<tr>';
        html += `<td>${escapeHtml(row.id)}</td>`;
        html += `<td>${dateDisplay}</td>`;
        html += `<td title="${escapeHtml(workDetails)}">${escapeHtml(workDetailsDisplay)}</td>`;
        html += `<td class="d-none d-lg-table-cell" title="${escapeHtml(sourceType)}">${escapeHtml(truncateText(sourceType, 15))}</td>`;
        html += `<td>${escapeHtml(phoneNumber)}</td>`;
        html += `<td class="d-none d-xl-table-cell" title="${escapeHtml(plan)}">${escapeHtml(truncateText(plan, 10))}</td>`;
        html += `<td class="update-cell" title="${escapeHtml(response)}">${escapeHtml(truncateText(response, 25))}</td>`;
        html += `<td class="d-none d-lg-table-cell">${getStatusBadge(status)}</td>`;
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
   GET STATUS BADGE
----------------------------- */
function getStatusBadge(status) {
    switch (status) {
        case 'Upgraded':
            return '<span class="badge bg-success">Upgraded</span>';
        case 'Not yet':
            return '<span class="badge bg-warning text-dark">Not yet</span>';
        case 'In Active':
            return '<span class="badge bg-danger">In Active</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
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
    $('#registeredCount').text(stats.registered_count || 0);
    $('#upgradedCount').text(stats.upgraded_count || 0);
    $('#followupCount').text(stats.followup_count || 0);
}

/* -----------------------------
   VIEW BUTTON CLICK
----------------------------- */
$(document).on('click', '.view-btn', function () {
    const id = $(this).data('id');
    
    $.ajax({
        url: BASE_URL + 'ajax/sellers/get_sales_person_details.php',
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
    
    let dateDisplay = '-';
    if (seller.entry_date) {
        const date = new Date(seller.entry_date + 'T00:00:00');
        dateDisplay = date.toLocaleDateString('en-GB');
    }
    
    let createdDisplay = '-';
    if (seller.created_at) {
        const date = new Date(seller.created_at);
        createdDisplay = date.toLocaleString('en-GB');
    }
    
    const fields = [
        ['ID', seller.id],
        ['Entry Date', dateDisplay],
        ['Seller/Store Name', seller.work_details_update],
        ['Source Type', seller.source_type],
        ['Registration Status', seller.registration_status],
        ['Phone Number', seller.phone_number],
        ['Plans Interested', seller.plans_interested],
        ['Customer Response', seller.customer_response],
        ['Remembering Notes', seller.remembering_notes],
        ['Latest Update', seller.latest_update],
        ['Current Status', seller.current_status],
        ['Customer Queries', seller.customer_queries],
        ['Video/Canva', seller.video_canva],
        ['Call Timing', seller.call_timing],
        ['Remarks', seller.remarks],
        ['Import Batch', seller.import_batch],
        ['Created At', createdDisplay]
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
        url: BASE_URL + 'ajax/sellers/get_sales_person_details.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                const seller = response.data;
                $('#editId').val(seller.id);
                $('#editEntryDate').val(seller.entry_date || '');
                $('#editWorkDetails').val(seller.work_details_update || '');
                $('#editSourceType').val(seller.source_type || '');
                $('#editRegStatus').val(seller.registration_status || '');
                $('#editPhone').val(seller.phone_number || '');
                $('#editPlansInterested').val(seller.plans_interested || '');
                $('#editCustomerResponse').val(seller.customer_response || '');
                $('#editRememberingNotes').val(seller.remembering_notes || '');
                $('#editLatestUpdate').val(seller.latest_update || '');
                $('#editCurrentStatus').val(seller.current_status || '');
                $('#editCustomerQueries').val(seller.customer_queries || '');
                $('#editVideoCanva').val(seller.video_canva || '');
                $('#editCallTiming').val(seller.call_timing || '');
                $('#editRemarks').val(seller.remarks || '');
                
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
        entry_date: $('#editEntryDate').val() || null,
        work_details_update: $('#editWorkDetails').val().trim(),
        source_type: $('#editSourceType').val(),
        registration_status: $('#editRegStatus').val(),
        phone_number: $('#editPhone').val().trim(),
        plans_interested: $('#editPlansInterested').val(),
        customer_response: $('#editCustomerResponse').val().trim(),
        remembering_notes: $('#editRememberingNotes').val().trim(),
        latest_update: $('#editLatestUpdate').val().trim(),
        current_status: $('#editCurrentStatus').val(),
        customer_queries: $('#editCustomerQueries').val().trim(),
        video_canva: $('#editVideoCanva').val().trim(),
        call_timing: $('#editCallTiming').val().trim(),
        remarks: $('#editRemarks').val().trim()
    };
    
    if (!data.work_details_update) {
        showToast('warning', 'Warning!', 'Seller/Store Name is required');
        return;
    }
    
    if (!data.phone_number) {
        showToast('warning', 'Warning!', 'Phone Number is required');
        return;
    }
    
    $.ajax({
        url: BASE_URL + 'ajax/sellers/update_sales_person_seller.php',
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
            url: BASE_URL + 'ajax/sellers/delete_sales_person_seller.php',
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
    window.location.href = BASE_URL + 'ajax/sellers/export_sales_person_sellers.php';
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