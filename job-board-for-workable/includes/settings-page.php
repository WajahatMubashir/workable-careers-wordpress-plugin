<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Add a settings menu
add_action('admin_menu', function() {
    add_options_page(
        'Job Board for Workable Settings',
        'Job Board for Workable',
        'manage_options',
        'job-board-for-workable-settings',
        'wjbfw_render_settings_page'
    );
});

// Enqueue color picker assets in admin
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'settings_page_job-board-for-workable-settings') {
        return;
    }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_add_inline_script('wp-color-picker', '
        jQuery(document).ready(function($) {
            $(".wjbfw-color-picker").wpColorPicker();
        });
    ');
});

// Get default theme colors
function wjbfw_get_default_colors() {
    // Try to get theme colors from WordPress theme.json or customizer
    $primary_color = get_theme_mod('header_textcolor', '333333');
    if ($primary_color === 'blank') $primary_color = '333333';
    
    return array(
        'filter_border' => '#555555',
        'department_title' => '#193459',
        'job_title' => '#1354c2',
        'job_location' => '#535353',
        'job_border' => '#e9e9e9'
    );
}

// Render the settings page
function wjbfw_render_settings_page() {
    // Save settings if form submitted
    if (isset($_POST['wjbfw_settings_submitted']) && check_admin_referer('wjbfw_save_settings')) {
        $api_token = isset($_POST['wjbfw_api_token']) ? sanitize_text_field(wp_unslash($_POST['wjbfw_api_token'])) : '';
        $subdomain = isset($_POST['wjbfw_workable_subdomain']) ? sanitize_text_field(wp_unslash($_POST['wjbfw_workable_subdomain'])) : '';
        
        update_option('wjbfw_api_token', $api_token);
        update_option('wjbfw_workable_subdomain', $subdomain);
        
        // Save color settings
        $filter_border = isset($_POST['wjbfw_filter_border_color']) ? sanitize_hex_color(wp_unslash($_POST['wjbfw_filter_border_color'])) : '';
        $department_title = isset($_POST['wjbfw_department_title_color']) ? sanitize_hex_color(wp_unslash($_POST['wjbfw_department_title_color'])) : '';
        $job_title = isset($_POST['wjbfw_job_title_color']) ? sanitize_hex_color(wp_unslash($_POST['wjbfw_job_title_color'])) : '';
        $job_location = isset($_POST['wjbfw_job_location_color']) ? sanitize_hex_color(wp_unslash($_POST['wjbfw_job_location_color'])) : '';
        $job_border = isset($_POST['wjbfw_job_border_color']) ? sanitize_hex_color(wp_unslash($_POST['wjbfw_job_border_color'])) : '';
        
        update_option('wjbfw_filter_border_color', $filter_border);
        update_option('wjbfw_department_title_color', $department_title);
        update_option('wjbfw_job_title_color', $job_title);
        update_option('wjbfw_job_location_color', $job_location);
        update_option('wjbfw_job_border_color', $job_border);
        
        echo '<div class="updated"><p>Settings saved!</p></div>';
    }
    
    $token = esc_attr(get_option('wjbfw_api_token', ''));
    $subdomain = esc_attr(get_option('wjbfw_workable_subdomain', ''));
    
    // Get color settings with defaults
    $defaults = wjbfw_get_default_colors();
    $filter_border_color = get_option('wjbfw_filter_border_color', $defaults['filter_border']);
    $department_title_color = get_option('wjbfw_department_title_color', $defaults['department_title']);
    $job_title_color = get_option('wjbfw_job_title_color', $defaults['job_title']);
    $job_location_color = get_option('wjbfw_job_location_color', $defaults['job_location']);
    $job_border_color = get_option('wjbfw_job_border_color', $defaults['job_border']);
    ?>
    <div class="wrap">
        <h1>Job Board for Workable Settings</h1>
        
        <!-- Shortcode Information -->
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; border-radius: 4px;">
            <h2>Available Shortcodes</h2>
            <p><strong>[job_board_for_workable_filters]</strong> - Displays search and filter controls for job listings</p>
            <p><strong>[job_board_for_workable]</strong> - Displays the complete job board with listings grouped by department</p>
            <p><em>Note: Use both shortcodes together for the best user experience. Place the filters shortcode above the job board shortcode.</em></p>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('wjbfw_save_settings'); ?>
            
            <h2>API Configuration</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wjbfw_api_token">Workable API Token</label></th>
                    <td>
                        <input type="text" id="wjbfw_api_token" name="wjbfw_api_token" value="<?php echo esc_attr( $token ); ?>" class="regular-text" />
                        <p class="description">Your Workable API token for accessing job data.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wjbfw_workable_subdomain">Workable Subdomain</label></th>
                    <td>
                        <input type="text" id="wjbfw_workable_subdomain" name="wjbfw_workable_subdomain" value="<?php echo esc_attr( $subdomain ); ?>" class="regular-text" />
                        <p class="description">Your Workable account subdomain (e.g., if your URL is company.workable.com, enter "company").</p>
                    </td>
                </tr>
            </table>
            
            <h2>Color Customization</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wjbfw_filter_border_color">Filter Controls Border Color</label></th>
                    <td>
                        <input type="text" id="wjbfw_filter_border_color" name="wjbfw_filter_border_color" value="<?php echo esc_attr($filter_border_color); ?>" class="wjbfw-color-picker" />
                        <p class="description">Border color for search and filter input fields.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wjbfw_department_title_color">Department Title Color</label></th>
                    <td>
                        <input type="text" id="wjbfw_department_title_color" name="wjbfw_department_title_color" value="<?php echo esc_attr($department_title_color); ?>" class="wjbfw-color-picker" />
                        <p class="description">Color for department headings in the job board.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wjbfw_job_title_color">Job Title Color</label></th>
                    <td>
                        <input type="text" id="wjbfw_job_title_color" name="wjbfw_job_title_color" value="<?php echo esc_attr($job_title_color); ?>" class="wjbfw-color-picker" />
                        <p class="description">Color for individual job titles/links.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wjbfw_job_location_color">Job Location Color</label></th>
                    <td>
                        <input type="text" id="wjbfw_job_location_color" name="wjbfw_job_location_color" value="<?php echo esc_attr($job_location_color); ?>" class="wjbfw-color-picker" />
                        <p class="description">Color for job location text under each job title.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wjbfw_job_border_color">Job Item Border Color</label></th>
                    <td>
                        <input type="text" id="wjbfw_job_border_color" name="wjbfw_job_border_color" value="<?php echo esc_attr($job_border_color); ?>" class="wjbfw-color-picker" />
                        <p class="description">Bottom border color for each job listing item.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="wjbfw_settings_submitted" class="button-primary" value="Save Changes" />
            </p>
        </form>
    </div>
    <?php
}
