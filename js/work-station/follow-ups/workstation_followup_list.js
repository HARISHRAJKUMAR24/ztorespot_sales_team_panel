$(document).ready(function () {
    // Define BASE_URL if not defined


    let currentPage = 1;
    let perPage = 10;
    let totalPages = 1;
    let sortColumn = 'id';
    let sortOrder = 'DESC';
    let searchTerm = '';
    let filters = {
        response: '',
        seller_type: '',
        status: '',
        date_range: ''
    };

    /* -----------------------------
       LOAD INITIAL DATA
    ------------------------------ */
    loadData();

    /* -----------------------------
       SEARCH INPUT
    ------------------------------ */
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
    ------------------------------ */
    $('#perPage').on('change', function () {
        perPage = parseInt($(this).val());
        currentPage = 1;
        loadData();
    });

    /* -----------------------------
       SORTING
    ------------------------------ */
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
    ------------------------------ */
    $('#applyFilters').on('click', function () {
        filters = {
            response: $('#filterResponse').val(),
            seller_type: $('#filterSellerType').val(),
            status: $('#filterStatus').val(),
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
    ------------------------------ */
    $('#clearFilters').on('click', function () {
        $('#filterResponse').val('');
        $('#filterSellerType').val('');
        $('#filterStatus').val('');
        $('#filterDate').val('');
        
        filters = {
            response: '',
            seller_type: '',
            status: '',
            date_range: ''
        };
        currentPage = 1;
        loadData();
    });

    /* -----------------------------
       PAGINATION CLICK HANDLER
    ------------------------------ */
    $(document).on('click', '.page-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadData();
            
            if (window.innerWidth < 768) {
                $('html, body').animate({
                    scrollTop: $('#dataTable').offset().top - 70
                }, 300);
            }
        }
    });

    /* -----------------------------
       LOAD DATA FUNCTION
    ------------------------------ */
    function loadData() {
        $('#loadingSpinner').show();
        $('#dataTable').hide();
        $('#noData').hide();

        $.ajax({
            url: BASE_URL + 'ajax/work-station/follow-ups/get_followup_list.php',
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
                        updateStats(response.data.stats);
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
    ------------------------------ */
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
            
            // Get badge color based on response
            const badgeClass = row.customer_response === 'Later' ? 'bg-warning' : 'bg-info';
            
            // Get status badge
            const statusBadge = row.customer_status === 'Upgraded' ? 'bg-success' : 'bg-secondary';
            
            html += '<tr>';
            html += `<td>${escapeHtml(row.id)}</td>`;
            html += `<td>${dateDisplay}</td>`;
            html += `<td>${escapeHtml(row.business_name || '-')}</td>`;
            html += `<td>${escapeHtml(row.phone_number || '-')}</td>`;
            html += `<td class="d-none d-lg-table-cell"><span class="badge ${badgeClass}">${escapeHtml(row.customer_response || '-')}</span></td>`;
            html += `<td class="d-none d-lg-table-cell">${escapeHtml(row.call_back_time || '-')}</td>`;
            html += `<td><span class="badge ${statusBadge}">${escapeHtml(row.customer_status || 'Pending')}</span></td>`;
            html += `<td class="text-center text-nowrap">
                <button class="btn btn-sm btn-outline-primary view-btn" data-id="${row.id}" title="View">
                    <i class="bi bi-eye"></i>
                </button>
                <a href="workstation_edit_seller.php?id=${row.id}" class="btn btn-sm btn-outline-warning" title="Edit">
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

    /* -----------------------------
       RENDER PAGINATION
    ------------------------------ */
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
    ------------------------------ */
    function updateStats(stats) {
        $('#totalCount').text(stats.total || 0);
        $('#laterCount').text(stats.later_count || 0);
        $('#callbackCount').text(stats.callback_count || 0);
    }

    /* -----------------------------
       VIEW BUTTON CLICK
    ------------------------------ */
    $(document).on('click', '.view-btn', function () {
        const id = $(this).data('id');
        
        $.ajax({
            url: BASE_URL + 'ajax/work-station/follow-ups/get_seller_details.php',
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
    ------------------------------ */
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
            ['Business Name', seller.business_name],
            ['Seller Type', seller.seller_type],
            ['Phone Number', seller.phone_number],
            ['Customer Response', seller.customer_response],
            ['Selected Plan', seller.selected_plan],
            ['Upgraded Plan', seller.upgraded_plan],
            ['Upgraded Duration', seller.upgraded_duration],
            ['Call Back Time', seller.call_back_time],
            ['Customer Queries', seller.customer_queries],
            ['Customer Status', seller.customer_status],
            ['Call Duration', seller.call_duration],
            ['Additional Notes', seller.additional_notes],
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
       DELETE BUTTON CLICK
    ------------------------------ */
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this seller?')) {
            $.ajax({
                url: BASE_URL + 'ajax/work-station/follow-ups/delete_seller.php',
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
       ESCAPE HTML
    ------------------------------ */
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
    ------------------------------ */
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
    ------------------------------ */
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