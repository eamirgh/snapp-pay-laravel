<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
class TransactionResult implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $transactionId,
        public readonly ?string $paymentToken = null,
        public readonly ?string $action = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, ?string $paymentToken = null, ?string $action = null): self
    {
        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            paymentToken: $paymentToken,
            action: $action,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'paymentToken' => $this->paymentToken,
            'action' => $this->action,
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
