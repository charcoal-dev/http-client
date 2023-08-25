<?php
/*
 * This file is a part of "charcoal-dev/http-client" package.
 * https://github.com/charcoal-dev/http-client
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/http-client/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\HTTP\Client;

use Charcoal\HTTP\Client\Exception\SecureRequestException;

/**
 * Class SslContext
 * @package Charcoal\HTTP\Client
 */
class SslContext
{
    /** @var bool */
    private bool $verify = true;
    /** @var null|string */
    private ?string $certPath = null;
    /** @var null|string */
    private ?string $certPassword = null;
    /** @var null|string */
    private ?string $privateKeyPath = null;
    /** @var null|string */
    private ?string $privateKeyPassword = null;
    /** @var null|string */
    private ?string $certAuthorityPath = null;

    /**
     * @throws \Charcoal\HTTP\Client\Exception\SecureRequestException
     */
    public function __construct()
    {
        if (!Curl::supportSslTls()) {
            throw new SecureRequestException('SSL/TLS support is unavailable in your cURL build');
        }
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function verify(bool $bool): static
    {
        $this->verify = $bool;
        return $this;
    }

    /**
     * @param string $file
     * @param string|null $password
     * @return $this
     * @throws \Charcoal\HTTP\Client\Exception\SecureRequestException
     */
    public function certificate(string $file, ?string $password = null): static
    {
        $path = realpath($file);
        if (!$path || !is_readable($path) || !is_file($path)) {
            throw new SecureRequestException(sprintf('SSL certificate "%s" not found or not readable', basename($file)));
        }

        $this->certPath = $path;
        $this->certPassword = $password;
        return $this;
    }

    /**
     * @param string $file
     * @param string|null $password
     * @return $this
     * @throws \Charcoal\HTTP\Client\Exception\SecureRequestException
     */
    public function privateKey(string $file, ?string $password = null): static
    {
        $path = realpath($file);
        if (!$path || !is_readable($path) || !is_file($path)) {
            throw new SecureRequestException(sprintf('SSL private key "%s" not found or not readable', basename($file)));
        }

        $this->privateKeyPath = $file;
        $this->privateKeyPassword = $password;
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     * @throws \Charcoal\HTTP\Client\Exception\SecureRequestException
     */
    public function ca(string $path): static
    {
        $path = realpath($path);
        if (!$path || !is_readable($path) || !is_file($path)) {
            throw new SecureRequestException('Path to CA certificate(s) is invalid or not readable');
        }

        $this->certAuthorityPath = $path;
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     * @throws \Charcoal\HTTP\Client\Exception\SecureRequestException
     */
    public function certificateAuthority(string $path): static
    {
        return $this->ca($path);
    }

    /**
     * @param \CurlHandle $ch
     */
    public function registerCh(\CurlHandle $ch): void
    {
        // Bypass SSL check?
        if (!$this->verify) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            return; // Return
        }

        // Work with SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // CA Bundle
        if ($this->certAuthorityPath) {
            if (is_file($this->certAuthorityPath)) {
                curl_setopt($ch, CURLOPT_CAINFO, $this->certAuthorityPath);
            } elseif (is_dir($this->certAuthorityPath)) {
                curl_setopt($ch, CURLOPT_CAPATH, $this->certAuthorityPath);
            }
        }

        if ($this->certPath) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, "PEM");
            if ($this->certPassword) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certPassword);
            }
        }

        if ($this->privateKeyPath) {
            curl_setopt($ch, CURLOPT_SSLKEY, $this->privateKeyPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, "PEM");
            if ($this->privateKeyPassword) {
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $this->privateKeyPassword);
            }
        }
    }
}
