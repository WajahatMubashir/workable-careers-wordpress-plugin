<?php
/**
 * Plugin Name: Job Board for Workable
 * Description: Displays job listings from Workable on your WordPress site. Use shortcodes [job_board_for_workable_filters] and [job_board_for_workable] to display the job board with filtering capabilities.
 * Version: 1.0.0
 * Author: Wajahat Mubashir
 * License: GPL2+
 * Text Domain: job-board-for-workable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access.
}

// === Constants ===
define( 'WJBFW_DIR', plugin_dir_path( __FILE__ ) );
define( 'WJBFW_URL', plugin_dir_url( __FILE__ ) );

// === Load Text Domain ===
// === Includes ===
require_once WJBFW_DIR . 'includes/enqueue.php';
require_once WJBFW_DIR . 'includes/api.php';
require_once WJBFW_DIR . 'includes/ajax-apply.php';
require_once WJBFW_DIR . 'includes/shortcodes.php';
require_once WJBFW_DIR . 'includes/settings-page.php';

// === Add Settings Link to Plugin Page ===
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=job-board-for-workable-settings') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// === Activation/Deactivation Hooks (Optional for future use) ===
register_activation_hook( __FILE__, function() {
    // setup actions if needed
});
register_deactivation_hook( __FILE__, function() {
    // cleanup actions if needed
});
