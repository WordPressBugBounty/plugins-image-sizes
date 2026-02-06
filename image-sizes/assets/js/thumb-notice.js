jQuery(document).ready(function ($) {
    $(document).on('click', '#thumbpress_settings_init .notice-dismiss', function () {
        $.ajax({
            url: THUMBPRESS.ajaxurl,
            type: 'POST',
            data: {
                action: 'thumbpress_init_notice_dismiss',
                value: 1,
            },
            success: function (response) {
                console.log('Option updated:', response);
            },
            error: function (error) {
                console.error('Error:', error);
            }
        });
    });
});