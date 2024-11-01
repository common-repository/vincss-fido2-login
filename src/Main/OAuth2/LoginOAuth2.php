<?php

namespace VinCSS\OAuth2;

class LoginOAuth2
{
    public static function initOAuth2()
    {
        $client_id = getOption('v_oauth_client_id');
        $client_secret = getOption('v_oauth_client_secret');
        $authorize_url = getOption('v_oauth_authorize_url');
        $redirect_uri = get_site_url() . '/wp-login.php';
        wp_enqueue_script('vincss_fido2_login_oauth2', VFido2LoginPluginURL . 'src/Assets/js/oauth2/oauth2.js', 1);
        wp_localize_script('vincss_fido2_login_oauth2', 'variables_oauth2', array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'authorize_url' => $authorize_url,
            'ajaxURL'        => admin_url('admin-ajax.php'),
            '_wpnonce' => wp_create_nonce('vincss_login_oauth2'),
            'redirect_uri' => $redirect_uri,
            'title_login_other' => 'or',
            'title_oauth2' => getOption('v_btn_oauth2_title'),
            'is_enable_oauth2' => getOption('v_enable_oauth2')
        ));
    }

    public static function verifyUserOAuth2()
    {
        check_ajax_referer('vincss_login_oauth2');
        $code = sanitize_text_field(wp_unslash($_POST['code']));
        if (!$code) {
            wp_send_json_error(
                array('error' => "Error!"),
                400
            );
            return;
        }


        $client_id = getOption('v_oauth_client_id');
        $client_secret = getOption('v_oauth_client_secret');
        $access_token_url = getOption('v_oauth_access_token_url');
        $user_info_url = getOption('v_oauth_user_info_url');
        $redirect_uri = get_site_url() . '/wp-login.php';

        $res_token_decode =  oAuth2GetToken($access_token_url, $code, $client_id, $client_secret, $redirect_uri);

        if ($res_token_decode['error']) {
            wp_send_json_error(
                array('error' => 'Cannot get token'),
                400
            );
        };

        $res_user_decode = oAuth2GetInfo($user_info_url, $res_token_decode['access_token']);
        if ($res_user_decode['status'] === "failed") {
            wp_send_json_error(
                array('error' => 'Get profile failed!'),
                400
            );
        };

        $field_mapping = getOption('oauth2_att_mapping');

        if (isset($res_user_decode[$field_mapping])) {
            $username = $res_user_decode[$field_mapping];
            $user = get_user_by('login', $username);
            if ($user) {

                wp_clear_auth_cookie();
                wp_set_auth_cookie($user->ID);


                update_user_meta($user->ID, 'vincss_is_oauth2_login', true);
                $redirect_to = getOption('v_oauth2_redirect_url');

                if (!$redirect_to) {
                    $redirect_to = home_url();
                }

                delete_user_meta($user->ID, 'vincss_is_oauth2_login');

                wp_send_json_success(
                    array(
                        'redirect_to' => $redirect_to,
                    ),
                    200
                );
                // wp_redirect($redirect_to);
                // exit();
            } else {
                wp_send_json_error(
                    array('error' => 'User not existed!'),
                    403
                );
            }
        } else {
            wp_send_json_error(
                array('error' => 'Mapping field is invalid!'),
                400
            );
        }
    }
}
