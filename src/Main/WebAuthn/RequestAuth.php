<?php

namespace VinCSS\WebAuthn;

use VinCSS\WebAuthn\Credential;
use Webauthn\Server;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7\Factory\Psr17Factory;
use stdClass;

class RequestAuth
{
    public function  __construct()
    {
    }


    public function initAction()
    {
        add_action('wp_ajax_v_pwl_create_request', array($this, 'createRequest'));
        add_action('wp_ajax_v_pwl_create_request_response', array($this, 'createResponseAuth'));
        add_action('wp_ajax_v_pwl_start_verify', array($this, 'startVerify'));
        add_action('wp_ajax_nopriv_v_pwl_start_verify', array($this, 'startVerify'));
        add_action('wp_ajax_v_pwl_check_authen', array($this, 'handleAuthentication'));
        add_action('wp_ajax_nopriv_v_pwl_check_authen', array($this, 'handleAuthentication'));
        add_action('wp_ajax_v_pwl_get_list_authenticator', array($this, 'getListAuthentication'));
        add_action('wp_ajax_v_pwl_update_authenticator', array($this, 'updateAuthenticator'));
        add_action('wp_ajax_v_pwl_get_logs', array($this, 'getLogs'));
        add_action('wp_ajax_v_pwl_clear_logs', array($this, 'clearLogs'));
        add_action('wp_ajax_v_pwl_check_environment', array($this, 'checkEnvRequired'));
        add_action('wp_ajax_v_pwl_update_settings', array($this, 'updateSettings'));
    }


    public static function checkEnvRequired()
    {
        check_ajax_referer('v_pwl_check_environment');

        $errors = getNotice();
        if (!empty($errors)) {
            wp_send_json_error(
                array('error' => $errors),
                400
            );
        }
        wp_send_json_success();
    }


    public static function createRequest()
    {
        try {
            $client_id = strval(time()) . randomStr(24);
            $res_nonce = randomStr(5);

            $name = sanitize_text_field($_GET["name"]);
            $usernameless = sanitize_text_field($_GET['usernameless']);
            $user_id = intval(sanitize_text_field($_GET["user_id"]));
            $type = sanitize_text_field($_GET["type"]);

            initOptions();

            addLog($res_nonce, "Start request");

            if (!current_user_can("read")) {
                addLog($res_nonce, "(ERROR) User not permission, Cannot start request");
                cancelAction("Something went wrong.", $client_id);
            }

            if (get_bloginfo('name') === "" || parse_url(site_url(), PHP_URL_HOST) === NULL) {
                addLog($res_nonce, "(ERROR) Site not config, exit");
                cancelAction("Site not config.", $client_id);
            }

            if (!isset($name) || !isset($usernameless)) {
                addLog($res_nonce, "(ERROR) Missing parameters, exit");
                cancelAction("Bad Request.", $client_id);
            } else {
                $payload = array();
                $payload["name"] = $name;
                $payload["type"] = $type;
                $payload["usernameless"] = $usernameless;
                addLog($res_nonce, "name => \"" . $payload["name"] . "\", type => \"" . $payload["type"] . "\", usernameless => \"" . $payload["usernameless"] . "\"");
            }

            $user_info = wp_get_current_user();

            if (isset($user_id)) {
                if ($user_id <= 0) {
                    addLog($res_nonce, "(ERROR) Wrong parameters, exit");
                    cancelAction("Bad Request.");
                }

                if ($user_info->ID !== $user_id) {
                    if (!current_user_can("edit_user", $user_id)) {
                        addLog($res_nonce, "(ERROR) No permission, exit");
                        cancelAction("Something went wrong.");
                    }
                    $user_info = get_user_by('id', $user_id);

                    if ($user_info === false) {
                        addLog($res_nonce, "(ERROR) Wrong user ID, exit");
                        cancelAction("Something went wrong.");
                    }
                }
            }

            if ($payload["name"] === "") {
                addLog($res_nonce, "(ERROR) Empty name, exit");
                cancelAction("Bad Request.", $client_id);
            }

            if ($payload["usernameless"] === "true" && getOption("usernameless_login") !== "true") {
                addLog($res_nonce, "(ERROR) Login without username authentication not allowed, exit");
                cancelAction("Bad Request.", $client_id);
            }



            $rpEntity = new PublicKeyCredentialRpEntity(
                get_bloginfo('name'),
                parse_url(site_url(), PHP_URL_HOST)
            );
            $publicKeyCredentialSourceRepository = new Credential();

            $server = new Server(
                $rpEntity,
                $publicKeyCredentialSourceRepository,
                null
            );

            addLog($res_nonce, "user => \"" . $user_info->user_login . "\"");

            $user_key = "";
            if (!isset(getOption("user_id")[$user_info->user_login])) {
                addLog($res_nonce, "User not initialized, initialize");
                $user_array = getOption("user_id");
                $user_key = hash("sha256", $user_info->user_login . "-" . $user_info->display_name . "-" . randomStr(10));
                $user_array[$user_info->user_login] = $user_key;
                updateOption("user_id", $user_array);
            } else {
                $user_key = getOption("user_id")[$user_info->user_login];
            }

            $user = array(
                "login" => $user_info->user_login,
                "id" => $user_key,
                "display" => $user_info->display_name,
                "icon" => get_avatar_url($user_info->user_email, array("scheme" => "https"))
            );

            $userEntity = new PublicKeyCredentialUserEntity(
                $user["login"],
                $user["id"],
                $user["display"],
                $user["icon"]
            );

            $credentialSourceRepository = new Credential();

            $credentialSources = $credentialSourceRepository->findAllForUserEntity($userEntity);

            // Convert the Credential Sources into Public Key Credential Descriptors for excluding
            $excludeCredentials = array_map(function (PublicKeyCredentialSource $credential) {
                return $credential->getPublicKeyCredentialDescriptor();
            }, $credentialSources);

            addLog($res_nonce, "excludeCredentials => " . json_encode($excludeCredentials));

            if (getOption("require_pin") === "true") {
                addLog($res_nonce, "require_pin => \"true\"");
                $require_pin = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
            } else {
                addLog($res_nonce, "require_pin => \"false\"");
                $require_pin = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED;
            }

            if ($payload["type"] === "platform") {
                $authenticator_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM;
            } else if ($payload["type"] === "cross-platform") {
                $authenticator_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM;
            } else {
                $authenticator_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE;
            }


            $resident_key = false;
            if ($payload["usernameless"] === "true") {
                addLog($res_nonce, "Login without username set, require_pin => \"true\"");
                $require_pin = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
                $resident_key = true;
            }

            $authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria(
                $authenticator_type,
                $resident_key,
                $require_pin
            );

            $publicKeyCredentialCreationOptions = $server->generatePublicKeyCredentialCreationOptions(
                $userEntity,
                PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
                $excludeCredentials,
                $authenticatorSelectionCriteria
            );

            setTempValue("pkcco", base64_encode(serialize($publicKeyCredentialCreationOptions)), $client_id);
            setTempValue("bind_config", array("name" => $payload["name"], "type" => $payload["type"], "usernameless" => $resident_key), $client_id);

            header("Content-Type: application/json");
            $publicKeyCredentialCreationOptions = json_decode(json_encode($publicKeyCredentialCreationOptions), true);
            $publicKeyCredentialCreationOptions["clientID"] = $client_id;
            echo json_encode($publicKeyCredentialCreationOptions);
            addLog($res_nonce, "Challenge sent");
            exit;
        } catch (\Exception $exception) {
            addLog($res_nonce, "(ERROR) " . $exception->getMessage());
            addLog($res_nonce, secureGenerateCallTrace($exception));
            addLog($res_nonce, "(ERROR) Unknown error, exit");
            cancelAction("Something went wrong.", $client_id);
        } catch (\Error $error) {
            addLog($res_nonce, "(ERROR) " . $error->getMessage());
            addLog($res_nonce, secureGenerateCallTrace($error));
            addLog($res_nonce, "(ERROR) Unknown error, exit");
            cancelAction("Something went wrong.", $client_id);
        }
    }
    public static function startVerify()
    {
        try {
            $res_nonce = randomStr(5);
            $client_id = strval(time()) . randomStr(24);

            initOptions();

            addLog($res_nonce, "ajax_auth: Start verify");
            $type = sanitize_text_field($_GET["type"]);
            $user = sanitize_text_field($_GET["user"]);
            $usernameless = sanitize_text_field($_GET["usernameless"]);
            if (!isset($type)) {
                addLog($res_nonce, "ajax_auth: (ERROR) Missing parameters, exit");
                cancelAction("Bad Request.", $client_id);
            } else {
                $payload = array();
                $payload["type"] = $type;
                if (isset($user)) {
                    $payload["user"] = $user;
                }
                if (isset($usernameless)) {
                    $payload["usernameless"] = $usernameless;
                    if ($payload["usernameless"] === "true" && getOption("usernameless_login") !== "true") {
                        addLog($res_nonce, "ajax_auth: (ERROR) Login without username authentication not allowed, exit");
                        cancelAction("Bad Request.", $client_id);
                    }
                }
            }

            $user_key = "";
            $is_login_usernameless = false;
            $user_icon = null;

            if (isset($payload["user"]) && $payload["user"] !== "") {
                if (get_user_by('login', $payload["user"])) {
                    $user_info = get_user_by('login', $payload["user"]);
                    $user_icon = get_avatar_url($user_info->user_email, array("scheme" => "https"));
                    addLog($res_nonce, "ajax_auth: type => \"auth\", user => \"" . $user_info->user_login . "\"");
                    if (!isset(getOption("user_id")[$user_info->user_login])) {
                        addLog($res_nonce, "ajax_auth: User not initialized, initialize");
                        $user_key = hash("sha256", $payload["user"] . "-" . $payload["user"] . "-" . randomStr(10));
                    } else {
                        $user_key = getOption("user_id")[$user_info->user_login];
                    }
                } else {
                    addLog($res_nonce, "ajax_auth: type => \"auth\", user => \"" . $payload["user"] . "\"");
                    addLog($res_nonce, "ajax_auth: User not exists");
                    cancelAction("User not existed");
                }
            } else {
                if (getOption("usernameless_login") === "true") {
                    $is_login_usernameless = true;
                    addLog($res_nonce, "ajax_auth: Empty username, try usernameless authentication");
                } else {
                    addLog($res_nonce, "ajax_auth: (ERROR) Missing parameters, exit");
                    cancelAction("Bad Request.", $client_id);
                }
            }

            if (!$is_login_usernameless) {
                $userEntity = new PublicKeyCredentialUserEntity(
                    $user_info->user_login,
                    $user_key,
                    $user_info->display_name,
                    $user_icon
                );
            }

            $credentialSourceRepository = new Credential();
            $rpEntity = new PublicKeyCredentialRpEntity(
                get_bloginfo('name'),
                parse_url(site_url(), PHP_URL_HOST)
            );

            $server = new Server(
                $rpEntity,
                $credentialSourceRepository,
                null
            );

            if ($is_login_usernameless) {
                addLog($res_nonce, "ajax_auth: Login without username authentication");
                $allowedCredentials = array();
            } else {
                $credentialSources = $credentialSourceRepository->findAllForUserEntity($userEntity);
                $allowedCredentials = array_map(function (PublicKeyCredentialSource $credential) {
                    return $credential->getPublicKeyCredentialDescriptor();
                }, $credentialSources);;
            }

            if (getOption("require_pin") === "true") {
                addLog($res_nonce, "ajax_auth: require_pin => \"true\"");
                $require_pin = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
            } else {
                addLog($res_nonce, "ajax_auth: require_pin => \"false\"");
                $require_pin = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED;
            }

            if ($is_login_usernameless) {
                addLog($res_nonce, "ajax_auth: Login without username authentication");
                $require_pin = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
            }

            $publicKeyCredentialRequestOptions = $server->generatePublicKeyCredentialRequestOptions(
                $require_pin,
                $allowedCredentials
            );

            setTempValue("pkcco_auth", base64_encode(serialize($publicKeyCredentialRequestOptions)), $client_id);
            setTempValue("auth_type", $payload["type"], $client_id);
            if (!$is_login_usernameless) {
                setTempValue("user_name_auth", $user_info->user_login, $client_id);
            }
            setTempValue("usernameless_auth", serialize($is_login_usernameless), $client_id);

            if (!current_user_can("read") && !$is_login_usernameless) {
                setTempValue("user_auth", serialize($userEntity), $client_id);
            }

            header("Content-Type: application/json");
            $publicKeyCredentialRequestOptions = json_decode(json_encode($publicKeyCredentialRequestOptions), true);
            $publicKeyCredentialRequestOptions["clientID"] = $client_id;
            echo json_encode($publicKeyCredentialRequestOptions);
            addLog($res_nonce, "ajax_auth: Challenge sent");
            exit;
        } catch (\Exception $exception) {
            addLog($res_nonce, "ajax_auth: (ERROR) " . $exception->getMessage());
            addLog($res_nonce, secureGenerateCallTrace($exception));
            addLog($res_nonce, "ajax_auth: (ERROR) Unknown error, exit");
            cancelAction("Something went wrong.", $client_id);
        } catch (\Error $error) {
            addLog($res_nonce, "ajax_auth: (ERROR) " . $error->getMessage());
            addLog($res_nonce, secureGenerateCallTrace($error));
            addLog($res_nonce, "ajax_auth: (ERROR) Unknown error, exit");
            cancelAction("Something went wrong.", $client_id);
        }
    }

    public static function createResponseAuth()
    {
        $client_id = false;
        try {
            $res_nonce = randomStr(5);

            initOptions();

            addLog($res_nonce, "create_response: Client response received");

            if (!isset($_POST["clientid"])) {
                addLog($res_nonce, "create_response: (ERROR) Missing parameters, exit");
                wp_die("Bad Request.");
            } else {
                if (strlen($_POST["clientid"]) < 34 || strlen($_POST["clientid"]) > 35) {
                    addLog($res_nonce, "create_response: (ERROR) Wrong client ID, exit");
                    cancelAction("Bad Request.", $client_id);
                }
                // Sanitize the input
                $client_id = sanitize_text_field($_POST["clientid"]);
            }

            if (!current_user_can("read")) {
                addLog($res_nonce, "create_response: (ERROR) Permission denied, exit");
                cancelAction("Something went wrong.", $client_id);
            }

            if (!isset($_POST["data"]) || !isset($_POST["name"]) || !isset($_POST["type"]) || !isset($_POST["usernameless"])) {
                addLog($res_nonce, "create_response: (ERROR) Missing parameters, exit");
                cancelAction("Bad Request.", $client_id);
            } else {
                $vincss_fido2_login_post = array();
                $vincss_fido2_login_post["name"] = sanitize_text_field($_POST["name"]);
                $vincss_fido2_login_post["type"] = sanitize_text_field($_POST["type"]);
                $vincss_fido2_login_post["usernameless"] = sanitize_text_field($_POST["usernameless"]);
                addLog($res_nonce, "create_response: name => \"" . $vincss_fido2_login_post["name"] . "\", type => \"" . $vincss_fido2_login_post["type"] . "\", usernameless => \"" . $vincss_fido2_login_post["usernameless"] . "\"");
            }

            if (isset($_GET["user_id"])) {
                $user_id = intval(sanitize_text_field($_POST["user_id"]));
                if ($user_id <= 0) {
                    addLog($res_nonce, "create_response: (ERROR) Wrong parameters, exit");
                    cancelAction("Bad Request.");
                }

                if (wp_get_current_user()->ID !== $user_id) {
                    if (!current_user_can("edit_user", $user_id)) {
                        addLog($res_nonce, "create_response: (ERROR) No permission, exit");
                        cancelAction("Something went wrong.");
                    }
                }
            }

            $temp_val = array(
                "pkcco" => getTempValue("pkcco", $client_id),
                "bind_config" => getTempValue("bind_config", $client_id)
            );

            if ($temp_val["bind_config"]["type"] !== $vincss_fido2_login_post["type"] || $temp_val["bind_config"]["name"] !== $vincss_fido2_login_post["name"]) {
                addLog($res_nonce, "create_response: (ERROR) Wrong parameters, exit");
                cancelAction("Bad Request.", $client_id);
            }

            if ($temp_val["bind_config"]["type"] !== "platform" && $temp_val["bind_config"]["type"] !== "cross-platform" && $temp_val["bind_config"]["type"] !== "none") {
                addLog($res_nonce, "create_response: (ERROR) Wrong type, exit");
                cancelAction("Bad request.", $client_id);
            }

            if ($temp_val["pkcco"] === false || $temp_val["bind_config"] === false) {
                addLog($res_nonce, "create_response: (ERROR) Challenge not found in transient, exit");
                cancelAction("Bad request.", $client_id);
            }


            $credential_id = base64_decode(json_decode(base64_decode($_POST["data"]), true)["rawId"]);
            $publicKeyCredentialSourceRepository = new Credential();
            if ($publicKeyCredentialSourceRepository->findOneMetaByCredentialId($credential_id) !== null) {
                addLog($res_nonce, "create_response: (ERROR) Credential ID not unique, ID => \"" . base64_encode($credential_id) . "\" , exit");
                cancelAction("Something went wrong.", $client_id);
            } else {
                addLog($res_nonce, "create_response: Credential ID unique check passed");
            }

            $psr17Factory = new Psr17Factory();
            $creator = new ServerRequestCreator(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory
            );

            $serverRequest = $creator->fromGlobals();

            $rpEntity = new PublicKeyCredentialRpEntity(
                get_bloginfo('name'),
                parse_url(site_url(), PHP_URL_HOST)
            );

            $server = new Server(
                $rpEntity,
                $publicKeyCredentialSourceRepository,
                null
            );

            $current_domain = parse_url(site_url(), PHP_URL_HOST);
            if ($current_domain === "localhost" || $current_domain === "127.0.0.1") {
                $server->setSecuredRelyingPartyId([$current_domain]);
                addLog($res_nonce, "create_response: Localhost, bypass HTTPS check");
            }

            try {
                $publicKeyCredentialSource = $server->loadAndCheckAttestationResponse(
                    base64_decode($_POST["data"]),
                    unserialize(base64_decode($temp_val["pkcco"])),
                    $serverRequest
                );

                addLog($res_nonce, "create_response: Challenge verified");

                $publicKeyCredentialSourceRepository->saveCredentialSource($publicKeyCredentialSource, $temp_val["bind_config"]["usernameless"]);

                if ($temp_val["bind_config"]["usernameless"]) {
                    addLog($res_nonce, "create_response: Authenticator added with usernameless authentication feature");
                } else {
                    addLog($res_nonce, "create_response: Authenticator added");
                }

                echo "true";
            } catch (\Throwable $exception) {
                addLog($res_nonce, "create_response: (ERROR) " . $exception->getMessage());
                addLog($res_nonce, secureGenerateCallTrace($exception));
                addLog($res_nonce, "create_response: (ERROR) Challenge not verified, exit");
                cancelAction("Something went wrong.", $client_id);
            }

            destroyTempVal($client_id);
            exit;
        } catch (\Exception $exception) {
            addLog($res_nonce, "create_response: (ERROR) " . $exception->getMessage());
            addLog($res_nonce, secureGenerateCallTrace($exception));
            addLog($res_nonce, "create_response: (ERROR) Unknown error, exit");
            cancelAction("Something went wrong.", $client_id);
        } catch (\Error $error) {
            addLog($res_nonce, "create_response: (ERROR) " . $error->getMessage());
            addLog($res_nonce, secureGenerateCallTrace($error));
            addLog($res_nonce, "create_response: (ERROR) Unknown error, exit");
            cancelAction("Something went wrong.", $client_id);
        }
    }


    public static function getListAuthentication()
    {
        $res_nonce = randomStr(5);
        initOptions();

        if (!current_user_can("read")) {
            addLog($res_nonce, "axjax_action_authenticator_list: (ERROR) Missing parameters, exit");
            cancelAction("Something went wrong.");
        }

        if (isset($_GET["user_id"])) {
            $user_id = intval(sanitize_text_field($_GET["user_id"]));
            if ($user_id <= 0) {
                addLog($res_nonce, "axjax_action_authenticator_list: (ERROR) Wrong parameters, exit");
                cancelAction("Bad Request.");
            }

            if ($user_info->ID !== $user_id) {
                if (!current_user_can("edit_user", $user_id)) {
                    addLog($res_nonce, "axjax_action_authenticator_list: (ERROR) No permission, exit");
                    cancelAction("Something went wrong.");
                }
                $user_info = get_user_by('id', $user_id);

                if ($user_info === false) {
                    addLog($res_nonce, "axjax_action_authenticator_list: (ERROR) Wrong user ID, exit");
                    cancelAction("Something went wrong.");
                }
            }
        }

        header('Content-Type: application/json');

        $user_key = "";
        if (!isset(getOption("user_id")[$user_info->user_login])) {
            echo "[]";
            exit;
        } else {
            $user_key = getOption("user_id")[$user_info->user_login];
        }

        $userEntity = new PublicKeyCredentialUserEntity(
            $user_info->user_login,
            $user_key,
            $user_info->display_name,
            get_avatar_url($user_info->user_email, array("scheme" => "https"))
        );


        $publicKeyCredentialSourceRepository = new Credential();
        echo json_encode($publicKeyCredentialSourceRepository->getShowList($userEntity));
        exit;
    }

    public static function clearLogs()
    {
        if (!current_user_can('manage_options')) {
            cancelAction("Bad Request.");
        }

        $log = get_option("vincss_fido2_login_log");

        if ($log !== false) {
            update_option("vincss_fido2_login_log", array());
        }

        echo "true";
        exit;
    }

    public static function handleAuthentication()
    {
        $client_id = false;
        try {
            $res_nonce = randomStr(5);

            initOptions();

            addLog($res_nonce, "ajax_auth_response: Client response received");

            if (!isset($_POST["clientid"])) {
                addLog($res_nonce, "ajax_auth_response: (ERROR) Missing parameters, exit");
                wp_die("Bad Request.");
            } else {
                if (strlen($_POST["clientid"]) < 34 || strlen($_POST["clientid"]) > 35) {
                    addLog($res_nonce, "ajax_auth_response: (ERROR) Wrong client ID, exit");
                    cancelAction("Bad Request.", $client_id);
                }
                $client_id = sanitize_text_field($_POST["clientid"]);
            }

            if (!isset($_POST["type"]) || !isset($_POST["data"]) || !isset($_POST["remember"])) {
                addLog($res_nonce, "ajax_auth_response: (ERROR) Missing parameters, exit");
                cancelAction("Bad Request.", $client_id);
            } else {
                $vincss_fido2_login_post = array();
                $vincss_fido2_login_post["type"] = sanitize_text_field($_POST["type"]);
                $vincss_fido2_login_post["remember"] = sanitize_text_field($_POST["remember"]);
            }

            $temp_val = array(
                "pkcco_auth" => getTempValue("pkcco_auth", $client_id),
                "auth_type" => getTempValue("auth_type", $client_id),
                "usernameless_auth" => getTempValue("usernameless_auth", $client_id),
                "user_auth" => getTempValue("user_auth", $client_id),
                "user_name_auth" => getTempValue("user_name_auth", $client_id)
            );

            if ($temp_val["auth_type"] === false) {
                addLog($res_nonce, "ajax_auth_response: (ERROR) Wrong parameters, exit");
                cancelAction("Bad Request.", $client_id);
            }

            if ($vincss_fido2_login_post["remember"] !== "true" && $vincss_fido2_login_post["remember"] !== "false") {
                addLog($res_nonce, "ajax_auth_response: (ERROR) Wrong parameters, exit");
                cancelAction("Bad Request.", $client_id);
            } else if (getOption('remember_login') !== 'true' && $vincss_fido2_login_post["remember"] === "true") {
                addLog($res_nonce, "ajax_auth_response: (ERROR) Wrong parameters, exit");
                cancelAction("Bad Request.", $client_id);
            }

            if ($temp_val["pkcco_auth"] === false || $temp_val["usernameless_auth"] === false || ($vincss_fido2_login_post["type"] !== "auth")) {
                addLog($res_nonce, "ajax_auth_response: (ERROR) Challenge not found in transient, exit");
                cancelAction("Bad request.", $client_id);
            }

            $temp_val["usernameless_auth"] = unserialize($temp_val["usernameless_auth"]);

            if ($temp_val["usernameless_auth"] === false && $temp_val["user_name_auth"] === false) {
                addLog($res_nonce, "ajax_auth_response: (ERROR) Username not found in transient, exit");
                cancelAction("Username not found.", $client_id);
            }

            if (($temp_val["usernameless_auth"] === false && $temp_val["user_auth"] === false)) {
                addLog($res_nonce, "ajax_auth_response: (ERROR) Permission denied, exit");
                cancelAction("Bad request.", $client_id);
            }

            $is_login_usernameless = $temp_val["usernameless_auth"];

            $psr17Factory = new Psr17Factory();
            $creator = new ServerRequestCreator(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory
            );

            $serverRequest = $creator->fromGlobals();
            $publicKeyCredentialSourceRepository = new Credential();

            if ($is_login_usernameless) {
                $data_array = json_decode(base64_decode($_POST["data"]), true);
                if (!isset($data_array["response"]["userHandle"]) || !isset($data_array["rawId"])) {
                    addLog($res_nonce, "ajax_auth_response: (ERROR) Client data not correct, exit");
                    cancelAction("Client data not correct.", $client_id);
                }

                addLog($res_nonce, "ajax_auth_response: type => \"" . $vincss_fido2_login_post["type"] . "\"");
                addLog($res_nonce, "ajax_auth_response: Login without username authentication, try to find user by credential_id => \"" . $data_array["rawId"] . "\", userHandle => \"" . $data_array["response"]["userHandle"] . "\"");

                $credential_meta = $publicKeyCredentialSourceRepository->findOneMetaByCredentialId(base64_decode($data_array["rawId"]));

                if ($credential_meta !== null) {
                    if ($credential_meta["usernameless"] === true) {
                        addLog($res_nonce, "ajax_auth_response: Credential found, usernameless => \"true\", user_key => \"" . $credential_meta["user"] . "\"");

                        $all_user = getOption("user_id");
                        $user_login_name = false;
                        foreach ($all_user as $user => $user_id) {
                            if ($user_id === $credential_meta["user"]) {
                                $user_login_name = $user;
                                break;
                            }
                        }

                        if ($credential_meta["user"] === base64_decode($data_array["response"]["userHandle"])) {
                            if ($user_login_name !== false) {
                                addLog($res_nonce, "ajax_auth_response: Found user => \"" . $user_login_name . "\", user_key => \"" . $credential_meta["user"] . "\"");



                                $user_info = get_user_by('login', $user_login_name);

                                if ($user_info === false) {
                                    addLog($res_nonce, "ajax_auth_response: (ERROR) Wrong user ID, exit");
                                    cancelAction("Wrong user ID.");
                                }

                                $userEntity = new PublicKeyCredentialUserEntity(
                                    $user_info->user_login,
                                    $credential_meta["user"],
                                    $user_info->display_name,
                                    get_avatar_url($user_info->user_email, array("scheme" => "https"))
                                );
                            } else {
                                addLog($res_nonce, "ajax_auth_response: (ERROR) Credential found, but user not found, exit");
                                cancelAction("User not found.", $client_id);
                            }
                        } else {
                            addLog($res_nonce, "ajax_auth_response: (ERROR) Credential found, but userHandle not matched, exit");
                            cancelAction("User not matched.", $client_id);
                        }
                    } else {
                        addLog($res_nonce, "ajax_auth_response: (ERROR) Credential found, but usernameless => \"false\", exit");
                        cancelAction("Please enter username.", $client_id);
                    }
                } else {
                    addLog($res_nonce, "ajax_auth_response: (ERROR) Credential not found, exit");
                    cancelAction("Credential not found.", $client_id);
                }
            } else {
                addLog($res_nonce, "ajax_auth_response: type => \"auth\", user => \"" . $temp_val["user_name_auth"] . "\"");
                $userEntity = unserialize($temp_val["user_auth"]);
            }


            $rpEntity = new PublicKeyCredentialRpEntity(
                get_bloginfo('name'),
                parse_url(site_url(), PHP_URL_HOST)
            );

            $server = new Server(
                $rpEntity,
                $publicKeyCredentialSourceRepository,
                null
            );

            $current_domain = parse_url(site_url(), PHP_URL_HOST);
            if ($current_domain === "localhost" || $current_domain === "127.0.0.1") {
                $server->setSecuredRelyingPartyId([$current_domain]);
                addLog($res_nonce, "ajax_auth_response: Localhost, bypass HTTPS check");
            }

            try {
                $server->loadAndCheckAssertionResponse(
                    base64_decode($_POST["data"]),
                    unserialize(base64_decode($temp_val["pkcco_auth"])),
                    $userEntity,
                    $serverRequest
                );

                addLog($res_nonce, "ajax_auth_response: Challenge verified");

                $publicKeyCredentialSourceRepository->updateCredentialLastUsed(base64_decode(json_decode(base64_decode($_POST["data"]), true)["rawId"]));
                if (!is_user_logged_in()) {
                    if (has_action('wp_login', array('Two_Factor_Core', 'wp_login')) !== false) {
                        remove_action('wp_login', array('Two_Factor_Core', 'wp_login'), 10, 2);
                    }


                    if (!$is_login_usernameless) {
                        $user_login = $temp_val["user_name_auth"];
                    } else {
                        $user_login = $user_login_name;
                    }

                    $user = get_user_by("login", $user_login);

                    if ($user_info === false) {
                        addLog($res_nonce, "ajax_auth_response: (ERROR) Wrong user ID, exit");
                        cancelAction("(ERROR)  Wrong user ID.");
                    }

                    $user_id = $user->ID;

                    addLog($res_nonce, "ajax_auth_response: Log in user => \"" . $user_login . "\"");

                    $remember_flag = false;

                    if ($vincss_fido2_login_post["remember"] === "true" && (getOption("remember_login") === false ? "false" : getOption("remember_login")) !== "false") {
                        $remember_flag = true;
                        addLog($res_nonce, "ajax_auth_response: Remember login for 14 days");
                    }

                    wp_set_current_user($user_id, $user_login);
                    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") {
                        wp_set_auth_cookie($user_id, $remember_flag, true);
                    } else {
                        wp_set_auth_cookie($user_id, $remember_flag);
                    }
                }
                echo "true";
            } catch (\Throwable $exception) {
                addLog($res_nonce, "ajax_auth_response: (ERROR) " . $exception->getMessage());
                addLog($res_nonce, secureGenerateCallTrace($exception));
                addLog($res_nonce, "ajax_auth_response: (ERROR) Challenge not verified, exit");
                cancelAction($exception->getMessage(), $client_id);
            }

            destroyTempVal($client_id);
            exit;
        } catch (\Exception $exception) {
            addLog($res_nonce, "ajax_auth_response: (ERROR) " . $exception->getMessage());
            addLog($res_nonce, secureGenerateCallTrace($exception));
            addLog($res_nonce, "ajax_auth_response: (ERROR) Unknown error, exit");
            cancelAction("Something went wrong.", $client_id);
        } catch (\Error $error) {
            addLog($res_nonce, "ajax_auth_response: (ERROR) " . $error->getMessage());
            addLog($res_nonce, secureGenerateCallTrace($error));
            addLog($res_nonce, "ajax_auth_response: (ERROR) Unknown error, exit");
            cancelAction("Something went wrong.", $client_id);
        }
    }

    public static function getLogs()
    {
        if (!current_user_can('manage_options')) {
            cancelAction("Bad Request.");
        }
        header('Content-Type: application/json');
        $log = get_option("vincss_fido2_login_log");

        if ($log === false) {
            echo "[]";
        } else {
            echo json_encode($log);
        }

        exit;
    }

    public static function updateSettings()
    {
        check_ajax_referer('vincss_pwless_config');
        $key = sanitize_text_field(wp_unslash($_POST['key']));
        $value = sanitize_text_field(wp_unslash($_POST['value']));


        updateOption($key, $value);
        wp_send_json_success(
            array('key' => $key, 'value' => $value)
        );
    }


    public static function updateAuthenticator()
    {
        try {
            $res_nonce = randomStr(5);
            $id = sanitize_text_field($_GET["id"]);
            $user_id = sanitize_text_field($_GET["user_id"]);
            $target = sanitize_text_field($_GET["target"]);
            $name = sanitize_text_field($_GET["name"]);
            initOptions();

            addLog($res_nonce, "ajax_modify_authenticator: Start");

            if (!current_user_can("read")) {
                addLog($res_nonce, "ajax_modify_authenticator: (ERROR) Permission denied, exit");
                cancelAction("Bad Request.");
            }

            if (!isset($id) || !isset($target)) {
                addLog($res_nonce, "ajax_modify_authenticator: (ERROR) Missing parameters, exit");
                cancelAction("Bad Request.");
            }

            $user_info = wp_get_current_user();

            if (isset($user_id)) {
                $user_id = intval($user_id);
                if ($user_id <= 0) {
                    addLog($res_nonce, "ajax_modify_authenticator: (ERROR) Wrong parameters, exit");
                    cancelAction("Bad Request.");
                }

                if ($user_info->ID !== $user_id) {
                    if (!current_user_can("edit_user", $user_id)) {
                        addLog($res_nonce, "ajax_modify_authenticator: (ERROR) No permission, exit");
                        cancelAction("Something went wrong.");
                    }
                    $user_info = get_user_by('id', $user_id);

                    if ($user_info === false) {
                        addLog($res_nonce, "ajax_modify_authenticator: (ERROR) Wrong user ID, exit");
                        cancelAction("Something went wrong.");
                    }
                }
            }

            if ($target !== "rename" && $target !== "remove") {
                addLog($res_nonce, "ajax_modify_authenticator: (ERROR) Wrong target, exit");
                cancelAction("Bad Request.");
            }

            if ($target === "rename" && !isset($name)) {
                addLog($res_nonce, "ajax_modify_authenticator: (ERROR) Missing parameters, exit");
                cancelAction("Bad Request.");
            }

            $user_key = "";
            if (!isset(getOption("user_id")[$user_info->user_login])) {
                addLog($res_nonce, "ajax_modify_authenticator: (ERROR) User not initialized, exit");
                cancelAction("User not inited.");
            } else {
                $user_key = getOption("user_id")[$user_info->user_login];
            }

            $userEntity = new PublicKeyCredentialUserEntity(
                $user_info->user_login,
                $user_key,
                $user_info->display_name,
                get_avatar_url($user_info->user_email, array("scheme" => "https"))
            );

            addLog($res_nonce, "ajax_modify_authenticator: user => \"" . $user_info->user_login . "\"");

            $publicKeyCredentialSourceRepository = new Credential();

            if ($target === "rename") {
                echo $publicKeyCredentialSourceRepository->modifyAuthenticator($id, $name, $userEntity, "rename", $res_nonce);
            } else if ($_GET["target"] === "remove") {
                echo $publicKeyCredentialSourceRepository->modifyAuthenticator($id, "", $userEntity, "remove", $res_nonce);
            }
            exit;
        } catch (\Exception $exception) {
            addLog($res_nonce, "ajax_modify_authenticator: (ERROR) " . $exception->getMessage());
            addLog($res_nonce, secureGenerateCallTrace($exception));
            addLog($res_nonce, "ajax_modify_authenticator: (ERROR) Unknown error, exit");
            cancelAction("Something went wrong.");
        } catch (\Error $error) {
            addLog($res_nonce, "ajax_modify_authenticator: (ERROR) " . $error->getMessage());
            addLog($res_nonce, secureGenerateCallTrace($error));
            addLog($res_nonce, "ajax_modify_authenticator: (ERROR) Unknown error, exit");
            cancelAction("Something went wrong.");
        }
    }
}
