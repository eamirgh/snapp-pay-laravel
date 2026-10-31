<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\DTOs;

use Eamirgh\SnappPay\Contracts\SnappPayCartInterface;
use Eamirgh\SnappPay\Enums\PaymentMethodType;
use Eamirgh\SnappPay\Exceptions\SnappPayValidationException;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
class PaymentTokenRequest implements Arrayable, JsonSerializable
{
    /**
     * @param int $amount Net order amount in IRR (sum(carts) - discountAmount - externalSourceAmount)
     * @param array<Cart> $cartList
     * @param int $discountAmount Discount amount in IRR
     * @param int $externalSourceAmount Paid via external source (wallet/voucher) in IRR
     * @param string $mobile Customer mobile number
     * @param string $returnURL Whitelisted callback URL (must accept POST)
     * @param string $transactionId Unique ID (5-10 digits, or >10 chars with >=1 letter)
     * @param array<PaymentMethodType|string>|null $forcedPaymentMethodTypes
     */
    public function __construct(
        public readonly int $amount,
        public readonly array $cartList,
        public readonly int $discountAmount,
        public readonly int $externalSourceAmount,
        public readonly string $mobile,
        public readonly string $returnURL,
        public readonly string $transactionId,
        public readonly ?array $forcedPaymentMethodTypes = null,
    ) {
    }

    /**
     * @param array<Cart|SnappPayCartInterface|array<string, mixed>> $cartList
     * @param array<PaymentMethodType|string>|null $forcedPaymentMethodTypes
     */
    public static function make(
        int $amount,
        array $cartList,
        string $mobile,
        string $returnURL,
        string $transactionId,
        int $discountAmount = 0,
        int $externalSourceAmount = 0,
        ?array $forcedPaymentMethodTypes = null,
        bool $validate = true
    ): self {
        $adaptedCarts = array_map(
            static fn ($cart): Cart => Cart::adapt($cart),
            $cartList
        );

        $request = new self(
            amount: $amount,
            cartList: $adaptedCarts,
            discountAmount: $discountAmount,
            externalSourceAmount: $externalSourceAmount,
            mobile: $mobile,
            returnURL: $returnURL,
            transactionId: $transactionId,
            forcedPaymentMethodTypes: $forcedPaymentMethodTypes,
        );

        if ($validate) {
            $request->validate();
        }

        return $request;
    }

    /**
     * Validate payload against SnappPay requirements.
     *
     * @throws SnappPayValidationException
     */
    public function validate(): void
    {
        $this->validateTransactionId($this->transactionId);
        $this->validateAmounts();
    }

    /**
     * Transaction ID validation:
     * - Length must be >= 5 chars
     * - If length > 10 chars, must contain at least one alphabetical letter [a-zA-Z].
     */
    public function validateTransactionId(string $transactionId): void
    {
        $len = strlen($transactionId);

        if ($len < 5) {
            throw SnappPayValidationException::invalidTransactionId($transactionId);
        }

        if ($len > 10 && !preg_match('/[a-zA-Z]/', $transactionId)) {
            throw SnappPayValidationException::invalidTransactionId($transactionId);
        }
    }

    /**
     * Amount calculation validation:
     * sum(cart totals) - discountAmount - externalSourceAmount == amount.
     */
    public function validateAmounts(): void
    {
        if (empty($this->cartList)) {
            return;
        }

        $cartsSum = 0;
        foreach ($this->cartList as $cart) {
            $cartsSum += $cart->getTotalAmount();
        }

        $expectedAmount = $cartsSum - $this->discountAmount - $this->externalSourceAmount;

        if ($expectedAmount !== $this->amount) {
            throw SnappPayValidationException::amountMismatch($this->amount, $expectedAmount);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $validate = true): self
    {
        $carts = [];
        if (!empty($data['cartList']) && is_array($data['cartList'])) {
            foreach ($data['cartList'] as $cartData) {
                $carts[] = Cart::adapt($cartData);
            }
        }

        $request = new self(
            amount: (int) ($data['amount'] ?? 0),
            cartList: $carts,
            discountAmount: (int) ($data['discountAmount'] ?? 0),
            externalSourceAmount: (int) ($data['externalSourceAmount'] ?? 0),
            mobile: (string) ($data['mobile'] ?? ''),
            returnURL: (string) ($data['returnURL'] ?? ''),
            transactionId: (string) ($data['transactionId'] ?? ''),
            forcedPaymentMethodTypes: $data['forcedPaymentMethodTypes'] ?? null,
        );

        if ($validate) {
            $request->validate();
        }

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'amount' => $this->amount,
            'cartList' => array_map(
                static fn (Cart $cart): array => $cart->toArray(),
                $this->cartList
            ),
            'discountAmount' => $this->discountAmount,
            'externalSourceAmount' => $this->externalSourceAmount,
            'mobile' => $this->mobile,
            'returnURL' => $this->returnURL,
            'transactionId' => $this->transactionId,
        ];

        if ($this->forcedPaymentMethodTypes !== null) {
            $payload['forcedPaymentMethodTypes'] = array_map(
                static fn ($method) => $method instanceof PaymentMethodType ? $method->value : (string) $method,
                $this->forcedPaymentMethodTypes
            );
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
