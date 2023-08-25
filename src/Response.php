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
use Charcoal\HTTP\Commons\Headers;
use Charcoal\HTTP\Commons\ReadOnlyPayload;

/**
 * Class Response
 * @package Charcoal\HTTP\Client
 */
class Response
{
    /**
     * @param \Charcoal\HTTP\Commons\Headers $headers
     * @param \Charcoal\HTTP\Commons\ReadOnlyPayload $payload
     * @param \Charcoal\Buffers\Buffer $body
     * @param int $statusCode
     */
    public function __construct(
        public readonly Headers         $headers,
        public readonly ReadOnlyPayload $payload,
        public readonly Buffer          $body,
        public readonly int             $statusCode
    )
    {
    }
}