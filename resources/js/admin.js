(function($) {
    'use strict';

    $('.s3-sync-file-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var url = $(e.currentTarget).data('url');

        jQuery.get(url, function() {
            $(e.currentTarget).html('<span class="dashicons dashicons-saved"></span>');
        });
    });
})(jQuery);