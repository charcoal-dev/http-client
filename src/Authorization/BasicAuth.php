<?php
/**
 * Part of the "charcoal-dev/http-client" package.
 * @link https://github.com/charcoal-dev/http-client
 */

declare(strict_types=1);

namespace Charcoal\Http\Client\Authorization;

use Charcoal\Http\Client\Contracts\ClientAuthInterface;
use Charcoal\Http\Client\Request;

/**
 * Class BasicAuth
 * @package Charcoal\Http\Client\Authorization
 */
readonly class BasicAuth implements ClientAuthInterface
{
    public function __construct(
        #[\SensitiveParameter]
        public string $username,
        #[\SensitiveParameter]
        public string $password
    )
    {
    }

    /**
     * @param Request $request
     * @return void
     */
    public function setCredentials(Request $request): void
    {
        $request->headers->set("Authorization", "Basic " . base64_encode(
                sprintf("%s:%s", $this->username, $this->password)));
    }
}