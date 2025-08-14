<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Header\Headers;

/**
 * Class Response
 * @package Charcoal\Http\Client
 */
readonly class Response
{
    public function __construct(
        public Headers       $headers,
        public UnsafePayload $payload,
        public Buffer        $body,
        public int           $statusCode
    )
    {
    }
}