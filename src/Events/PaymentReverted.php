<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Events;

use Illuminate\Queue\SerializesModels;

class PaymentReverted
{
    use SerializesModels;

    public function __construct(
        public readonly string $paymentToken,
        public readonly string $transactionId,
    ) {
    }
}
