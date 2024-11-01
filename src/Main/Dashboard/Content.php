<div class="vincss-secure-content w-100">
    <?php

    use VinCSS\TwoFactor\TwoFactor;

    switch ($tab_selected):
        case 'two-factor':
            $two_factor = new TwoFactor();
            $two_factor->renderMenuPage();
            break;
        case 'oauth2':
            include VFido2LoginSourcePath . "Main/OAuth2/Index.php";
            break;
        case 'logs':
            include VFido2LoginSourcePath . "Main/Logs/Index.php";
            break;
        default:
            include(VFido2LoginSourcePath . "Main/WebAuthn/Settings.php");
            break;
    endswitch; ?>
</div>