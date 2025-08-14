<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Security;

use Charcoal\Http\Client\Contracts\CurlConfigurableInterface;
use Charcoal\Http\Client\Enums\TlsVerify;
use Charcoal\Http\Client\Exception\SecureRequestException;
use Charcoal\Http\Client\Request;

/**
 * Class TlsContext
 * @package Charcoal\Http\Client\Concerns
 */
class TlsContext implements CurlConfigurableInterface
{
    /**
     * @param TlsContext $context
     * @return static
     */
    public static function from(TlsContext $context): static
    {
        return new static(
            verify: $context->verify,
            caPath: $context->caPath,
            certificate: $context->certificate,
        );
    }

    /**
     * @param TlsVerify $verify
     * @param CaPath|null $caPath
     * @param CertificateCredentials|null $certificate
     */
    public function __construct(
        protected TlsVerify               $verify = TlsVerify::Enforce,
        protected ?CaPath                 $caPath,
        protected ?CertificateCredentials $certificate,
    )
    {
    }

    /**
     * @param \CurlHandle $ch
     * @param Request $request
     * @return void
     * @throws SecureRequestException
     */
    public function applyPolicy(\CurlHandle $ch, Request $request): void
    {
        match ($this->verify) {
            TlsVerify::Disable => $this->disableTlsVerification($ch),
            TlsVerify::Check => $this->checkTlsVerification($ch, $request),
            TlsVerify::Enforce => $this->enforceTlsVerification($ch, $request),
        };
    }

    /**
     * @param \CurlHandle $ch
     * @return void
     */
    private function disableTlsVerification(\CurlHandle $ch): void
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    /**
     * @param \CurlHandle $ch
     * @param Request $request
     * @return void
     * @throws SecureRequestException
     */
    private function checkTlsVerification(\CurlHandle $ch, Request $request): void
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        if (!$this->caPath) {
            throw new SecureRequestException("CA path is required to check TLS verification", 451);
        }

        $this->caPath->applyPolicy($ch, $request);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    }

    /**
     * @param \CurlHandle $ch
     * @param Request $request
     * @return void
     * @throws SecureRequestException
     */
    private function enforceTlsVerification(\CurlHandle $ch, Request $request): void
    {
        $this->checkTlsVerification($ch, $request);
        if (!$this->certificate) {
            throw new SecureRequestException(
                "Certificate credentials are required to enforce TLS verification", 452);
        }

        $this->certificate->applyPolicy($ch, $request);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }
}
