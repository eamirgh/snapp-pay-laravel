<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Contracts;

use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;

/**
 * Contract for application Cart / Basket models.
 */
interface SnappPayCartInterface
{
    /**
     * Unique cart identifier.
     */
    public function getSnappPayCartId(): int|string;

    /**
     * Cart line items (array of SnappPayCartItemInterface, CartItem, or associative arrays).
     *
     * @return array<SnappPayCartItemInterface|CartItem|array<string, mixed>>
     */
    public function getSnappPayCartItems(): array;

    /**
     * Whether shipping cost is already included in item amounts.
     */
    public function isSnappPayShipmentIncluded(): bool;

    /**
     * Whether tax amount is already included in item amounts.
     */
    public function isSnappPayTaxIncluded(): bool;

    /**
     * Shipping amount in IRR (added to total when isSnappPayShipmentIncluded is false).
     */
    public function getSnappPayShippingAmount(): int;

    /**
     * Tax amount in IRR (added to total when isSnappPayTaxIncluded is false).
     */
    public function getSnappPayTaxAmount(): int;

    /**
     * Explicit total amount if pre-calculated, or null to auto-compute from formula.
     */
    public function getSnappPayTotalAmount(): ?int;

    /**
     * Convert this model to a package Cart DTO.
     */
    public function toSnappPayCart(): Cart;
}
