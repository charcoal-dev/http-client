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
 * Class BasicAuth
 * @package Charcoal\HTTP\Client\Authorization
 */
class BasicAuth extends AbstractAuthorization
{
    /**
     * @param string $username
     * @param string $password
     */
    public function __construct(
        public readonly string $username,
        public readonly string $password
    )
    {
    }

    /**
     * @param \CurlHandle $ch
     * @return void
     */
    public function registerCh(\CurlHandle $ch): void
    {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->username, $this->password));
    }
}