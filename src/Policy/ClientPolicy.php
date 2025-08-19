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
 * This class defines all the parameters and settings required to configure
 * and manage the behavior of an HTTP client, including HTTP version,
 * content types, headers, payloads, timeouts, authentication, and more.
 * It supports inheritance of settings from a previous policy.
 */
readonly class ClientPolicy
{
    public Http $version;
    public ContentType $contentType;
    public ?TlsContext $tlsContext;
    public ?ClientAuthInterface $authContext;
    public ?ProxyServer $proxyServer;
    public ?ContentType $responseContentType;
    public HeadersPolicy $requestHeaders;
    public HeadersPolicy $responseHeaders;
    public PayloadPolicy $requestPayload;
    public PayloadPolicy $responsePayload;
    public string $userAgent;
    public int $timeout;
    public int $connectTimeout;

    /** @var class-string<ContentTypeEncoderInterface> */
    public string $encoder;

    public function __construct(
        ?ClientPolicy        $previous = null,
        ?Http                $version = null,
        ?ContentType         $contentType = null,
        ?TlsContext          $tlsContext = null,
        ?ClientAuthInterface $authContext = null,
        ?ProxyServer         $proxyServer = null,
        ?string              $userAgent = null,
        ?int                 $timeout = null,
        ?int                 $connectTimeout = null,
        ?ContentType         $responseContentType = null,
        ?HeadersPolicy       $requestHeaders = null,
        ?HeadersPolicy       $responseHeaders = null,
        ?PayloadPolicy       $requestPayload = null,
        ?PayloadPolicy       $responsePayload = null,
        ?string              $encoder = null,
    )
    {
        $this->version = $version ?? $previous?->version ?? Http::Version3;
        $this->contentType = $contentType ?? $previous?->contentType;
        $this->authContext = $authContext ?? $previous?->authContext;
        $this->proxyServer = $proxyServer ?? $previous?->proxyServer;
        $this->userAgent = $userAgent ?? $previous?->userAgent ?? "Charcoal/HttpClient";
        $this->timeout = $timeout ?? $previous?->timeout ?? 0;
        $this->connectTimeout = $connectTimeout ?? $previous?->connectTimeout ?? 0;
        $this->responseContentType = $responseContentType ?? $previous?->responseContentType;
        $this->requestHeaders = $requestHeaders ?? $previous->requestHeaders ?? new HeadersPolicy();
        $this->requestPayload = $requestPayload ?? $previous->requestPayload ?? new PayloadPolicy();
        $this->responseHeaders = $responseHeaders ?? $previous->responseHeaders ?? new HeadersPolicy();
        $this->responsePayload = $responsePayload ?? $previous->responsePayload ?? new PayloadPolicy();

        $tlsContext = $tlsContext ?? $previous?->tlsContext ?? null;
        $this->tlsContext = $tlsContext ? TlsContext::from($tlsContext) : null;

        if ($encoder) {
            if (!class_exists($encoder) ||
                !is_subclass_of($encoder, ContentTypeEncoderInterface::class)) {
                throw new \InvalidArgumentException("Invalid encoder classname");
            }

            $this->encoder = $encoder;
        } else {
            $this->encoder = $previous?->encoder ?? BaseEncoder::class;
        }
    }
}