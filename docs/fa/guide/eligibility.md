# بررسی امکان پرداخت اقساطی (Eligible)

پیش از آنکه گزینه پرداخت اقساطی اسنپ‌پی را در صفحه سبد خرید یا تسویه حساب سایت به کاربر نمایش دهید، باید از طریق این وب‌سرویس وضعیت امکان‌پذیری خرید اقساطی را استعلام کنید.

---

## قوانین مهم پیاده‌سازی از دیدگاه اسنپ‌پی

::: warning قوانین نمایش رابط کاربری (الزامات فنی اسنپ‌پی)
1. **نمایش داینامیک عنوان و توضیحات**: عنوان (`titleMessage`) و توضیحات (`description`) بازگشتی از این سرویس همواره داینامیک هستند. به هیچ عنوان این متون را به صورت دستی یا هاردکد نمایش ندهید.
2. **محاسبه مجدد با تغییر مبلغ**: با هر تغییر در قیمت، سبد خرید، کد تخفیف یا هزینه ارسال، باید مجدداً این سرویس را فراخوانی کنید.
3. **عدم نمایش در صورت `false` بودن**: اگر پاسخ سرویس `false` بود، گزینه پرداخت اسنپ‌پی باید به طور کامل از دید کاربر پنهان شود.
4. **محدودیت مبالغ در استیج**: در محیط تست، مبالغ زیر ۴ هزار تومان و بالای ۱۰ میلیون تومان به صورت خودکار `false` برمی‌گردانند.
:::

---

## نحوه استفاده در کنترلر یا Livewire

```php
use Eamirgh\SnappPay\Facades\SnappPay;

// مبلغ حتماً باید به ریال (IRR) باشد
$amountInRials = 15000000; // معادل ۱,۵۰۰,۰۰۰ تومان

$eligibility = SnappPay::getEligibility($amountInRials);

if ($eligibility->isEligible()) {
    // روش پرداخت فعال است
    $title = $eligibility->titleMessage; // مثلا: "پرداخت اقساطی اسنپ‌پی"
    $description = $eligibility->description; // مثلا: "پرداخت در ۴ قسط بدون سود و کارمزد"
} else {
    // روش پرداخت اسنپ‌پی را در فرم تسویه حساب پنهان کنید
}
```

---

## نمونه پیاده‌سازی در قالب Blade

```blade
@if($eligibility->isEligible())
    <div class="border rounded-lg p-4 flex items-start gap-3 my-2 bg-white">
        <input type="radio" name="payment_gateway" value="snapppay" id="gateway_snapppay" class="mt-1" />
        <label for="gateway_snapppay" class="cursor-pointer">
            <span class="block font-bold text-gray-900">
                {{ $eligibility->titleMessage }}
            </span>
            <span class="block text-sm text-gray-600 mt-1">
                {{ $eligibility->description }}
            </span>
        </label>
    </div>
@endif
```
