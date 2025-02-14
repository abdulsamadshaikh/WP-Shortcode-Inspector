<?php
if (!defined('ABSPATH')) {
    exit;
}

// Admin Menu
function wsi_add_admin_menu() {
    add_menu_page(
        'Shortcode Inspector',
        'Shortcode Inspector',
        'manage_options',
        'wsi-dashboard',
        'wsi_dashboard_page',
        'dashicons-search',
        25
    );
}

add_action('admin_menu', 'wsi_add_admin_menu');

// Localize Script & Pass Nonce to JS
function wsi_enqueue_scripts($hook) {
    // Load script only on WP Shortcode Inspector page
    if ($hook !== 'toplevel_page_wsi-dashboard') {
        return;
    }

    // Enqueue the JavaScript file
    wp_enqueue_script(
        'wsi-ajax-script',
        plugins_url('assets/js/script.js', dirname(__FILE__)), // Correct path
        array('jquery'),
        null,
        true
    );

    // Pass AJAX URL & Nonce to JavaScript
    wp_localize_script('wsi-ajax-script', 'wsi_ajax', array(
        'ajaxurl'   => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('wsi_scan_nonce'),
        'edit_url'  => admin_url('post.php?post=')
    ));
}

add_action('admin_enqueue_scripts', 'wsi_enqueue_scripts');

// Dashboard Page
function wsi_dashboard_page() {
    ?>
    <div class="wrap">
        <h1>WP Shortcode Inspector</h1>
        <p>Scan and analyze shortcodes across your website.</p>
        <button id="wsi-scan-btn" class="button button-primary">Scan Now</button>
        <div id="wsi-results"></div>
        <a href="<?php echo admin_url('admin-post.php?action=wsi_export'); ?>" class="button button-secondary">Download CSV Report</a>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('#wsi-scan-btn').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'wsi_scan_shortcodes' },
                    beforeSend: function() {
                        $('#wsi-results').html('<p>Scanning...</p>');
                    },
                    success: function(response) {
                        $('#wsi-results').html(response);
                    }
                });
            });
        });
    </script>
    <?php
}

// Export Report Feature
function wsi_export_shortcodes_csv() {
    global $wpdb;
    $filename = "shortcode-inspector-report-" . date("Y-m-d") . ".csv";
    
    // Set headers for CSV file download
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Expires: 0");

    $output = fopen("php://output", "w");

    // Add CSV column headers
    fputcsv($output, array('Shortcode', 'Post ID', 'Active'));

    // Fetch posts with content
    $posts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status='publish'");

    foreach ($posts as $post) {
        preg_match_all('/\[([a-zA-Z0-9-_]+)[^\]]*\]/', $post->post_content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $shortcode) {
                // Format shortcode with square brackets
                $formatted_shortcode = '[' . $shortcode . ']';
                fputcsv($output, [$formatted_shortcode, $post->ID, shortcode_exists($shortcode) ? 'Yes' : 'No']);
            }
        }
    }

    fclose($output);
    exit;
}

// Register admin action for CSV export
add_action('admin_post_wsi_export', 'wsi_export_shortcodes_csv');

function wsi_bulk_replace_form() {
    ?>
    <h2>Bulk Replace Shortcodes</h2>
    <form method="post">
        <label for="find_shortcode">Find Shortcode:</label>
        <input type="text" id="find_shortcode" name="find_shortcode" placeholder="[old_shortcode]" required>
        
        <label for="replace_shortcode">Replace With:</label>
        <input type="text" id="replace_shortcode" name="replace_shortcode" placeholder="[new_shortcode]" required>

        <button type="submit" name="wsi_bulk_replace" class="button button-primary">Replace Now</button>
    </form>
    <?php

    if (isset($_POST['wsi_bulk_replace'])) {
        wsi_bulk_replace_shortcodes($_POST['find_shortcode'], $_POST['replace_shortcode']);
    }
}

function wsi_bulk_replace_shortcodes($find, $replace) {
    global $wpdb;
    
    // Remove brackets from input if user included them
    $find_shortcode = trim($find, '[]');
    $replace_shortcode = trim($replace, '[]');

    // Get all posts
    $posts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status='publish'");

    foreach ($posts as $post) {
        $updated_content = str_replace("[$find_shortcode]", "[$replace_shortcode]", $post->post_content);

        // Update only if changes are made
        if ($updated_content !== $post->post_content) {
            $wpdb->update($wpdb->posts, ['post_content' => $updated_content], ['ID' => $post->ID]);
        }
    }

    echo "<p style='color: green;'>Replacement complete!</p>";
}

function wsi_disable_shortcode_settings() {
    ?>
    <h2>Disable Shortcodes</h2>
    <form method="post">
        <label for="disable_shortcode">Shortcode to Disable:</label>
        <input type="text" id="disable_shortcode" name="disable_shortcode" placeholder="[shortcode]" required>
        <button type="submit" name="wsi_disable_shortcode" class="button button-warning">Disable</button>
    </form>
    <?php

    if (isset($_POST['wsi_disable_shortcode'])) {
        update_option('wsi_disabled_shortcode', trim($_POST['disable_shortcode'], '[]'));
        echo "<p style='color: red;'>Shortcode disabled successfully.</p>";
    }
}

// Block shortcode execution
function wsi_block_disabled_shortcode($tag) {
    $disabled_shortcode = get_option('wsi_disabled_shortcode');
    if ($tag === $disabled_shortcode) {
        return '__return_empty_string';
    }
    return $tag;
}

add_filter('shortcode_atts', 'wsi_block_disabled_shortcode');