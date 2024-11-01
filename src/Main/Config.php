<?php

namespace VinCSS;

require(VFido2LoginSourcePath . 'AutoLoad.php');

use VinCSS\TwoFactor\Model\Login2FA;
use VinCSS\TwoFactor\Model\Profile2FA;
use VinCSS\TwoFactor\TwoFactor;
use VinCSS\OAuth2\LoginOAuth2;

class Config
{
    public function __construct()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('vincss_fido2_login_2fa_profile', VFido2LoginPluginURL . 'src/Assets/js/2fa/2fa.js');
        wp_localize_script('vincss_fido2_login_2fa_profile', 'variables_2fa', array(
            'ajaxURL'        => admin_url('admin-ajax.php')
        ));
    }

    public function init()
    {
        $this->two_factor = new TwoFactor();
        $this->profile_2fa = new Profile2FA();
        $this->login = new Login2FA();
        $this->oauth2 = new LoginOAuth2();

        $this->add_actions();
    }

    public function getSetupPageLink()
    {
        $link =  add_query_arg('action', 'vincss-setup-2fa', admin_url('profile.php'));
        return $link;
    }


    public function add_actions()
    {
        // profile 2fa
        add_action('show_user_profile', array($this->two_factor, 'renderProfilePage'));
        add_action('edit_user_profile', array($this->two_factor, 'renderProfilePage'));

        // profile passwordless
        if (!isEnvError()) {
            add_action('show_user_profile', array($this, 'renderWebAuthnProfile'));
        }
        add_action('edit_user_profile', array($this, 'renderWebAuthnProfile'));

        // save profile
        add_action('personal_options_update', array($this, 'onSaveProfile'));

        // 2fa
        add_action('wp_ajax_vincss_verify_2fa_otp', array($this->profile_2fa, 'verifyOTP'));
        add_action('wp_ajax_vincss_remove_2fa', array($this->profile_2fa, 'remove2FA'));
        add_action('wp_ajax_vincss_generate_code_recovery', array($this->profile_2fa, 'handleGenerateRecovery'));
        // check enforce 2fa
        if (is_admin()) {
            add_action("init", array($this,  'enforceTwoFactorSetup'), 10);
        }

        // Login.
        add_action('wp_login', array($this->login, 'handleLogin2FA'), 20, 2);
        add_action('login_form_validate_2fa', array($this->login, 'validateOTP'));
        // // Run only after the core wp_authenticate_username_password() check.
        add_filter('authenticate', array($this->login, 'filterAuthenticate'), 50);
        // add_filter('wp_authenticate_user', array($this->login, 'runAuthenticationCheck'), 10, 2);
        add_action('set_auth_cookie', array($this->login, 'getAuthCookie'));
        add_action('set_logged_in_cookie', array($this->login, 'getAuthCookie'));


        // oauth2

        if (getOption('v_enable_oauth2')) {
            add_action('login_form', array($this->oauth2, 'initOAuth2'));
            add_action('wp_ajax_nopriv_vincss_verify_user_oauth2', array($this->oauth2, 'verifyUserOAuth2'));
        }
    }

    public function redirectProfilePage()
    {
        global $pagenow;
        if (wp_doing_ajax() && isset($_REQUEST['action'])) {
            return;
        }

        $action = sanitize_text_field($_GET['action']);

        if ($pagenow === 'profile.php' && (isset($action) && $action === 'vincss-setup-2fa')) {
            return;
        }
        wp_redirect($this->getSetupPageLink());
        exit;
    }


    public function enforceTwoFactorSetup()
    {
        $user = wp_get_current_user();
        $is_enable_2fa = get_user_meta($user->ID, 'vincss_enable_2fa', true);

        $require_2fa = $this->two_factor->isRequireEnable2FA($user->roles);
        if ($require_2fa && !$is_enable_2fa) {
            $this->redirectProfilePage();
        }
        return;
    }

    public function renderWebAuthnProfile()
    {
        include(VFido2LoginSourcePath . 'Main/WebAuthn/Profile.php');
    }


    public function onSaveProfile($user_id)
    {

        $pwl_only = sanitize_text_field($_POST['passwordless_only']);
        $_wpnonce = sanitize_text_field($_POST['_wpnonce']);
        if (empty($_wpnonce) || !wp_verify_nonce($_wpnonce, 'update-user_' . $user_id)) {
            return;
        }

        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        if (getOption('login_method') === 'passwordless') {
            return;
        }

        if (!isset($pwl_only)) {
            update_user_meta($user_id, 'passwordless_only', 'false');
        } else if (sanitize_text_field($pwl_only) === 'true') {
            update_user_meta($user_id, 'passwordless_only', 'true');
        } else {
            update_user_meta($user_id, 'passwordless_only', 'false');
        }
    }
}
