<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Exceptions;

use Throwable;

class SnappPayApiException extends SnappPayException
{
    /**
     * @param mixed $errorData
     */
    public function __construct(
        string $message,
        protected int|string $errorCode = 0,
        protected mixed $errorData = null,
        protected int $statusCode = 0,
        protected ?string $rawResponse = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, is_int($errorCode) ? $errorCode : 0, $previous);
    }

    public function getErrorCode(): int|string
    {
        return $this->errorCode;
    }

    public function getErrorData(): mixed
    {
        return $this->errorData;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRawResponse(): ?string
    {
        return $this->rawResponse;
    }

    /**
     * Create exception from SnappPay JSON error envelope.
     *
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload, int $statusCode, ?string $raw = null): self
    {
        $errorData = $payload['errorData'] ?? [];
        $message = is_array($errorData) && !empty($errorData['message'])
            ? (string) $errorData['message']
            : ($payload['message'] ?? 'SnappPay API returned an error.');

        $errorCode = is_array($errorData) && isset($errorData['errorCode'])
            ? $errorData['errorCode']
            : ($payload['errorCode'] ?? $statusCode);

        $data = is_array($errorData) && array_key_exists('data', $errorData)
            ? $errorData['data']
            : null;

        return new self($message, $errorCode, $data, $statusCode, $raw);
    }
}
