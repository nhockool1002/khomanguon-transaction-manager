jQuery(document).ready(function($) {
    'use strict';

    $('.khomanguon-unlock-btn').on('click', function() {
        var unlockButton = $(this);
        var postId = unlockButton.data('post_id');

        unlockButton.prop('disabled', true);
        unlockButton.text('Liên kết tải đang được khởi tạo ...');

        $.ajax({
            url: khomanguonTransaction.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_signed_s3_url',
                nonce: khomanguonTransaction.unlockNonce,
                post_id: postId
            },
            success: function(response) {
                window.open(response.message, '_blank');
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr) {
                if (window.toastr) {
                    toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không thể tạo liên kết tải.');
                } else {
                    alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không thể tạo liên kết tải.');
                }

                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            }
        });
    });
});
