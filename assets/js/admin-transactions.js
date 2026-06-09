jQuery(document).ready(function($) {
    'use strict';

    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#table_history_payment')) {
        $('#table_history_payment').DataTable({
            pageLength: 10,
            order: [[3, 'desc']]
        });
    }

    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#table_history_payment_2')) {
        $('#table_history_payment_2').DataTable({
            pageLength: 10
        });
    }

    $('.method').each(function() {
        updateStatusColor($(this));
    });

    $('.method').on('change', function() {
        updateStatusColor($(this));
    });

    function updateStatusColor($select) {
        var selectedValue = parseInt($select.val(), 10);
        $select.css('font-weight', 'bold');

        if (selectedValue === -1) {
            $select.css({ backgroundColor: 'red', color: 'white' });
        } else if (selectedValue === 0) {
            $select.css({ backgroundColor: 'yellow', color: 'black' });
        } else if (selectedValue === 1) {
            $select.css({ backgroundColor: 'green', color: 'white' });
        }
    }

    $('.khomanguon-update-payment').on('click', function() {
        var paymentButton = $(this);
        var paymentId = paymentButton.data('payment_id');
        var status = $('.khomanguon-status-select-' + paymentId).val();

        paymentButton.prop('disabled', true);

        $.ajax({
            url: khomanguonAdminTransaction.ajaxUrl,
            method: 'POST',
            data: {
                action: 'update_order_status',
                nonce: khomanguonAdminTransaction.nonce,
                order_id: paymentId,
                status: status
            },
            success: function(response) {
                Swal.fire(response.message).then(function() {
                    window.location.reload();
                });
            },
            error: function(xhr) {
                paymentButton.prop('disabled', false);
                Swal.fire(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Cập nhật thất bại.');
            }
        });
    });
});
