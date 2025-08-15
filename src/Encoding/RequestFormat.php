<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Encoding;

use Charcoal\Http\Client\Exceptions\RequestException;
use Charcoal\Http\Client\Request;
use Charcoal\Http\Commons\Enums\ContentType;

/**
 * Class MethodFormat
 * @package Charcoal\Http\Client\Encoding
 * @internal
 */
class RequestFormat
{
    /**
     * @param Request $request
     * @param \CurlHandle $ch
     * @return void
     */
    public static function formatGet(Request $request, \CurlHandle $ch): void
    {
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        if ($request->payload->count()) {
            $sep = $request->url->query ? "&" : "?";
            curl_setopt($ch, CURLOPT_URL, $request->url->complete . $sep .
                http_build_query($request->payload->getArray()));
        }
    }

    /**
     * @param Request $request
     * @param \CurlHandle $ch
     * @return void
     * @throws RequestException
     */
    public static function formatCustom(Request $request, \CurlHandle $ch): void
    {
        $contentType = $request->policy->contentType ??
            ContentType::find($request->headers->get("Content-Type") ?? "");

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->method->value);
        $payload = $request->body->raw();
        if (!$payload) {
            $payload = $request->policy->encoder()::encode($request->payload, $contentType);
        }

        if ($payload === false) {
            throw new RequestException("No ContentType provided for payload");
        }

        // Content-type header
        if (!$request->headers->has("Content-Type")) {
            $contentTypeHeader = $request->policy->encoder()::headerFor($contentType);
            if ($contentTypeHeader) {
                $request->headers->set("Content-Type", $contentTypeHeader);
            }
        }

        // Content-length header
        if (!$request->headers->has("Content-Length")) {
            $request->headers->set("Content-Length", strval(strlen($payload)));
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
}