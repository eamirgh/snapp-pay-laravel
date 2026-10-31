# Lifecycle Events

The package emits typed Laravel events throughout the payment lifecycle. You can listen to these events for audit logging, notification dispatching, or internal analytics.

---

## Available Events

| Event Class | Payload | Dispatched When |
|---|---|---|
| `Eamirgh\SnappPay\Events\PaymentTokenCreated` | `$event->result`, `$event->request` | Payment token was generated. |
| `Eamirgh\SnappPay\Events\PaymentVerified` | `$event->paymentToken`, `$event->transactionId` | Payment was verified. |
| `Eamirgh\SnappPay\Events\PaymentSettled` | `$event->paymentToken`, `$event->transactionId` | Payment was settled. |
| `Eamirgh\SnappPay\Events\PaymentReverted` | `$event->paymentToken`, `$event->transactionId` | Payment was reverted. |
| `Eamirgh\SnappPay\Events\PaymentCancelled` | `$event->paymentToken`, `$event->transactionId` | Order was cancelled. |
| `Eamirgh\SnappPay\Events\PaymentUpdated` | `$event->paymentToken`, `$event->transactionId`, `$event->request` | Order items were updated. |

---

## Registering Event Listeners

In your `AppServiceProvider` or `EventServiceProvider`:

```php
namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Eamirgh\SnappPay\Events\PaymentSettled;
use Eamirgh\SnappPay\Events\PaymentCancelled;
use App\Listeners\SendPaymentReceipt;
use App\Listeners\NotifyWarehouseOfCancellation;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(PaymentSettled::class, SendPaymentReceipt::class);
        Event::listen(PaymentCancelled::class, NotifyWarehouseOfCancellation::class);
    }
}
```

### Example Listener

```php
namespace App\Listeners;

use Eamirgh\SnappPay\Events\PaymentSettled;
use Illuminate\Support\Facades\Log;

class SendPaymentReceipt
{
    public function handle(PaymentSettled $event): void
    {
        Log::info('SnappPay order settled', [
            'payment_token' => $event->paymentToken,
            'transaction_id' => $event->transactionId,
        ]);

        // Send SMS/Email receipt, trigger invoice creation, etc.
    }
}
```
