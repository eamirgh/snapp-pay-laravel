<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Contracts;

use Illuminate\Http\Request;
use Eamirgh\SnappPay\DTOs\CallbackPayload;
use Eamirgh\SnappPay\DTOs\EligibilityResult;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\DTOs\PaymentTokenResult;
use Eamirgh\SnappPay\DTOs\StatusResult;
use Eamirgh\SnappPay\DTOs\TransactionResult;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;
use Eamirgh\SnappPay\Enums\PaymentMethodType;
use Eamirgh\SnappPay\Exceptions\SnappPayException;

interface SnappPayGateway
{
    /**
     * Check merchant & cart installment eligibility.
     *
     * @param int $amount Cart total in IRR
     * @param array<PaymentMethodType|string> $paymentMethodTypes
     *
     * @throws SnappPayException
     */
    public function getEligibility(int $amount, array $paymentMethodTypes = []): EligibilityResult;

    /**
     * Request a payment token and redirect URL using full request DTO.
     *
     * @throws SnappPayException
     */
    public function getPaymentToken(PaymentTokenRequest $request): PaymentTokenResult;

    /**
     * Request payment directly for an application domain model (Order, Invoice, etc.).
     *
     * @param array<PaymentMethodType|string>|null $forcedPaymentMethodTypes
     *
     * @throws SnappPayException
     */
    public function requestPaymentFor(SnappPayable $payable, ?array $forcedPaymentMethodTypes = null): PaymentTokenResult;

    /**
     * Quick payment token builder for simple baskets.
     *
     * @param array<mixed> $items
     * @param array<PaymentMethodType|string>|null $forcedPaymentMethodTypes
     *
     * @throws SnappPayException
     */
    public function quickToken(
        int $amount,
        string $mobile,
        string $returnURL,
        string $transactionId,
        array $items = [],
        int $discountAmount = 0,
        int $externalSourceAmount = 0,
        ?array $forcedPaymentMethodTypes = null
    ): PaymentTokenResult;

    /**
     * Verify payment directly after callback.
     *
     * @throws SnappPayException
     */
    public function verify(string $paymentToken): TransactionResult;

    /**
     * Settle payment directly after verification.
     *
     * @throws SnappPayException
     */
    public function settle(string $paymentToken): TransactionResult;

    /**
     * Revert payment before settlement.
     *
     * @throws SnappPayException
     */
    public function revert(string $paymentToken): TransactionResult;

    /**
     * Get payment status.
     *
     * @throws SnappPayException
     */
    public function getStatus(string $paymentToken): StatusResult;

    /**
     * Cancel an order in SETTLE status.
     *
     * @throws SnappPayException
     */
    public function cancel(string $paymentToken): TransactionResult;

    /**
     * Update order cart or sum post-settlement.
     *
     * @throws SnappPayException
     */
    public function update(UpdateOrderRequest $request): TransactionResult;

    /**
     * Resilient Verify workflow (SnappPay Documentation Section 11).
     *
     * @throws SnappPayException
     */
    public function verifyWithResilience(string $paymentToken, ?int $maxRetries = null): TransactionResult;

    /**
     * Resilient Settle workflow (SnappPay Documentation Section 12).
     *
     * @throws SnappPayException
     */
    public function settleWithResilience(string $paymentToken, ?int $maxRetries = null): TransactionResult;

    /**
     * Convenience method to verify and immediately settle with full resilience.
     *
     * @throws SnappPayException
     */
    public function verifyAndSettle(string $paymentToken): TransactionResult;

    /**
     * Parse incoming callback request into a typed DTO.
     */
    public function parseCallback(Request|array $request): CallbackPayload;
}
