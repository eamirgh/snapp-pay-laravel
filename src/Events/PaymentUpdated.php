<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Events;

use Illuminate\Queue\SerializesModels;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;

class PaymentUpdated
{
    use SerializesModels;

    public function __construct(
        public readonly string $paymentToken,
        public readonly string $transactionId,
        public readonly UpdateOrderRequest $request,
    ) {
    }
}
