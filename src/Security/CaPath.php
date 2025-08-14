<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Security;

use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Http\Client\Contracts\CurlConfigurableInterface;
use Charcoal\Http\Client\Exception\SecureRequestException;
use Charcoal\Http\Client\Request;

/**
 * Class CaPath
 * @package Charcoal\Http\Client\Security
 */
readonly class CaPath implements CurlConfigurableInterface
{
    public bool $isFile;

    use NoDumpTrait;

    /**
     * @throws SecureRequestException
     */
    public function __construct(
        #[\SensitiveParameter]
        public string $path
    )
    {
        $path = realpath($path);
        if (!$path || !is_readable($path)) {
            throw new SecureRequestException('Path to CA certificate(s) is invalid or not readable', 141);
        }

        match (true) {
            is_file($path) => $this->isFile = true,
            is_dir($path) => $this->isFile = false,
            default => throw new SecureRequestException('Path to CA certificate(s) is invalid or not readable', 142),
        };
    }

    /**
     * @param \CurlHandle $ch
     * @param Request $request
     * @return void
     */
    public function applyPolicy(\CurlHandle $ch, Request $request): void
    {
        match ($this->isFile) {
            true => curl_setopt($ch, CURLOPT_CAINFO, $this->path),
            false => curl_setopt($ch, CURLOPT_CAPATH, $this->path),
        };
    }
}