# Testing & Mocking

Because `eamirgh/snapp-pay-laravel` uses Laravel's native HTTP client under the hood, writing automated tests is seamless using `Http::fake()`.

---

## Mocking Checkout Flow in Feature Tests

```php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\Order;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_initiate_snapppay_payment(): void
    {
        $order = Order::factory()->create([
            'amount' => 5000000,
            'mobile' => '09120000000',
        ]);

        // Fake SnappPay OAuth and Payment Token endpoints
        Http::fake([
            'https://api.snapppay.ir/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'fake_jwt_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.ir/api/online/payment/v1/token' => Http::response([
                'successful' => true,
                'response' => [
                    'paymentToken' => 'fake_payment_token',
                    'paymentPageUrl' => 'https://snapppay.ir/pay/fake_payment_token',
                ],
            ], 200),
        ]);

        $response = $this->post(route('checkout.snapppay', $order));

        $response->assertRedirect('https://snapppay.ir/pay/fake_payment_token');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'snapppay_token' => 'fake_payment_token',
        ]);
    }
}
```

---

## Mocking Callback & Settlement

```php
public function test_successful_callback_settles_order(): void
{
    $order = Order::factory()->create([
        'snapppay_token' => 'fake_tok',
        'snapppay_transaction_id' => 'TX-12345',
        'status' => 'pending',
    ]);

    Http::fake([
        'https://api.snapppay.ir/api/online/v1/oauth/token' => Http::response([
            'access_token' => 'fake_jwt_token',
            'expires_in' => 3600,
        ], 200),
        'https://api.snapppay.ir/api/online/payment/v1/verify' => Http::response([
            'successful' => true,
            'response' => ['transactionId' => 'TX-12345'],
        ], 200),
        'https://api.snapppay.ir/api/online/payment/v1/settle' => Http::response([
            'successful' => true,
            'response' => ['transactionId' => 'TX-12345'],
        ], 200),
    ]);

    $response = $this->post(route('snapppay.callback'), [
        'transactionId' => 'TX-12345',
        'state' => 'OK',
        'amount' => 5000000,
    ]);

    $response->assertRedirect(route('checkout.success'));

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'paid',
    ]);
}
```

---

## Testing Events

```php
use Illuminate\Support\Facades\Event;
use Eamirgh\SnappPay\Events\PaymentSettled;

public function test_payment_settled_event_is_dispatched(): void
{
    Event::fake([PaymentSettled::class]);

    // Perform verification/settlement...

    Event::assertDispatched(PaymentSettled::class, function ($event) {
        return $event->transactionId === 'TX-12345';
    });
}
```
