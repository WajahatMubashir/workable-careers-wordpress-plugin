<?php
// Enqueue all plugin assets (JS & CSS)
function workable_job_board_enqueue_assets() {
    // Enqueue CSS
    wp_enqueue_style(
        'tcp-job-board-css',
        plugin_dir_url(__FILE__) . '../assets/css/workable-job-board.css',
        array(),
        '1.0'
    );

    // Enqueue JS
    wp_enqueue_script(
        'workable-job-board',
        plugin_dir_url(__FILE__) . '../assets/js/workable-job-board.js',
        array('jquery'),
        '1.0.0',
        true
    );

    // Localize for AJAX
    wp_localize_script('workable-job-board', 'wjb_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wjb_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'workable_job_board_enqueue_assets');
