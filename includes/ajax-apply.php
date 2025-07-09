<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ---- Get application form structure for a job ----
add_action('wp_ajax_wjb_get_application_form', 'wjb_ajax_get_application_form');
add_action('wp_ajax_nopriv_wjb_get_application_form', 'wjb_ajax_get_application_form');
function wjb_ajax_get_application_form() {
    // Always check fields and return 400 for missing/invalid
    if (empty($_POST['nonce']) || empty($_POST['shortcode'])) {
        wp_send_json_error('Missing required fields.', 400);
    }
    if (!wp_verify_nonce($_POST['nonce'], 'wjb_nonce')) {
        wp_send_json_error('Invalid or expired nonce.', 400);
    }
    $shortcode = sanitize_text_field($_POST['shortcode']);
    require_once __DIR__ . '/api.php';
    $form = wjb_get_application_form($shortcode);
    if (is_wp_error($form)) {
        wp_send_json_error($form->get_error_message(), 500);
    } else {
        wp_send_json_success($form);
    }
}

// ---- Submit application ----
add_action('wp_ajax_wjb_submit_application', 'wjb_ajax_submit_application');
add_action('wp_ajax_nopriv_wjb_submit_application', 'wjb_ajax_submit_application');
function wjb_ajax_submit_application() {
//     file_put_contents(__DIR__ . '/candidate_debug.txt', print_r($_POST, true), FILE_APPEND);
    if (empty($_POST['nonce']) || empty($_POST['shortcode'])) {
        wp_send_json_error('Missing required fields.', 400);
    }
    if (!wp_verify_nonce($_POST['nonce'], 'wjb_nonce')) {
        wp_send_json_error('Invalid or expired nonce.', 400);
    }
    $shortcode = sanitize_text_field($_POST['shortcode']);

    // Collect only candidate fields (exclude WP internals)
    $candidate = $_POST;
    unset($candidate['action'], $candidate['nonce'], $candidate['shortcode']);

    // Ensure required fields
    if (empty($candidate['name']) || empty($candidate['email'])) {
        wp_send_json_error('Name and Email are required fields.', 400);
    }

    // Log candidate for debugging (remove or comment after confirming)
//     file_put_contents(__DIR__ . '/candidate_debug.txt', print_r($candidate, true), FILE_APPEND);

    // Handle files
    $files = [];
    foreach ($_FILES as $key => $file) {
        $files[$key] = $file;
    }

    require_once __DIR__ . '/api.php';
    $form = wjb_get_application_form($shortcode);

    // Backend file validation
    if (!is_wp_error($form) && isset($form['form_fields'])) {
        foreach ($form['form_fields'] as $field) {
            if ($field['type'] === 'file') {
                $file_key = $field['key'];
                if (!empty($files[$file_key]['name'])) {
                    $allowed = isset($field['supported_file_types']) ? $field['supported_file_types'] : [];
                    $max_size = isset($field['max_file_size']) ? $field['max_file_size'] : 0;
                    $ext = strtolower(pathinfo($files[$file_key]['name'], PATHINFO_EXTENSION));
                    if (!empty($allowed) && !in_array($ext, $allowed)) {
                        wp_send_json_error("File type for '{$field['label']}' not allowed. Allowed: " . implode(', ', $allowed), 400);
                    }
                    if ($max_size && $files[$file_key]['size'] > $max_size) {
                        wp_send_json_error("File '{$field['label']}' is too large. Max: " . round($max_size / 1024 / 1024, 2) . "MB", 400);
                    }
                } elseif (!empty($field['required'])) {
                    wp_send_json_error("The field '{$field['label']}' is required.", 400);
                }
            }
        }
    }

    // Submit to Workable
    $result = wjb_submit_application($shortcode, $candidate, $files);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message(), 500);
    } else {
        wp_send_json_success("Application submitted successfully!");
    }
}
