# Resilience Workflows (Sections 11 & 12)

Real-world financial networks face transient hiccups: DNS timeouts, network drops, or gateway latency spikes.

Sections 11 and 12 of the [SnappPay Self-CMS Technical Documentation](https://academy.snapppay.ir/self-cms/) explicitly require merchants to implement specific status polling workflows to prevent discrepancies.

`eamirgh/snapp-pay-laravel` builds this resilience directly into the service layer.

---

## 1. Verify Management (Section 11)

### The Problem
If your server calls `POST /api/online/payment/v1/verify` and times out (e.g. after 30 seconds), you cannot assume the payment failed. The SnappPay server may have processed the verification successfully before your network socket dropped.

### The Algorithm
1. Dispatch `verify` with a 30-second timeout threshold.
2. If timeout or network failure occurs without an HTTP response, immediately call `GET /api/online/payment/v1/status`.
3. Check the returned status:
   - **`VERIFY`**: The verification already succeeded on SnappPay! Proceed safely to `settle`.
   - **`PENDING`**: Verification did not reach SnappPay. Retry the `verify` call.
   - **Other status (`CANCEL`, `REVERT`)**: Classify the transaction as failed and abort.

### Using `verifyWithResilience()`

```php
use Eamirgh\SnappPay\Facades\SnappPay;

// Automatically executes the 3-step mitigation algorithm
$result = SnappPay::verifyWithResilience(
    paymentToken: $order->snapppay_token,
    maxRetries: 2
);
```

---

## 2. Settle Management (Section 12)

### The Problem
`POST /api/online/payment/v1/settle` is the final mandatory step to capture funds. If Settle fails or times out, repeatedly calling Settle without checking status can produce errors if the order was already settled.

### The Algorithm
1. Dispatch `settle` call for the verified order.
2. If Settle returns `false` or experiences a network drop, call `GET /api/online/payment/v1/status`.
3. Check the returned status:
   - **`SETTLE`**: Order was finalized successfully! Register order as paid.
   - **`VERIFY`**: Order is still in verified state. Re-dispatch the `settle` call.
   - **Other**: Throw exception.

### Using `settleWithResilience()`

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$result = SnappPay::settleWithResilience(
    paymentToken: $order->snapppay_token,
    maxRetries: 2
);
```

---

## 3. The One-Line Solution: `verifyAndSettle()`

For 99% of e-commerce integrations, you can combine both resilient workflows in a single call:

```php
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\Exceptions\SnappPayException;

try {
    $result = SnappPay::verifyAndSettle($order->snapppay_token);

    // Transaction is guaranteed verified & settled
    $order->markAsPaid($result->transactionId);
} catch (SnappPayException $e) {
    // Handled failure; log and report
    report($e);
}
```
