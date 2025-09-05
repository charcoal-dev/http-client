<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Security;

use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Base\Objects\Traits\NoDumpTrait;
use Charcoal\Http\Client\Contracts\CurlConfigurableInterface;
use Charcoal\Http\Client\Exceptions\SecureRequestException;
use Charcoal\Http\Client\Request;
use Charcoal\Http\Commons\Contracts\CredentialObjectInterface;
use Charcoal\Http\Commons\Enums\Security\CredentialEncoding;
use Charcoal\Http\Commons\Security\CredentialBlob;

/**
 * Class CertificateCredentials
 * @package Charcoal\Http\Client\Security
 */
readonly class CertificateCredentials implements CurlConfigurableInterface
{
    use NoDumpTrait;

    /**
     * @throws SecureRequestException
     */
    public function __construct(
        #[\SensitiveParameter]
        public string|CredentialObjectInterface|null $certificate,
        #[\SensitiveParameter]
        public ?string                               $certificatePassword = null,
        #[\SensitiveParameter]
        public string|CredentialObjectInterface|null $privateKey,
        #[\SensitiveParameter]
        public ?string                               $privateKeyPassword = null,
    )
    {
        if (($this->certificate && !$this->privateKey) ||
            ($this->privateKey && !$this->certificate) ||
            ($this->certificatePassword && !$this->certificate) ||
            ($this->privateKeyPassword && !$this->privateKey)) {
            throw new SecureRequestException("Cannot create request without certificate and private key", 151);
        }

        if (is_string($this->certificate) && !$this->validateFilepath($this->certificate)) {
            throw new SecureRequestException("Cannot read TLS certificate file", 151);
        }

        if (is_string($this->privateKey) && !$this->validateFilepath($this->privateKey)) {
            throw new SecureRequestException("Cannot read TLS certificate file", 151);
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    private function validateFilepath(string $path): bool
    {
        return $path && file_exists($path) && is_readable($path) && is_file($path);
    }

    /**
     * @param \CurlHandle $ch
     * @param Request $request
     * @return void
     * @throws SecureRequestException
     */
    public function applyPolicy(\CurlHandle $ch, Request $request): void
    {
        try {
            $this->applyCertificate($ch);
            $this->applyPrivateKey($ch);
        } catch (SecureRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new SecureRequestException(
                ObjectHelper::baseClassName($e::class) . ": " . $e->getMessage(), 179, $e);
        }
    }

    /**
     * @param \CurlHandle $ch
     * @return void
     * @throws SecureRequestException
     * @throws \Charcoal\Http\Commons\Exceptions\CredentialFileException
     */
    private function applyCertificate(\CurlHandle $ch): void
    {
        if (!$this->certificate) {
            return;
        }

        if ($this->certificate instanceof CredentialBlob) {
            if (!defined("CURLOPT_SSLCERT_BLOB")) {
                throw new SecureRequestException("Cannot use CredentialBlob with current cURL version", 171);
            }

            curl_setopt($ch, CURLOPT_SSLCERT_BLOB, [
                "data" => $this->certificate->getBlob(),
                "len" => $this->certificate->getSize(),
                "type" => $this->certificate->encoding->name,
            ]);
        } else {
            $filepath = $this->certificate instanceof CredentialObjectInterface
                ? $this->certificate->filepath()
                : $this->certificate;
            curl_setopt($ch, CURLOPT_SSLCERT, $filepath);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, $this->certificate instanceof CredentialObjectInterface
                ? $this->certificate->encoding->name
                : CredentialEncoding::PEM->name);
        }

        if ($this->certificatePassword) {
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certificatePassword);
        }
    }

    /**
     * @param \CurlHandle $ch
     * @return void
     * @throws SecureRequestException
     * @throws \Charcoal\Http\Commons\Exceptions\CredentialFileException
     */
    private function applyPrivateKey(\CurlHandle $ch): void
    {
        if (!$this->privateKey) {
            return;
        }

        if ($this->privateKey instanceof CredentialBlob) {
            if (!defined("CURLOPT_SSLKEY_BLOB")) {
                throw new SecureRequestException("Cannot use CredentialBlob with current cURL version", 172);
            }

            curl_setopt($ch, CURLOPT_SSLKEY_BLOB, [
                "data" => $this->privateKey->getBlob(),
                "len" => $this->privateKey->getSize(),
                "type" => $this->privateKey->encoding->name,
            ]);
        } else {
            $filepath = $this->privateKey instanceof CredentialObjectInterface
                ? $this->privateKey->filepath()
                : $this->privateKey;
            curl_setopt($ch, CURLOPT_SSLKEY, $filepath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, $this->privateKey instanceof CredentialObjectInterface
                ? $this->privateKey->encoding->name
                : CredentialEncoding::PEM->name);
        }

        if ($this->privateKeyPassword) {
            curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $this->privateKeyPassword);
        }
    }
}