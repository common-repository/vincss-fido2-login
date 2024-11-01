    <?php
    $vincss_fido2_login_not_allowed = false;
    $res_id = randomStr(5);
    $v_pwl_ref_settings = sanitize_text_field($_POST['v_pwl_ref_settings']);
    $remember_login = sanitize_text_field($_POST['remember_login']);
    $require_pin = sanitize_text_field($_POST['require_pin']);
    $usernameless_login = sanitize_text_field($_POST['usernameless_login']);
    $login_method = sanitize_text_field($_POST['login_method']);
    // Only admin can change settings
    if ((isset($v_pwl_ref_settings) && $v_pwl_ref_settings === 'true') && check_admin_referer('v_pwl_options_update') && current_user_can('manage_options')) {

        if (!isset($remember_login) || !$remember_login) {
            $remember_login = 'false';
        }
        if (!isset($require_pin) || !$require_pin) {
            $require_pin = 'false';
        }
        if (!isset($usernameless_login) || !$usernameless_login) {
            $usernameless_login = 'false';
        }

        $post_require_pin = sanitize_text_field($require_pin);
        if ($post_require_pin !== getOption('require_pin')) {
            addLog($res_id, "require_pin: \"" . getOption('require_pin') . "\"->\"" . $post_require_pin . "\"");
        }
        updateOption('require_pin', $post_require_pin);

        $post_remember_login = sanitize_text_field($remember_login);
        if ($post_remember_login !== getOption('remember_login')) {
            addLog($res_id, "Keep users logged in	: \"" . getOption('remember_login') . "\"->\"" . $post_remember_login . "\"");
        }
        updateOption('remember_login', $post_remember_login);

        $post_usernameless_login = sanitize_text_field($usernameless_login);
        if ($post_usernameless_login !== getOption('usernameless_login')) {
            addLog($res_id, "usernameless_login: \"" . getOption('usernameless_login') . "\"->\"" . $post_usernameless_login . "\"");
        }
        updateOption('usernameless_login', $post_usernameless_login);

        $post_login_method = sanitize_text_field($login_method);
        if ($post_login_method !== getOption('login_method')) {
            addLog($res_id, "login_method: \"" . getOption('login_method') . "\"->\"" . $post_login_method . "\"");
        }
        updateOption('login_method', $post_login_method);

        add_settings_error("vincss_fido2_login_settings", "save_success", __("Settings saved.", 'vincss-fido2-login'), "success");
    } elseif ((isset($v_pwl_ref_settings) && $v_pwl_ref_settings === 'true')) {
        add_settings_error("vincss_fido2_login_settings", "save_error", __("Settings NOT saved.", 'vincss-fido2-login'));
    }

    $error_config = isEnvError();
    if ($error_config) {
        updateOption('login_method', 'false');
        $vincss_fido2_login_not_allowed = true;
    }

    wp_localize_script('vincss_passwordless_admin', 'configs', array('usernameless' => (getOption('usernameless_login') === false ? "false" : getOption('usernameless_login'))));


    $remember_login = getOption('remember_login');
    if (!$remember_login) {
        $remember_login = 'false';
    }

    $v_authn_require_pin = getOption('require_pin');
    if ($v_authn_require_pin === false) {
        $v_authn_require_pin = 'false';
    }

    $allow_without_username = getOption('usernameless_login');
    if ($allow_without_username === false) {
        $allow_without_username = 'false';
    }

    $errors = getNotice();
    foreach ($errors as $error) {
        add_settings_error("vincss_fido2_login_settings", "save_error", __($error, 'vincss-fido2-login'));
    }
    ?>

    <form method="post" action="">
        <h1 class="v-title"><?php _e('Passwordless', 'vincss-fido2-login'); ?></h1>
        <div class="vcss_notice mt-4">
            <?php settings_errors("vincss_fido2_login_settings"); ?>
        </div>
        <?php
        wp_nonce_field('v_pwl_options_update');
        ?>
        <input type='hidden' name='v_pwl_ref_settings' value='true'>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="login_method"><?php _e('Default login method', 'vincss-fido2-login'); ?></label></th>
                <td>
                    <?php $vincss_fido2_login_v_login_method = getOption('login_method'); ?>
                    <select name="login_method" id="login_method">
                        <option value="true" <?php if ($vincss_fido2_login_not_allowed) { ?> disabled<?php } ?> <?php if ($vincss_fido2_login_v_login_method === 'true' && !$vincss_fido2_login_not_allowed) { ?> selected<?php } ?>><?php _e('Prefer Passwordless', 'vincss-fido2-login'); ?></option>
                        <option value="false" <?php if ($vincss_fido2_login_v_login_method === 'false') { ?> selected<?php } ?>><?php _e('Prefer Password', 'vincss-fido2-login'); ?></option>
                        <!-- <option value="passwordless" <?php if ($vincss_fido2_login_v_login_method === 'passwordless' && !$vincss_fido2_login_not_allowed) { ?> selected<?php }
                                                                                                                                                                    if ($vincss_fido2_login_not_allowed) { ?> disabled<?php } ?>><?php _e('Passwordless Only', 'vincss-fido2-login'); ?>
                        </option> -->
                    </select>

                    <p class="description" id="des_passwordless_only"><?php _e('When using "Passwordless Only", password login will be completely disabled. Please make sure your browser supports Passwordless.', 'vincss-fido2-login'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="remember_login"><?php _e('Keep users logged in', 'vincss-fido2-login'); ?></label></th>
                <td>

                    <fieldset>
                        <label class="toggle-switch">
                            <input type="checkbox" id="remember_login" name="remember_login" value="true" <?php echo  $remember_login === 'true' ? 'checked' : '' ?>> <span>
                                <i></i>
                            </span>
                        </label>
                        <p class="description"><?php _e('Show \'Remember me\' on WordPress Login page.', 'vincss-fido2-login'); ?></p`>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="require_pin"><?php _e(' User verification', 'vincss-fido2-login'); ?></label></th>
                <td>
                    <fieldset>
                        <div class="d-flex align-items-center">
                            <label class="toggle-switch">
                                <input type="checkbox" id="remember_login" name="require_pin" value="true" <?php echo  $v_authn_require_pin === 'true' ? 'checked' : '' ?>> <span>
                                    <i></i>
                                </span>
                            </label>
                        </div>
                        <p class="description"><?php _e('Users need to be verified before using their keys.
', 'vincss-fido2-login'); ?></p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="usernameless_login"><?php _e('Usernameless', 'vincss-fido2-login'); ?></label></th>
                <td>
                    <fieldset>
                        <label class="toggle-switch">
                            <input type="checkbox" id="remember_login" name="usernameless_login" value="true" <?php echo  $allow_without_username === 'true' ? 'checked' : '' ?>> <span>
                                <i></i>
                            </span>
                        </label>
                        <p class="description"><?php _e('User does not need to enter a username to login.', 'vincss-fido2-login'); ?></p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="user_config"><?php _e('Register security key', 'vincss-fido2-login'); ?></label></th>
                <td>
                    <a href=" <?php echo admin_url('profile.php?#v-pwl-registered'); ?>" class="button button-primary"><?php _e('Register', 'vincss-fido2-login'); ?></a>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    <?php
