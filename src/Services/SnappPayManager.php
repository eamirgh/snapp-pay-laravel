<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Eamirgh\SnappPay\Contracts\SnappPayGateway;
use Eamirgh\SnappPay\Contracts\SnappPayable;
use Eamirgh\SnappPay\DTOs\CallbackPayload;
use Eamirgh\SnappPay\DTOs\EligibilityResult;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\DTOs\PaymentTokenResult;
use Eamirgh\SnappPay\DTOs\StatusResult;
use Eamirgh\SnappPay\DTOs\TransactionResult;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;

/**
 * @mixin SnappPayService
 */
class SnappPayManager implements SnappPayGateway
{
    /**
     * @var array<string, SnappPayService>
     */
    protected array $services = [];

    public function __construct(
        protected Application $app
    ) {
    }

    /**
     * Get a SnappPay service instance for a specific connection.
     */
    public function connection(?string $name = null): SnappPayService
    {
        $name = $name ?? $this->getDefaultConnection();

        if (!isset($this->services[$name])) {
            $this->services[$name] = $this->resolve($name);
        }

        return $this->services[$name];
    }

    /**
     * Build an on-the-fly SnappPayService with custom credentials.
     *
     * @param array<string, mixed> $customConfig
     */
    public function build(array $customConfig): SnappPayService
    {
        $mergedConfig = array_merge(
            $this->app['config']->get('snapppay', []),
            $customConfig
        );

        $tokenManager = new TokenManager($mergedConfig, $this->app->make('cache.store'));
        $client = new SnappPayClient($mergedConfig, $tokenManager);

        return new SnappPayService(
            $mergedConfig,
            $client,
            $this->app->bound('events') ? $this->app['events'] : null
        );
    }

    protected function resolve(string $name): SnappPayService
    {
        $connections = $this->app['config']->get('snapppay.connections', []);

        if (!isset($connections[$name])) {
            throw new InvalidArgumentException("SnappPay connection [{$name}] is not configured.");
        }

        $connectionConfig = array_merge(
            $this->app['config']->get('snapppay', []),
            $connections[$name]
        );

        $tokenManager = new TokenManager($connectionConfig, $this->app->make('cache.store'));
        $client = new SnappPayClient($connectionConfig, $tokenManager);

        return new SnappPayService(
            $connectionConfig,
            $client,
            $this->app->bound('events') ? $this->app['events'] : null
        );
    }

    public function getDefaultConnection(): string
    {
        return (string) $this->app['config']->get('snapppay.default', 'default');
    }

    public function getEligibility(int $amount, array $paymentMethodTypes = []): EligibilityResult
    {
        return $this->connection()->getEligibility($amount, $paymentMethodTypes);
    }

    public function getPaymentToken(PaymentTokenRequest $request): PaymentTokenResult
    {
        return $this->connection()->getPaymentToken($request);
    }

    public function requestPaymentFor(SnappPayable $payable, ?array $forcedPaymentMethodTypes = null): PaymentTokenResult
    {
        return $this->connection()->requestPaymentFor($payable, $forcedPaymentMethodTypes);
    }

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
        return $this->connection()->quickToken(
            $amount,
            $mobile,
            $returnURL,
            $transactionId,
            $items,
            $discountAmount,
            $externalSourceAmount,
            $forcedPaymentMethodTypes
        );
    }

    public function verify(string $paymentToken): TransactionResult
    {
        return $this->connection()->verify($paymentToken);
    }

    public function settle(string $paymentToken): TransactionResult
    {
        return $this->connection()->settle($paymentToken);
    }

    public function revert(string $paymentToken): TransactionResult
    {
        return $this->connection()->revert($paymentToken);
    }

    public function getStatus(string $paymentToken): StatusResult
    {
        return $this->connection()->getStatus($paymentToken);
    }

    public function cancel(string $paymentToken): TransactionResult
    {
        return $this->connection()->cancel($paymentToken);
    }

    public function update(UpdateOrderRequest $request): TransactionResult
    {
        return $this->connection()->update($request);
    }

    public function verifyWithResilience(string $paymentToken, ?int $maxRetries = null): TransactionResult
    {
        return $this->connection()->verifyWithResilience($paymentToken, $maxRetries);
    }

    public function settleWithResilience(string $paymentToken, ?int $maxRetries = null): TransactionResult
    {
        return $this->connection()->settleWithResilience($paymentToken, $maxRetries);
    }

    public function verifyAndSettle(string $paymentToken): TransactionResult
    {
        return $this->connection()->verifyAndSettle($paymentToken);
    }

    public function parseCallback(Request|array $request): CallbackPayload
    {
        return $this->connection()->parseCallback($request);
    }

    /**
     * Pass dynamic methods to the default SnappPay service instance.
     *
     * @param string $method
     * @param array<mixed> $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->$method(...$parameters);
    }
}
