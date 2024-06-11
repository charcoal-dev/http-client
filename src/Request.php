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

use Charcoal\Buffers\Buffer;
use Charcoal\HTTP\Client\Authorization\AbstractAuthorization;
use Charcoal\HTTP\Client\Exception\RequestException;
use Charcoal\HTTP\Client\Exception\ResponseException;
use Charcoal\HTTP\Commons\Headers;
use Charcoal\HTTP\Commons\HttpMethod;
use Charcoal\HTTP\Commons\ReadOnlyPayload;
use Charcoal\HTTP\Commons\UrlInfo;
use Charcoal\HTTP\Commons\WritableHeaders;
use Charcoal\HTTP\Commons\WritablePayload;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class Request
 * @package Charcoal\HTTP\Client
 */
class Request
{
    public readonly UrlInfo $url;
    public readonly WritableHeaders $headers;
    public readonly WritablePayload $payload;
    public readonly Buffer $body;

    /** @var bool Send payload as application/json regardless of content-type */
    public bool $contentTypeJSON = false;
    /** @var bool Expect JSON body in response */
    public bool $expectJSON = false;
    /** @var bool If expectJSON is true, use this prop to ignore received content-type */
    public bool $expectJSON_ignoreResContentType = false;

    private ?AbstractAuthorization $auth = null;
    private ?SslContext $ssl = null;
    private ?int $timeOut = null;
    private ?int $connectTimeOut = null;
    private ?int $httpVersion = null;
    private ?string $userAgent = null;
    private ?string $proxyHost = null;
    private ?int $proxyPort = null;
    private ?string $proxyAuthUsername = null;
    private ?string $proxyAuthPassword = null;

    use NoDumpTrait;
    use NotSerializableTrait;
    use NotCloneableTrait;

    /**
     * @param \Charcoal\HTTP\Commons\HttpMethod $method
     * @param string $url
     * @throws \Charcoal\HTTP\Client\Exception\RequestException
     */
    public function __construct(
        public readonly HttpMethod $method,
        string                     $url
    )
    {
        $this->url = new UrlInfo($url);
        if (!$this->url->scheme || !$this->url->host) {
            throw new RequestException('Cannot create cURL request without URL scheme and host');
        }

        $this->headers = new WritableHeaders();
        $this->payload = new WritablePayload();
        $this->body = new Buffer();
    }

    /**
     * @param \Charcoal\HTTP\Client\Authorization\AbstractAuthorization $auth
     * @return $this
     */
    public function useAuthorization(AbstractAuthorization $auth): static
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * @return SslContext
     */
    public function ssl(): SslContext
    {
        if (!$this->ssl) {
            $this->ssl = new SslContext();
        }

        return $this->ssl;
    }

    /**
     * @return $this
     */
    public function ignoreSSL(): static
    {
        $this->ssl()->verify(false);
        return $this;
    }

    /**
     * @param int $version
     * @return $this
     */
    public function useHttpVersion(int $version): static
    {
        if (!in_array($version, Curl::HTTP_VERSIONS)) {
            throw new \OutOfBoundsException('Invalid query Http version');
        }

        $this->httpVersion = $version;
        return $this;
    }

    /**
     * @param string|null $agent
     * @return $this
     */
    public function userAgent(?string $agent = null): static
    {
        $this->userAgent = $agent;
        return $this;
    }

    /**
     * @return $this
     */
    public function contentTypeJSON(): static
    {
        $this->contentTypeJSON = true;
        return $this;
    }

    /**
     * @param bool $ignoreReceivedContentType
     * @return $this
     */
    public function expectJSON(bool $ignoreReceivedContentType = false): static
    {
        $this->expectJSON = true;
        $this->expectJSON_ignoreResContentType = $ignoreReceivedContentType;
        return $this;
    }

    /**
     * @param int|null $timeOut
     * @param int|null $connectTimeout
     * @return $this
     */
    public function setTimeouts(?int $timeOut = null, ?int $connectTimeout = null): static
    {
        if ($timeOut > 0) {
            $this->timeOut = $timeOut;
        }

        if ($connectTimeout > 0) {
            $this->connectTimeOut = $connectTimeout;
        }

        return $this;
    }

    /**
     * @param string $host
     * @param int|null $port
     * @return $this
     */
    public function useProxy(string $host, ?int $port = null): static
    {
        $this->proxyHost = $host;
        $this->proxyPort = $port;
        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function useProxyCredentials(string $username, string $password): static
    {
        $this->proxyAuthUsername = $username;
        $this->proxyAuthPassword = $password;
        return $this;
    }

    /**
     * @return \Charcoal\HTTP\Client\Response
     * @throws \Charcoal\HTTP\Client\Exception\ResponseException
     */
    public function send(): Response
    {
        $ch = curl_init(); // Init cURL handler
        curl_setopt($ch, CURLOPT_URL, $this->url->complete); // Set URL
        if ($this->httpVersion) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, $this->httpVersion);
        }

        // Proxy Server?
        if ($this->proxyHost) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost . ($this->proxyPort ? ":" . $this->proxyPort : ""));
            if ($this->proxyAuthUsername) {
                curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyAuthUsername . ":" . $this->proxyAuthPassword);
            }
        }

        // SSL?
        if (strtolower($this->url->scheme) === "https") {
            $this->ssl()->registerCh($ch); // Register SSL/TLS context
        }

        // Content-type
        $contentType = $this->headers->has("content-type") ?
            trim(explode(";", $this->headers->get("content-type"))[0]) : null;

        // Payload
        switch ($this->method->value) {
            case "GET":
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                if ($this->payload->count()) {
                    $sep = $this->url->query ? "&" : "?"; // Override URL
                    curl_setopt($ch, CURLOPT_URL, $this->url->complete . $sep . http_build_query($this->payload->toArray()));
                }

                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method->value);
                $payloadIsJSON = $this->contentTypeJSON || $contentType === "application/json";
                $payload = $this->body->raw();
                if (!$payload) {
                    if ($this->payload->count()) {
                        $payload = $payloadIsJSON ?
                            json_encode($this->payload->toArray()) : http_build_query($this->payload->toArray());
                    }
                }

                if ($payload) {
                    // Content-type JSON
                    if ($payloadIsJSON) {
                        // Content-type header
                        if (!$this->headers->has("content-type")) {
                            $this->headers->set("Content-type", "application/json; charset=utf-8");
                        }

                        // Content-length header
                        if (!$this->headers->has("content-length")) {
                            $this->headers->set("Content-length", strval(strlen($payload)));
                        }
                    }

                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                }

                break;
        }

        // Headers
        if ($this->headers->count()) {
            $httpHeaders = [];
            foreach ($this->headers->toArray() as $hn => $hv) {
                $httpHeaders[] = $hn . ": " . $hv;
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        }

        // Authorization
        $this->auth?->registerCh($ch);

        // User agent
        if ($this->userAgent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        }

        // Timeouts
        if ($this->timeOut) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);
        }

        if ($this->connectTimeOut) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeOut);
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
        $payload = [];
        $responseIsJSON = is_string($responseType) && str_contains($responseType, 'json') || $this->expectJSON;
        if ($responseIsJSON) {
            if (!$this->expectJSON_ignoreResContentType) {
                if (!is_string($responseType)) {
                    throw new ResponseException('Invalid "Content-type" header received, expecting JSON', $responseCode);
                }

                if (strtolower(trim(explode(";", $responseType)[0])) !== "application/json") {
                    throw new ResponseException(
                        sprintf('Expected "application/json", got "%s"', $responseType),
                        $responseCode
                    );
                }
            }

            // Decode JSON body
            try {
                $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new ResponseException('Failed to decode JSON response', previous: $e);
            }
        }

        // Final CurlResponse instance
        return new Response(
            new Headers($responseHeaders),
            new ReadOnlyPayload($payload),
            (new Buffer($body))->readOnly(),
            $responseCode
        );
    }
}

