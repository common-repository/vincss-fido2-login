<?php

function isEnvError()
{
    $error_config = false;
    if (phpversion() < 7.2 || !function_exists("gmp_intval") || get_bloginfo('version') < 5.0  || (!checkSSL() && (parse_url(site_url(), PHP_URL_HOST) !== "localhost" && parse_url(site_url(), PHP_URL_HOST) !== "127.0.0.1"))) {
        $error_config = true;
    }
    return $error_config;
}


function getNotice()
{
    $errors  = [];

    if (phpversion() < 7.2) {
        $errors[] = "PHP version is required 7.2 or higher";
    }
    if (!function_exists("gmp_intval")) {
        $errors[] = "PHP extension gmp doesn't seem to exist, rendering VinCSS FIDO2 Login unable to function.";
    }

    if (get_bloginfo('version') < 5.0) {
        $errors[] = "WordPress version needs to be greater than 5.0";
    }

    if (!function_exists("mb_substr")) {
        $errors[] = "PHP extension mbstring doesn't seem to exist, rendering VinCSS FIDO2 Login unable to function.";
    }
    if (!checkSSL() && (parse_url(site_url(), PHP_URL_HOST) !== "localhost" && parse_url(site_url(), PHP_URL_HOST) !== "127.0.0.1")) {
        $errors[] = "Passwordless features are restricted to websites in secure contexts. Please make sure your website is served over HTTPS or locally with <code>localhost</code>.";
    }
    return $errors;
}


function getCurrentUserId()
{
    $user_id = null;

    if (defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE) {
        $user_id = get_current_user_id();
    } elseif (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $user_id = intval(sanitize_text_field($_GET['user_id']));
    } else {
        die('No user id defined.');
    }

    return $user_id;
}

function addLog($id, $content = '', $init = false)
{

    $log = get_option('vincss_fido2_login_log');
    if ($log === false) {
        $log = array();
    }
    $log[] = '[' . date('Y-m-d H:i:s', current_time('timestamp')) . '][' . $id . '] ' . $content;
    update_option('vincss_fido2_login_log', $log);
}

function setTempValue($name, $value, $client_id)
{
    return set_transient('vincss_fido2_login_' . $name . $client_id, serialize($value), 90);
}

function getTempValue($name, $client_id)
{
    $val = get_transient('vincss_fido2_login_' . $name . $client_id);
    return $val === false ? false : unserialize($val);
}

function vincss_fido2_login_delete_temp_val($name, $client_id)
{
    return delete_transient('vincss_fido2_login_' . $name . $client_id);
}


function cancelAction($message = '', $client_id = false)
{
    if ($client_id !== false) {
        destroyTempVal($client_id);
    }
    wp_die($message);
}


function initOptions()
{
    if (getOption('remember_login') === false) {
        updateOption('remember_login', 'false');
    }
    if (getOption('usernameless_login') === false) {
        updateOption('usernameless_login', 'false');
    }
}


function destroyTempVal($client_id)
{
    vincss_fido2_login_delete_temp_val('user_name_auth', $client_id);
    vincss_fido2_login_delete_temp_val('user_auth', $client_id);
    vincss_fido2_login_delete_temp_val('pkcco', $client_id);
    vincss_fido2_login_delete_temp_val('bind_config', $client_id);
    vincss_fido2_login_delete_temp_val('pkcco_auth', $client_id);
    vincss_fido2_login_delete_temp_val('usernameless_auth', $client_id);
    vincss_fido2_login_delete_temp_val('auth_type', $client_id);
}

function randomStr($length = 10)
{
    if (function_exists('random_bytes')) {
        $bytes = random_bytes(round($length / 2));
        return bin2hex($bytes);
    } else {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
        $randomStr = '';
        for ($i = 0; $i < $length; $i++) {
            $randomStr .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomStr;
    }
}



function secureGenerateCallTrace($exception = false)
{
    $e = $exception;
    if ($exception === false) {
        $e = new Exception();
    }
    $trace = explode("\n", $e->getTraceAsString());
    $trace = array_reverse($trace);
    array_shift($trace);
    array_pop($trace);
    $length = count($trace);
    $result = array();

    for ($i = 0; $i < $length; $i++) {
        $result[] = ($i + 1) . ')' . substr($trace[$i], strpos($trace[$i], ' '));
    }

    return "Traceback:\n                              " . implode("\n                              ", $result);
}

function vincssSecureDeleteUser($user_id)
{
    $res_id = randomStr(5);

    $user_data = get_userdata($user_id);
    $all_user_meta = getOption("user_id");
    $user_key = "";
    addLog($res_id, "Delete user => \"" . $user_data->user_login . "\"");

    foreach ($all_user_meta as $user => $id) {
        if ($user === $user_data->user_login) {
            $user_key = $id;
            addLog($res_id, "Delete user_key => \"" . $id . "\"");
            unset($all_user_meta[$user]);
        }
    }

    $all_credentials_meta = json_decode(getOption("user_credentials_meta"), true);
    $all_credentials = json_decode(getOption("user_credentials"), true);
    foreach ($all_credentials_meta as $credential => $meta) {
        if ($user_key === $meta["user"]) {
            addLog($res_id, "Delete credential => \"" . $credential . "\"");
            unset($all_credentials_meta[$credential]);
            unset($all_credentials[$credential]);
        }
    }
    updateOption("user_id", $all_user_meta);
    updateOption("user_credentials_meta", json_encode($all_credentials_meta));
    updateOption("user_credentials", json_encode($all_credentials));
    addLog($res_id, "Done");
}
add_action('delete_user', 'vincssSecureDeleteUser');

function initScriptJS()
{
    $vincss_fido2_login_not_allowed = false;
    if (!function_exists("mb_substr") || !function_exists("gmp_intval") || !checkSSL() && (parse_url(site_url(), PHP_URL_HOST) !== 'localhost' && parse_url(site_url(), PHP_URL_HOST) !== '127.0.0.1')) {
        $vincss_fido2_login_not_allowed = true;
    }
    $login_method = getOption('login_method');

    wp_enqueue_script('jquery');
    wp_enqueue_script('vincss_fido2_login_login', VFido2LoginPluginURL . 'src/Assets/js/passwordless/login.js', array(), get_option('vincss_fido2_login_version')['version'], true);
    wp_localize_script('vincss_fido2_login_login', 'pwl_vars', array(
        'ajax_uri' => admin_url('admin-ajax.php'),
        'admin_url' => admin_url(),
        'login_method' => $login_method,
        'login' => __('Log In', 'vincss-fido2-login'),
        'title_auth_with_pwl' => __('Authenticate with Passwordless', 'vincss-fido2-login'),
        'hold_on' => __('Hold on...', 'vincss-fido2-login'),
        'retry' => __('Retry...', 'vincss-fido2-login'),
        'please_proceed' => __('Please proceed...', 'vincss-fido2-login'),
        'status_authenticated' => __('Login successful', 'vincss-fido2-login'),
        'error_label' => __('Error: ', 'vincss-fido2-login'),
        'status_failed' => __(
            'Auth failed',
            'vincss-fido2-login'
        ),
        'title_oauth2' => getOption('v_btn_oauth2_title'),
        'status_authenticating' => __('Authenticating...', 'vincss-fido2-login'),
        'passwordless_only' => ($login_method === 'passwordless' && !$vincss_fido2_login_not_allowed) ? 'true' : 'false',
        'username_empty' => __('The username field is empty.', 'vincss-fido2-login'),
        'status_try_enter_name' => 'Try to enter the username',
        'is_enable_oauth2' => getOption('v_enable_oauth2'),
        'remember_login' => (getOption('remember_login') === false ? 'false' : getOption('remember_login')),
        'toggle_pwl_login' => __('Login with passwordless', 'vincss-fido2-login'),
        'toggle_username_pass_login' => __('Login with username/password', 'vincss-fido2-login'),
        'label_username' => __('Username', 'vincss-fido2-login'),
        'usernameless' => (getOption('usernameless_login') === false ? 'false' : getOption('usernameless_login')),

    ));
    if ($login_method === 'true' || $login_method === 'passwordless') {
        wp_enqueue_script('vincss_fido2_login_default', VFido2LoginPluginURL . 'src/Assets/js/passwordless/default-login.js', array(), get_option('vincss_fido2_login_version')['version'], true);
        wp_localize_script('vincss_fido2_login_default', 'default_pwl_login_vars', array(
            'label_username' => __('Username', 'vincss-fido2-login'),
            'passwordless_only' => ($login_method === 'passwordless' && !$vincss_fido2_login_not_allowed) ? 'true' : 'false',
            'status_failed' => __(
                'Auth failed',
                'vincss-fido2-login'
            ),
            'not_support' => __(
                __('Browser not support passwordless', 'vincss-fido2-login')
            ),
            'remember_login' => (getOption('remember_login') === false ? 'false' : getOption('remember_login')),
            'username_empty' => __('The username field is empty.', 'vincss-fido2-login')
        ));
    }
    wp_enqueue_style('vincss_fido2_login_login_css', VFido2LoginPluginURL . 'src/Assets/css/login.css', array(), get_option('vincss_fido2_login_version')['version']);
}

if (!isEnvError()) {
    add_action('login_enqueue_scripts', 'initScriptJS', 999);
}


function noAuthenticatorWarning()
{
    $user_info = wp_get_current_user();
    $login_method = getOption('login_method');
    $check_self = true;
    if ($login_method !== 'passwordless' && get_the_author_meta('passwordless_only', $user_info->ID) !== 'true') {
        $check_self = false;
    }

    if ($check_self) {
        $user_id = '';
        $show_notice_flag = false;
        if (!isset(getOption('user_id')[$user_info->user_login])) {
            $show_notice_flag = true;
        } else {
            $user_id = getOption('user_id')[$user_info->user_login];
        }

        if (!$show_notice_flag) {
            $show_notice_flag = true;
            $data = json_decode(getOption('user_credentials_meta'), true);
            foreach ($data as $value) {
                if ($user_id === $value['user']) {
                    $show_notice_flag = false;
                    break;
                }
            }
        }

        if ($show_notice_flag) { ?>
            <div class="notice notice-warning">
                <p><?php printf(__('Logging in with password has been disabled for %s but you haven\'t register any Passwordless authenticator yet. You may unable to login again once you log out. <a href="%s#authn-start">Register</a>', 'vincss-fido2-login'), $login_method === 'passwordless' ? __('the site', 'vincss-fido2-login') : __('your account', 'vincss-fido2-login'), admin_url('profile.php')); ?></p>
            </div>
        <?php }
    }
    global $pagenow;
    if ($pagenow == 'user-edit.php' && isset($_GET['user_id']) && $_GET['user_id'] !== $user_info->ID) {
        $user_id_wp = intval($_GET['user_id']);
        if ($user_id_wp <= 0) {
            return;
        }
        if (!current_user_can('edit_user', $user_id_wp)) {
            return;
        }
        $user_info = get_user_by('id', $user_id_wp);

        if ($user_info === false) {
            return;
        }

        if ($login_method !== 'passwordless' && get_the_author_meta('passwordless_only', $user_info->ID) !== 'true') {
            return;
        }

        $user_id = '';
        $show_notice_flag = false;
        if (!isset(getOption('user_id')[$user_info->user_login])) {
            $show_notice_flag = true;
        } else {
            $user_id = getOption('user_id')[$user_info->user_login];
        }

        if (!$show_notice_flag) {
            $show_notice_flag = true;
            $data = json_decode(getOption('user_credentials_meta'), true);
            foreach ($data as $value) {
                if ($user_id === $value['user']) {
                    $show_notice_flag = false;
                    break;
                }
            }
        }

        if ($show_notice_flag) { ?>
            <div class="notice notice-warning">
                <p><?php printf(__('Logging in with password has been disabled for %s but <strong>this account</strong> haven\'t register any Passwordless authenticator yet. This user may unable to login.', 'vincss-fido2-login'), $login_method === 'passwordless' ? __('the site', 'vincss-fido2-login') : __('this account', 'vincss-fido2-login')); ?></p>
            </div>
<?php }
    }
}
add_action('admin_notices', 'noAuthenticatorWarning');





function checkSSL()
{
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }
    if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/3.0') {
        return true;
    }
    if (isset($_SERVER['REQUEST_SCHEME']) && ($_SERVER['REQUEST_SCHEME'] === 'quic' || $_SERVER['REQUEST_SCHEME'] === 'https')) {
        return true;
    }
    return false;
}


function oAuth2GetToken($endpoint, $code, $client_id, $client_secret, $redirect_uri)
{
    $body = [
        'code' => $code, // required
        'client_id' => $client_id, // required
        'client_secret' => $client_secret, // required
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code' // required
    ];
    // $response = callAPI("POST", $endpoint, $params);

    $response = wp_remote_post($endpoint, array(
        'body'    => $body,
        'headers' => array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ),
    ));
    $response = wp_remote_retrieve_body($response);
    return json_decode($response, true);
}

function oAuth2GetInfo($endpoint, $accessToken)
{
    $arg = [
        'headers' => array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded'
        )
    ];

    $response = wp_remote_get($endpoint . '?access_token=' . $accessToken, $arg);
    $response = wp_remote_retrieve_body($response);
    return json_decode($response, true);
}





function disablePassword($user)
{
    if (!function_exists("mb_substr") || !function_exists("gmp_intval") || !checkSSL() && (parse_url(site_url(), PHP_URL_HOST) !== 'localhost' && parse_url(site_url(), PHP_URL_HOST) !== '127.0.0.1')) {
        return $user;
    }
    if (getOption('login_method') === 'passwordless') {
        return new WP_Error('vincss_fido2_login_password_disabled', __('Logging in with password has been disabled by the site manager.', 'vincss-fido2-login'));
    }
    if (is_wp_error($user)) {
        return $user;
    }
    if (get_the_author_meta('passwordless_only', $user->ID) === 'true') {
        return new WP_Error('vincss_fido2_login_password_disabled_for_account', __('Logging in with password has been disabled for this account.', 'vincss-fido2-login'));
    }
    return $user;
}
add_filter('wp_authenticate_user', 'disablePassword', 10, 1);



function sanitizeArray($array)
{
    if (!isset($array)) return null;
    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            $value = sanitizeArray($value);
        } else {
            $value = sanitize_text_field($value);
        }
    }

    return $array;
}

?>