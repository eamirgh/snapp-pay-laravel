<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Contracts;

use Eamirgh\SnappPay\DTOs\Cart;

/**
 * Interface for application models (e.g. Order, Invoice, Booking)
 * that can be paid via SnappPay.
 */
interface SnappPayable
{
    /**
     * Unique transaction identifier (5-10 digits or >10 chars with >=1 letter).
     */
    public function getSnappPayTransactionId(): string;

    /**
     * Net order amount in IRR (Rials).
     */
    public function getSnappPayAmount(): int;

    /**
     * Customer mobile phone number.
     */
    public function getSnappPayMobile(): string;

    /**
     * Whitelisted callback URL for POST outcome.
     */
    public function getSnappPayReturnUrl(): string;

    /**
     * Itemized carts. If empty, the package generates a default cart for the order.
     *
     * @return array<Cart>
     */
    public function toSnappPayCartList(): array;

    /**
     * Discount applied in IRR.
     */
    public function getSnappPayDiscountAmount(): int;

    /**
     * Amount paid via external source (wallet, voucher) in IRR.
     */
    public function getSnappPayExternalSourceAmount(): int;
}
