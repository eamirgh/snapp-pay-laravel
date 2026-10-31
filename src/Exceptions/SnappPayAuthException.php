<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Exceptions;

class SnappPayAuthException extends SnappPayException
{
    public static function invalidCredentials(?string $details = null): self
    {
        $message = 'SnappPay Authentication failed: Invalid Client ID, Client Secret, Username or Password.';
        if ($details) {
            $message .= ' Details: ' . $details;
        }

        return new self($message, 401);
    }

    public static function accessDenied(?string $details = null): self
    {
        $message = 'SnappPay Access Denied: Server IP is not whitelisted by SnappPay operations team.';
        if ($details) {
            $message .= ' Details: ' . $details;
        }

        return new self($message, 403);
    }
}
