# SnappPay Laravel (11, 12, 13)

[![Latest Stable Version](https://img.shields.io/packagist/v/eamirgh/snapp-pay-laravel.svg?style=flat-square)](https://packagist.org/packages/eamirgh/snapp-pay-laravel)
[![Documentation](https://img.shields.io/badge/docs-vitepress-00D166.svg?style=flat-square)](https://eamirgh.github.io/snapp-pay-laravel/)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-11.x%20%7C%2012.x%20%7C%2013.x-red?style=flat-square)](https://laravel.com)
[![License](https://img.shields.io/packagist/l/eamirgh/snapp-pay-laravel.svg?style=flat-square)](LICENSE)

Production-ready, highly scalable, and resilient Laravel integration for **SnappPay (اسنپ‌پی)** BNPL (Buy Now, Pay Later) payment gateway.

📖 **Documentation**: [https://eamirgh.github.io/snapp-pay-laravel/](https://eamirgh.github.io/snapp-pay-laravel/) (Available in English & فارسی)

Built strictly according to the official [SnappPay Self-CMS Technical Documentation v2.1](https://academy.snapppay.ir/self-cms/).

---

## ✨ Features

- **Compatibility**: Supports Laravel 11.x, 12.x, and 13.x on PHP 8.2, 8.3, and 8.4.
- **Complete Endpoint Coverage**:
  - `POST /api/online/v1/oauth/token` (OAuth2 Client Credential / Password Grant with Basic Auth).
  - `GET /api/online/offer/v1/eligible` (Dynamic merchant & cart installment eligibility check).
  - `POST /api/online/payment/v1/token` (Obtain payment token with multi-item cart calculation).
  - `POST /api/online/payment/v1/verify` (Single-execution payment verification).
  - `POST /api/online/payment/v1/settle` (Mandatory order settlement).
  - `POST /api/online/payment/v1/revert` (Pre-settlement payment reversal).
  - `GET /api/online/payment/v1/status` (Payment status inspection).
  - `POST /api/online/payment/v1/cancel` (Settled order cancellation).
  - `POST /api/online/payment/v1/update` (Post-settlement multi-item cart update).
- **Automated Resilience (Doc Sections 11 & 12)**:
  - Built-in `verifyWithResilience()` with 30s timeout handling and fallback to status query.
  - Built-in `settleWithResilience()` handling transient network drops and status verification.
  - One-line `verifyAndSettle()` for seamless callback fulfillment.
- **Auto-Token Management**: Automatic Bearer JWT fetching, Basic Auth encoding, retry on 401 expiration, and optional Laravel cache storage.
- **Strictly Typed DTOs & Validation**:
  - `CartItem`, `Cart`, `PaymentTokenRequest`, `UpdateOrderRequest`, `CallbackPayload`, `StatusResult`, `EligibilityResult`, `TransactionResult`.
  - Enforces SnappPay's `transactionId` rules (5-10 digits, or >10 chars with letter) and basket calculation checks.
- **Events & Auditability**: Emits typed Laravel events for token creation, verification, settlement, revert, cancellation, and updates.
- **Multi-Tenant / Multi-Merchant**: Switch between multiple SnappPay accounts via `SnappPay::connection('second_account')` or `SnappPay::build([...])`.
- **Developer Experience**: Full IDE autocompletion via `SnappPay` Facade and test-ready with Laravel's `Http::fake()`.

---

## 📦 Installation

Install via Composer:

```bash
composer require eamirgh/snapp-pay-laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="snapppay-config"
```

---

## ⚙️ Configuration

Set your SnappPay credentials in `.env`:

```dotenv
# Base URL (Stage or Production provided by SnappPay operations)
SNAPP_PAY_BASE_URL=https://api.snapppay.ir

# OAuth & Merchant Credentials
SNAPP_PAY_CLIENT_ID=your_client_id
SNAPP_PAY_CLIENT_SECRET=your_client_secret
SNAPP_PAY_USERNAME=your_username
SNAPP_PAY_PASSWORD=your_password

# HTTP Client Timeouts (seconds)
SNAPP_PAY_TIMEOUT=30
SNAPP_PAY_CONNECT_TIMEOUT=10

# OAuth Token Caching (false by default per SnappPay security guidance)
SNAPP_PAY_CACHE_TOKEN=false
```

> ⚠️ **Important (IP Whitelist)**: SnappPay requires your outbound server IP to be **whitelisted** before any request can succeed. Contact `merchant-support@snapppay.ir` or your account manager to whitelist your server IP.

---

## 🚀 Complete Payment Workflow

### 1. Check Eligibility (Eligible API)

Call `getEligibility` on checkout or cart pages to decide whether to display the SnappPay payment option:

```php
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\Enums\PaymentMethodType;

// Total amount in IRR (Rials)
$amount = 15000000; // 15,000,000 Rials (1,500,000 Tomans)

$eligibility = SnappPay::getEligibility(
    amount: $amount,
    paymentMethodTypes: [PaymentMethodType::INSTALLMENT, PaymentMethodType::FINANCING]
);

if ($eligibility->isEligible()) {
    // Render dynamic title and description in your checkout UI
    $title = $eligibility->titleMessage;    // e.g. "پرداخت اقساطی اسنپ‌پی"
    $desc  = $eligibility->description;     // e.g. "پرداخت در ۴ قسط بدون سود و کارمزد"
} else {
    // Hide SnappPay payment method
}
```

---

### 2. Request Payment Token & Redirect

#### Option A: Domain Model Integration (Cleanest & Recommended)

Your Eloquent models (e.g. `Order`, `Invoice`) can implement the `SnappPayable` contract:

```php
use Illuminate\Database\Eloquent\Model;
use Eamirgh\SnappPay\Contracts\SnappPayable;
use Eamirgh\SnappPay\Traits\HasSnappPayPayment;

class Order extends Model implements SnappPayable
{
    use HasSnappPayPayment;

    // HasSnappPayPayment automatically maps:
    // id, total_amount, customer_mobile, discount_amount, and items relations
}
```

Initiate checkout with a single line:

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$result = SnappPay::requestPaymentFor($order);

// Store token & redirect customer
$order->update(['snapppay_token' => $result->paymentToken]);

return redirect()->away($result->paymentPageUrl);
```

#### Option B: Full Multi-Item Cart DTOs

```php
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;

// 1. Build Cart Items
$item1 = CartItem::make(
    id: 101,
    name: 'Leather Shoes',
    count: 1,
    amount: 10000000, // Unit price in IRR
    category: 'Footwear',
    commissionType: 100 // Default 100
);

$item2 = CartItem::make(
    id: 102,
    name: 'Cotton Socks',
    count: 2,
    amount: 1000000, // Unit price in IRR
    category: 'Clothing',
    commissionType: 100
);

// 2. Build Cart (Total = 10,000,000 + 2 * 1,000,000 = 12,000,000 IRR)
$cart = Cart::make(
    cartId: 'cart_5501',
    cartItems: [$item1, $item2],
    isShipmentIncluded: true,
    isTaxIncluded: true
);

// 3. Build Payment Token Request
// Calculation rule: sum(carts) - discountAmount - externalSourceAmount == amount
$request = PaymentTokenRequest::make(
    amount: 11000000, // 12,000,000 - 1,000,000 (discount) = 11,000,000 IRR
    cartList: [$cart],
    mobile: '09123456789',
    returnURL: route('snapppay.callback'),
    transactionId: 'ORD-987654', // 5-10 digits or >10 chars with letters
    discountAmount: 1000000,
    externalSourceAmount: 0
);

// 4. Dispatch request
$result = SnappPay::getPaymentToken($request);

// Store paymentToken and transactionId in your database
$order->update([
    'snapppay_token' => $result->paymentToken,
    'snapppay_transaction_id' => $result->transactionId,
]);

// 5. Redirect customer to SnappPay payment gateway
return redirect()->away($result->paymentPageUrl);
```

#### Option C: Quick Token (Simple orders / single basket)

```php
$result = SnappPay::quickToken(
    amount: 5000000, // IRR
    mobile: '09123456789',
    returnURL: route('snapppay.callback'),
    transactionId: 'ORD-' . $order->id,
    discountAmount: 0
);

return redirect()->away($result->paymentPageUrl);
```

---

### 3. Handle Callback & Resilient Settlement

When the customer finishes payment, SnappPay dispatches an **HTTP POST** request to your `returnURL`:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\Exceptions\SnappPayException;

class SnappPayCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Parse inbound callback POST payload
        $callback = SnappPay::parseCallback($request);

        $order = Order::where('snapppay_transaction_id', $callback->transactionId)->firstOrFail();

        // 2. Check callback state
        if ($callback->isFailed()) {
            $order->markAsFailed();
            return redirect()->route('checkout.failed')->with('error', 'Payment was canceled or failed.');
        }

        // 3. Verify and Settle with built-in resilience (Sections 11 & 12)
        try {
            $settleResult = SnappPay::verifyAndSettle($order->snapppay_token);

            $order->markAsPaid($settleResult->transactionId);

            return redirect()->route('checkout.success')->with('success', 'Order completed successfully!');
        } catch (SnappPayException $e) {
            report($e);
            return redirect()->route('checkout.failed')->with('error', $e->getMessage());
        }
    }
}
```

---

## 🛡️ Resilience Workflows (Sections 11 & 12)

SnappPay documentation requires merchants to handle network timeouts and transient errors explicitly:

### Verify Management (Section 11)
```php
// Handles 30-second timeout.
// If verify times out, queries payment status:
//   - If VERIFY: continues successfully.
//   - If PENDING: retries verify.
//   - Otherwise: throws SnappPayApiException.
$verifyResult = SnappPay::verifyWithResilience($paymentToken);
```

### Settle Management (Section 12)
```php
// If settle fails or times out:
//   - Queries payment status.
//   - If SETTLE: considers transaction finalized.
//   - If VERIFY: retries settle call.
//   - Otherwise: raises exception.
$settleResult = SnappPay::settleWithResilience($paymentToken);
```

---

## 🔄 Post-Settlement Operations

### Cancel Order
> ⚠️ **Warning**: Permanently voids orders resting in `SETTLE` status. Irreversible.

```php
$cancelResult = SnappPay::cancel($order->snapppay_token);
```

### Update Order (Multi-Item Cart Adjustment)
> ⚠️ **Warning**: Mandatory for multi-item stores when items change or are removed post-settlement. New amount must be strictly less than original total. Completely removed products must be excluded from cart items.

```php
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;

$updatedCart = Cart::make(
    cartId: 1,
    cartItems: [$item1] // Item 2 was removed
);

$updateRequest = UpdateOrderRequest::make(
    paymentToken: $order->snapppay_token,
    amount: 10000000, // Strictly less than original (11,000,000 IRR)
    cartList: [$updatedCart],
    originalAmount: 11000000
);

$updateResult = SnappPay::update($updateRequest);
```

### Revert Payment (Pre-Settlement Fallback)
```php
// Void unfulfilled order before settlement (e.g., inventory ran out before callback)
$revertResult = SnappPay::revert($order->snapppay_token);
```

### Query Payment Status
```php
$status = SnappPay::getStatus($order->snapppay_token);

$status->transactionId; // e.g. "ORD-987654"
$status->status;        // PaymentStatus::SETTLE, VERIFY, PENDING, CANCEL, REVERT
$status->amount;        // 11000000
$status->isSettled();   // bool
$status->isVerified();  // bool
```

---

## 🧩 Clean Architecture: Interface Contracts

For decoupled enterprise design and effortless testing, type-hint the `SnappPayGateway` contract in your controllers or payment coordinators:

```php
namespace App\Http\Controllers;

use Eamirgh\SnappPay\Contracts\SnappPayGateway;
use App\Models\Order;

class CheckoutController extends Controller
{
    public function __construct(
        protected SnappPayGateway $snappPay
    ) {
    }

    public function checkout(Order $order)
    {
        $result = $this->snappPay->requestPaymentFor($order);

        return redirect()->away($result->paymentPageUrl);
    }
}
```

### Model Adaptation Contracts & Traits

| Contract | Trait | Target Model | Purpose |
|---|---|---|---|
| `SnappPayable` | `HasSnappPayPayment` | `Order`, `Invoice` | Adapts model directly for checkout via `requestPaymentFor($order)`. |
| `SnappPayCartInterface` | `HasSnappPayCart` | `Cart`, `Basket` | Adapts cart container into `Cart` DTO via `Cart::adapt($cart)`. |
| `SnappPayCartItemInterface` | `HasSnappPayCartItem` | `Product`, `OrderItem` | Adapts line items into `CartItem` DTO via `CartItem::adapt($item)`. |

---

## 🏢 Multi-Merchant & Dynamic Connections

Define multiple connections in `config/snapppay.php`:

```php
'connections' => [
    'default' => [
        'base_url' => env('SNAPP_PAY_BASE_URL'),
        'client_id' => env('SNAPP_PAY_CLIENT_ID'),
        // ...
    ],
    'secondary_store' => [
        'base_url' => env('SNAPP_PAY_STORE2_BASE_URL'),
        'client_id' => env('SNAPP_PAY_STORE2_CLIENT_ID'),
        // ...
    ],
],
```

Switch connections at runtime:

```php
$store2Service = SnappPay::connection('secondary_store');
$store2Service->getPaymentToken($request);
```

Or build an on-the-fly instance with dynamic database credentials:

```php
$customService = SnappPay::build([
    'base_url' => 'https://api.snapppay.ir',
    'client_id' => $tenant->snapppay_client_id,
    'client_secret' => $tenant->snapppay_client_secret,
    'username' => $tenant->snapppay_username,
    'password' => $tenant->snapppay_password,
]);

$customService->getPaymentToken($request);
```

---

## 📡 Lifecycle Events

Register listeners in your `EventServiceProvider`:

| Event | Dispatched When |
|---|---|
| `Eamirgh\SnappPay\Events\PaymentTokenCreated` | Payment token was generated. |
| `Eamirgh\SnappPay\Events\PaymentVerified` | Payment was verified. |
| `Eamirgh\SnappPay\Events\PaymentSettled` | Payment was settled. |
| `Eamirgh\SnappPay\Events\PaymentReverted` | Payment was reverted. |
| `Eamirgh\SnappPay\Events\PaymentCancelled` | Order was cancelled. |
| `Eamirgh\SnappPay\Events\PaymentUpdated` | Order items were updated. |

---

## 🧪 Testing & Mocking

Testing your integration is painless using Laravel's native `Http::fake()`:

```php
use Illuminate\Support\Facades\Http;
use Eamirgh\SnappPay\Facades\SnappPay;

public function test_payment_redirect()
{
    Http::fake([
        'https://api.snapppay.ir/api/online/v1/oauth/token' => Http::response([
            'access_token' => 'mock_token',
            'expires_in' => 3600,
        ], 200),
        'https://api.snapppay.ir/api/online/payment/v1/token' => Http::response([
            'successful' => true,
            'response' => [
                'paymentToken' => 'tok_test',
                'paymentPageUrl' => 'https://snapppay.ir/pay/tok_test',
            ],
        ], 200),
    ]);

    $res = SnappPay::quickToken(1000000, '09121111111', 'https://shop.test/cb', 'TX-12345');

    $this->assertSame('tok_test', $res->paymentToken);
}
```

Run package test suite:

```bash
composer test
```

---

## 🇮🇷 راهنمای فارسی (Persian Quick Guide)

پکیج اسنپ‌پی برای لاراول به‌صورت اختصاصی بر اساس آخرین مستندات رسمی سلف اسنپ‌پی (نسخه ۲.۱) توسعه یافته است.

### مراحل اصلی پیاده‌سازی:
1. **بررسی Eligible**: پیش از نمایش گزینه پرداخت در سایت، متد `SnappPay::getEligibility()` را فراخوانی کنید. در صورت `true` بودن، عنوان و توضیحات داینامیک را در صفحه تسویه حساب نمایش دهید.
2. **دریافت توکن پرداخت**: با متد `SnappPay::getPaymentToken()` یا متد ساده‌شده `SnappPay::quickToken()` توکن پرداخت را دریافت و کاربر را به آدرس `paymentPageUrl` ریدایرکت کنید.
3. **کالبک**: نتیجه پرداخت به‌صورت POST به آدرس بازگشت شما ارسال می‌شود. با متد `SnappPay::parseCallback($request)` اطلاعات دریافتی را تحلیل کنید.
4. **وریفای و تسویه حساب (Verify & Settle)**: در صورت موفقیت کالبک، متد یک‌خطی و تاب‌آور `SnappPay::verifyAndSettle($paymentToken)` را فراخوانی کنید تا تاییدیه و نهایی‌سازی سفارش به‌صورت خودکار و مطابق الزامات بند ۱۱ و ۱۲ مستندات فنی انجام شود.

> **نکته بسیار مهم**: کلیه مبالغ به **ریال (IRR)** است و آی‌پی سرور شما باید توسط تیم فنی اسنپ‌پی در لیست سفید (Whitelist) قرار گرفته باشد.

---

## 📄 License

This package is open-source software licensed under the [MIT license](LICENSE).
