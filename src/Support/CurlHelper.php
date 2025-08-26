<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Support;

use Charcoal\Http\Commons\Enums\HttpProtocol;

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
     * @param HttpProtocol $version
     * @return int
     */
    public static function httpVersionForCurl(HttpProtocol $version): int
    {
        return match ($version) {
            HttpProtocol::Version2 => CURL_HTTP_VERSION_2_0,
            HttpProtocol::Version1_1 => CURL_HTTP_VERSION_1_1,
            HttpProtocol::Version1 => CURL_HTTP_VERSION_1_0,
            default => CURL_HTTP_VERSION_NONE,
        };
    }
}
