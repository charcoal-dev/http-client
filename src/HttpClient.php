<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client;

use Charcoal\Http\Client\Contracts\ClientAuthInterface;
use Charcoal\Http\Client\Encoding\BaseEncoder;
use Charcoal\Http\Client\Proxy\ProxyServer;
use Charcoal\Http\Client\Security\TlsContext;
use Charcoal\Http\Commons\Body\Payload;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\Http;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Header\Headers;

/**
 * Represents an HTTP client responsible for making HTTP requests.
 * Manages client configuration and facilitates performing requests
 * using specified HTTP methods, URLs, headers, and payloads.
 */
class HttpClient
{
    /**
     * @param ClientConfig $config
     */
    public function __construct(protected ClientConfig $config)
    {
    }

    /**
     * @return ClientConfig
     */
    public function config(): ClientConfig
    {
        return $this->config;
    }

    /**
     * @param Http|null $version
     * @param ContentType|null $contentType
     * @param TlsContext|null $tlsContext
     * @param ClientAuthInterface|null $authContext
     * @param ProxyServer|null $proxyServer
     * @param string|null $userAgent
     * @param int|null $timeout
     * @param int|null $connectTimeout
     * @param ContentType|null $responseContentType
     * @param string|null $encoder
     * @return $this
     */
    public function changeConfig(
        ?Http                $version = Http::Version3,
        ?ContentType         $contentType = null,
        ?TlsContext          $tlsContext = null,
        ?ClientAuthInterface $authContext = null,
        ?ProxyServer         $proxyServer = null,
        ?string              $userAgent = null,
        ?int                 $timeout = null,
        ?int                 $connectTimeout = null,
        ?ContentType         $responseContentType = null,
        ?string              $encoder = BaseEncoder::class,
    ): static
    {
        $this->config = new ClientConfig(
            $version,
            $contentType,
            $tlsContext,
            $authContext,
            $proxyServer,
            $userAgent,
            $timeout,
            $connectTimeout,
            $responseContentType,
            $encoder,
            $this->config // User previous config as default value
        );

        return $this;
    }

    /**
     * @throws Exceptions\RequestException
     */
    public function request(
        HttpMethod         $method,
        string             $url,
        Headers|array|null $headers = null,
        Payload|array|null $payload = null,
    ): Request
    {
        return new Request($this->config, $method, $url, $headers, $payload);
    }

    /**
     * @throws Exceptions\RequestException
     * @throws Exceptions\ResponseException
     * @throws Exceptions\SecureRequestException
     */
    public function send(
        HttpMethod         $method,
        string             $url,
        Headers|array|null $headers = null,
        Payload|array|null $payload = null,
    ): Response
    {
        return (new Request($this->config, $method, $url, $headers, $payload))->send();
    }
}