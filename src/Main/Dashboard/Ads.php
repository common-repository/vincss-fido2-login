<?php

use VinCSS\Ads\AdsManager;

$ads = new AdsManager();
?>

    <?php
    switch ($tab_selected):
        case 'two-factor':
            $ads->renderAdminAds2FA();
            break;
        case 'oauth2':
            $ads->renderAdminAdsOAuth2();
            break;
        case 'logs':
            $ads->renderAdminAdsPwless();
            break;
        default:
            $ads->renderAdminAdsPwless();
            break;
    endswitch;
    ?>