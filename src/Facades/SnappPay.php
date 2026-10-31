<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Eamirgh\SnappPay\Contracts\SnappPayable;
use Eamirgh\SnappPay\DTOs\CallbackPayload;
use Eamirgh\SnappPay\DTOs\EligibilityResult;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\DTOs\PaymentTokenResult;
use Eamirgh\SnappPay\DTOs\StatusResult;
use Eamirgh\SnappPay\DTOs\TransactionResult;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;
use Eamirgh\SnappPay\Enums\PaymentMethodType;
use Eamirgh\SnappPay\Services\SnappPayManager;
use Eamirgh\SnappPay\Services\SnappPayService;

/**
 * @method static SnappPayService connection(?string $name = null)
 * @method static SnappPayService build(array $customConfig)
 * @method static EligibilityResult getEligibility(int $amount, array $paymentMethodTypes = [])
 * @method static PaymentTokenResult getPaymentToken(PaymentTokenRequest $request)
 * @method static PaymentTokenResult requestPaymentFor(SnappPayable $payable, ?array $forcedPaymentMethodTypes = null)
 * @method static PaymentTokenResult quickToken(int $amount, string $mobile, string $returnURL, string $transactionId, array $items = [], int $discountAmount = 0, int $externalSourceAmount = 0, ?array $forcedPaymentMethodTypes = null)
 * @method static TransactionResult verify(string $paymentToken)
 * @method static TransactionResult settle(string $paymentToken)
 * @method static TransactionResult revert(string $paymentToken)
 * @method static StatusResult getStatus(string $paymentToken)
 * @method static TransactionResult cancel(string $paymentToken)
 * @method static TransactionResult update(UpdateOrderRequest $request)
 * @method static TransactionResult verifyWithResilience(string $paymentToken, ?int $maxRetries = null)
 * @method static TransactionResult settleWithResilience(string $paymentToken, ?int $maxRetries = null)
 * @method static TransactionResult verifyAndSettle(string $paymentToken)
 * @method static CallbackPayload parseCallback(Request|array $request)
 *
 * @see SnappPayManager
 * @see SnappPayService
 */
class SnappPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'snapppay';
    }
}
