<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Contracts;

use Eamirgh\SnappPay\DTOs\CartItem;

/**
 * Contract for application order line items, products, or cart items.
 */
interface SnappPayCartItemInterface
{
    /**
     * Unique line item / SKU identifier.
     */
    public function getSnappPayItemId(): int|string;

    /**
     * Product / item display name.
     */
    public function getSnappPayItemName(): string;

    /**
     * Purchased quantity / count.
     */
    public function getSnappPayItemCount(): int;

    /**
     * Unit price in IRR (Rials).
     */
    public function getSnappPayItemAmount(): int;

    /**
     * Product category name.
     */
    public function getSnappPayItemCategory(): string;

    /**
     * SnappPay commission type (default 100).
     */
    public function getSnappPayItemCommissionType(): int;

    /**
     * Convert this model to a package CartItem DTO.
     */
    public function toSnappPayCartItem(): CartItem;
}
