<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client;

use Charcoal\Base\Abstracts\Dataset\BatchEnvelope;
use Charcoal\Base\Enums\ExceptionAction;
use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Client\Contracts\ClientAuthInterface;
use Charcoal\Http\Client\Contracts\RequestObserverInterface;
use Charcoal\Http\Client\Encoding\BaseEncoder;
use Charcoal\Http\Client\Encoding\RequestFormat;
use Charcoal\Http\Client\Exceptions\HttpClientException;
use Charcoal\Http\Client\Exceptions\RequestException;
use Charcoal\Http\Client\Exceptions\ResponseException;
use Charcoal\Http\Client\Exceptions\SecureRequestException;
use Charcoal\Http\Client\Proxy\ProxyServer;
use Charcoal\Http\Client\Security\TlsContext;
use Charcoal\Http\Client\Support\CurlHelper;
use Charcoal\Http\Commons\Body\Payload;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\Http;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Header\Headers;
use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Commons\Support\UrlInfo;

/**
 * Class Request
 * @package Charcoal\Http\Client
 */
final class Request
{
    public readonly UrlInfo $url;
    public readonly WritableHeaders $headers;
    public readonly Payload|WritablePayload $payload;
    public readonly Buffer $body;

    protected ?RequestObserverInterface $observer = null;
    protected ?array $observerContext = null;

    /**
     * @param ClientConfig $config
     * @param HttpMethod $method
     * @param string $url
     * @param Headers|array|null $headers
     * @param Payload|array|null $payload
     * @throws RequestException
     */
    public function __construct(
        protected ClientConfig     $config,
        public readonly HttpMethod $method,
        string                     $url,
        Headers|array|null         $headers = null,
        Payload|array|null         $payload = null,
    )
    {
        try {
            $this->url = new UrlInfo($url);
            if (!$this->url->scheme || !$this->url->host) {
                throw new \RuntimeException();
            }
        } catch (\Exception) {
            throw new RequestException('Cannot create cURL request without URL scheme and host');
        }

        try {
            if ($headers instanceof WritableHeaders) {
                $this->headers = $headers;
            } else {
                $this->headers = new WritableHeaders(new BatchEnvelope(match (true) {
                    is_array($headers) => $headers,
                    $headers instanceof Headers => $headers->getArray(),
                    default => []
                }, ExceptionAction::Throw));
            }
        } catch (\Exception $e) {
            throw new RequestException(ObjectHelper::baseClassName($e::class) . ": " . $e->getMessage(),
                previous: $e);
        }

        $this->body = new Buffer();
        try {
            if ($payload instanceof Payload) {
                $this->payload = $payload;
            } else {
                $this->payload = new WritablePayload(
                    new BatchEnvelope(is_array($payload) ? $payload : [], ExceptionAction::Throw));
            }
        } catch (\Exception $e) {
            throw new RequestException(ObjectHelper::baseClassName($e::class) . ": " . $e->getMessage(),
                previous: $e);
        }
    }

    /**
     * @param RequestObserverInterface $observer
     * @param array $context
     * @return $this
     */
    public function observe(RequestObserverInterface $observer, array $context = []): self
    {
        $this->observer = $observer;
        $this->observerContext = $context;
        return $this;
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
    public function config(
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
            $this->config
        );

        return $this;
    }

    /**
     * @return Response
     * @throws HttpClientException
     */
    public function send(): Response
    {
        if (!$this->observer) {
            return $this->submit();
        }

        try {
            $result = $this->submit();
            $this->observer->onRequestResult($this, $result, $this->observerContext);
            return $result;
        } catch (\Throwable $t) {
            $this->observer->onRequestResult($this, $t, $this->observerContext);
            throw new HttpClientException("Exception thrown during request, check previous", previous: $t);
        }
    }

    /**
     * @throws RequestException
     * @throws ResponseException
     * @throws SecureRequestException
     */
    private function submit(): Response
    {
        $ch = curl_init(); // Init cURL handler
        curl_setopt($ch, CURLOPT_URL, $this->url->complete); // Set URL
        if ($this->config->version) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CurlHelper::httpVersionForCurl($this->config->version));
        }

        // Proxy Server?
        $this->config->proxyServer?->applyPolicy($ch, $this);

        // SSL?
        if (strtolower($this->url->scheme) === "https") {
            if (!$this->config->tlsContext) {
                throw new SecureRequestException("TLS context is required to send HTTPS requests", 150);
            }

            $this->config->tlsContext->applyPolicy($ch, $this);
        }

        // Payload
        match ($this->method) {
            HttpMethod::GET => RequestFormat::formatGet($this, $ch),
            default => RequestFormat::formatCustom($this, $ch)
        };

        // Authorization
        $this->config->authContext?->setCredentials($this);

        // Headers
        if ($this->headers->count()) {
            $headers = [];
            foreach ($this->headers->getArray() as $n => $v) {
                $headers[] = $n . ": " . $v;
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // User agent
        if ($this->config->userAgent && $this->config->userAgent !== "") {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->config->userAgent);
        }

        // Timeouts
        if ($this->config->timeout > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->timeout);
        }

        if ($this->config->connectTimeout > 0) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->config->connectTimeout);
        }

        // Response
        $responseHeaders = [];

        // Finalise request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $line) use (&$responseHeaders) {
            if (preg_match('/^[\w\-]+:/', $line)) {
                $header = explode(':', $line, 2);
                $name = trim(strval($header[0] ?? null));
                $value = trim(strval($header[1] ?? null));
                if ($name && $value) {
                    $responseHeaders[$name] = $value;
                }
            }

            return strlen($line);
        });

        // Execute cURL request
        $body = curl_exec($ch);
        if ($body === false) {
            throw new ResponseException(
                sprintf('cURL error [%d]: %s', curl_error($ch), curl_error($ch))
            );
        }

        // Response code
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        if (is_string($responseCode) && preg_match('/[0-9]+/', $responseCode)) {
            $responseCode = intval($responseCode); // In case HTTP response code is returned as string
        }

        if (!is_int($responseCode)) {
            throw new ResponseException('Could not retrieve HTTP response code');
        }

        // Close cURL resource
        curl_close($ch);

        // Response Payload
        $responseType = ContentType::find(strval($responseType)) ?? $this->config->responseContentType;
        if (!$responseType) {
            throw new ResponseException('No "Content-type" header received');
        }

        $payload = $this->config->encoder::decode($body, $responseType) ?: [];
        if ($payload) {
            $body = null;
        }

        // Final CurlResponse instance
        try {
            return new Response(
                new Headers(new BatchEnvelope($responseHeaders, ExceptionAction::Throw)),
                new UnsafePayload(new BatchEnvelope($payload, ExceptionAction::Throw)),
                (new Buffer($body ?: null))->readOnly(),
                $responseCode
            );
        } catch (\Exception $e) {
            throw new ResponseException(ObjectHelper::baseClassName($e::class) . ": " . $e->getMessage(),
                previous: $e);
        }
    }
}

