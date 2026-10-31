# فرآیند کامل پرداخت و توکن

فرآیند پرداخت اسنپ‌پی شامل دریافت یک توکن معتبر خرید و انتقال کاربر به درگاه پرداخت اسنپ‌پی است.

---

## ۱. نحوه ارسال سبد خرید و دریافت توکن (پیشنهادی برای فروشگاه‌ها)

اسنپ‌پی در فروشگاه‌های چندآیتمی، اطلاعات جزء‌به‌جزء کالاها را برای پردازش اقساط دریافت می‌کند:

```php
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;

// ۱. تعریف اقلام سبد خرید
$item1 = CartItem::make(
    id: 101,
    name: 'کفش ورزشی نایک',
    count: 1,
    amount: 12000000, // مبلغ هر واحد به ریال
    category: 'پوشاک',
    commissionType: 100 // در صورت عدم دسته‌بندی خاص، عدد ۱۰۰ ارسال شود
);

$item2 = CartItem::make(
    id: 102,
    name: 'جوراب ورزشی',
    count: 2,
    amount: 1000000, // مبلغ هر واحد به ریال
    category: 'پوشاک',
    commissionType: 100
);

// ۲. ساخت سبد خرید
$cart = Cart::make(
    cartId: $order->id,
    cartItems: [$item1, $item2],
    isShipmentIncluded: true,
    isTaxIncluded: true
);

// ۳. ایجاد درخواست دریافت توکن
// فرمول: مجموع سبدها منهای تخفیف منهای منبع خارجی برابر با مبلغ نهایی سفارش
$request = PaymentTokenRequest::make(
    amount: 13000000, // 14,000,000 - 1,000,000 (تخفیف) = 13,000,000 ریال
    cartList: [$cart],
    mobile: $order->customer_mobile, // مثلا 09123456789
    returnURL: route('payment.snapppay.callback'),
    transactionId: 'ORD-' . $order->id, // ۵ تا ۱۰ رقم، یا بالای ۱۰ کاراکتر با حداقل یک حرف
    discountAmount: 1000000,
    externalSourceAmount: 0
);

// ۴. ارسال درخواست و دریافت پاسخ
$result = SnappPay::getPaymentToken($request);

// ذخیره توکن در دیتابیس
$order->update([
    'snapppay_token' => $result->paymentToken,
    'snapppay_transaction_id' => $result->transactionId,
]);

// ۵. ریدایرکت مشتری به درگاه اسنپ‌پی
return redirect()->away($result->paymentPageUrl);
```

---

## ۲. روش سریع و تک‌خطی (`quickToken`)

در صورتی که نیازی به تفکیک جداگانه کالاها ندارید یا سفارش ساده دارید:

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$result = SnappPay::quickToken(
    amount: 5000000, // ریال
    mobile: '09123456789',
    returnURL: route('payment.snapppay.callback'),
    transactionId: 'TX-' . $order->id,
    discountAmount: 0
);

return redirect()->away($result->paymentPageUrl);
```

---

## ۳. پردازش کالبک و تسویه حساب (Callback Controller)

پس از پرداخت، اسنپ‌پی کاربر را از طریق متد **HTTP POST** به آدرس `returnURL` بازمی‌گرداند:

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
        // ۱. پارس کردن داده‌های دریافتی از اسنپ‌پی
        $callback = SnappPay::parseCallback($request);

        $order = Order::where('snapppay_transaction_id', $callback->transactionId)->firstOrFail();

        // ۲. بررسی وضعیت بازگشتی
        if ($callback->isFailed()) {
            $order->update(['status' => 'failed']);
            return redirect()->route('cart')->with('error', 'پرداخت با شکست مواجه شد.');
        }

        // ۳. اجرای وریفای و تسویه با تاب‌آوری خودکار
        try {
            $settleResult = SnappPay::verifyAndSettle($order->snapppay_token);

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return redirect()->route('order.success', $order)->with('success', 'خرید با موفقیت نهایی شد.');
        } catch (SnappPayException $e) {
            report($e);
            return redirect()->route('cart')->with('error', 'خطا در تایید سفارش: ' . $e->getMessage());
        }
    }
}
```

::: tip استثنا کردن مسیر کالبک از CSRF
به دلیل اینکه متد بازگشت اسنپ‌پی `POST` است، حتماً آدرس کالبک را در فایل `bootstrap/app.php` (لاراول ۱۱ و ۱۲) از تاییدیه CSRF مستثنی نمایید:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'payment/snapppay/callback',
    ]);
})
```
:::
