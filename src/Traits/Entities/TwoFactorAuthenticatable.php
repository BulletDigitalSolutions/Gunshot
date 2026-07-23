<?php

namespace BulletDigitalSolutions\Gunshot\Traits\Entities;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;

trait TwoFactorAuthenticatable
{
    /**
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Gedmo\Versioned]
    protected $twoFactorSecret;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Gedmo\Versioned]
    protected $twoFactorRecoveryCodes;

    /**
     * @ORM\Column(type="carbondatetime", nullable=true)
     */
    #[ORM\Column(type: 'carbondatetime', nullable: true)]
    protected $twoFactorConfirmedAt;

    /**
     * @return mixed
     */
    public function getTwoFactorSecret()
    {
        return $this->twoFactorSecret;
    }

    /**
     * @param  mixed  $twoFactorSecret
     */
    public function setTwoFactorSecret($twoFactorSecret): void
    {
        $this->twoFactorSecret = $twoFactorSecret;
    }

    /**
     * @return mixed
     */
    public function getTwoFactorRecoveryCodes()
    {
        return $this->twoFactorRecoveryCodes;
    }

    /**
     * @param  mixed  $twoFactorRecoveryCodes
     */
    public function setTwoFactorRecoveryCodes($twoFactorRecoveryCodes): void
    {
        $this->twoFactorRecoveryCodes = $twoFactorRecoveryCodes;
    }

    /**
     * @return Carbon|null
     */
    public function getTwoFactorConfirmedAt()
    {
        return $this->twoFactorConfirmedAt;
    }

    /**
     * @param  Carbon|null  $twoFactorConfirmedAt
     * @return void
     */
    public function setTwoFactorConfirmedAt(?Carbon $twoFactorConfirmedAt = null): void
    {
        $this->twoFactorConfirmedAt = $twoFactorConfirmedAt;
    }

    /**
     * Determine if two-factor authentication has been enabled.
     *
     * @return bool
     */
    public function hasEnabledTwoFactorAuthentication()
    {
        if (Fortify::confirmsTwoFactorAuthentication()) {
            return ! is_null($this->getTwoFactorSecret()) &&
                ! is_null($this->getTwoFactorConfirmedAt());
        }

        return ! is_null($this->getTwoFactorSecret());
    }

    /**
     * Get the user's two factor authentication recovery codes.
     *
     * @return array
     */
    public function recoveryCodes()
    {
        return json_decode(decrypt($this->getTwoFactorRecoveryCodes()), true);
    }

    /**
     * Replace the given recovery code with a new one in the user's stored codes.
     *
     * @param  string  $code
     * @return void
     */
    public function replaceRecoveryCode($code)
    {
        $this->forceFill([
            'two_factor_recovery_codes' => encrypt(str_replace(
                $code,
                RecoveryCode::generate(),
                decrypt($this->getTwoFactorRecoveryCodes())
            )),
        ])->save();
    }

    /**
     * Get the QR code SVG of the user's two factor authentication QR code URL.
     *
     * @return string
     */
    public function twoFactorQrCodeSvg()
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd
            )
        ))->writeString($this->twoFactorQrCodeUrl());

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    /**
     * Get the two factor authentication QR code URL.
     *
     * @return string
     */
    public function twoFactorQrCodeUrl()
    {
        return app(TwoFactorAuthenticationProvider::class)->qrCodeUrl(
            config('app.name'),
            $this->{Fortify::username()},
            decrypt($this->getTwoFactorSecret())
        );
    }
}
