<?php

namespace VinCSS\Ads;

class AdsManager
{
    public function renderAdminAdsPwless()
    {
?>

        <div style="width: 250px">
            <a href="https://passwordless.vincss.net/authenticator/" target="_blank">
                <img src="<?php echo esc_attr(VFido2LoginAssetsPath . 'images/fido2-key.png') ?>" class="v-img-ads " />
            </a>
        </div>

    <?php
    }

    public function renderAdminAds2FA()
    {
    ?>
        <div style="width: 320px">
            <a href="https://passwordless.vincss.net/en/ecosystem/" target="_blank">
                <div class="row">
                    <img src="<?php echo esc_attr(VFido2LoginAssetsPath . 'images/fido2.png') ?>" class="v-img-ads" />
                </div>
            </a>
        </div>
    <?php
    }


    public function renderAdminAdsOAuth2()
    {
    ?> <div style="width: 320px">
            <a href="https://passwordless.vincss.net/en/fido2clouden/" target="_blank">
                <div class="row">
                    <img src="<?php echo esc_attr(VFido2LoginAssetsPath . 'images/oauth2.png') ?>" class="v-img-ads" style="border-radius: 16px" />
                </div>
            </a>
        </div>
<?php
    }
}
