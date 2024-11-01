<?php

namespace VinCSS\TwoFactor\Model;


use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Recovery\Recovery;

class Generate2FA
{

    public static $google2fa = null;

    public function __construct()
    {
        self::$google2fa = new Google2FA();
    }

    public function generateSecretKey()
    {
        return self::$google2fa->generateSecretKey(32);
    }

    public function generateQRCodeImage($domain, $username, $secretKey)
    {
        $qrCodeUrl =  self::$google2fa->getQRCodeUrl(
            $domain,
            $username,
            $secretKey
        );
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new SvgImageBackEnd()
            )
        );

        $qrcode_image = base64_encode($writer->writeString($qrCodeUrl));
        return 'data:image/svg+xml;base64,' . $qrcode_image;
    }
    public function verifyRecoveryCode($user_id, $otp)
    {
        $codes = get_user_meta($user_id, 'vincss_recovery_code', true);

        $valid = false;
        $code_filtered = [];
        if ($codes) {
            if (in_array($otp, $codes)) {
                $valid = true;
                foreach ($codes as $key => $code) {
                    if ($code != $otp) {
                        $code_filtered[] = $code;
                    }
                }
                update_user_meta($user_id, 'vincss_recovery_code', $code_filtered);
            }
        }


        return $valid;
    }

    public function verifyOTP($secretKey, $otp, $user_id = null)
    {
        $valid = self::$google2fa->verifyKey($secretKey, $otp, 0);
        $vincss_2fa_backup_code = getOption('is_backup_code');
        if (!$valid && isset($user_id) && $vincss_2fa_backup_code === 'true') {
            $valid = $this->verifyRecoveryCode($user_id, $otp);
        }
        return $valid;
    }

    public function generateRecoveryCode()
    {
        $this->recovery = new Recovery();

        $codes = $this->recovery->setBlocks(1)->setCount(10)->setChars(16)->lowercase()->toArray();
        return $codes;
    }
}
