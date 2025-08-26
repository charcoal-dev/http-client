<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Proxy;

use Charcoal\Http\Client\Contracts\CurlConfigurableInterface;
use Charcoal\Http\Client\Request;

/**
 * Represents a proxy server configuration that can be applied to a cURL handle.
 * This class implements the CurlConfigurableInterface and provides functionality
 * to configure a cURL request with proxy server details, including host, port,
 * username, and password.
 */
readonly class ProxyServer implements CurlConfigurableInterface
{
    public function __construct(
        #[\SensitiveParameter]
        public string $host,
        #[\SensitiveParameter]
        public int    $port,
        #[\SensitiveParameter]
        public string $username,
        #[\SensitiveParameter]
        public string $password,
    )
    {
        if ($this->port < 80 || $this->port > 65535) {
            throw new \InvalidArgumentException("Invalid port for ProxyServer");
        }
    }

    /**
     * @param \CurlHandle $ch
     * @param Request $request
     * @return void
     */
    public function applyPolicy(\CurlHandle $ch, Request $request): void
    {
        curl_setopt($ch, CURLOPT_PROXY, $this->host . ($this->port ? ":" . $this->port : ""));
        if ($this->username) {
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->username . ":" . $this->password);
        }
    }
}