<?php
/**
 * Part of the "charcoal-dev/http-commons" package.
 * @link https://github.com/charcoal-dev/http-commons
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Enums;

/**
 * Class CertVerify
 * @package Charcoal\Http\Commons\Enums\Tls
 */
enum TlsVerify
{
    case Disable;
    case Check;
    case Enforce;
}