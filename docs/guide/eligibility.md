# Checking Eligibility

Before rendering the SnappPay payment option on your checkout or shopping cart page, you must query the **Eligibility API** (`GET /api/online/offer/v1/eligible`).

This service dynamically determines whether the given order amount qualifies for installment payments and returns dynamic display titles and descriptions.

---

## Technical Specifications

- **Route**: `GET /api/online/offer/v1/eligible`
- **Currency**: Iranian Rial (**IRR**)
- **Test Thresholds**: In staging, amounts below 40,000 IRR (4,000 Tomans) and above 100,000,000 IRR (10,000,000 Tomans) will return `eligible: false`.

---

## Basic Usage

```php
use Eamirgh\SnappPay\Facades\SnappPay;

// Amount must be in Iranian Rial (IRR)
$orderAmount = 15000000; // 1,500,000 Tomans

$result = SnappPay::getEligibility($orderAmount);

if ($result->isEligible()) {
    // Show SnappPay in payment methods list
    echo $result->titleMessage; // Dynamic title
    echo $result->description;  // Dynamic subtitle/description
} else {
    // Hide SnappPay option
}
```

---

## Filtering by Payment Methods

You can restrict available payment methods by passing `paymentMethodTypes`:

```php
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\Enums\PaymentMethodType;

$result = SnappPay::getEligibility(
    amount: 15000000,
    paymentMethodTypes: [
        PaymentMethodType::INSTALLMENT,
        PaymentMethodType::FINANCING,
        // PaymentMethodType::POSTPAID
    ]
);
```

If omitted or passed as an empty array, SnappPay returns all available options configured for the merchant.

---

## Strict UI Guidelines

::: warning Mandatory UI Rules (from SnappPay Technical Team)
SnappPay requires all merchants to follow strict visual guidelines:
1. **Dynamic Text Only**: Do **NOT** hardcode Persian labels such as "پرداخت اقساطی ۴ ماهه". Always render `titleMessage` on the first line and `description` on the second line directly from the API response.
2. **Dynamic Recalculation**: Whenever the cart total changes (e.g., promo code applied, shipping updated, item quantity changed), you **must** re-call the eligibility endpoint.
3. **Hide on False**: If `eligible` is `false`, the SnappPay payment option must be completely hidden from the customer.
:::

### Example Blade Component

```blade
@if($eligibility->isEligible())
    <div class="payment-option snapppay-card">
        <input type="radio" name="gateway" value="snapppay" id="gateway_snapppay" />
        <label for="gateway_snapppay">
            <span class="gateway-title font-bold text-gray-900">
                {{ $eligibility->titleMessage }}
            </span>
            <span class="gateway-desc text-sm text-gray-600 block">
                {{ $eligibility->description }}
            </span>
        </label>
    </div>
@endif
```
