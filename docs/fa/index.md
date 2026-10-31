---
layout: home

hero:
  name: "اسنپ‌پی برای لاراول"
  text: "پکیج اتصال به درگاه پرداخت اقساطی اسنپ‌پی"
  tagline: "پیاده‌سازی کامل، مقیاس‌پذیر و تاب‌آور برای لاراول ۱۱، ۱۲ و ۱۳ (PHP 8.2+)"
  image:
    src: /logo.svg
    alt: اسنپ‌پی لاراول
  actions:
    - theme: brand
      text: شروع به کار و راهنما
      link: /fa/guide/getting-started
    - theme: alt
      text: مستندات در گیت‌هاب
      link: https://github.com/eamirgh/snapp-pay-laravel
    - theme: alt
      text: English Docs
      link: /

features:
  - title: منطبق با ویرایش ۲.۱ مستندات فنی
    details: پیاده‌سازی کامل و دقیق وب‌سرویس‌های سلف اسنپ‌پی شامل احراز هویت OAuth، بررسی صلاحیت، دریافت توکن، وریفای، تسویه، لغو و ویرایش سفارش.
  - title: تاب‌آوری خودکار (بند ۱۱ و ۱۲ مستندات)
    details: مدیریت خودکار تایم‌اوت‌های ۳۰ ثانیه‌ای وریفای و قطعی‌های شبکه تسویه حساب مطابق با الزامات فنی رسمی اسنپ‌پی.
  - title: اعتبارسنجی کامل داده‌ها (DTOs)
    details: اعتبارسنجی دقیق شناسه تراکنش (transactionId)، تطابق ارقام سبد خرید، تخفیف‌ها و منابع خارجی پیش از ارسال به وب‌سرویس.
  - title: پشتیبانی از چند پذیرنده (Multi-Merchant)
    details: امکان تعریف همزمان چندین فروشگاه در تنظیمات و یا تزریق داینامیک اطلاعات اتصال برای پلتفرم‌های چندفروشگاهی (Marketplace).
  - title: رویدادها و لاگ‌های امنیتی
    details: انتشار Eventهای استاندارد لاراول در تمامی مراحل پرداخت جهت ثبت گزارشات مالی و وب‌هوک‌های داخلی.
  - title: تست‌پذیری سریع با Http::fake
    details: سازگاری ۱۰۰٪ با ابزارهای تست خودکار لاراول و امکان شبیه‌سازی تمام سناریوهای پرداخت بدون نیاز به اتصال به محیط استیج.
---

<div class="tip custom-block" style="padding-top: 8px; margin-top: 2rem; direction: ltr; text-align: left;">
  <p class="custom-block-title">نصب سریع با کامپوزر</p>
  <pre><code>composer require eamirgh/snapp-pay-laravel</code></pre>
</div>
