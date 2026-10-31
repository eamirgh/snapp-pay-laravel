<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Eamirgh\SnappPay\DTOs\TransactionResult;
use Eamirgh\SnappPay\Exceptions\SnappPayApiException;
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\Tests\TestCase;

class ResilienceWorkflowTest extends TestCase
{
    protected function fakeOAuth(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
        ]);
    }

    public function test_verify_resilience_handles_timeout_when_status_is_verify(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            // Verify endpoint times out
            'https://api.snapppay.test/api/online/payment/v1/verify' => function () {
                throw new ConnectionException('Operation timed out after 30000 milliseconds');
            },
            // Status endpoint reports transaction was indeed VERIFY
            'https://api.snapppay.test/api/online/payment/v1/status*' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-RESILIENT-1',
                    'status' => 'VERIFY',
                    'amount' => 1000000,
                ],
            ], 200),
        ]);

        $result = SnappPay::verifyWithResilience('tok_123');

        $this->assertInstanceOf(TransactionResult::class, $result);
        $this->assertSame('TX-RESILIENT-1', $result->transactionId);
        $this->assertSame('tok_123', $result->paymentToken);
    }

    public function test_verify_resilience_retries_when_status_is_pending(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            // 1st verify times out, 2nd verify succeeds
            'https://api.snapppay.test/api/online/payment/v1/verify' => Http::sequence()
                ->pushFailedConnection('Timeout')
                ->push([
                    'successful' => true,
                    'response' => ['transactionId' => 'TX-RETRY-SUCCESS'],
                ], 200),
            // Status call after 1st timeout returns PENDING
            'https://api.snapppay.test/api/online/payment/v1/status*' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-RETRY-SUCCESS',
                    'status' => 'PENDING',
                    'amount' => 500000,
                ],
            ], 200),
        ]);

        $result = SnappPay::verifyWithResilience('tok_123', maxRetries: 2);

        $this->assertSame('TX-RETRY-SUCCESS', $result->transactionId);
    }

    public function test_verify_resilience_throws_when_status_is_cancelled(): void
    {
        $this->expectException(SnappPayApiException::class);
        $this->expectExceptionMessage('payment status is [CANCEL]');

        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/verify' => function () {
                throw new ConnectionException('Timeout');
            },
            'https://api.snapppay.test/api/online/payment/v1/status*' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-FAILED',
                    'status' => 'CANCEL',
                    'amount' => 500000,
                ],
            ], 200),
        ]);

        SnappPay::verifyWithResilience('tok_123');
    }

    public function test_settle_resilience_handles_timeout_when_status_is_settle(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            // Settle fails / times out
            'https://api.snapppay.test/api/online/payment/v1/settle' => function () {
                throw new ConnectionException('Connection reset');
            },
            // Status endpoint indicates SETTLE
            'https://api.snapppay.test/api/online/payment/v1/status*' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-ALREADY-SETTLED',
                    'status' => 'SETTLE',
                    'amount' => 2000000,
                ],
            ], 200),
        ]);

        $result = SnappPay::settleWithResilience('tok_settle_1');

        $this->assertInstanceOf(TransactionResult::class, $result);
        $this->assertSame('TX-ALREADY-SETTLED', $result->transactionId);
    }

    public function test_settle_resilience_retries_when_status_is_verify(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            // Settle fails on 1st attempt, succeeds on 2nd
            'https://api.snapppay.test/api/online/payment/v1/settle' => Http::sequence()
                ->push(['successful' => false, 'errorData' => ['errorCode' => 500, 'message' => 'Transient error']], 500)
                ->push(['successful' => true, 'response' => ['transactionId' => 'TX-SETTLED-RETRY']], 200),
            // Status query after 1st fail returns VERIFY
            'https://api.snapppay.test/api/online/payment/v1/status*' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-SETTLED-RETRY',
                    'status' => 'VERIFY',
                    'amount' => 1000000,
                ],
            ], 200),
        ]);

        $result = SnappPay::settleWithResilience('tok_retry_settle', maxRetries: 2);

        $this->assertSame('TX-SETTLED-RETRY', $result->transactionId);
    }

    public function test_verify_and_settle_convenience_flow(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/verify' => Http::response([
                'successful' => true,
                'response' => ['transactionId' => 'TX-END-TO-END'],
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/settle' => Http::response([
                'successful' => true,
                'response' => ['transactionId' => 'TX-END-TO-END'],
            ], 200),
        ]);

        $result = SnappPay::verifyAndSettle('tok_e2e');

        $this->assertSame('TX-END-TO-END', $result->transactionId);
    }
}
