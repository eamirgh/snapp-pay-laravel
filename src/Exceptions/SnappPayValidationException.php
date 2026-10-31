<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Exceptions;

class SnappPayValidationException extends SnappPayException
{
    public static function invalidTransactionId(string $transactionId): self
    {
        return new self(
            "Invalid transactionId [{$transactionId}]. SnappPay requires transactionId to be 5-10 numeric digits, or if longer than 10 characters, contain at least one alphabetical character."
        );
    }

    public static function amountMismatch(int $declaredAmount, int $calculatedAmount): self
    {
        return new self(
            "Order amount mismatch: declared amount is [{$declaredAmount}] IRR but sum of carts minus discounts and external sources equals [{$calculatedAmount}] IRR."
        );
    }

    public static function updateAmountTooHigh(int $newAmount, int $originalAmount): self
    {
        return new self(
            "Update order amount [{$newAmount}] IRR must be strictly less than original order amount [{$originalAmount}] IRR."
        );
    }
}
