<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Enums;

enum PaymentStatus: string
{
    case SETTLE = 'SETTLE';
    case CANCEL = 'CANCEL';
    case VERIFY = 'VERIFY';
    case PENDING = 'PENDING';
    case REVERT = 'REVERT';

    public function isSettled(): bool
    {
        return $this === self::SETTLE;
    }

    public function isVerified(): bool
    {
        return $this === self::VERIFY;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCEL;
    }

    public function isReverted(): bool
    {
        return $this === self::REVERT;
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
