<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Tests\Unit;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;
use Eamirgh\SnappPay\Enums\PaymentMethodType;
use Eamirgh\SnappPay\Enums\PaymentStatus;
use Eamirgh\SnappPay\Exceptions\SnappPayApiException;
use Eamirgh\SnappPay\Exceptions\SnappPayAuthException;
use Eamirgh\SnappPay\Exceptions\SnappPayTimeoutException;
use Eamirgh\SnappPay\Services\SnappPayClient;
use Eamirgh\SnappPay\Services\TokenManager;
use Eamirgh\SnappPay\Tests\TestCase;

class ClientTest extends TestCase
{
    protected array $config = [
        'base_url' => 'https://api.snapppay.test',
        'client_id' => 'client',
        'client_secret' => 'secret',
        'username' => 'user',
        'password' => 'pass',
        'timeout' => 30,
        'connect_timeout' => 10,
    ];

    protected function createClient(): SnappPayClient
    {
        $tokenManager = new TokenManager($this->config);
        return new SnappPayClient($this->config, $tokenManager);
    }

    protected function fakeOAuthToken(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
        ]);
    }

    public function test_get_eligibility_successful(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/offer/v1/eligible*' => Http::response([
                'successful' => true,
                'response' => [
                    'eligible' => true,
                    'title_message' => 'پرداخت اقساطی اسنپ‌پی',
                    'description' => 'پرداخت در ۴ قسط بدون سود و کارمزد',
                ],
            ], 200),
        ]);

        $client = $this->createClient();
        $result = $client->getEligibility(5000000, [PaymentMethodType::INSTALLMENT, PaymentMethodType::FINANCING]);

        $this->assertTrue($result->isEligible());
        $this->assertSame('پرداخت اقساطی اسنپ‌پی', $result->titleMessage);
        $this->assertSame('پرداخت در ۴ قسط بدون سود و کارمزد', $result->description);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/online/offer/v1/eligible')
                && str_contains($request->url(), 'amount=5000000')
                && str_contains($request->url(), 'paymentMethodTypes=INSTALLMENT%2CFINANCING')
                && $request->hasHeader('Authorization', 'Bearer mock_token');
        });
    }

    public function test_get_payment_token_successful(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/token' => Http::response([
                'successful' => true,
                'response' => [
                    'paymentToken' => 'snapp_tok_12345',
                    'paymentPageUrl' => 'https://snapppay.ir/pay/snapp_tok_12345',
                ],
            ], 200),
        ]);

        $cart = Cart::make(cartId: 1, cartItems: [CartItem::make(1, 'Item', 1, 1000000, 'Cat')]);
        $request = PaymentTokenRequest::make(
            amount: 1000000,
            cartList: [$cart],
            mobile: '09121111111',
            returnURL: 'https://shop.test/cb',
            transactionId: 'TX-998877'
        );

        $client = $this->createClient();
        $result = $client->getPaymentToken($request);

        $this->assertSame('snapp_tok_12345', $result->paymentToken);
        $this->assertSame('https://snapppay.ir/pay/snapp_tok_12345', $result->paymentPageUrl);
        $this->assertSame('TX-998877', $result->transactionId);
    }

    public function test_verify_successful(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/verify' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-998877',
                ],
            ], 200),
        ]);

        $client = $this->createClient();
        $result = $client->verify('snapp_tok_12345');

        $this->assertSame('TX-998877', $result->transactionId);
        $this->assertSame('snapp_tok_12345', $result->paymentToken);
        $this->assertSame('verify', $result->action);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.snapppay.test/api/online/payment/v1/verify'
                && $request['paymentToken'] === 'snapp_tok_12345';
        });
    }

    public function test_settle_successful(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/settle' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-998877',
                ],
            ], 200),
        ]);

        $client = $this->createClient();
        $result = $client->settle('snapp_tok_12345');

        $this->assertSame('TX-998877', $result->transactionId);
        $this->assertSame('settle', $result->action);
    }

    public function test_revert_successful(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/revert' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-998877',
                ],
            ], 200),
        ]);

        $client = $this->createClient();
        $result = $client->revert('snapp_tok_12345');

        $this->assertSame('TX-998877', $result->transactionId);
        $this->assertSame('revert', $result->action);
    }

    public function test_get_status_successful(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/status*' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-998877',
                    'status' => 'SETTLE',
                    'amount' => 1000000,
                ],
            ], 200),
        ]);

        $client = $this->createClient();
        $result = $client->getStatus('snapp_tok_12345');

        $this->assertSame('TX-998877', $result->transactionId);
        $this->assertSame(PaymentStatus::SETTLE, $result->status);
        $this->assertTrue($result->isSettled());
        $this->assertSame(1000000, $result->amount);
    }

    public function test_cancel_successful(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/cancel' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-998877',
                ],
            ], 200),
        ]);

        $client = $this->createClient();
        $result = $client->cancel('snapp_tok_12345');

        $this->assertSame('TX-998877', $result->transactionId);
        $this->assertSame('cancel', $result->action);
    }

    public function test_update_successful(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/update' => Http::response([
                'successful' => true,
                'response' => [
                    'transactionId' => 'TX-998877',
                ],
            ], 200),
        ]);

        $cart = Cart::make(cartId: 1, cartItems: [CartItem::make(1, 'Item', 1, 800000, 'Cat')]);
        $request = UpdateOrderRequest::make('snapp_tok_12345', 800000, [$cart]);

        $client = $this->createClient();
        $result = $client->update($request);

        $this->assertSame('TX-998877', $result->transactionId);
        $this->assertSame('update', $result->action);
    }

    public function test_api_error_response_throws_api_exception(): void
    {
        $this->expectException(SnappPayApiException::class);
        $this->expectExceptionMessage('User installment limit reached.');

        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/verify' => Http::response([
                'successful' => false,
                'errorData' => [
                    'errorCode' => 4001,
                    'message' => 'User installment limit reached.',
                    'data' => null,
                ],
            ], 400),
        ]);

        $client = $this->createClient();
        $client->verify('invalid_token');
    }

    public function test_401_triggers_token_refresh_and_retries(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::sequence()
                ->push(['access_token' => 'token_expired', 'expires_in' => 3600])
                ->push(['access_token' => 'token_refreshed', 'expires_in' => 3600]),
            'https://api.snapppay.test/api/online/payment/v1/verify' => Http::sequence()
                ->push('Unauthorized', 401)
                ->push([
                    'successful' => true,
                    'response' => ['transactionId' => 'TX-123'],
                ], 200),
        ]);

        $client = $this->createClient();
        $result = $client->verify('tok_test');

        $this->assertSame('TX-123', $result->transactionId);
        // Verify was called twice, oauth token was requested twice
        Http::assertSentCount(4);
    }

    public function test_connection_exception_converted_to_snapp_pay_timeout_exception(): void
    {
        $this->expectException(SnappPayTimeoutException::class);

        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/verify' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
        ]);

        $client = $this->createClient();
        $client->verify('tok_test');
    }
}
