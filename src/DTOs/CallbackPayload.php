<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use JsonSerializable;
use Eamirgh\SnappPay\Enums\CallbackState;

/**
 * @implements Arrayable<string, mixed>
 */
class CallbackPayload implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $transactionId,
        public readonly CallbackState $state,
        public readonly int $amount,
        public readonly array $raw = [],
    ) {
    }

    /**
     * Parse incoming HTTP request from SnappPay callback.
     * SnappPay POSTs form-data or JSON with transactionId, state, amount.
     */
    public static function fromRequest(Request|array $request): self
    {
        $data = $request instanceof Request ? $request->all() : $request;

        $stateStr = (string) ($data['state'] ?? 'FAILED');
        $state = CallbackState::tryFrom(strtoupper($stateStr)) ?? CallbackState::FAILED;

        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            state: $state,
            amount: (int) ($data['amount'] ?? 0),
            raw: $data,
        );
    }

    public function isOk(): bool
    {
        return $this->state->isOk();
    }

    public function isFailed(): bool
    {
        return $this->state->isFailed();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'state' => $this->state->value,
            'amount' => $this->amount,
            'raw' => $this->raw,
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
