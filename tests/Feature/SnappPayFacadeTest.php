<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\Tests\TestCase;

class SnappPayFacadeTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('snapppay.connections.branch_2', [
            'base_url' => 'https://branch2.snapppay.test',
            'client_id' => 'branch2_client',
            'client_secret' => 'branch2_secret',
            'username' => 'branch2_user',
            'password' => 'branch2_pass',
            'timeout' => 30,
        ]);
    }

    public function test_facade_get_eligibility(): void
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
                    'title_message' => 'SnappPay Offer',
                    'description' => 'Pay in 4 installments',
                ],
            ], 200),
        ]);

        $res = SnappPay::getEligibility(500000);

        $this->assertTrue($res->eligible);
        $this->assertSame('SnappPay Offer', $res->titleMessage);
    }

    public function test_facade_quick_token(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/token' => Http::response([
                'successful' => true,
                'response' => [
                    'paymentToken' => 'tok_quick_1',
                    'paymentPageUrl' => 'https://snapppay.ir/pay/tok_quick_1',
                ],
            ], 200),
        ]);

        $res = SnappPay::quickToken(
            amount: 2500000,
            mobile: '09120000000',
            returnURL: 'https://shop.test/cb',
            transactionId: 'TX-QUICK-01'
        );

        $this->assertSame('tok_quick_1', $res->paymentToken);
        $this->assertSame('https://snapppay.ir/pay/tok_quick_1', $res->paymentPageUrl);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.snapppay.test/api/online/payment/v1/token'
                && $request['amount'] === 2500000
                && $request['mobile'] === '09120000000'
                && $request['cartList'][0]['cartItems'][0]['commissionType'] === 100;
        });
    }

    public function test_multi_connection_support(): void
    {
        Http::fake([
            'https://branch2.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'branch2_token',
                'expires_in' => 3600,
            ], 200),
            'https://branch2.snapppay.test/api/online/payment/v1/verify' => Http::response([
                'successful' => true,
                'response' => ['transactionId' => 'TX-BRANCH2'],
            ], 200),
        ]);

        $branchService = SnappPay::connection('branch_2');
        $res = $branchService->verify('tok_branch2');

        $this->assertSame('TX-BRANCH2', $res->transactionId);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://branch2.snapppay.test');
        });
    }

    public function test_build_on_the_fly_service(): void
    {
        Http::fake([
            'https://dynamic.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'dyn_token',
                'expires_in' => 3600,
            ], 200),
            'https://dynamic.snapppay.test/api/online/payment/v1/verify' => Http::response([
                'successful' => true,
                'response' => ['transactionId' => 'TX-DYNAMIC'],
            ], 200),
        ]);

        $dynamicService = SnappPay::build([
            'base_url' => 'https://dynamic.snapppay.test',
            'client_id' => 'dyn_client',
            'client_secret' => 'dyn_secret',
            'username' => 'dyn_user',
            'password' => 'dyn_pass',
        ]);

        $res = $dynamicService->verify('tok_dyn');

        $this->assertSame('TX-DYNAMIC', $res->transactionId);
    }

    public function test_parse_callback_from_request_object(): void
    {
        $request = Request::create('/callback', 'POST', [
            'transactionId' => 'TX-CB-12345',
            'state' => 'OK',
            'amount' => 1250000,
        ]);

        $callback = SnappPay::parseCallback($request);

        $this->assertTrue($callback->isOk());
        $this->assertSame('TX-CB-12345', $callback->transactionId);
        $this->assertSame(1250000, $callback->amount);
    }
}
