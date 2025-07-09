<?php
// Add a settings menu
add_action('admin_menu', function() {
    add_options_page(
        'Workable Job Board Settings',
        'Workable Job Board',
        'manage_options',
        'workable-job-board-settings',
        'wjb_render_settings_page'
    );
});

// Render the settings page
function wjb_render_settings_page() {
    // Save settings if form submitted
    if (isset($_POST['wjb_settings_submitted']) && check_admin_referer('wjb_save_settings')) {
        update_option('wjb_api_token', sanitize_text_field($_POST['wjb_api_token']));
        update_option('wjb_workable_subdomain', sanitize_text_field($_POST['wjb_workable_subdomain']));
        echo '<div class="updated"><p>Settings saved!</p></div>';
    }
    $token = esc_attr(get_option('wjb_api_token', ''));
    $subdomain = esc_attr(get_option('wjb_workable_subdomain', ''));
    ?>
    <div class="wrap">
        <h2>Workable Job Board Settings</h2>
        <form method="post">
            <?php wp_nonce_field('wjb_save_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wjb_api_token">Workable API Token</label></th>
                    <td><input type="text" id="wjb_api_token" name="wjb_api_token" value="<?php echo $token; ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wjb_workable_subdomain">Workable Subdomain</label></th>
                    <td><input type="text" id="wjb_workable_subdomain" name="wjb_workable_subdomain" value="<?php echo $subdomain; ?>" class="regular-text" /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="wjb_settings_submitted" class="button-primary" value="Save Changes" />
            </p>
        </form>
    </div>
    <?php
}
