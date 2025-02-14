// JavaScript for AJAX Call
jQuery(document).ready(function($) {
    $('#wsi-scan-btn').on('click', function() {
        $.ajax({
            url: wsi_ajax.ajaxurl, // Correct AJAX URL
            type: 'POST',
            data: { 
                action: 'wsi_scan_shortcodes',
                wsi_nonce: wsi_ajax.nonce // Include security nonce
            },
            beforeSend: function() {
                $('#wsi-results').html('<p>Scanning...</p>');
            },
            success: function(response) {
                if (response.success) {
                    let output = '<table class="widefat"><thead><tr><th>Shortcode</th><th>Post ID</th><th>Active</th></tr></thead><tbody>';
                    response.data.forEach(item => {
                        output += `<tr>
                                    <td>${item.shortcode}</td>
                                    <td><a href="${wsi_ajax.edit_url}${item.post_id}" target="_blank">${item.post_id}</a></td>
                                    <td>${item.exists}</td>
                                   </tr>`;
                    });
                    output += '</tbody></table>';
                    $('#wsi-results').html(output);
                } else {
                    $('#wsi-results').html(`<p style="color: red;">${response.data.message}</p>`);
                }
            },
            error: function(xhr) {
                $('#wsi-results').html(`<p style="color: red;">Error: ${xhr.responseJSON?.message || 'AJAX request failed'}</p>`);
            }
        });
    });
});