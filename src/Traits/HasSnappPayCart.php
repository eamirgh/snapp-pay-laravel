<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Traits;

use Eamirgh\SnappPay\DTOs\Cart;

/**
 * Trait providing conventional property mapping for Eloquent models
 * implementing SnappPayCartInterface.
 */
trait HasSnappPayCart
{
    public function getSnappPayCartId(): int|string
    {
        return $this->cart_id ?? $this->getKey() ?? 0;
    }

    public function getSnappPayCartItems(): array
    {
        if (method_exists($this, 'items') && $this->relationLoaded('items')) {
            return $this->items->all();
        }

        if (isset($this->items)) {
            return is_array($this->items) ? $this->items : iterator_to_array($this->items);
        }

        if (isset($this->cart_items)) {
            return is_array($this->cart_items) ? $this->cart_items : iterator_to_array($this->cart_items);
        }

        return [];
    }

    public function isSnappPayShipmentIncluded(): bool
    {
        return (bool) ($this->is_shipment_included ?? true);
    }

    public function isSnappPayTaxIncluded(): bool
    {
        return (bool) ($this->is_tax_included ?? true);
    }

    public function getSnappPayShippingAmount(): int
    {
        return (int) ($this->shipping_amount ?? $this->shipping_cost ?? 0);
    }

    public function getSnappPayTaxAmount(): int
    {
        return (int) ($this->tax_amount ?? $this->tax ?? 0);
    }

    public function getSnappPayTotalAmount(): ?int
    {
        return isset($this->total_amount) ? (int) $this->total_amount : null;
    }

    public function toSnappPayCart(): Cart
    {
        return Cart::fromContract($this);
    }
}
