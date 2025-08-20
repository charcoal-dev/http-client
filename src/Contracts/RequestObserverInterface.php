<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Contracts;

use Charcoal\Http\Client\Request;
use Charcoal\Http\Client\Response;

/**
 * Interface RequestObserverInterface
 * @package Charcoal\Http\Client\Contracts
 */
interface RequestObserverInterface
{
    /**
     * @param Request $request
     * @param Response|\Throwable $result
     * @return void
     */
    public function onRequestResult(Request $request, Response|\Throwable $result): void;
}