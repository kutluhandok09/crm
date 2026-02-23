<?php

namespace App\Services\Security;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(
        protected Google2FA $google2FA = new Google2FA(),
    ) {
    }

    public function generateSecret(): string
    {
        return $this->google2FA->generateSecretKey();
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2FA->verifyKey($secret, $code);
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(10));
        }

        return $codes;
    }

    public function getOtpAuthUrl(User $user, string $secret): string
    {
        return $this->google2FA->getQRCodeUrl(
            config('app.name', 'Laravel'),
            $user->email,
            $secret
        );
    }

    public function makeQrCodeSvg(string $otpAuthUrl): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($otpAuthUrl);
    }
}
