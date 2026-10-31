# لغو و ویرایش سفارش (پس از تسویه)

پس از آنکه سفارش به مرحله `SETTLE` رسید و مبلغ قطعی شد، عملیات مالی از طریق سرویس‌های زیر مدیریت می‌شوند.

---

## ۱. لغو سفارش (Cancel)

اگر پس از اتمام فرآیند خرید و قرار گرفتن در وضعیت تسویه، نیاز به لغو کلی سفارش باشد، این وب‌سرویس فراخوانی می‌شود تا قسط‌ها باطل شده و مبلغ پرداختی کاربر بازگردانده شود.

::: danger عملیات برگشت‌ناپذیر
عملیات لغو سفارش قطعی و غیرقابل بازگشت است. در پنل ادمین حتماً از کاربر تاییدیه دریافت کنید.
:::

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$result = SnappPay::cancel($order->snapppay_token);
```

---

## ۲. ویرایش سفارش (Update)

در فروشگاه‌های چندآیتمی، چنانچه یک یا چند کالا از سفارش مشتری حذف شود یا تعداد آن کاهش یابد، این وب‌سرویس الزامی است.

::: warning قوانین مهم ویرایش سفارش
1. مبلغ جدید باید **حتماً کمتر از مبلغ اولیه** سفارش باشد.
2. کالاهایی که کاملاً حذف شده‌اند، باید از آرایه اقلام سبد خرید نیز حذف شوند.
3. این عملیات برگشت‌ناپذیر است.
:::

```php
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;

// بازسازی سبد خرید فقط با اقلام باقی‌مانده
$remainingItem = CartItem::make(
    id: 101,
    name: 'کالای باقی‌مانده',
    count: 1,
    amount: 7000000,
    category: 'عمومی'
);

$cart = Cart::make(
    cartId: $order->id,
    cartItems: [$remainingItem]
);

$request = UpdateOrderRequest::make(
    paymentToken: $order->snapppay_token,
    amount: 7000000, // باید کمتر از مبلغ اولیه (مثلاً ۱۰ میلیون) باشد
    cartList: [$cart],
    originalAmount: 10000000
);

$result = SnappPay::update($request);
```

---

## ۳. بازگشت وجه پیش از تسویه (Revert)

چنانچه کاربر بازگشت موفقی از درگاه داشت اما قبل از فراخوانی `settle()` مشخص شد که کالا در انبار موجود نیست یا امکان ارائه خدمت وجود ندارد، از سرویس Revert استفاده می‌شود.

::: tip نکته
این متد تنها **پیش از تسویه (Settle)** قابل اجراست.
:::

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$result = SnappPay::revert($order->snapppay_token);
```
