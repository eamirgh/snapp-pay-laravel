# اینترفیس‌ها و قراردادها (Contracts)

در معماری‌های تمیز (Clean Architecture) و نرم‌افزارهای تجاری لاراولی، وابستگی به لایه‌های تجریدی (Interfaces) به جای کلاس‌های مشخص (Concrete Classes)، انعطاف‌پذیری و تست‌پذیری برنامه را ارتقا می‌دهد.

پکیج `eamirgh/snapp-pay-laravel` شامل مجموعه‌ای از قراردادها (Contracts) و تریت‌ها (Traits) برای تزریق وابستگی در کنترلرها و متصل کردن مدل‌های دامنه به درگاه اسنپ‌پی است.

---

## ۱. قرارداد `SnappPayGateway`

به جای استفاده مستقیم از فساد استاتیک `SnappPay`، می‌توانید اینترفیس `SnappPayGateway` را در کنترلرها یا سرویس‌های لاراولی خود تزریق کنید (Dependency Injection):

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

    public function initiatePayment(Order $order)
    {
        $result = $this->snappPay->requestPaymentFor($order);

        return redirect()->away($result->paymentPageUrl);
    }
}
```

کانتینر لاراول به صورت خودکار نمونه پیش‌فرض `SnappPayGateway` را حل (Resolve) می‌کند.

---

## ۲. قرارداد مدل دامنه `SnappPayable`

مدل‌های برنامه مانند `Order` یا `Invoice` می‌توانند قرارداد `SnappPayable` را پیاده‌سازی کنند:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Eamirgh\SnappPay\Contracts\SnappPayable;
use Eamirgh\SnappPay\Traits\HasSnappPayPayment;

class Order extends Model implements SnappPayable
{
    use HasSnappPayPayment;

    // تریت HasSnappPayPayment موارد زیر را به طور پیش‌فرض مپ می‌کند:
    // - getSnappPayTransactionId() -> شناسه یکتای سفارش با طول مجاز
    // - getSnappPayAmount() -> مبلغ کل به ریال
    // - getSnappPayMobile() -> شماره همراه مشتری
    // - getSnappPayReturnUrl() -> آدرس کالبک
    // - getSnappPayDiscountAmount() -> مبلغ تخفیف
    // - getSnappPayExternalSourceAmount() -> مبلغ کیف پول یا بن
}
```

### پرداخت تک‌خطی برای مدل

```php
$result = SnappPay::requestPaymentFor($order);

return redirect()->away($result->paymentPageUrl);
```

---

## ۳. قرارداد سبد خرید `SnappPayCartInterface`

مدل‌های سبد خرید یا پیش‌فاکتور در برنامه شما می‌توانند این اینترفیس را با استفاده از تریت `HasSnappPayCart` پیاده‌سازی کنند:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Eamirgh\SnappPay\Contracts\SnappPayCartInterface;
use Eamirgh\SnappPay\Traits\HasSnappPayCart;

class Cart extends Model implements SnappPayCartInterface
{
    use HasSnappPayCart;

    // تریت HasSnappPayCart به صورت پیش‌فرض موارد زیر را مپ می‌کند:
    // - getSnappPayCartId() -> شناسه سبد خرید
    // - getSnappPayCartItems() -> لیست اقلام سبد خرید
    // - isSnappPayShipmentIncluded() -> احتساب هزینه ارسال
    // - isSnappPayTaxIncluded() -> احتساب مالیات
    // - getSnappPayShippingAmount() -> هزینه ارسال
    // - getSnappPayTaxAmount() -> مبلغ مالیات
    // - toSnappPayCart() -> تبدیل به DTO پکیج
}
```

می‌توانید مدل‌های `SnappPayCartInterface` را مستقیماً به آرایه `cartList` در متد `PaymentTokenRequest::make()` یا `UpdateOrderRequest::make()` پاس دهید.

---

## ۴. قرارداد اقلام سفارش `SnappPayCartItemInterface`

برای فروشگاه‌های چندکالایی، مدل‌های اقلام سفارش (`OrderItem` یا `CartItem`) می‌توانند از تریت `HasSnappPayCartItem` استفاده کنند:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Eamirgh\SnappPay\Contracts\SnappPayCartItemInterface;
use Eamirgh\SnappPay\Traits\HasSnappPayCartItem;

class CartItem extends Model implements SnappPayCartItemInterface
{
    use HasSnappPayCartItem;

    // تریت HasSnappPayCartItem به صورت پیش‌فرض مپ می‌کند:
    // - getSnappPayItemId() -> شناسه محصول
    // - getSnappPayItemName() -> نام کالا
    // - getSnappPayItemCount() -> تعداد
    // - getSnappPayItemAmount() -> قیمت واحد به ریال
    // - getSnappPayItemCategory() -> نام دسته‌بندی
    // - getSnappPayItemCommissionType() -> نوع کارمزد (پیش‌فرض ۱۰۰)
    // - toSnappPayCartItem() -> تبدیل به DTO پکیج
}
```
