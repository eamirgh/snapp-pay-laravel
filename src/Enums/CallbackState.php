<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Enums;

enum CallbackState: string
{
    case OK = 'OK';
    case FAILED = 'FAILED';

    public function isOk(): bool
    {
        return $this === self::OK;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}
