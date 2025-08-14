<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Support;

use Charcoal\Http\Commons\Enums\Http;

/**
 * Class Curl
 * @package Charcoal\Http\Client
 */
class CurlHelper
{
    private static ?bool $tlsSupport = null;

    /**
     * Returns TRUE is Curl is built with SSL/TLS support for secure connections
     * @return bool
     */
    public static function supportTls(): bool
    {
        if (!is_bool(static::$tlsSupport)) {
            static::$tlsSupport = (bool)((curl_version()["features"] & CURL_VERSION_SSL));
        }

        return static::$tlsSupport;
    }

    /**
     * @param Http $version
     * @return int
     */
    public static function httpVersionForCurl(Http $version): int
    {
        return match ($version) {
            Http::Version2 => CURL_HTTP_VERSION_2_0,
            Http::Version1_1 => CURL_HTTP_VERSION_1_1,
            Http::Version1 => CURL_HTTP_VERSION_1_0,
            default => CURL_HTTP_VERSION_NONE,
        };
    }
}
