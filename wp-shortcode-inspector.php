<?php
/**
 * Plugin Name: WP Shortcode Inspector
 * Plugin URI: https://getabdulsamad.com
 * Description: Scan, analyze, and manage shortcodes across your WordPress site.
 * Version: 1.0.0
 * Author: Abdul Samad
 * Author URI: https://getabdulsamad.com
 * License: GPL-2.0+
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WSI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WSI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include files only once
require_once WSI_PLUGIN_DIR . 'includes/admin-menu.php';
require_once WSI_PLUGIN_DIR . 'includes/shortcode-scanner.php';
require_once WSI_PLUGIN_DIR . 'includes/ajax-handler.php';