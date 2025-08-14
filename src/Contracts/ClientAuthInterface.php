<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Contracts;

use Charcoal\Http\Client\Request;

/**
 * Interface ClientAuthInterface
 * @package Charcoal\Http\Client\Contracts
 */
interface ClientAuthInterface
{
    public function setCredentials(Request $request): void;
}