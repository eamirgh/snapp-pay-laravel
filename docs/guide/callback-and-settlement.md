# Callback Handling & Settlement

Once the customer completes or cancels payment on the SnappPay portal, they are redirected back to your `returnURL` via an **HTTP POST** request.

---

## Inbound Callback Payload

SnappPay dispatches the following parameters via POST:

| Parameter | Type | Description |
|---|---|---|
| `transactionId` | String | The unique ID sent during payment token creation. |
| `state` | String | Outcome state: `'OK'` or `'FAILED'`. |
| `amount` | Integer | Total captured value in IRR. |

---

## Callback Controller Implementation

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\Exceptions\SnappPayException;
use App\Models\Order;

class SnappPayCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Parse inbound callback
        $callback = SnappPay::parseCallback($request);

        // 2. Locate matching order in DB
        $order = Order::where('snapppay_transaction_id', $callback->transactionId)->firstOrFail();

        // 3. Check callback state
        if ($callback->isFailed()) {
            $order->update(['status' => 'failed']);
            return redirect()->route('checkout.failed')->with('error', 'Payment was canceled or failed.');
        }

        // 4. Verify and Settle in one resilient call
        try {
            $settleResult = SnappPay::verifyAndSettle($order->snapppay_token);

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return redirect()->route('checkout.success')->with('success', 'Order completed successfully!');
        } catch (SnappPayException $e) {
            report($e);
            return redirect()->route('checkout.failed')->with('error', 'Verification failed: ' . $e->getMessage());
        }
    }
}
```

---

## CSRF Exclusion for Callback Route

Because SnappPay sends a cross-site POST request to your application, you must exclude the callback URL from Laravel's CSRF verification:

### In Laravel 11 & 12 (`bootstrap/app.php`):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'payment/snapppay/callback',
    ]);
})
```

---

## Step-by-Step Manual Verification

If you prefer to separate Verify and Settle steps manually:

```php
// Step 1: Verify payment (MUST be called only ONCE)
$verifyResult = SnappPay::verify($order->snapppay_token);

// Step 2: Settle payment (Required to finalize and claim funds)
$settleResult = SnappPay::settle($order->snapppay_token);
```

::: danger Strict Verification Requirement
Unverified payments are automatically cancelled and refunded to the customer within a limited time window by SnappPay's automated reconciliation engine.
:::
