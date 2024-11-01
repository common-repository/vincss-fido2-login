<?php


wp_enqueue_script('vincss_fido2_login_pwl_settings', VFido2LoginPluginURL . 'src/Assets/js/passwordless/settings.js');
wp_localize_script('vincss_fido2_login_pwl_settings', 'variables_pwl_settings', array(
    'ajax_uri' => admin_url('admin-ajax.php'),
    'log_count' => __('Log count: ', 'vincss-fido2-login')
));
$tab_selected = sanitize_text_field($_GET['tab']);
?>

<div class="vincss-secure-root">
    <div class="vincss-secure-container">
        <?php include(VFido2LoginSourcePath . "Main/Dashboard/Sidebar.php"); ?>
        <div class="vincss-secure-right">
            <div class="v-card w-100 m-4">
                <?php include(VFido2LoginSourcePath . "Main/Dashboard/Content.php"); ?>
            </div>
            <div class="fido2-login-ads my-4">
                <?php include(VFido2LoginSourcePath . "Main/Dashboard/Ads.php"); ?>
            </div>
        </div>
    </div>
</div>