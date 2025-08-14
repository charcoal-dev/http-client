<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Encoding;

use Charcoal\Http\Client\Contracts\ContentTypeEncoderInterface;
use Charcoal\Http\Commons\Body\Payload;
use Charcoal\Http\Commons\Enums\ContentType;

/**
 * Class BaseEncoder
 * @package Charcoal\Http\Client\Encoding
 */
class BaseEncoder implements ContentTypeEncoderInterface
{
    public static function headerFor(ContentType $contentType): ?string
    {
        return match ($contentType) {
            ContentType::Json => "application/json; charset=utf-8",
            ContentType::FormSubmit => "application/x-www-form-urlencoded; charset=utf-8",
            default => null,
        };
    }

    public static function encode(Payload|array $data, ContentType $contentType): string|false
    {
        $data = is_array($data) ? $data : $data->getArray();
        return match ($contentType) {
            ContentType::Json => json_encode($data),
            ContentType::FormSubmit => http_build_query($data),
            default => false,
        };
    }

    public static function decode(string $data, ContentType $contentType): array|false
    {
        if ($contentType === ContentType::Json) {
            return json_decode($data, true);
        }

        if ($contentType === ContentType::FormSubmit) {
            $payload = [];
            parse_str($data, $payload);
            return $payload;
        }

        return false;
    }
}