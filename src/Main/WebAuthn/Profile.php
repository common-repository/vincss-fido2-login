<?php
global $current_user;
$user = wp_get_current_user();
wp_enqueue_script('vincss_fido2_login_pwl_profile', VFido2LoginPluginURL . 'src/Assets/js/passwordless/profile.js');
wp_localize_script('vincss_fido2_login_pwl_profile', 'variables_pwl_profile', array(
    'ajax_uri' => admin_url('admin-ajax.php'),
    'initializing' => __('Initializing...', 'vincss-fido2-login'),
    'registrating' => __('Registrating...', 'vincss-fido2-login'),
    'enter_name' => __('Please enter the authenticator name', 'vincss-fido2-login'),
    'load_failed' => __('Did loading fail, maybe try refreshing?', 'vincss-fido2-login'),
    'any_type' => __('Any', 'vincss-fido2-login'),
    'verifying' => __('Verifying...', 'vincss-fido2-login'),
    'renaming' => __('Renaming...', 'vincss-fido2-login'),
    'ready' => __('Ready', 'vincss-fido2-login'),
    'no' => __('No', 'vincss-fido2-login'),
    'verification_failed' => '<span class="vincss-secure-failed">' . __('Verification failed', 'vincss-fido2-login') . '</span>',
    'verification_passed' => '<span class="vincss-secure-success">' . __('Verification passed! You can now log in through Passwordless', 'vincss-fido2-login') . '</span>',
    'no_registered' => __('No registered authenticators', 'vincss-fido2-login'),
    'confirm_delete' => __('Are you sure to delete: ', 'vincss-fido2-login'),
    'platform_type' => __('Local computer', 'vincss-fido2-login'),
    'roaming_type' => __('Remote device', 'vincss-fido2-login'),
    'remove' => __('Delete', 'vincss-fido2-login'),
    'removing' => __('Deleting...', 'vincss-fido2-login'),
    'flow_instructions' => __('Please follow instructions to finish registration...', 'vincss-fido2-login'),
    'disabled_usernameless' => __('The site administrator has disabled usernameless login feature.', 'vincss-fido2-login'),
    'after_removing' => __('After removing this authenticator, you will not be able to login with Passwordless', 'vincss-fido2-login'),
    'registered' => '<span class="vincss-secure-success">' . _x('Registered', 'action', 'vincss-fido2-login') . '</span>',
    'registration_failed' => '<span class="vincss-secure-failed">' . __('Registration failed', 'vincss-fido2-login') . '</span>',
    'browser_not_support' => __('Your browser does not support Passwordless', 'vincss-fido2-login'),
    'rename' => __('Rename', 'vincss-fido2-login'),
    'rename_authenticator' => __('Rename the authenticator', 'vincss-fido2-login'),
    'unavailable' => __(' (Unavailable)', 'vincss-fido2-login'),
    'disabled' => __(' (Disabled)', 'vincss-fido2-login'),
    'user_id' => getCurrentUserId(),
    'admin_id' => $user->ID,
));
wp_localize_script('vincss_fido2_login_pwl_profile', 'configs', array('usernameless' => (getOption('usernameless_login') === false ? "false" : getOption('usernameless_login'))));
?>
<br>
<?php
if (isEnvError()) {
?>
    <div id="v-pwl-error-container">
        <div class="notice notice-error" id="v-pwl-error">
            <p><?php _e('Passwordless cannot active. Please contact the site administrator.', 'vincss-fido2-login') ?></p>
        </div>
    </div>
<?php } else {
?>
    <h3 id="v-pwl-registered"><?php _e('Registered Passwordless', 'vincss-fido2-login'); ?></h3>
    <div class="vincss-secure-table">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'vincss-fido2-login'); ?></th>
                    <th><?php _e('Type', 'vincss-fido2-login'); ?></th>
                    <th class="username-less-th"><?php _e('Login without username', 'vincss-fido2-login'); ?></th>
                    <th><?php _e('Registered', 'vincss-fido2-login'); ?></th>

                    <th><?php _e('Action', 'vincss-fido2-login'); ?></th>
                </tr>
            </thead>
            <tbody id="v-pwl-list">
                <tr>
                    <td colspan="5"><?php _e('Loading...', 'vincss-fido2-login'); ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th><?php _e('Name', 'vincss-fido2-login'); ?></th>
                    <th><?php _e('Type', 'vincss-fido2-login'); ?></th>
                    <th class="username-less-th"><?php _e('Login without username', 'vincss-fido2-login'); ?></th>
                    <th><?php _e('Registered', 'vincss-fido2-login'); ?></th>

                    <th><?php _e('Action', 'vincss-fido2-login'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
    <p id="v_pwl_usernameless_tip"></p>
    <button id="v-pwl-reg-new-btn" class="button"><?php _e('Register New', 'vincss-fido2-login'); ?></button>

    <div id="vincss-secure-new-block">
        <button class="button button-small vincss-secure-cancel"><?php _e('Close'); ?></button>
        <h2><?php _e('Register New', 'vincss-fido2-login'); ?></h2>
        <p class="description"><?php printf(__('You can register multiple authenticators for account <strong>%s</strong>', 'vincss-fido2-login'), $user->user_login); ?></p>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="v-pwl-type"><?php _e('Type', 'vincss-fido2-login'); ?></label></th>
                <td>
                    <?php

                    ?>
                    <select name="v-pwl-type" id="v-pwl-type">
                        <!-- <option value="none" id="type-none" class="sub-type"><?php _e('Any', 'vincss-fido2-login'); ?></option> -->
                        <option value="platform" id="type-platform" class="sub-type"> <?php _e('Local Computer (e.g. built-in fingerprint sensors)', 'vincss-fido2-login'); ?></option>
                        <option value="cross-platform" id="type-cross-platform" class="sub-type"><?php _e('Remote Device (e.g. USB security keys)', 'vincss-fido2-login'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="v-pwl-authenticator-name"><?php _e('Authenticator name', 'vincss-fido2-login'); ?> <span class="fz-12 text-danger">*</span></label></th>
                <td>
                    <input name="v-pwl-authenticator-name" type="text" id="v-pwl-authenticator-name" class="regular-text">
                    <p class="description"><?php _e('An easily identifiable name for the authenticator. <strong>DOES NOT</strong> affect the authentication process in any way.', 'vincss-fido2-login'); ?></p>
                </td>
            </tr>
            <?php if (getOption('usernameless_login') === "true") { ?>
                <tr>
                    <th scope="row"><label for="vincss_fido2_login_authenticator_usernameless"><?php _e('Login without username', 'vincss-fido2-login'); ?></th>
                    <td>
                        <fieldset>
                            <label class="toggle-switch">
                                <input type="checkbox" class="vincss_fido2_login_authenticator_usernameless" name="vincss_fido2_login_authenticator_usernameless" value="true"> <span>
                                    <i></i>
                                </span>

                            </label>
                        </fieldset>
                        <p class="description"><?php _e('Some authenticators like U2F-only authenticators and some browsers DO NOT support this feature.', 'vincss-fido2-login'); ?></p>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <div class="d-flex align-items-center">
            <button id="v-pwl-btn-start-reg" type="button" class="button me-2"><?php _e('Start Registration', 'vincss-fido2-login'); ?></button><span id="v-pwl-show-progress"></span>
        </div>
    </div>
<?php } ?>