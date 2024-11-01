<?php

namespace VinCSS\TwoFactor\Model;

class Login2FA
{
    const USER_META_NONCE_KEY             = 'vincss_fido2_login_nonce';
    const ENABLED_PROVIDERS_USER_META_KEY = 'enabled_methods';

    private static $password_auth_tokens = array();

    public static function isEnable2FA($user_id)
    {
        $is_enable_2fa = get_user_meta($user_id, 'vincss_enable_2fa', true);
        return $is_enable_2fa == 1;
    }



    public static function handleLogin2FA($user_login, $user)
    {
        $user_id = $user->ID;
        $is_oauth2_login = get_user_meta($user_id, 'is_oauth2_login', true);

        $is_enable_2fa = self::isEnable2FA($user_id);
        if ($is_enable_2fa) {
            if ($is_oauth2_login) {
                $is_oauth2_login = delete_user_meta($user_id, 'is_oauth2_login');
                return;
            }
            return self::startTwoFactorAuthenticatin($user);
        }
        return;
    }

    public static function getAuthCookie($cookie)
    {
        $parsed = wp_parse_auth_cookie($cookie);

        if (!empty($parsed['token'])) {
            self::$password_auth_tokens[] = $parsed['token'];
        }
    }


    public static function startTwoFactorAuthenticatin($user)
    {
        // Invalidate session
        $session_manager = \WP_Session_Tokens::get_instance($user->ID);

        foreach (self::$password_auth_tokens as $auth_token) {
            $session_manager->destroy($auth_token);
        }
        // clear cookie
        wp_clear_auth_cookie();
        self::startLogin2FA($user);
        exit;
    }

    public static function startLogin2FA($user)
    {
        if (!$user) {
            $user = wp_get_current_user();
        }
        $login_nonce = self::createLoginNonce($user->ID);
        if (!$login_nonce) {
            wp_die('Failed to create a login nonce.');
        }

        $redirect_to = isset($_REQUEST['redirect_to']) ? esc_url_raw(wp_unslash($_REQUEST['redirect_to'])) : admin_url();

        self::render2FAVerifyScreen($user, $login_nonce['key'], $redirect_to);
    }




    public static function createLoginNonce($user_id)
    {
        $login_nonce = array();
        try {
            $login_nonce['key'] = bin2hex(random_bytes(32));
        } catch (Exception $ex) {
            $login_nonce['key'] = wp_hash($user_id . mt_rand() . microtime(), 'nonce');
        }
        $login_nonce['expiration'] = time() + HOUR_IN_SECONDS;

        if (!update_user_meta($user_id, self::USER_META_NONCE_KEY, $login_nonce)) {
            return false;
        }

        return $login_nonce;
    }

    public static function getLoginURL($params = array(), $scheme = 'login')
    {
        if (!is_array($params)) {
            $params = array();
        }

        $params = urlencode_deep($params);

        return add_query_arg($params, site_url('wp-login.php', $scheme));
    }

    public static function rememberMe()
    {
        $rememberMe = false;

        if (!empty($_REQUEST['rememberMe'])) {
            $rememberMe = true;
        }

        return (bool) apply_filters('vincss_fido2_login_rememberme', $rememberMe);
    }


    public static function verifyLoginNonce($user_id, $nonce)
    {
        $login_nonce = get_user_meta($user_id, self::USER_META_NONCE_KEY, true);
        if (!$login_nonce) {
            return false;
        }

        if ($nonce !== $login_nonce['key'] || time() > $login_nonce['expiration']) {
            self::deleteLoginNonce($user_id);
            return false;
        }

        return true;
    }

    public static function deleteLoginNonce($user_id)
    {
        return delete_user_meta($user_id, self::USER_META_NONCE_KEY);
    }

    public static function validateOTP()
    {
        $user_id = sanitize_text_field($_POST['user_id']);
        $v_otp_input = sanitize_text_field(wp_unslash($_POST['v_otp_input']));
        $v_nonce = sanitize_text_field(wp_unslash($_POST['v_nonce']));
        $redirect_to = wp_unslash($_REQUEST['redirect_to']);

        if (!isset($user_id, $_POST['v_nonce'])) {
            return;
        }

        $auth_id = (int) $user_id;
        $user    = get_userdata($auth_id);
        if (!$user) {
            return;
        }

        $nonce = (isset($_POST['v_nonce'])) ? $v_nonce : '';
        if (true !== self::verifyLoginNonce($user->ID, $nonce)) {
            wp_safe_redirect(get_bloginfo('url'));
            exit;
        }
        $secret = get_user_meta($auth_id, 'vincss_2fa_secret', true);

        $google2FA = new Generate2FA();
        $is_valid  =  $google2FA->verifyOTP($secret, $v_otp_input, $user->ID);

        if (!$is_valid) {
            do_action('wp_login_failed', $user->user_login);

            $login_nonce = self::createLoginNonce($user->ID);
            if (!$login_nonce) {
                wp_die('Failed to create a login nonce.');
            }

            self::render2FAVerifyScreen($user, $login_nonce['key'], esc_url_raw($redirect_to), 'ERROR: Invalid two-factor code');
            exit;
        }
        self::deleteLoginNonce($user->ID);
        $rememberMe = false;
        $remember   = (isset($_REQUEST['rememberMe'])) ? filter_var($_REQUEST['rememberMe'], FILTER_VALIDATE_BOOLEAN) : '';
        if (!empty($remember)) {
            $rememberMe = true;
        }

        wp_set_auth_cookie($user->ID, $rememberMe);

        $redirect_to = apply_filters('login_redirect', esc_url_raw($redirect_to), esc_url_raw($redirect_to), $user);

        wp_safe_redirect($redirect_to);
        exit;
    }


    public static function render2FAVerifyScreen($user, $login_nonce, $redirect_to, $error_msg = '', $provider = null)
    {
        $interim_login = (isset($_REQUEST['interim-login'])) ? filter_var(wp_unslash($_REQUEST['interim-login']), FILTER_VALIDATE_BOOLEAN) : false;

        $rememberMe = intval(self::rememberMe());
        login_header();

        if (!empty($error_msg)) {
            echo '<div id="login_error"><strong>' . esc_html($error_msg) . '</strong><br /></div>';
        }
?>

        <form name="validate_2fa_form" id="loginform" action="<?php echo esc_url(self::getLoginURL(array('action' => 'validate_2fa'), 'login_post')); ?>" method="post" autocomplete="off">
            <input type="hidden" name="provider" id="provider" value="<?php echo esc_attr($provider); ?>" />
            <input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr($user->ID); ?>" />
            <input type="hidden" name="v_nonce" id="v_nonce" value="<?php echo esc_attr($login_nonce); ?>" />
            <?php if ($interim_login) { ?>
                <input type="hidden" name="interim-login" value="1" />
            <?php } else { ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />
            <?php } ?>
            <input type="hidden" name="rememberMe" id="rememberMe" value="<?php echo esc_attr($rememberMe); ?>" />

            <?php
            self::renderVerifyOTP($user);
            ?>
        </form>
        <p id="backtoblog">
            <a href="<?php echo esc_url(home_url('/wp-login.php')); ?>">
                <?php
                echo esc_html(
                    sprintf(
                        (__('&larr; Back to %s', 'vincss-fido2-login')),
                        get_bloginfo('title', 'display')
                    )
                );
                ?>
            </a>
        </p>
        </div>
        <?php
        do_action('login_footer');
        ?>
        <div class="clear"></div>
        </body>

        </html>
    <?php

    }


    public static function renderVerifyOTP($user)
    {
        $is_backup_code = getOption('is_backup_code');
        require_once ABSPATH . '/wp-admin/includes/template.php';
    ?>
        <p><?php _e('Enter the code from the two-factor app on your mobile device', 'vincss-fido2-login'); ?></p>
        <div style="margin-top: 8px">
            <label for="v_otp_input"><?php _e('Two-factor authentication code', 'vincss-fido2-login'); ?></label>
            <input name="v_otp_input" id="v_otp_input" class="input" value="" size="20" style="margin-bottom:2px" />
        </div>
        <?php if ($is_backup_code) {
            echo '<p style="font-size: 12px; margin-top: 2px;">';
            _e('If you\'ve lost your device, you may enter one of your recovery codes.', 'vincss-fido2-login');
            echo '</p>';
        } ?>
        <br />

        <script type="text/javascript">
            setTimeout(function() {
                try {
                    const input = document.querySelector('#v_otp_input');
                    input.value = '';
                    input.focus();

                    const thirdparty_login = document.querySelector('.thirdparty_login')
                    thirdparty_login.style.display = 'none';
                } catch (e) {}
            }, 200);
        </script>
<?php
        submit_button(__('Verify code', 'vincss-fido2-login'));
    }

    // public static function runAuthenticationCheck($user, $password)
    // {
    //     if (is_a($user, '\WP_Error')) {
    //         return $user;
    //     }
    //     return $user;
    // }



    public static function filterAuthenticate($user)
    {
        if ($user instanceof WP_User && self::isEnable2FA($user->ID)) {
            return new \WP_Error(
                'invalid_application_credentials',
                'Error: API login for user disabled.'
            );
        }
        return $user;
    }
}
