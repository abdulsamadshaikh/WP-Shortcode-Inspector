<?php
if (!defined('ABSPATH')) {
    exit;
}

// Ensure function exists before adding the AJAX action
if (function_exists('wsi_scan_shortcodes')) {
    add_action('wp_ajax_wsi_scan_shortcodes', 'wsi_scan_shortcodes');
    add_action('wp_ajax_nopriv_wsi_scan_shortcodes', 'wsi_scan_shortcodes');
}