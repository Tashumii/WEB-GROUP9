<?php

namespace App\Libraries;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorAuth
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function getOtpAuthUrl(string $company, string $holder, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($company, $holder, $secret);
    }

    public function getQRCodeSvg(string $otpAuthUrl): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        return $writer->writeString($otpAuthUrl);
    }

    public function verifyCode(string $secret, string $code, ?int $oldTimestamp = null): bool|int
    {
        $code = trim($code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        if ($oldTimestamp === null) {
            return $this->google2fa->verifyKey($secret, $code, 1)
                ? $this->google2fa->getTimestamp()
                : false;
        }
        $timestamp = $this->google2fa->verifyKeyNewer($secret, $code, $oldTimestamp, 1);
        if ($timestamp === false) {
            return false;
        }
        return (int) $timestamp;
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(5))) . '-' . strtoupper(bin2hex(random_bytes(5)));
        }
        return $codes;
    }

    public function hashRecoveryCode(string $code): string
    {
        return password_hash(str_replace([' ', '-'], '', strtoupper(trim($code))), PASSWORD_DEFAULT);
    }

    public function verifyRecoveryCode(string $code, array $hashes): int
    {
        $normalized = str_replace([' ', '-'], '', strtoupper(trim($code)));
        foreach ($hashes as $index => $hash) {
            if (is_string($hash) && password_verify($normalized, $hash)) {
                return (int) $index;
            }
        }
        return -1;
    }

    public function encryptSecret(string $secret): string
    {
        $encrypter = \Config\Services::encrypter();
        return base64_encode($encrypter->encrypt($secret));
    }

    public function decryptSecret(string $encrypted): string
    {
        $encrypter = \Config\Services::encrypter();
        return $encrypter->decrypt(base64_decode($encrypted, true));
    }
}
