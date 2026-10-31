<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Traits;

use Eamirgh\SnappPay\Contracts\SnappPayCartInterface;
use Eamirgh\SnappPay\Contracts\SnappPayCartItemInterface;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;

/**
 * Trait providing conventional defaults for Eloquent models
 * implementing SnappPayable.
 */
trait HasSnappPayPayment
{
    public function getSnappPayTransactionId(): string
    {
        $id = (string) ($this->snapppay_transaction_id ?? $this->transaction_id ?? $this->order_number ?? $this->getKey());

        // Ensure length >= 5 and if > 10 contains a letter
        if (strlen($id) < 5) {
            return str_pad($id, 5, '0', STR_PAD_LEFT);
        }

        if (strlen($id) > 10 && !preg_match('/[a-zA-Z]/', $id)) {
            return 'ORD-' . $id;
        }

        return $id;
    }

    public function getSnappPayAmount(): int
    {
        return (int) ($this->total_amount ?? $this->amount ?? $this->price ?? 0);
    }

    public function getSnappPayMobile(): string
    {
        return (string) ($this->mobile ?? $this->phone ?? $this->customer_mobile ?? $this->user?->mobile ?? '');
    }

    public function getSnappPayReturnUrl(): string
    {
        if (isset($this->return_url)) {
            return (string) $this->return_url;
        }

        return route('payment.snapppay.callback', ['order' => $this->getKey()]);
    }

    public function getSnappPayDiscountAmount(): int
    {
        return (int) ($this->discount_amount ?? $this->discount ?? 0);
    }

    public function getSnappPayExternalSourceAmount(): int
    {
        return (int) ($this->external_source_amount ?? $this->wallet_amount ?? 0);
    }

    /**
     * @return array<Cart>
     */
    public function toSnappPayCartList(): array
    {
        // 1. Direct Cart model relation
        if (isset($this->cart) && $this->cart instanceof SnappPayCartInterface) {
            return [$this->cart->toSnappPayCart()];
        }

        if (isset($this->carts) && is_iterable($this->carts)) {
            $carts = [];
            foreach ($this->carts as $c) {
                if ($c instanceof SnappPayCartInterface) {
                    $carts[] = $c->toSnappPayCart();
                }
            }
            if (!empty($carts)) {
                return $carts;
            }
        }

        // 2. Line Items relation
        if (!method_exists($this, 'items') && !isset($this->items)) {
            return [];
        }

        $items = $this->items;
        if (empty($items)) {
            return [];
        }

        $cartItems = [];
        foreach ($items as $item) {
            if ($item instanceof SnappPayCartItemInterface || $item instanceof CartItem || is_array($item)) {
                $cartItems[] = CartItem::adapt($item);
            }
        }

        if (empty($cartItems)) {
            return [];
        }

        return [
            Cart::make(
                cartId: $this->getSnappPayTransactionId(),
                cartItems: $cartItems,
                isShipmentIncluded: true,
                isTaxIncluded: true
            ),
        ];
    }
}
