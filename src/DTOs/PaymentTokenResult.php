<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
class PaymentTokenResult implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $paymentToken,
        public readonly string $paymentPageUrl,
        public readonly ?string $transactionId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, ?string $transactionId = null): self
    {
        return new self(
            paymentToken: (string) ($data['paymentToken'] ?? ''),
            paymentPageUrl: (string) ($data['paymentPageUrl'] ?? ''),
            transactionId: $transactionId ?? ($data['transactionId'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'paymentToken' => $this->paymentToken,
            'paymentPageUrl' => $this->paymentPageUrl,
            'transactionId' => $this->transactionId,
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
