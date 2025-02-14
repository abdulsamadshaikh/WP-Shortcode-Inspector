<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WSI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WSI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files in the correct order
require_once WSI_PLUGIN_DIR . 'includes/admin-menu.php';
require_once WSI_PLUGIN_DIR . 'includes/shortcode-scanner.php'; // This must be included first
require_once WSI_PLUGIN_DIR . 'includes/ajax-handler.php'; // AJAX hooks must come after function declarations

// Function to execute shortcode and measure performance
function wsi_measure_shortcode_performance($shortcode) {
    if (!shortcode_exists($shortcode)) {
        return ['shortcode' => $shortcode, 'execution_time' => 'N/A'];
    }

    // Start time
    $start_time = microtime(true);

    // Prevent caching issues
    wp_suspend_cache_invalidation(true);

    // Execute shortcode safely
    $output = do_shortcode("[$shortcode]");

    // Restore caching
    wp_suspend_cache_invalidation(false);

    // End time
    $end_time = microtime(true);

    // Calculate execution time
    $execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds

    return ['shortcode' => $shortcode, 'execution_time' => $execution_time . " ms"];
}

// Modify the scanner to also show performance
function wsi_scan_shortcodes() {
    global $wpdb;

    // Verify security nonce FIRST
    if (!isset($_POST['wsi_nonce']) || !wp_verify_nonce($_POST['wsi_nonce'], 'wsi_scan_nonce')) {
        error_log('Nonce verification failed. Received nonce: ' . ($_POST['wsi_nonce'] ?? 'NONE'));
        wp_send_json_error(['message' => 'Security check failed!'], 400);
    }

    $shortcodes = [];
    $posts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status='publish'");

    if ($posts) {
        foreach ($posts as $post) {
            preg_match_all('/\[([a-zA-Z0-9-_]+)[^\]]*\]/', $post->post_content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $shortcode) {
                    $performance = wsi_measure_shortcode_performance($shortcode);
                    $shortcodes[] = [
                        'shortcode' => '[' . $shortcode . ']',
                        'post_id'   => $post->ID,
                        'exists'    => shortcode_exists($shortcode) ? 'Yes' : 'No',
                        'execution_time' => $performance['execution_time']
                    ];
                }
            }
        }
    }

    if (!empty($shortcodes)) {
        wp_send_json_success($shortcodes);
    } else {
        wp_send_json_error(['message' => 'No shortcodes found.']);
    }

    wp_die();
}

function wsi_scan_page_builder_shortcodes() {
    global $wpdb;
    $shortcodes = [];

    // Fetch only necessary meta values
    $meta_posts = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE '_elementor%' OR meta_key LIKE '_wpb%' OR meta_key LIKE '_fl_builder%' LIMIT 500");

    foreach ($meta_posts as $meta) {
        if (!empty($meta->meta_value)) {
            preg_match_all('/\[([a-zA-Z0-9-_]+)[^\]]*\]/', $meta->meta_value, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $shortcode) {
                    $shortcodes[] = ['shortcode' => '[' . $shortcode . ']', 'post_id' => $meta->post_id];
                }
            }
        }
    }

    return $shortcodes;
}