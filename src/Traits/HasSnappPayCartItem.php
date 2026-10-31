<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Traits;

use Eamirgh\SnappPay\DTOs\CartItem;

/**
 * Trait providing conventional property mapping for Eloquent models
 * implementing SnappPayCartItemInterface.
 */
trait HasSnappPayCartItem
{
    public function getSnappPayItemId(): int|string
    {
        return $this->product_id ?? $this->item_id ?? $this->getKey() ?? 0;
    }

    public function getSnappPayItemName(): string
    {
        return (string) ($this->name ?? $this->title ?? $this->product_name ?? 'Item');
    }

    public function getSnappPayItemCount(): int
    {
        return (int) ($this->quantity ?? $this->count ?? $this->qty ?? 1);
    }

    public function getSnappPayItemAmount(): int
    {
        return (int) ($this->unit_price ?? $this->price ?? $this->amount ?? 0);
    }

    public function getSnappPayItemCategory(): string
    {
        return (string) ($this->category ?? $this->category_name ?? $this->product?->category ?? 'General');
    }

    public function getSnappPayItemCommissionType(): int
    {
        return (int) ($this->commission_type ?? 100);
    }

    public function toSnappPayCartItem(): CartItem
    {
        return CartItem::fromContract($this);
    }
}
