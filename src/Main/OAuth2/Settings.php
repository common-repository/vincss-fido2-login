<?php
$v_pwl_ref_settings = sanitize_text_field($_POST['v_pwl_ref_settings']);
if ((isset($v_pwl_ref_settings) && $v_pwl_ref_settings  === 'true') && check_admin_referer('v_pwl_options_update') && current_user_can('manage_options')) {
    updateOption('v_is_show_btn_login', sanitize_text_field($_POST['v_is_show_btn_login']));
    updateOption('v_btn_oauth2_title', sanitize_text_field($_POST['v_btn_oauth2_title']));
    updateOption('v_oauth2_redirect_url', sanitize_text_field($_POST['v_oauth2_redirect_url']));
    updateOption('v_enable_oauth2', sanitize_text_field($_POST['v_enable_oauth2']));
    updateOption('oauth2_att_mapping', sanitize_text_field($_POST['oauth2_att_mapping']));
    add_settings_error("vincss_oauth2_settings", "save_success", __("Settings saved.", 'vincss-fido2-login'), "success");
} elseif ((isset($v_pwl_ref_settings) && $v_pwl_ref_settings  === 'true')) {
    add_settings_error("vincss_oauth2_settings", "save_error", __("Settings NOT saved.", 'vincss-fido2-login'));
}

?>
<div class="vcss_notice">
    <?php settings_errors("vincss_oauth2_settings"); ?>
</div>

<div id="vincss_form_oauth2">

    <form method="post" action="">
        <?php
        wp_nonce_field('v_pwl_options_update');
        $is_enabled = getOption('v_enable_oauth2');
        ?>
        <input type='hidden' name='v_pwl_ref_settings' value='true'>
        <table class="form-table form_oauth2_setting">
            <tbody>
                <tr>
                    <th scope="row">
                        <?php _e('Enable:', 'vincss-fido2-login'); ?>
                    </th>
                    <td>

                        <label class="toggle-switch">
                            <input type="checkbox" id="v_enable_oauth2" name="v_enable_oauth2" value="true" <?php echo  $is_enabled ? 'checked' : '' ?>> <span>
                                <i></i>
                            </span>
                        </label>

                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Button Title:', 'vincss-fido2-login'); ?><span class="required">*</span>
                    </th>
                    <td>
                        <input required="" type="text" class="form-control ms-0" value="<?php echo esc_attr(getOption('v_btn_oauth2_title')); ?>" id="v_btn_oauth2_title" name="v_btn_oauth2_title">
                    </td>

                </tr>
                <tr>
                    <th scope="row" class="pt-1">
                        <?php _e('Redirect URL:', 'vincss-fido2-login'); ?><span class="required">*</span>
                    </th>
                    <td>
                        <input required="" type="text" class="form-control ms-0" value="<?php echo esc_attr(getOption('v_oauth2_redirect_url')); ?>" id="v_oauth2_redirect_url" name="v_oauth2_redirect_url">

                        <p class="description"><?php _e('Redirect a URL after successful login', 'vincss-fido2-login'); ?></p>
                    </td>

                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Attribute Mapping:', 'vincss-fido2-login'); ?><span class="required">*</span>
                    </th>
                    <td>
                        <div class="d-flex align-items-center">
                            <div>
                                <input required="" type="text" value="username" disabled class="v_oauth2_mapping_field form-control">
                            </div>
                            <span class="mx-2">-</span>
                            <div>
                                <?php
                                $enforce_2fa = getOption('oauth2_att_mapping');
                                ?>
                                <input required="" type="text" value="<?php echo esc_attr(getOption('oauth2_att_mapping')); ?>" id="v_btn_oauth2_title" name="oauth2_att_mapping" class="v_oauth2_mapping_field form-control">

                            </div>

                        </div>

                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button(); ?>
    </form>
</div>