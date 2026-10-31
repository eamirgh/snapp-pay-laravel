# Getting Started

`eamirgh/snapp-pay-laravel` is a production-grade package designed to integrate the **SnappPay (اسنپ‌پی)** Buy Now, Pay Later (BNPL) payment gateway into Laravel applications.

## Requirements

- **PHP**: `^8.2`, `^8.3`, or `^8.4`
- **Laravel Framework**: `^11.0`, `^12.0`, or `^13.0`
- **PHP Extensions**: `ext-json`, `ext-curl`, `ext-mbstring`

---

## Installation

Install the package via Composer:

```bash
composer require eamirgh/snapp-pay-laravel
```

### Publish Configuration

Publish the `snapppay.php` configuration file to your application's `config/` directory:

```bash
php artisan vendor:publish --tag="snapppay-config"
```

---

## Prerequisite: IP Whitelisting

::: danger Outbound IP Whitelist Required
SnappPay enforces an outbound IP whitelist for **all API calls**, including OAuth authentication and the eligibility endpoint.
Requests originating from unauthorized IP addresses receive an `Access Denied` error (`HTTP 403`).

Before going live:
1. Obtain your application server's static outbound IP address.
2. Submit your IP address to the SnappPay Technical Operations team (`merchant-support@snapppay.ir`).
:::

---

## Environment Stages

SnappPay follows a two-phase onboarding process:

1. **Stage Environment**: Initially, merchants receive sandbox/staging credentials to develop, test scenarios, and participate in pre-demo and demo sessions with SnappPay engineers.
2. **Production Environment**: Granted after passing the technical demo validation.

Set up your `.env` variables accordingly:

```dotenv
# API Base URL (Provided by SnappPay)
SNAPP_PAY_BASE_URL=https://api.snapppay.ir

# Credentials
SNAPP_PAY_CLIENT_ID=your_client_id
SNAPP_PAY_CLIENT_SECRET=your_client_secret
SNAPP_PAY_USERNAME=your_username
SNAPP_PAY_PASSWORD=your_password

# Timeouts (in seconds)
SNAPP_PAY_TIMEOUT=30
SNAPP_PAY_CONNECT_TIMEOUT=10
```

---

## Quick Verification

You can verify your connection in Laravel Tinker:

```bash
php artisan tinker
```

```php
use Eamirgh\SnappPay\Facades\SnappPay;

// Check eligibility for a test amount of 500,000 IRR (50,000 Tomans)
$result = SnappPay::getEligibility(500000);

dump($result);
// => Eamirgh\SnappPay\DTOs\EligibilityResult {
//      eligible: true,
//      titleMessage: "پرداخت اقساطی اسنپ‌پی",
//      description: "پرداخت در ۴ قسط بدون سود و کارمزد"
//    }
```
