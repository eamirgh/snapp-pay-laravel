<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Enums;

enum PaymentMethodType: string
{
    case POSTPAID = 'POSTPAID';
    case INSTALLMENT = 'INSTALLMENT';
    case FINANCING = 'FINANCING';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
