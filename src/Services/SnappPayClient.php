<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Eamirgh\SnappPay\Contracts\SnappPayClientInterface;
use Eamirgh\SnappPay\Contracts\TokenManagerInterface;
use Eamirgh\SnappPay\DTOs\EligibilityResult;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\DTOs\PaymentTokenResult;
use Eamirgh\SnappPay\DTOs\StatusResult;
use Eamirgh\SnappPay\DTOs\TransactionResult;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;
use Eamirgh\SnappPay\Enums\PaymentMethodType;
use Eamirgh\SnappPay\Exceptions\SnappPayApiException;
use Eamirgh\SnappPay\Exceptions\SnappPayAuthException;
use Eamirgh\SnappPay\Exceptions\SnappPayException;
use Eamirgh\SnappPay\Exceptions\SnappPayTimeoutException;

class SnappPayClient implements SnappPayClientInterface
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $connectTimeout;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected array $config,
        protected TokenManagerInterface $tokenManager,
    ) {
        $this->baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://api.snapppay.ir'), '/');
        $this->timeout = (int) ($this->config['timeout'] ?? 30);
        $this->connectTimeout = (int) ($this->config['connect_timeout'] ?? 10);
    }

    public function getTokenManager(): TokenManagerInterface
    {
        return $this->tokenManager;
    }

    /**
     * Check merchant & cart installment eligibility.
     *
     * @param int $amount Cart total in IRR
     * @param array<PaymentMethodType|string> $paymentMethodTypes Optional payment methods
     *
     * @throws SnappPayException
     */
    public function getEligibility(int $amount, array $paymentMethodTypes = []): EligibilityResult
    {
        $query = ['amount' => $amount];

        if (!empty($paymentMethodTypes)) {
            $methods = array_map(
                static fn ($m) => $m instanceof PaymentMethodType ? $m->value : (string) $m,
                $paymentMethodTypes
            );
            $query['paymentMethodTypes'] = implode(',', $methods);
        }

        $data = $this->send('GET', '/api/online/offer/v1/eligible', ['query' => $query]);

        return EligibilityResult::fromArray($data['response'] ?? []);
    }

    /**
     * Obtain payment token and checkout URL.
     *
     * @throws SnappPayException
     */
    public function getPaymentToken(PaymentTokenRequest $request): PaymentTokenResult
    {
        $data = $this->send('POST', '/api/online/payment/v1/token', [
            'json' => $request->toArray(),
        ]);

        return PaymentTokenResult::fromArray($data['response'] ?? [], $request->transactionId);
    }

    /**
     * Verify payment after successful callback.
     *
     * @throws SnappPayException
     */
    public function verify(string $paymentToken): TransactionResult
    {
        $data = $this->send('POST', '/api/online/payment/v1/verify', [
            'json' => ['paymentToken' => $paymentToken],
        ], $paymentToken, 'verify');

        return TransactionResult::fromArray($data['response'] ?? [], $paymentToken, 'verify');
    }

    /**
     * Settle payment (finalize transaction).
     *
     * @throws SnappPayException
     */
    public function settle(string $paymentToken): TransactionResult
    {
        $data = $this->send('POST', '/api/online/payment/v1/settle', [
            'json' => ['paymentToken' => $paymentToken],
        ], $paymentToken, 'settle');

        return TransactionResult::fromArray($data['response'] ?? [], $paymentToken, 'settle');
    }

    /**
     * Revert unfulfilled payment before settlement.
     *
     * @throws SnappPayException
     */
    public function revert(string $paymentToken): TransactionResult
    {
        $data = $this->send('POST', '/api/online/payment/v1/revert', [
            'json' => ['paymentToken' => $paymentToken],
        ], $paymentToken, 'revert');

        return TransactionResult::fromArray($data['response'] ?? [], $paymentToken, 'revert');
    }

    /**
     * Query payment status.
     *
     * @throws SnappPayException
     */
    public function getStatus(string $paymentToken): StatusResult
    {
        $data = $this->send('GET', '/api/online/payment/v1/status', [
            'query' => ['paymentToken' => $paymentToken],
        ], $paymentToken, 'status');

        return StatusResult::fromArray($data['response'] ?? [], $paymentToken);
    }

    /**
     * Cancel an order resting in SETTLE status.
     *
     * @throws SnappPayException
     */
    public function cancel(string $paymentToken): TransactionResult
    {
        $data = $this->send('POST', '/api/online/payment/v1/cancel', [
            'json' => ['paymentToken' => $paymentToken],
        ], $paymentToken, 'cancel');

        return TransactionResult::fromArray($data['response'] ?? [], $paymentToken, 'cancel');
    }

    /**
     * Update order cart or sum post-settlement.
     *
     * @throws SnappPayException
     */
    public function update(UpdateOrderRequest $request): TransactionResult
    {
        $data = $this->send('POST', '/api/online/payment/v1/update', [
            'json' => $request->toArray(),
        ], $request->paymentToken, 'update');

        return TransactionResult::fromArray($data['response'] ?? [], $request->paymentToken, 'update');
    }

    /**
     * Execute HTTP request against SnappPay API.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     *
     * @throws SnappPayException
     */
    protected function send(
        string $method,
        string $path,
        array $options = [],
        ?string $paymentToken = null,
        ?string $action = null
    ): array {
        $attempt = 0;
        $maxAuthAttempts = 2;

        while ($attempt < $maxAuthAttempts) {
            $attempt++;
            $token = $this->tokenManager->getToken();

            try {
                $client = Http::withToken($token)
                    ->acceptJson()
                    ->timeout($this->timeout)
                    ->connectTimeout($this->connectTimeout);

                if (!empty($options['query'])) {
                    $url = $this->baseUrl . $path . '?' . http_build_query($options['query']);
                } else {
                    $url = $this->baseUrl . $path;
                }

                /** @var Response $response */
                $response = match (strtoupper($method)) {
                    'GET' => $client->get($url),
                    'POST' => $client->post($url, $options['json'] ?? []),
                    default => throw new SnappPayException("Unsupported HTTP method: {$method}"),
                };

                // If token expired, clear cache and retry once
                if ($response->status() === 401 && $attempt < $maxAuthAttempts) {
                    $this->tokenManager->clearToken();
                    continue;
                }

                return $this->parseResponse($response);
            } catch (ConnectionException $e) {
                throw new SnappPayTimeoutException(
                    message: "SnappPay request connection timeout: {$e->getMessage()}",
                    paymentToken: $paymentToken,
                    action: $action,
                    previous: $e
                );
            }
        }

        throw SnappPayAuthException::invalidCredentials('Authentication token rejected repeatedly.');
    }

    /**
     * Validate and parse SnappPay API JSON response.
     *
     * @return array<string, mixed>
     *
     * @throws SnappPayException
     */
    protected function parseResponse(Response $response): array
    {
        $status = $response->status();
        $body = $response->body();

        if ($status === 403 || str_contains($body, 'Access Denied')) {
            throw SnappPayAuthException::accessDenied($body);
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new SnappPayApiException(
                message: "SnappPay returned invalid JSON (HTTP {$status}): {$body}",
                statusCode: $status,
                rawResponse: $body
            );
        }

        $isSuccessful = ($payload['successful'] ?? null) === true;

        if (!$isSuccessful || $response->failed()) {
            throw SnappPayApiException::fromPayload($payload, $status, $body);
        }

        return $payload;
    }
}
