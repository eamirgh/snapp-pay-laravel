<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Eamirgh\SnappPay\Enums\PaymentStatus;

/**
 * @implements Arrayable<string, mixed>
 */
class StatusResult implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $transactionId,
        public readonly PaymentStatus $status,
        public readonly int $amount,
        public readonly ?string $paymentToken = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, ?string $paymentToken = null): self
    {
        $statusStr = (string) ($data['status'] ?? 'PENDING');
        $status = PaymentStatus::tryFrom(strtoupper($statusStr)) ?? PaymentStatus::PENDING;

        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            status: $status,
            amount: (int) ($data['amount'] ?? 0),
            paymentToken: $paymentToken,
        );
    }

    public function isSettled(): bool
    {
        return $this->status->isSettled();
    }

    public function isVerified(): bool
    {
        return $this->status->isVerified();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public function isReverted(): bool
    {
        return $this->status->isReverted();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'status' => $this->status->value,
            'amount' => $this->amount,
            'paymentToken' => $this->paymentToken,
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
