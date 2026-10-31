# Interface Contracts & Clean Architecture

In enterprise Laravel applications, adhering to Dependency Inversion (SOLID) means depending on abstractions rather than concrete classes.

`eamirgh/snapp-pay-laravel` provides comprehensive interface contracts for dependency injection and domain model binding.

---

## 1. `SnappPayGateway` Contract

Instead of coupling your controllers and services to the static `SnappPay` facade, you can type-hint `SnappPayGateway`:

```php
namespace App\Services;

use Eamirgh\SnappPay\Contracts\SnappPayGateway;
use App\Models\Order;

class PaymentCoordinator
{
    public function __construct(
        protected SnappPayGateway $gateway
    ) {
    }

    public function processCheckout(Order $order)
    {
        return $this->gateway->requestPaymentFor($order);
    }

    public function handleCallback(string $paymentToken)
    {
        return $this->gateway->verifyAndSettle($paymentToken);
    }
}
```

The Laravel service container resolves `SnappPayGateway` to the default configured `SnappPayManager` instance automatically.

---

## 2. `SnappPayable` Domain Model Contract

Application models (e.g. `Order`, `Invoice`, `CheckoutSession`, `Booking`) can implement `SnappPayable`:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Eamirgh\SnappPay\Contracts\SnappPayable;
use Eamirgh\SnappPay\Traits\HasSnappPayPayment;

class Order extends Model implements SnappPayable
{
    use HasSnappPayPayment;

    // HasSnappPayPayment automatically maps:
    // - getSnappPayTransactionId() -> 'ORD-' . $this->getKey()
    // - getSnappPayAmount() -> (int) $this->total_amount
    // - getSnappPayMobile() -> $this->customer_mobile
    // - getSnappPayReturnUrl() -> route('payment.snapppay.callback', ['order' => $this->id])
    // - getSnappPayDiscountAmount() -> (int) $this->discount_amount
    // - getSnappPayExternalSourceAmount() -> (int) $this->wallet_amount
}
```

### Requesting Payment for a Payable Model

With `SnappPayable`, initiating payment is a clean single call:

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$result = SnappPay::requestPaymentFor($order);

return redirect()->away($result->paymentPageUrl);
```

---

## 3. `SnappPayCartInterface` Cart Model Contract

Your application's Cart / Basket model can implement `SnappPayCartInterface` using the `HasSnappPayCart` trait:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Eamirgh\SnappPay\Contracts\SnappPayCartInterface;
use Eamirgh\SnappPay\Traits\HasSnappPayCart;

class Cart extends Model implements SnappPayCartInterface
{
    use HasSnappPayCart;

    // HasSnappPayCart automatically maps:
    // - getSnappPayCartId() -> $this->getKey()
    // - getSnappPayCartItems() -> $this->items
    // - isSnappPayShipmentIncluded() -> $this->is_shipment_included ?? true
    // - isSnappPayTaxIncluded() -> $this->is_tax_included ?? true
    // - getSnappPayShippingAmount() -> $this->shipping_amount ?? 0
    // - getSnappPayTaxAmount() -> $this->tax_amount ?? 0
    // - toSnappPayCart() -> Cart::fromContract($this)
}
```

You can pass `SnappPayCartInterface` models directly into `PaymentTokenRequest::make()` or `UpdateOrderRequest::make()`. The DTOs will adapt them automatically via `Cart::adapt($model)`.

---

## 4. `SnappPayCartItemInterface` Line Item Contract

For individual line items or products, implement `SnappPayCartItemInterface` with the `HasSnappPayCartItem` trait:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Eamirgh\SnappPay\Contracts\SnappPayCartItemInterface;
use Eamirgh\SnappPay\Traits\HasSnappPayCartItem;

class CartItem extends Model implements SnappPayCartItemInterface
{
    use HasSnappPayCartItem;

    // HasSnappPayCartItem automatically maps:
    // - getSnappPayItemId() -> $this->product_id ?? $this->id
    // - getSnappPayItemName() -> $this->name ?? $this->title
    // - getSnappPayItemCount() -> $this->quantity ?? 1
    // - getSnappPayItemAmount() -> $this->unit_price ?? $this->amount (IRR)
    // - getSnappPayItemCategory() -> $this->category ?? 'General'
    // - getSnappPayItemCommissionType() -> $this->commission_type ?? 100
    // - toSnappPayCartItem() -> CartItem::fromContract($this)
}
```

---

## 5. `SnappPayClientInterface` & `TokenManagerInterface`

For low-level customization or mock testing:

- **`SnappPayClientInterface`**: Contract for the HTTP client executing SnappPay API routes.
- **`TokenManagerInterface`**: Contract for OAuth2 JWT token acquisition and caching.
