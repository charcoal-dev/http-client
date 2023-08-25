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

/**
 * Class Curl
 * @package Charcoal\HTTP\Client
 */
class Curl
{
    public const HTTP_VERSION_1 = CURL_HTTP_VERSION_1_0;
    public const HTTP_VERSION_1_1 = CURL_HTTP_VERSION_1_1;
    public const HTTP_VERSION_2 = CURL_HTTP_VERSION_2_0;

    public const HTTP_VERSIONS = [
        self::HTTP_VERSION_1,
        self::HTTP_VERSION_1_1,
        self::HTTP_VERSION_2
    ];

    private static ?bool $tlsSupport = null;

    /**
     * Returns TRUE is Curl is built with SSL/TLS support for secure connections
     * @return bool
     */
    public static function supportSslTls(): bool
    {
        if (!is_bool(static::$tlsSupport)) {
            static::$tlsSupport = !(curl_version()["features"] & CURL_VERSION_SSL);
        }

        return static::$tlsSupport;
    }
}
