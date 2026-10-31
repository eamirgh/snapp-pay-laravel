<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Contracts;

use Eamirgh\SnappPay\Exceptions\SnappPayAuthException;

interface TokenManagerInterface
{
    /**
     * Retrieve a valid Bearer access token.
     *
     * @throws SnappPayAuthException
     */
    public function getToken(bool $forceFresh = false): string;

    /**
     * Clear token from memory and persistent cache.
     */
    public function clearToken(): void;
}
