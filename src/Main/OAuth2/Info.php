<?php

$ref = sanitize_text_field($_POST['v_pwl_ref_settings']);


if ((isset($ref) && $ref === 'true') && check_admin_referer('v_pwl_options_update') && current_user_can('manage_options')) {
    updateOption('v_oauth_client_id', sanitize_text_field($_POST['v_oauth_client_id']));
    updateOption('v_oauth_client_secret', sanitize_text_field($_POST['v_oauth_client_secret']));
    updateOption('v_oauth_authorize_url', sanitize_text_field($_POST['v_oauth_authorize_url']));
    updateOption('v_oauth_access_token_url', sanitize_text_field($_POST['v_oauth_access_token_url']));
    updateOption('v_oauth_user_info_url', sanitize_text_field($_POST['v_oauth_user_info_url']));

    add_settings_error("vincss_oauth2_settings", "save_success", __("Settings saved.", 'vincss-fido2-login'), "success");
} elseif ((isset($ref) && $ref === 'true')) {
    add_settings_error("vincss_oauth2_settings", "save_error", __("Settings NOT saved.", 'vincss-fido2-login'));
}
?>

<div class="vcss_notice">
    <?php settings_errors("vincss_oauth2_settings"); ?>
</div>


<div class="d-block">
    <a href="https://fido2cloud.vincss.net/" target="_blank" class="description v-link link-dark v-text-primary mt-3 d-block"> <?php _e('Create a free account at VinCSS FIDO2® Public Cloud service to initialize OAuth2 application', 'vincss-fido2-login'); ?></a>

    <div id="vincss_form_oauth2">
        <form method="post" action="">
            <?php
            wp_nonce_field('v_pwl_options_update');

            ?>
            <input class="form-control" type='hidden' name='v_pwl_ref_settings' value='true'>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <?php _e('Client ID:', 'vincss-fido2-login'); ?><span class="required">*</span>
                        </th>
                        <td><input class="form-control" id="v_oauth_client_id" value="<?php echo esc_attr(getOption('v_oauth_client_id')); ?>" required="" type="text" name="v_oauth_client_id"></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php _e('Client Secret:', 'vincss-fido2-login'); ?><span class="required">*</span>
                        </th>
                        <td>
                            <input class="form-control" id="v_oauth_client_secret" value="<?php echo esc_attr(getOption('v_oauth_client_secret')); ?>" required="" type="password" name="v_oauth_client_secret">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php _e('Authorize Endpoint:', 'vincss-fido2-login'); ?><span class="required">*</span>
                        </th>
                        <td><input class="form-control" required="" type="text" value="<?php echo esc_attr(getOption('v_oauth_authorize_url')); ?>" id="v_oauth_authorize_url" name="v_oauth_authorize_url"></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php _e('Access Token Endpoint:', 'vincss-fido2-login'); ?><span class="required">*</span>
                        </th>
                        <td><input class="form-control" required="" type="text" value="<?php echo esc_attr(getOption('v_oauth_access_token_url')); ?>" id="v_oauth_access_token_url" name="v_oauth_access_token_url"></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php _e('Get User Info Endpoint:', 'vincss-fido2-login'); ?><span class="required">*</span>
                        </th>
                        <td><input class="form-control" required="" type="text" value="<?php echo esc_attr(getOption('v_oauth_user_info_url')); ?>" id="v_oauth_user_info_url" name="v_oauth_user_info_url"></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php _e('Authorization callback URL:', 'vincss-fido2-login'); ?><span class="required">*</span>
                        </th>
                        <td>
                            <input class="form-control" disabled value="<?php echo esc_attr(get_site_url() . '/wp-login.php'); ?>" required="" type="text" name="v_oauth_callback_url">
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
</div>