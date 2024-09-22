jQuery(document).ready(function($) {
    $(document).on('contextmenu', 'img', function(e) {
        e.preventDefault();
    });
});