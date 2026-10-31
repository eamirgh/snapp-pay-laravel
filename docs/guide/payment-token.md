# Payment Token & Cart Construction

To initiate a payment, you must obtain a **Payment Token** (`POST /api/online/payment/v1/token`).
SnappPay generates a unique payment session and returns a checkout redirect URL (`paymentPageUrl`).

---

## Technical Specifications

- **Route**: `POST /api/online/payment/v1/token`
- **Method**: `POST`
- **Payload Format**: `application/json`

---

## Option 1: Full Multi-Item Cart (Recommended)

For stores selling multiple physical or digital goods, SnappPay requires detailed itemized cart data:

```php
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;

// 1. Build line items
$item1 = CartItem::make(
    id: 'SKU-001',
    name: 'Wireless Bluetooth Headphones',
    count: 1,
    amount: 15000000, // Unit price in IRR
    category: 'Electronics',
    commissionType: 100 // Default 100
);

$item2 = CartItem::make(
    id: 'SKU-002',
    name: 'Protective Carrying Case',
    count: 2,
    amount: 1000000,
    category: 'Accessories',
    commissionType: 100
);

// 2. Build Cart
// totalAmount calculation:
// sum(count * amount) + (isShipmentIncluded ? 0 : shippingAmount) + (isTaxIncluded ? 0 : taxAmount)
$cart = Cart::make(
    cartId: 'CART-' . $order->id,
    cartItems: [$item1, $item2],
    isShipmentIncluded: true,
    isTaxIncluded: true
);

// 3. Assemble Payment Token Request
// Arithmetic check: sum(carts) - discountAmount - externalSourceAmount == amount
$request = PaymentTokenRequest::make(
    amount: 16000000, // 17,000,000 - 1,000,000 (discount) = 16,000,000 IRR
    cartList: [$cart],
    mobile: $user->mobile, // e.g. "09123456789"
    returnURL: route('payment.snapppay.callback'),
    transactionId: 'ORD-' . $order->id, // 5-10 digits, or >10 chars with letter
    discountAmount: 1000000,
    externalSourceAmount: 0
);

// 4. Request Token
$result = SnappPay::getPaymentToken($request);

// Store token for verification after callback
$order->update([
    'snapppay_token' => $result->paymentToken,
    'snapppay_transaction_id' => $result->transactionId,
]);

// 5. Redirect user to SnappPay gateway
return redirect()->away($result->paymentPageUrl);
```

::: tip Model Contracts
If your application models already represent orders and carts, have them implement `SnappPayable`, `SnappPayCartInterface`, or `SnappPayCartItemInterface`. You can pass them directly without manual mapping, or call `SnappPay::requestPaymentFor($order)`. See [Interface Contracts](/guide/contracts).
:::

---

## Option 2: Quick Token (Single Order Shortcut)

If you only need to charge an order without detailing individual line items, use `quickToken()`:

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$result = SnappPay::quickToken(
    amount: 5000000, // Amount in IRR
    mobile: '09123456789',
    returnURL: route('payment.snapppay.callback'),
    transactionId: 'TX-' . $order->id,
    discountAmount: 0
);

return redirect()->away($result->paymentPageUrl);
```

---

## Validation Rules Enforced

`eamirgh/snapp-pay-laravel` validates your payload automatically before making network calls:

### 1. `transactionId` Rules
- Must be at least **5 characters**.
- If between **5 and 10 characters**: Can be numeric digits or alphanumeric.
- If strictly greater than **10 characters**: **Must contain at least one alphabetical letter** (`[a-zA-Z]`).
- A `SnappPayValidationException` is thrown if invalid.

### 2. Arithmetic Balance
```
sum(cart totals) - discountAmount - externalSourceAmount === amount
```
If your declared `amount` does not match the sum of items minus deductions, the package throws `SnappPayValidationException::amountMismatch()`.

### 3. Return URL
- Must match the base domain whitelist registered with SnappPay.
- Must accept incoming **HTTP POST** requests.
