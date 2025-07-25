jQuery(document).ready(function($) {
    $('#esps-push-to-live-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $msg = $('#esps-push-to-live-msg');
        $btn.prop('disabled', true);
        $msg.text('Pushing...');
        $.ajax({
            url: ezcps_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'ezcps_push_to_live',
                security: ezcps_ajax_object.nonce,
                post_id: ezcps_ajax_object.post_id
            },
            success: function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    $msg.css('color', 'green').text(response.data.message);
                } else {
                    $msg.css('color', 'red').text(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false);
                $msg.css('color', 'red').text('Error: ' + error);
            }
        });
    });
});
