<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Headers\HeadersImmutable;

/**
 * Represents an immutable HTTP response.
 * This class encapsulates the HTTP response data, including headers, payload, body, and status code.
 * It is designed to be immutable, ensuring the response object cannot be modified after creation.
 */
final readonly class Response
{
    public function __construct(
        public HeadersImmutable $headers,
        public UnsafePayload    $payload,
        public Buffer           $body,
        public int              $statusCode
    )
    {
    }
}