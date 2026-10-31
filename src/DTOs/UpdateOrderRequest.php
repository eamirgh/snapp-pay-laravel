<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\DTOs;

use Eamirgh\SnappPay\Contracts\SnappPayCartInterface;
use Eamirgh\SnappPay\Exceptions\SnappPayValidationException;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
class UpdateOrderRequest implements Arrayable, JsonSerializable
{
    /**
     * @param string $paymentToken
     * @param int $amount New total order amount in IRR (must be strictly less than original amount)
     * @param array<Cart> $cartList Updated cart list (fully removed products must be omitted)
     * @param int $discountAmount
     * @param int $externalSourceAmount
     */
    public function __construct(
        public readonly string $paymentToken,
        public readonly int $amount,
        public readonly array $cartList,
        public readonly int $discountAmount = 0,
        public readonly int $externalSourceAmount = 0,
    ) {
    }

    /**
     * @param array<Cart|SnappPayCartInterface|array<string, mixed>> $cartList
     */
    public static function make(
        string $paymentToken,
        int $amount,
        array $cartList,
        int $discountAmount = 0,
        int $externalSourceAmount = 0,
        ?int $originalAmount = null,
    ): self {
        $adaptedCarts = array_map(
            static fn ($cart): Cart => Cart::adapt($cart),
            $cartList
        );

        $request = new self(
            paymentToken: $paymentToken,
            amount: $amount,
            cartList: $adaptedCarts,
            discountAmount: $discountAmount,
            externalSourceAmount: $externalSourceAmount,
        );

        if ($originalAmount !== null) {
            $request->validateAgainstOriginalAmount($originalAmount);
        }

        return $request;
    }

    public function validateAgainstOriginalAmount(int $originalAmount): void
    {
        if ($this->amount >= $originalAmount) {
            throw SnappPayValidationException::updateAmountTooHigh($this->amount, $originalAmount);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $carts = [];
        if (!empty($data['cartList']) && is_array($data['cartList'])) {
            foreach ($data['cartList'] as $cartData) {
                $carts[] = Cart::adapt($cartData);
            }
        }

        return new self(
            paymentToken: (string) ($data['paymentToken'] ?? ''),
            amount: (int) ($data['amount'] ?? 0),
            cartList: $carts,
            discountAmount: (int) ($data['discountAmount'] ?? 0),
            externalSourceAmount: (int) ($data['externalSourceAmount'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'paymentToken' => $this->paymentToken,
            'amount' => $this->amount,
            'cartList' => array_map(
                static fn (Cart $cart): array => $cart->toArray(),
                $this->cartList
            ),
            'discountAmount' => $this->discountAmount,
            'externalSourceAmount' => $this->externalSourceAmount,
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
