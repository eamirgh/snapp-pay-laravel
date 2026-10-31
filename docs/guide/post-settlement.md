# Post-Settlement Operations

After an order has been settled, merchants can perform status inspections, cancellations, or basket updates.

---

## 1. Cancel Order

Once an order reaches `SETTLE` status, the only way to void it and refund the customer's installment credit is via the **Cancel API**.

::: danger Irreversible Operation
Cancelling an order is permanent and cannot be undone. In administrative dashboards, require human confirmation before triggering this endpoint.
:::

- **Endpoint**: `POST /api/online/payment/v1/cancel`

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$result = SnappPay::cancel($order->snapppay_token);

$order->update(['status' => 'cancelled']);
```

---

## 2. Update Order (Multi-Item Cart Adjustment)

If a customer partially cancels items from an order or modifies quantities post-settlement, multi-item stores must inform SnappPay through the **Update API**.

- **Endpoint**: `POST /api/online/payment/v1/update`

::: warning Strict Rules for Update
1. **Lower Total Only**: The updated total amount **must be strictly less** than the original settled amount.
2. **Exclude Removed Items**: If a product line was completely cancelled, it must be omitted from the updated `cartItems` array.
3. **Irreversible**: Once updated, the original amount cannot be restored.
:::

### Example Usage:

```php
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;

// Re-build cart excluding the cancelled product line
$remainingItem = CartItem::make(
    id: 'ITEM-1',
    name: 'Retained Product',
    count: 1,
    amount: 8000000,
    category: 'Electronics'
);

$updatedCart = Cart::make(
    cartId: $order->id,
    cartItems: [$remainingItem]
);

$updateRequest = UpdateOrderRequest::make(
    paymentToken: $order->snapppay_token,
    amount: 8000000,
    cartList: [$updatedCart],
    originalAmount: 12000000 // Validates that 8M < 12M
);

$result = SnappPay::update($updateRequest);
```

---

## 3. Revert Payment (Pre-Settlement Fallback)

If customer payment succeeded at the gateway, but your backend cannot fulfill the order (e.g. out-of-stock race condition), you can revert the transaction **before** calling Settle.

::: tip Revert Limitation
Revert is **only applicable prior to settlement**. Once `settle()` has succeeded, you must use `cancel()`.
:::

- **Endpoint**: `POST /api/online/payment/v1/revert`

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$result = SnappPay::revert($order->snapppay_token);
```

---

## 4. Query Payment Status

Query the current lifecycle state of any payment session:

- **Endpoint**: `GET /api/online/payment/v1/status`

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$status = SnappPay::getStatus($order->snapppay_token);

echo $status->transactionId;   // e.g. "ORD-987654"
echo $status->status->value;   // "SETTLE", "CANCEL", "VERIFY", "PENDING", "REVERT"
echo $status->amount;          // 12000000

// Helper methods:
$status->isSettled();    // bool
$status->isVerified();   // bool
$status->isPending();    // bool
$status->isCancelled();  // bool
$status->isReverted();   // bool
```
