<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Enqueue all plugin assets (JS & CSS)
function wjbfw_enqueue_assets() {
    // Enqueue CSS
    wp_enqueue_style(
        'wjbfw-job-board',
        plugin_dir_url(__FILE__) . '../assets/css/workable-job-board.css',
        array(),
        '1.0.0'
    );

    // Add dynamic CSS based on color settings
    wjbfw_add_dynamic_css();

    // Enqueue frontend JS (consolidated)
    wp_register_script(
        'wjbfw-frontend',
        plugin_dir_url(__FILE__) . '../assets/js/frontend.js',
        array('jquery'),
        '1.0.0',
        true
    );

    wp_localize_script('wjbfw-frontend', 'wjbfw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wjbfw_nonce'),
    ));

    wp_enqueue_script('wjbfw-frontend');
}
add_action('wp_enqueue_scripts', 'wjbfw_enqueue_assets');

// Generate dynamic CSS based on color settings
function wjbfw_add_dynamic_css() {
    // Get color settings with defaults
    require_once plugin_dir_path(__FILE__) . 'settings-page.php';
    $defaults = wjbfw_get_default_colors();
    
    $filter_border_color = sanitize_hex_color( get_option('wjbfw_filter_border_color', $defaults['filter_border']) );
    $department_title_color = sanitize_hex_color( get_option('wjbfw_department_title_color', $defaults['department_title']) );
    $job_title_color = sanitize_hex_color( get_option('wjbfw_job_title_color', $defaults['job_title']) );
    $job_location_color = sanitize_hex_color( get_option('wjbfw_job_location_color', $defaults['job_location']) );
    $job_border_color = sanitize_hex_color( get_option('wjbfw_job_border_color', $defaults['job_border']) );
    
    // Generate CSS
    $custom_css = "
        /* Job Board for Workable Dynamic Colors */
        div#wjbfw-job-filters :is(select, input) {
            border-color: {$filter_border_color} !important;
        }
        
        .wjbfw-dept-title {
            color: {$department_title_color} !important;
        }
        
        .wjbfw-job-item .wjbfw-view-detail-btn,
        strong.wjbfw-job-title {
            color: {$job_title_color} !important;
        }
        
        .wjbfw-job-location {
            color: {$job_location_color} !important;
        }
        
        li.wjbfw-job-item {
            border-bottom-color: {$job_border_color} !important;
        }
    ";
    
    // Add the custom CSS
    wp_add_inline_style('wjbfw-job-board', $custom_css);
}
