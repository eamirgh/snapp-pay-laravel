<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Contracts;

use Eamirgh\SnappPay\DTOs\EligibilityResult;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\DTOs\PaymentTokenResult;
use Eamirgh\SnappPay\DTOs\StatusResult;
use Eamirgh\SnappPay\DTOs\TransactionResult;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;
use Eamirgh\SnappPay\Enums\PaymentMethodType;
use Eamirgh\SnappPay\Exceptions\SnappPayException;

interface SnappPayClientInterface
{
    public function getTokenManager(): TokenManagerInterface;

    /**
     * @param array<PaymentMethodType|string> $paymentMethodTypes
     *
     * @throws SnappPayException
     */
    public function getEligibility(int $amount, array $paymentMethodTypes = []): EligibilityResult;

    /**
     * @throws SnappPayException
     */
    public function getPaymentToken(PaymentTokenRequest $request): PaymentTokenResult;

    /**
     * @throws SnappPayException
     */
    public function verify(string $paymentToken): TransactionResult;

    /**
     * @throws SnappPayException
     */
    public function settle(string $paymentToken): TransactionResult;

    /**
     * @throws SnappPayException
     */
    public function revert(string $paymentToken): TransactionResult;

    /**
     * @throws SnappPayException
     */
    public function getStatus(string $paymentToken): StatusResult;

    /**
     * @throws SnappPayException
     */
    public function cancel(string $paymentToken): TransactionResult;

    /**
     * @throws SnappPayException
     */
    public function update(UpdateOrderRequest $request): TransactionResult;
}
