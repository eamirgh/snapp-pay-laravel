<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\DTOs;

use Eamirgh\SnappPay\Contracts\SnappPayCartItemInterface;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
class CartItem implements Arrayable, JsonSerializable
{
    /**
     * @param int|string $id
     * @param string $name
     * @param int $count
     * @param int $amount Unit price in IRR
     * @param string $category
     * @param int $commissionType Default 100 for non-diversified merchant catalogs
     */
    public function __construct(
        public readonly int|string $id,
        public readonly string $name,
        public readonly int $count,
        public readonly int $amount,
        public readonly string $category,
        public readonly int $commissionType = 100,
    ) {
    }

    public static function make(
        int|string $id,
        string $name,
        int $count,
        int $amount,
        string $category,
        int $commissionType = 100,
    ): self {
        return new self($id, $name, $count, $amount, $category, $commissionType);
    }

    /**
     * Adapt any supported input into a CartItem DTO.
     */
    public static function adapt(mixed $item): self
    {
        if ($item instanceof self) {
            return $item;
        }

        if ($item instanceof SnappPayCartItemInterface) {
            return $item->toSnappPayCartItem();
        }

        if (is_array($item)) {
            return self::fromArray($item);
        }

        throw new InvalidArgumentException('Cannot adapt given value into a SnappPay CartItem.');
    }

    /**
     * Create CartItem from contract interface.
     */
    public static function fromContract(SnappPayCartItemInterface $item): self
    {
        return new self(
            id: $item->getSnappPayItemId(),
            name: $item->getSnappPayItemName(),
            count: $item->getSnappPayItemCount(),
            amount: $item->getSnappPayItemAmount(),
            category: $item->getSnappPayItemCategory(),
            commissionType: $item->getSnappPayItemCommissionType(),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 0,
            name: (string) ($data['name'] ?? ''),
            count: (int) ($data['count'] ?? 1),
            amount: (int) ($data['amount'] ?? 0),
            category: (string) ($data['category'] ?? 'default'),
            commissionType: (int) ($data['commissionType'] ?? 100),
        );
    }

    /**
     * Total amount for this item line (count * unit amount).
     */
    public function totalAmount(): int
    {
        return $this->count * $this->amount;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => is_numeric($this->id) ? (int) $this->id : $this->id,
            'name' => $this->name,
            'count' => $this->count,
            'amount' => $this->amount,
            'category' => $this->category,
            'commissionType' => $this->commissionType,
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
