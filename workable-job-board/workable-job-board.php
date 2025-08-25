<?php
/**
 * Plugin Name: Workable Job Board
 * Description: Displays job listings from Workable on your WordPress site.
 * Version: 1.0.1
 * Author: Wajahat
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access.
}

// === Constants ===
define( 'WJB_DIR', plugin_dir_path( __FILE__ ) );
define( 'WJB_URL', plugin_dir_url( __FILE__ ) );

// === Includes ===
require_once WJB_DIR . 'includes/enqueue.php';
require_once WJB_DIR . 'includes/api.php';
require_once WJB_DIR . 'includes/ajax-apply.php';
require_once WJB_DIR . 'includes/shortcodes.php';
require_once WJB_DIR . 'includes/settings-page.php';


// === Activation/Deactivation Hooks (Optional for future use) ===
register_activation_hook( __FILE__, function() {
    // setup actions if needed
});
register_deactivation_hook( __FILE__, function() {
    // cleanup actions if needed
});
