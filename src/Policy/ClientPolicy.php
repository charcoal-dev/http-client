<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Policy;

use Charcoal\Http\Client\Contracts\ClientAuthInterface;
use Charcoal\Http\Client\Contracts\ContentTypeEncoderInterface;
use Charcoal\Http\Client\Encoding\BaseEncoder;
use Charcoal\Http\Client\Proxy\ProxyServer;
use Charcoal\Http\Client\Security\TlsContext;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\Http;

/**
 * Class ClientPolicy
 * @package Charcoal\Http\Client\Policy
 */
class ClientPolicy
{
    public ?Http $version = Http::Version2;
    public ?ContentType $contentType = null;
    public ?TlsContext $tlsContext = null;
    public ?ClientAuthInterface $authContext = null;
    public ?ProxyServer $proxyServer = null;

    public string $userAgent = "Charcoal/HttpClient";
    public int $timeout = 0;
    public int $connectTimeout = 0;

    public ?ContentType $responseContentType = null;
    public HeadersPolicy $requestHeaders;
    public HeadersPolicy $responseHeaders;
    public PayloadPolicy $requestPayload;
    public PayloadPolicy $responsePayload;

    protected string $encoder = ContentTypeEncoderInterface::class;

    /**
     * @param ClientPolicy|null $previous
     */
    public function __construct(?ClientPolicy $previous = null)
    {
        $this->version = $previous?->version;
        $this->contentType = $previous?->contentType;
        $this->authContext = $previous?->authContext;
        $this->proxyServer = $previous?->proxyServer;
        $this->userAgent = $previous?->userAgent ?? $this->userAgent;
        $this->timeout = $previous?->timeout ?? $this->timeout;
        $this->connectTimeout = $previous?->connectTimeout ?? $this->connectTimeout;
        $this->responseContentType = $previous?->responseContentType ?? $this->responseContentType;
        $this->tlsContext = $previous?->tlsContext;

        $this->requestHeaders = $previous->requestHeaders ?? new HeadersPolicy();
        $this->requestPayload = $previous->requestPayload ?? new PayloadPolicy();
        $this->responseHeaders = $previous->responseHeaders ?? new HeadersPolicy();
        $this->responsePayload = $previous->responsePayload ?? new PayloadPolicy();
        $this->encoder = $previous?->encoder() ?? BaseEncoder::class;
    }

    /**
     * @return class-string<ContentTypeEncoderInterface>
     */
    public function encoder(): string
    {
        return $this->encoder;
    }

    /**
     * @param class-string<ContentTypeEncoderInterface> $classname
     * @return void
     */
    public function changeEncoder(string $classname): void
    {
        if (!class_exists($classname) ||
            !is_subclass_of($classname, ContentTypeEncoderInterface::class)) {
            throw new \InvalidArgumentException("Invalid encoder classname");
        }

        $this->encoder = $classname;
    }
}