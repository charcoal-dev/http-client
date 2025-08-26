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
use Charcoal\Http\Commons\Contracts\HeadersInterface;
use Charcoal\Http\Commons\Contracts\PayloadInterface;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Enums\HttpProtocol;

/**
 * Represents an HTTP client responsible for making HTTP requests.
 * Manages client configuration and facilitates performing requests
 * using specified HTTP methods, URLs, headers, and payloads.
 */
final class HttpClient
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
     * @return $this
     */
    public function changeConfig(
        ?HttpProtocol        $version = HttpProtocol::Version3,
        ?ContentType         $contentType = null,
        ?TlsContext          $tlsContext = null,
        ?ClientAuthInterface $authContext = null,
        ?ProxyServer         $proxyServer = null,
        ?string              $userAgent = null,
        ?int                 $timeout = null,
        ?int                 $connectTimeout = null,
        ?ContentType         $responseContentType = null,
        ?string              $encoder = BaseEncoder::class,
    ): self
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
        HeadersInterface|array|null $headers = null,
        PayloadInterface|array|null $payload = null,
    ): Request
    {
        return new Request($this->config, $method, $url, $headers, $payload);
    }

    /**
     * @throws Exceptions\HttpClientException
     * @throws Exceptions\RequestException
     */
    public function send(
        HttpMethod         $method,
        string             $url,
        HeadersInterface|array|null $headers = null,
        PayloadInterface|array|null $payload = null,
    ): Response
    {
        return (new Request($this->config, $method, $url, $headers, $payload))->send();
    }
}