<?php
register_activation_hook(__FILE__, 'initPlugin');

//check blog version
function initPlugin()
{
    if (version_compare(get_bloginfo('version'), '4.4', '<')) {
        deactivate_plugins(basename(__FILE__));
    }
}

function initPluginData()
{
    wp_enqueue_style('plugin_style', VFido2LoginPluginURL . 'src/Assets/css/style.css');
    wp_enqueue_style('plugin_style_dashboard', VFido2LoginPluginURL . 'src/Assets/css/dashboard.css');
    wp_enqueue_style('v_bootstrap_utilities', VFido2LoginPluginURL . 'src/Assets/css/bootstrap/bootstrap-utilities.min.css');
    wp_enqueue_style('bootstrap_modal', VFido2LoginPluginURL . 'src/Assets/css/bootstrap/bootstrap-modal.css');
    wp_enqueue_script('bootstrap_js', VFido2LoginPluginURL . 'src/Assets/js/bootstrap.min.js');


    include(VFido2LoginSourcePath . 'Version.php');
    if (!get_option('init_plugin')) {
        $vincss_fido2_login_init_options = array(
            'is_backup_code' => "false",
            'enforce_2fa' => "none",
            'role_enforce_2fa' => [],
            'oauth2_att_mapping' => "username",
            'user_credentials' => "{}",
            'user_credentials_meta' => "{}",
            'user_id' => array(),
            'login_method' => 'true',
            'remember_login' => 'false',
            'require_pin' => 'false',
            'usernameless_login' => 'false',
            'logging' => 'true',
            'v_oauth2_redirect_url' => site_url() . '/wp-admin',

        );
        update_option('vincss_fido2_login_options', $vincss_fido2_login_init_options);
        update_option('vincss_fido2_login_version', $vincss_fido2_login_version);
        update_option('vincss_fido2_login_log', array());
        update_option('init_plugin', md5(date('Y-m-d H:i:s')));
    } else {
        if (!get_option('vincss_fido2_login_version') || get_option('vincss_fido2_login_version')['version'] != $vincss_fido2_login_version['version']) {
            update_option('vincss_fido2_login_version', $vincss_fido2_login_version); //update version
        }
    }
}

function getOption($option_name)
{
    $val = get_option("vincss_fido2_login_options");
    if (isset($val[$option_name])) {
        return $val[$option_name];
    } else {
        return false;
    }
}

function updateOption($option_name, $option_value)
{
    $options = get_option("vincss_fido2_login_options");
    $options[$option_name] = $option_value;
    update_option('vincss_fido2_login_options', $options);
    return true;
}


function debug($value)
{
    if ($value) {
        ob_flush();
        ob_start();
        print_r($value);
        file_put_contents(VFido2LoginPluginPath . "app.log~", ob_get_flush());
    } else {
        file_put_contents(VFido2LoginPluginPath . "app.log~", 'no data');
    }
}


// include source
include(VFido2LoginSourcePath . 'Utils/Helper.php');
include VFido2LoginSourcePath . 'Menu.php';
require_once(__DIR__ . '/AutoLoad.php');
require_once(VFido2LoginPluginPath . 'vendor/autoload.php');


use VinCSS\Config;
use VinCSS\WebAuthn\RequestAuth;

initPluginData();
$profile = new Config();
$profile->init();
$request_pwl = new RequestAuth();
$request_pwl->initAction();
