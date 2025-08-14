<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Contracts;

use Charcoal\Http\Commons\Body\Payload;
use Charcoal\Http\Commons\Enums\ContentType;

/**
 * Interface ContentTypeEncoderInterface
 * @package Charcoal\Http\Client\Contracts
 */
interface ContentTypeEncoderInterface
{
    public static function headerFor(ContentType $contentType): ?string;

    public static function encode(Payload|array $data, ContentType $contentType): string|false;

    public static function decode(string $data, ContentType $contentType): array|false;
}