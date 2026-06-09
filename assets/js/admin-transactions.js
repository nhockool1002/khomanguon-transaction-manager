jQuery(document).ready(function($) {
    'use strict';

    var dataTableLanguage = {
        search: 'Tìm kiếm:',
        lengthMenu: 'Hiển thị _MENU_ dòng',
        info: 'Đang hiển thị _START_ đến _END_ trong _TOTAL_ dòng',
        infoEmpty: 'Không có dữ liệu',
        infoFiltered: '(lọc từ _MAX_ dòng)',
        zeroRecords: 'Không tìm thấy dữ liệu phù hợp',
        emptyTable: 'Không có dữ liệu',
        paginate: {
            first: 'Đầu',
            previous: 'Trước',
            next: 'Sau',
            last: 'Cuối'
        }
    };

    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#table_history_payment')) {
        $('#table_history_payment').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Tất cả']],
            autoWidth: false,
            order: [[3, 'desc']],
            language: dataTableLanguage,
            columnDefs: [
                { orderable: false, targets: [4, 5] }
            ]
        });
    }

    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#table_history_payment_2')) {
        $('#table_history_payment_2').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Tất cả']],
            autoWidth: false,
            order: [[4, 'desc']],
            language: dataTableLanguage
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
            $select.css({ backgroundColor: '#dc2626', color: 'white' });
        } else if (selectedValue === 0) {
            $select.css({ backgroundColor: '#facc15', color: '#422006' });
        } else if (selectedValue === 1) {
            $select.css({ backgroundColor: '#16a34a', color: 'white' });
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
