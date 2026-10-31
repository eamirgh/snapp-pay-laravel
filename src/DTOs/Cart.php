<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\DTOs;

use Eamirgh\SnappPay\Contracts\SnappPayCartInterface;
use Eamirgh\SnappPay\Contracts\SnappPayCartItemInterface;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
class Cart implements Arrayable, JsonSerializable
{
    /**
     * @param int|string $cartId
     * @param array<CartItem> $cartItems
     * @param bool $isShipmentIncluded
     * @param bool $isTaxIncluded
     * @param int $shippingAmount
     * @param int $taxAmount
     * @param int|null $totalAmount
     */
    public function __construct(
        public readonly int|string $cartId,
        public array $cartItems = [],
        public readonly bool $isShipmentIncluded = true,
        public readonly bool $isTaxIncluded = true,
        public readonly int $shippingAmount = 0,
        public readonly int $taxAmount = 0,
        public readonly ?int $totalAmount = null,
    ) {
    }

    /**
     * @param array<CartItem|SnappPayCartItemInterface|array<string, mixed>> $cartItems
     */
    public static function make(
        int|string $cartId = 0,
        array $cartItems = [],
        bool $isShipmentIncluded = true,
        bool $isTaxIncluded = true,
        int $shippingAmount = 0,
        int $taxAmount = 0,
        ?int $totalAmount = null,
    ): self {
        $items = array_map(
            static fn ($item): CartItem => CartItem::adapt($item),
            $cartItems
        );

        return new self(
            cartId: $cartId,
            cartItems: $items,
            isShipmentIncluded: $isShipmentIncluded,
            isTaxIncluded: $isTaxIncluded,
            shippingAmount: $shippingAmount,
            taxAmount: $taxAmount,
            totalAmount: $totalAmount,
        );
    }

    /**
     * Adapt any supported input into a Cart DTO.
     */
    public static function adapt(mixed $cart): self
    {
        if ($cart instanceof self) {
            return $cart;
        }

        if ($cart instanceof SnappPayCartInterface) {
            return $cart->toSnappPayCart();
        }

        if (is_array($cart)) {
            return self::fromArray($cart);
        }

        throw new InvalidArgumentException('Cannot adapt given value into a SnappPay Cart.');
    }

    /**
     * Create Cart from SnappPayCartInterface contract.
     */
    public static function fromContract(SnappPayCartInterface $cart): self
    {
        $items = array_map(
            static fn ($item): CartItem => CartItem::adapt($item),
            $cart->getSnappPayCartItems()
        );

        return new self(
            cartId: $cart->getSnappPayCartId(),
            cartItems: $items,
            isShipmentIncluded: $cart->isSnappPayShipmentIncluded(),
            isTaxIncluded: $cart->isSnappPayTaxIncluded(),
            shippingAmount: $cart->getSnappPayShippingAmount(),
            taxAmount: $cart->getSnappPayTaxAmount(),
            totalAmount: $cart->getSnappPayTotalAmount(),
        );
    }

    public function addItem(CartItem|SnappPayCartItemInterface $item): self
    {
        $this->cartItems[] = CartItem::adapt($item);

        return $this;
    }

    /**
     * Calculate total cart amount according to SnappPay rules:
     * sum(count * amount) + (isShipmentIncluded ? 0 : shippingAmount) + (isTaxIncluded ? 0 : taxAmount).
     */
    public function calculateTotal(): int
    {
        $itemsSum = 0;
        foreach ($this->cartItems as $item) {
            $itemsSum += $item->totalAmount();
        }

        $shipping = $this->isShipmentIncluded ? 0 : $this->shippingAmount;
        $tax = $this->isTaxIncluded ? 0 : $this->taxAmount;

        return $itemsSum + $shipping + $tax;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount ?? $this->calculateTotal();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        if (!empty($data['cartItems']) && is_array($data['cartItems'])) {
            foreach ($data['cartItems'] as $itemData) {
                $items[] = CartItem::adapt($itemData);
            }
        }

        return new self(
            cartId: $data['cartId'] ?? 0,
            cartItems: $items,
            isShipmentIncluded: (bool) ($data['isShipmentIncluded'] ?? true),
            isTaxIncluded: (bool) ($data['isTaxIncluded'] ?? true),
            shippingAmount: (int) ($data['shippingAmount'] ?? 0),
            taxAmount: (int) ($data['taxAmount'] ?? 0),
            totalAmount: isset($data['totalAmount']) ? (int) $data['totalAmount'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cartId' => is_numeric($this->cartId) ? (int) $this->cartId : $this->cartId,
            'isShipmentIncluded' => $this->isShipmentIncluded,
            'isTaxIncluded' => $this->isTaxIncluded,
            'shippingAmount' => $this->shippingAmount,
            'taxAmount' => $this->taxAmount,
            'totalAmount' => $this->getTotalAmount(),
            'cartItems' => array_map(
                static fn (CartItem $item): array => $item->toArray(),
                $this->cartItems
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
