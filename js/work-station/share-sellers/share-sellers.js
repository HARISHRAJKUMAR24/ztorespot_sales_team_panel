$(document).ready(function () {
    // Load recent shares
    loadRecentShares();

    // Toggle between existing and new seller
    $('input[name="shareOption"]').on('change', function () {
        if ($(this).val() === 'existing') {
            $('#existingSellerSection').show();
            $('#newSellerSection').hide();
            // Remove required attributes from new fields
            $('#newSellerId').prop('required', false);
            $('#newCustomerName').prop('required', false);
            $('#newPhoneNumber').prop('required', false);
            $('#newCustomerResponse').prop('required', false);
        } else {
            $('#existingSellerSection').hide();
            $('#newSellerSection').show();
            // Add required attributes to new fields
            $('#newSellerId').prop('required', true);
            $('#newCustomerName').prop('required', true);
            $('#newPhoneNumber').prop('required', true);
            $('#newCustomerResponse').prop('required', true);
            // Clear sellers preview
            $('#sellersPreview').hide();
            $('#sellerIds').val([]);
        }
    });

    // Live preview for new seller fields
    $('#newSellerId, #newCustomerName, #newPhoneNumber, #newCustomerResponse').on('input change', function () {
        const sellerId = $('#newSellerId').val().trim();
        const customerName = $('#newCustomerName').val().trim();
        const phoneNumber = $('#newPhoneNumber').val().trim();
        const response = $('#newCustomerResponse').val();

        if (sellerId || customerName || phoneNumber || response) {
            $('#previewNewSellerId').text(sellerId || '-');
            $('#previewNewBusinessName').text(customerName || '-');
            $('#previewNewPhoneNumber').text(phoneNumber || '-');
            $('#previewNewResponse').text(response || '-');
            $('#newSellerPreview').show();
        } else {
            $('#newSellerPreview').hide();
        }
    });

    // Toggle share to all option
    $('#shareToAll').on('change', function () {
        if ($(this).is(':checked')) {
            $('#sharedWithUser').prop('disabled', true).hide();
            $('#userQuickButtons').hide();
            $('.select-all-note').show();
            // Clear all selections
            $('#sharedWithUser').val([]);
        } else {
            $('#sharedWithUser').prop('disabled', false).show();
            $('#userQuickButtons').show();
            $('.select-all-note').hide();
        }
    });

    // Seller selection change handler
    $('#sellerIds').on('change', function () {
        const selectedOptions = $(this).find('option:selected');
        const selectedCount = selectedOptions.length;

        if (selectedCount > 0) {
            let previewHtml = '';

            selectedOptions.each(function () {
                const sellerId = $(this).val();
                const businessName = $(this).data('business') || '';
                const phoneNumber = $(this).data('phone') || '';
                const response = $(this).data('response') || '';

                previewHtml += `
                    <tr>
                        <td><span class="badge bg-secondary">${sellerId}</span></td>
                        <td>${escapeHtml(businessName)}</td>
                        <td>${escapeHtml(phoneNumber)}</td>
                        <td><span class="badge bg-light text-dark">${escapeHtml(response)}</span></td>
                    </tr>
                `;
            });

            $('#selectedCount').text(selectedCount);
            $('#previewTableBody').html(previewHtml);
            $('#sellersPreview').show();
        } else {
            $('#sellersPreview').hide();
        }
    });

    // Select all sellers
    $('#selectAllSellers').on('click', function () {
        $('#sellerIds option').prop('selected', true);
        $('#sellerIds').trigger('change');
    });

    // Deselect all sellers
    $('#deselectAllSellers').on('click', function () {
        $('#sellerIds option').prop('selected', false);
        $('#sellerIds').trigger('change');
    });

    // Select all users
    $('#selectAllUsers').on('click', function () {
        if (!$('#shareToAll').is(':checked')) {
            $('#sharedWithUser option').prop('selected', true);
        }
    });

    // Deselect all users
    $('#deselectAllUsers').on('click', function () {
        if (!$('#shareToAll').is(':checked')) {
            $('#sharedWithUser option').prop('selected', false);
        }
    });

    // Phone number validation
    $('#newPhoneNumber, #sharePhoneNumber').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });

    // Seller ID validation
    $('#newSellerId').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Form reset handler
    $('button[type="reset"]').on('click', function (e) {
        e.preventDefault();
        $('#shareSellerForm')[0].reset();
        $('#sellersPreview').hide();
        $('#newSellerPreview').hide();
        $('#existingSellerSection').show();
        $('#newSellerSection').hide();
        $('input[name="shareOption"][value="existing"]').prop('checked', true);
        $('#shareToAll').prop('checked', false);
        $('#sharedWithUser').prop('disabled', false).show();
        $('#userQuickButtons').show();
        $('.select-all-note').hide();
        $('#sharedWithUser').val([]);
        $('#sellerIds').val([]);
        $('#selectedCount').text('0');
        $('#previewTableBody').empty();
    });

    // Form submit handler
    $('#shareSellerForm').on('submit', function (e) {
        e.preventDefault();

        const shareOption = $('input[name="shareOption"]:checked').val();
        const shareToAll = $('#shareToAll').is(':checked');
        const sharedWithUsers = shareToAll ? [] : $('#sharedWithUser').val();
        const notes = $('#shareNotes').val().trim();

        // Validate common fields
        if (!shareToAll && (!sharedWithUsers || sharedWithUsers.length === 0)) {
            showToast('warning', 'Warning!', 'Please select at least one user to share with');
            $('#sharedWithUser').focus();
            return;
        }

        let formData = {
            share_option: shareOption,
            notes: notes,
            share_to_all: shareToAll
        };

        if (!shareToAll) {
            formData.shared_with_users = sharedWithUsers;
        }

        // Validate based on share option
        if (shareOption === 'existing') {
            const sellerIds = $('#sellerIds').val();
            if (!sellerIds || sellerIds.length === 0) {
                showToast('warning', 'Warning!', 'Please select at least one seller to share');
                $('#sellerIds').focus();
                return;
            }
            formData.seller_ids = sellerIds;
        } else {
            const sellerId = $('#newSellerId').val().trim();
            const customerName = $('#newCustomerName').val().trim();
            const phoneNumber = $('#newPhoneNumber').val().trim();
            const customerResponse = $('#newCustomerResponse').val();

            if (!sellerId) {
                showToast('warning', 'Warning!', 'Please enter seller ID');
                $('#newSellerId').focus();
                return;
            }

            if (!customerName) {
                showToast('warning', 'Warning!', 'Please enter customer name');
                $('#newCustomerName').focus();
                return;
            }

            if (!phoneNumber) {
                showToast('warning', 'Warning!', 'Please enter phone number');
                $('#newPhoneNumber').focus();
                return;
            }

            if (!/^\d{10}$/.test(phoneNumber)) {
                showToast('warning', 'Warning!', 'Please enter a valid 10-digit phone number');
                $('#newPhoneNumber').focus();
                return;
            }

            if (!customerResponse) {
                showToast('warning', 'Warning!', 'Please select customer response');
                $('#newCustomerResponse').focus();
                return;
            }

            formData.seller_id = sellerId;
            formData.customer_name = customerName;
            formData.phone_number = phoneNumber;
            formData.customer_response = customerResponse;
        }

        // Show loading state
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.html();
        $submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Sharing...').prop('disabled', true);

        // Send AJAX request
        $.ajax({
            url: BASE_URL + 'ajax/work-station/share-sellers/share-sellers.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            traditional: true,
            success: function (response) {
                console.log('Success response:', response); // Debug log
                if (response.status === 'success') {
                    let message = response.message;
                    if (response.total_shares) {
                        message = `Successfully created ${response.total_shares} share(s) with ${response.success_count} user(s)`;
                    }
                    showToast('success', 'Success!', message);

                    // Reset form
                    $('#shareSellerForm')[0].reset();
                    $('#sellersPreview').hide();
                    $('#newSellerPreview').hide();
                    $('#existingSellerSection').show();
                    $('#newSellerSection').hide();
                    $('input[name="shareOption"][value="existing"]').prop('checked', true);
                    $('#shareToAll').prop('checked', false);
                    $('#sharedWithUser').prop('disabled', false).show();
                    $('#userQuickButtons').show();
                    $('.select-all-note').hide();
                    $('#sharedWithUser').val([]);
                    $('#sellerIds').val([]);
                    $('#selectedCount').text('0');
                    $('#previewTableBody').empty();

                    // Reload recent shares
                    loadRecentShares();
                } else {
                    showToast('danger', 'Error!', response.message);
                }
            },
            error: function (xhr, status, error) {
                console.log('Error response:', xhr.responseText); // Debug log
                console.log('Status:', status);
                console.log('Error:', error);

                let errorMessage = 'Failed to share seller. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                }
                showToast('danger', 'Error!', errorMessage);
            },
            complete: function () {
                $submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Load recent shares
    function loadRecentShares() {
        $.ajax({
            url: BASE_URL + 'ajax/work-station/share-sellers/get-recent-shares.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success' && response.data && response.data.length > 0) {
                    renderRecentShares(response.data);
                } else {
                    $('#recentSharesTable').html(`
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox me-2"></i>
                                No recent shares found
                            </td>
                        </tr>
                    `);
                }
            },
            error: function () {
                $('#recentSharesTable').html(`
                    <tr>
                        <td colspan="7" class="text-center py-4 text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Failed to load recent shares
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Render recent shares
    function renderRecentShares(shares) {
        let html = '';

        shares.forEach(function (share) {
            const statusClass =
                share.status === 'accepted' ? 'badge-accepted' :
                    share.status === 'rejected' ? 'badge-rejected' :
                        'badge-pending';

            const statusText = share.status.charAt(0).toUpperCase() + share.status.slice(1);

            html += `
                <tr>
                    <td><span class="badge bg-secondary">${escapeHtml(share.seller_id) || 'N/A'}</span></td>
                    <td>
                        <div class="fw-bold">${escapeHtml(share.customer_name)}</div>
                        <small class="text-muted">${escapeHtml(share.phone_number)}</small>
                    </td>
                    <td>
                        <div class="fw-bold">${escapeHtml(share.shared_with_name)}</div>
                        <small class="text-muted">${escapeHtml(share.shared_with_phone)}</small>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">${escapeHtml(share.customer_response)}</span>
                    </td>
                    <td>
                        <span class="badge ${statusClass}">${statusText}</span>
                    </td>
                    <td>
                        <small>${formatDate(share.shared_at)}</small>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary view-share" 
                                data-id="${share.id}"
                                title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        $('#recentSharesTable').html(html);

        // Attach view handlers
        $('.view-share').on('click', function () {
            const shareId = $(this).data('id');
            viewShareDetails(shareId);
        });
    }

    // View share details
    function viewShareDetails(shareId) {
        $.ajax({
            url: BASE_URL + 'ajax/work-station/share-sellers/get-share-details.php',
            type: 'GET',
            data: { share_id: shareId },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success' && response.data) {
                    showShareDetailsModal(response.data);
                } else {
                    showToast('danger', 'Error', response.message || 'Failed to load details');
                }
            },
            error: function () {
                showToast('danger', 'Error', 'Failed to load share details');
            }
        });
    }

    // Show share details modal
    function showShareDetailsModal(share) {
        const statusClass =
            share.status === 'accepted' ? 'badge-accepted' :
                share.status === 'rejected' ? 'badge-rejected' :
                    'badge-pending';

        const statusText = share.status.charAt(0).toUpperCase() + share.status.slice(1);

        const html = `
            <div class="mb-3">
                <label class="fw-bold">Seller ID:</label>
                <div><span class="badge bg-secondary">${escapeHtml(share.seller_id) || 'N/A'}</span></div>
            </div>
            <div class="mb-3">
                <label class="fw-bold">Customer Name:</label>
                <div>${escapeHtml(share.customer_name)}</div>
            </div>
            <div class="mb-3">
                <label class="fw-bold">Phone Number:</label>
                <div>${escapeHtml(share.phone_number)}</div>
            </div>
            <div class="mb-3">
                <label class="fw-bold">Customer Response:</label>
                <div><span class="badge bg-light text-dark">${escapeHtml(share.customer_response)}</span></div>
            </div>
            <div class="mb-3">
                <label class="fw-bold">Shared By:</label>
                <div>${escapeHtml(share.shared_by_name)} (${escapeHtml(share.shared_by_phone)})</div>
            </div>
            <div class="mb-3">
                <label class="fw-bold">Shared With:</label>
                <div>${escapeHtml(share.shared_with_name)} (${escapeHtml(share.shared_with_phone)})</div>
            </div>
            <div class="mb-3">
                <label class="fw-bold">Status:</label>
                <div><span class="badge ${statusClass}">${statusText}</span></div>
            </div>
            <div class="mb-3">
                <label class="fw-bold">Shared At:</label>
                <div>${formatDate(share.shared_at)}</div>
            </div>
            ${share.notes ? `
            <div class="mb-3">
                <label class="fw-bold">Notes:</label>
                <div class="p-2 bg-light rounded">${escapeHtml(share.notes)}</div>
            </div>
            ` : ''}
        `;

        $('#shareDetailsContent').html(html);
        $('#shareDetailsModal').modal('show');
    }

    // Helper functions
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' +
            date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function showToast(type, title, message) {
        const id = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' :
            type === 'danger' ? 'bg-danger' :
                type === 'warning' ? 'bg-warning' : 'bg-info';

        const html = `
            <div id="${id}" class="toast text-white ${bgClass}" role="alert" 
                 data-bs-autohide="true" data-bs-delay="3000">
                <div class="toast-header ${bgClass} text-white border-0">
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">${message}</div>
            </div>
        `;

        $('.toast-container').append(html);
        const toast = new bootstrap.Toast(document.getElementById(id));
        toast.show();

        $(`#${id}`).on('hidden.bs.toast', function () {
            $(this).remove();
        });
    }
});