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

namespace Charcoal\HTTP\Client\Authorization;

/**
 * Class AbstractAuthorization
 * @package Charcoal\HTTP\Client\Authorization
 */
abstract class AbstractAuthorization
{
    abstract public function registerCh(\CurlHandle $ch): void;
}