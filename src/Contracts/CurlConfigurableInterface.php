<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Contracts;

use Charcoal\Http\Client\Request;

/**
 * Interface CurlConfigurableInterface
 * @package Charcoal\Http\Client\Contracts
 */
interface CurlConfigurableInterface
{
    public function applyPolicy(\CurlHandle $ch, Request $request): void;
}