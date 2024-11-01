<?php
function renderAdminMenu()
{
    $capability = 'manage_options';
    $slug = 'fido2-login';

    add_menu_page(
        __('FIDO2 Login', 'vincss-fido2-login'),
        __('FIDO2 Login', 'vincss-fido2-login'),
        $capability,
        $slug,
        'dashboard',
        VFido2LoginPluginURL . 'src/Assets/images/icon-menu.png',
        75
    );
}
add_action('admin_menu', 'renderAdminMenu');


function dashboard()
{
    include VFido2LoginSourcePath . "Main/Dashboard/Index.php";
}
