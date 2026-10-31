<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;
use Eamirgh\SnappPay\Events\PaymentCancelled;
use Eamirgh\SnappPay\Events\PaymentReverted;
use Eamirgh\SnappPay\Events\PaymentSettled;
use Eamirgh\SnappPay\Events\PaymentTokenCreated;
use Eamirgh\SnappPay\Events\PaymentUpdated;
use Eamirgh\SnappPay\Events\PaymentVerified;
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\Tests\TestCase;

class EventDispatchTest extends TestCase
{
    protected function fakeApiResponses(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/token' => Http::response([
                'successful' => true,
                'response' => [
                    'paymentToken' => 'tok_ev_1',
                    'paymentPageUrl' => 'https://snapppay.test/pay',
                ],
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/verify' => Http::response([
                'successful' => true,
                'response' => ['transactionId' => 'TX-EV-1'],
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/settle' => Http::response([
                'successful' => true,
                'response' => ['transactionId' => 'TX-EV-1'],
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/revert' => Http::response([
                'successful' => true,
                'response' => ['transactionId' => 'TX-EV-1'],
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/cancel' => Http::response([
                'successful' => true,
                'response' => ['transactionId' => 'TX-EV-1'],
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/update' => Http::response([
                'successful' => true,
                'response' => ['transactionId' => 'TX-EV-1'],
            ], 200),
        ]);
    }

    public function test_all_lifecycle_events_are_dispatched(): void
    {
        Event::fake([
            PaymentTokenCreated::class,
            PaymentVerified::class,
            PaymentSettled::class,
            PaymentReverted::class,
            PaymentCancelled::class,
            PaymentUpdated::class,
        ]);

        $this->fakeApiResponses();

        // 1. Payment token created
        $cart = Cart::make(1, [CartItem::make(1, 'Item', 1, 1000000, 'Cat')]);
        $req = PaymentTokenRequest::make(1000000, [$cart], '09121111111', 'https://shop.test/cb', 'TX-EV-1');
        SnappPay::getPaymentToken($req);

        Event::assertDispatched(PaymentTokenCreated::class, function ($e) use ($req) {
            return $e->result->paymentToken === 'tok_ev_1'
                && $e->request->transactionId === $req->transactionId;
        });

        // 2. Verify
        SnappPay::verify('tok_ev_1');
        Event::assertDispatched(PaymentVerified::class, function ($e) {
            return $e->paymentToken === 'tok_ev_1' && $e->transactionId === 'TX-EV-1';
        });

        // 3. Settle
        SnappPay::settle('tok_ev_1');
        Event::assertDispatched(PaymentSettled::class, function ($e) {
            return $e->paymentToken === 'tok_ev_1' && $e->transactionId === 'TX-EV-1';
        });

        // 4. Revert
        SnappPay::revert('tok_ev_1');
        Event::assertDispatched(PaymentReverted::class, function ($e) {
            return $e->paymentToken === 'tok_ev_1' && $e->transactionId === 'TX-EV-1';
        });

        // 5. Cancel
        SnappPay::cancel('tok_ev_1');
        Event::assertDispatched(PaymentCancelled::class, function ($e) {
            return $e->paymentToken === 'tok_ev_1' && $e->transactionId === 'TX-EV-1';
        });

        // 6. Update
        $updCart = Cart::make(1, [CartItem::make(1, 'Item', 1, 800000, 'Cat')]);
        $updReq = UpdateOrderRequest::make('tok_ev_1', 800000, [$updCart]);
        SnappPay::update($updReq);

        Event::assertDispatched(PaymentUpdated::class, function ($e) use ($updReq) {
            return $e->paymentToken === 'tok_ev_1'
                && $e->transactionId === 'TX-EV-1'
                && $e->request->amount === $updReq->amount;
        });
    }
}
