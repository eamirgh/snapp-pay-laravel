<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
class EligibilityResult implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly bool $eligible,
        public readonly string $titleMessage,
        public readonly string $description,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            eligible: (bool) ($data['eligible'] ?? false),
            titleMessage: (string) ($data['title_message'] ?? $data['titleMessage'] ?? ''),
            description: (string) ($data['description'] ?? ''),
        );
    }

    public function isEligible(): bool
    {
        return $this->eligible;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eligible' => $this->eligible,
            'title_message' => $this->titleMessage,
            'description' => $this->description,
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
