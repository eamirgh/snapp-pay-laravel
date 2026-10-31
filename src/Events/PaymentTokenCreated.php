<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Events;

use Illuminate\Queue\SerializesModels;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\DTOs\PaymentTokenResult;

class PaymentTokenCreated
{
    use SerializesModels;

    public function __construct(
        public readonly PaymentTokenResult $result,
        public readonly PaymentTokenRequest $request,
    ) {
    }
}
