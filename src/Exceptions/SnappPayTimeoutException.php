<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Exceptions;

use Throwable;

class SnappPayTimeoutException extends SnappPayException
{
    public function __construct(
        string $message = 'SnappPay request timed out.',
        public readonly ?string $paymentToken = null,
        public readonly ?string $action = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 408, $previous);
    }
}
