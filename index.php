<?php
/*
Plugin Name: VinCSS FIDO2 Login
Plugin URI: https://passwordless.vincss.net
Description: Passwordless Authentication.
Version: 1.0.2
Author: VinCSS LLC
Author URI: https://vincss.net
License: GPLv3
Text Domain: vincss-fido2-login
Domain Path: /languages
Copyright © 2021 VinCSS LLC
*/

if (!defined('ABSPATH')) : exit();
endif; // prevent public user to directly access your .php files through URL.


define('VFido2LoginPluginPath', plugin_dir_path(__FILE__));
define('VFido2LoginPluginURL', plugin_dir_url(__FILE__));

define('VFido2LoginSourcePath', plugin_dir_path(__FILE__) . 'src/');
define('VFido2LoginAssetsPath', plugins_url('/src/Assets/', __FILE__));

function loadTextDomain()
{
    load_plugin_textdomain('vincss-fido2-login', false, dirname(plugin_basename(__FILE__)) . '/languages');
    if ($GLOBALS["pagenow"] === "wp-login.php") {
        wp_enqueue_script('login_custom_js', VFido2LoginPluginURL . 'src/Assets/js/login-custom.js');
        wp_localize_script('login_custom_js', 'login_custom_vars', array(
            'label_or' => __('or', 'vincss-fido2-login')
        ));
    }
}
add_action('init', 'loadTextDomain');

require_once(VFido2LoginSourcePath . 'Init.php');
