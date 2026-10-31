<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Eamirgh\SnappPay\Contracts\SnappPayClientInterface;
use Eamirgh\SnappPay\Contracts\SnappPayGateway;
use Eamirgh\SnappPay\Contracts\SnappPayable;
use Eamirgh\SnappPay\Contracts\TokenManagerInterface;
use Eamirgh\SnappPay\DTOs\CallbackPayload;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\EligibilityResult;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\DTOs\PaymentTokenResult;
use Eamirgh\SnappPay\DTOs\StatusResult;
use Eamirgh\SnappPay\DTOs\TransactionResult;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;
use Eamirgh\SnappPay\Enums\PaymentMethodType;
use Eamirgh\SnappPay\Enums\PaymentStatus;
use Eamirgh\SnappPay\Events\PaymentCancelled;
use Eamirgh\SnappPay\Events\PaymentReverted;
use Eamirgh\SnappPay\Events\PaymentSettled;
use Eamirgh\SnappPay\Events\PaymentTokenCreated;
use Eamirgh\SnappPay\Events\PaymentUpdated;
use Eamirgh\SnappPay\Events\PaymentVerified;
use Eamirgh\SnappPay\Exceptions\SnappPayApiException;
use Eamirgh\SnappPay\Exceptions\SnappPayException;
use Eamirgh\SnappPay\Exceptions\SnappPayTimeoutException;

class SnappPayService implements SnappPayGateway
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected array $config,
        protected SnappPayClientInterface $client,
        protected ?Dispatcher $events = null
    ) {
    }

    public function client(): SnappPayClientInterface
    {
        return $this->client;
    }

    public function tokenManager(): TokenManagerInterface
    {
        return $this->client->getTokenManager();
    }

    /**
     * Check merchant & cart installment eligibility.
     *
     * @param int $amount Cart total in IRR
     * @param array<PaymentMethodType|string> $paymentMethodTypes
     *
     * @throws SnappPayException
     */
    public function getEligibility(int $amount, array $paymentMethodTypes = []): EligibilityResult
    {
        return $this->client->getEligibility($amount, $paymentMethodTypes);
    }

    /**
     * Request a payment token and redirect URL.
     *
     * @throws SnappPayException
     */
    public function getPaymentToken(PaymentTokenRequest $request): PaymentTokenResult
    {
        $result = $this->client->getPaymentToken($request);

        $this->events?->dispatch(new PaymentTokenCreated($result, $request));

        return $result;
    }

    /**
     * Request payment directly for an application domain model (Order, Invoice, etc.)
     * that implements the SnappPayable contract.
     *
     * @param array<PaymentMethodType|string>|null $forcedPaymentMethodTypes
     *
     * @throws SnappPayException
     */
    public function requestPaymentFor(SnappPayable $payable, ?array $forcedPaymentMethodTypes = null): PaymentTokenResult
    {
        $carts = $payable->toSnappPayCartList();
        $amount = $payable->getSnappPayAmount();
        $discountAmount = $payable->getSnappPayDiscountAmount();
        $externalSourceAmount = $payable->getSnappPayExternalSourceAmount();
        $transactionId = $payable->getSnappPayTransactionId();
        $mobile = $payable->getSnappPayMobile();
        $returnUrl = $payable->getSnappPayReturnUrl();

        if (empty($carts)) {
            $cartItem = CartItem::make(
                id: $transactionId,
                name: 'Order ' . $transactionId,
                count: 1,
                amount: $amount + $discountAmount + $externalSourceAmount,
                category: 'General',
                commissionType: (int) ($this->config['default_commission_type'] ?? 100)
            );

            $carts = [
                Cart::make(
                    cartId: $transactionId,
                    cartItems: [$cartItem],
                    isShipmentIncluded: true,
                    isTaxIncluded: true
                ),
            ];
        }

        $request = PaymentTokenRequest::make(
            amount: $amount,
            cartList: $carts,
            mobile: $mobile,
            returnURL: $returnUrl,
            transactionId: $transactionId,
            discountAmount: $discountAmount,
            externalSourceAmount: $externalSourceAmount,
            forcedPaymentMethodTypes: $forcedPaymentMethodTypes
        );

        return $this->getPaymentToken($request);
    }

    /**
     * Quick payment token builder for simple baskets.
     *
     * @param array<CartItem|array<string, mixed>> $items
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
    ): PaymentTokenResult {
        $cartItems = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                $cartItems[] = $item instanceof CartItem ? $item : CartItem::fromArray($item);
            }
        } else {
            // Fallback: single item representing the order
            $cartItems[] = CartItem::make(
                id: $transactionId,
                name: 'Order ' . $transactionId,
                count: 1,
                amount: $amount + $discountAmount + $externalSourceAmount,
                category: 'General',
                commissionType: (int) ($this->config['default_commission_type'] ?? 100)
            );
        }

        $cart = Cart::make(
            cartId: $transactionId,
            cartItems: $cartItems,
            isShipmentIncluded: true,
            isTaxIncluded: true
        );

        $request = PaymentTokenRequest::make(
            amount: $amount,
            cartList: [$cart],
            mobile: $mobile,
            returnURL: $returnURL,
            transactionId: $transactionId,
            discountAmount: $discountAmount,
            externalSourceAmount: $externalSourceAmount,
            forcedPaymentMethodTypes: $forcedPaymentMethodTypes
        );

        return $this->getPaymentToken($request);
    }

    /**
     * Verify payment directly.
     *
     * @throws SnappPayException
     */
    public function verify(string $paymentToken): TransactionResult
    {
        $result = $this->client->verify($paymentToken);

        $this->events?->dispatch(new PaymentVerified($paymentToken, $result->transactionId));

        return $result;
    }

    /**
     * Settle payment directly.
     *
     * @throws SnappPayException
     */
    public function settle(string $paymentToken): TransactionResult
    {
        $result = $this->client->settle($paymentToken);

        $this->events?->dispatch(new PaymentSettled($paymentToken, $result->transactionId));

        return $result;
    }

    /**
     * Revert payment directly before settlement.
     *
     * @throws SnappPayException
     */
    public function revert(string $paymentToken): TransactionResult
    {
        $result = $this->client->revert($paymentToken);

        $this->events?->dispatch(new PaymentReverted($paymentToken, $result->transactionId));

        return $result;
    }

    /**
     * Get payment status.
     *
     * @throws SnappPayException
     */
    public function getStatus(string $paymentToken): StatusResult
    {
        return $this->client->getStatus($paymentToken);
    }

    /**
     * Cancel an order in SETTLE status.
     *
     * @throws SnappPayException
     */
    public function cancel(string $paymentToken): TransactionResult
    {
        $result = $this->client->cancel($paymentToken);

        $this->events?->dispatch(new PaymentCancelled($paymentToken, $result->transactionId));

        return $result;
    }

    /**
     * Update order cart or sum post-settlement.
     *
     * @throws SnappPayException
     */
    public function update(UpdateOrderRequest $request): TransactionResult
    {
        $result = $this->client->update($request);

        $this->events?->dispatch(new PaymentUpdated($request->paymentToken, $result->transactionId, $request));

        return $result;
    }

    /**
     * Resilient Verify workflow as required by SnappPay Documentation Section 11:
     * 1. Dispatch Verify call with 30s timeout threshold.
     * 2. If timeout or network failure occurs without response, call getStatus.
     * 3. If status is VERIFY, proceed. If PENDING, retry Verify. Otherwise throw.
     *
     * @throws SnappPayException
     */
    public function verifyWithResilience(string $paymentToken, ?int $maxRetries = null): TransactionResult
    {
        $retries = $maxRetries ?? (int) ($this->config['resilience']['max_retries'] ?? 2);
        $retryDelay = (int) ($this->config['resilience']['retry_delay_ms'] ?? 500);

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                $result = $this->client->verify($paymentToken);

                $this->events?->dispatch(new PaymentVerified($paymentToken, $result->transactionId));

                return $result;
            } catch (SnappPayTimeoutException) {
                // Step 2: Handle timeout by querying status
                $status = $this->client->getStatus($paymentToken);

                if ($status->isVerified()) {
                    $result = new TransactionResult($status->transactionId, $paymentToken, 'verify');
                    $this->events?->dispatch(new PaymentVerified($paymentToken, $result->transactionId));

                    return $result;
                }

                if ($status->isPending() && $attempt < $retries) {
                    usleep($retryDelay * 1000);
                    continue;
                }

                throw new SnappPayApiException(
                    message: "Verify timed out and payment status is [{$status->status->value}].",
                    statusCode: 408
                );
            }
        }

        throw new SnappPayApiException('Verify failed after maximum retry attempts.', statusCode: 408);
    }

    /**
     * Resilient Settle workflow as required by SnappPay Documentation Section 12:
     * 1. Dispatch Settle call for verified order.
     * 2. If Settle fails or times out, call getStatus.
     * 3. If status is SETTLE, consider transaction finalized.
     *    If status is VERIFY, re-dispatch Settle.
     *
     * @throws SnappPayException
     */
    public function settleWithResilience(string $paymentToken, ?int $maxRetries = null): TransactionResult
    {
        $retries = $maxRetries ?? (int) ($this->config['resilience']['max_retries'] ?? 2);
        $retryDelay = (int) ($this->config['resilience']['retry_delay_ms'] ?? 500);

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                $result = $this->client->settle($paymentToken);

                $this->events?->dispatch(new PaymentSettled($paymentToken, $result->transactionId));

                return $result;
            } catch (SnappPayException $e) {
                // If it's auth/whitelist error, rethrow immediately
                if ($e instanceof \Eamirgh\SnappPay\Exceptions\SnappPayAuthException) {
                    throw $e;
                }

                // Query payment status to mitigate failure / timeout
                try {
                    $status = $this->client->getStatus($paymentToken);
                } catch (\Throwable) {
                    if ($attempt >= $retries) {
                        throw $e;
                    }
                    usleep($retryDelay * 1000);
                    continue;
                }

                if ($status->isSettled()) {
                    $result = new TransactionResult($status->transactionId, $paymentToken, 'settle');
                    $this->events?->dispatch(new PaymentSettled($paymentToken, $result->transactionId));

                    return $result;
                }

                if ($status->isVerified() && $attempt < $retries) {
                    usleep($retryDelay * 1000);
                    continue;
                }

                throw $e;
            }
        }

        throw new SnappPayApiException('Settle failed after maximum retry attempts.');
    }

    /**
     * Convenience method to verify and immediately settle with full resilience.
     * Standard workflow after receiving a successful callback (state = 'OK').
     *
     * @throws SnappPayException
     */
    public function verifyAndSettle(string $paymentToken): TransactionResult
    {
        $this->verifyWithResilience($paymentToken);

        return $this->settleWithResilience($paymentToken);
    }

    /**
     * Parse incoming callback request into a typed DTO.
     */
    public function parseCallback(Request|array $request): CallbackPayload
    {
        return CallbackPayload::fromRequest($request);
    }
}
