# Multi-Tenant & Multi-Merchant

Many enterprise applications, SaaS platforms, or multi-vendor marketplaces require handling separate SnappPay credentials for different merchants, stores, or subsidiaries.

`eamirgh/snapp-pay-laravel` natively supports both static configuration connections and on-the-fly dynamic credential building.

---

## 1. Multiple Configured Connections

Define multiple merchant accounts in `config/snapppay.php`:

```php
'connections' => [
    'default' => [
        'base_url' => env('SNAPP_PAY_BASE_URL'),
        'client_id' => env('SNAPP_PAY_CLIENT_ID'),
        'client_secret' => env('SNAPP_PAY_CLIENT_SECRET'),
        'username' => env('SNAPP_PAY_USERNAME'),
        'password' => env('SNAPP_PAY_PASSWORD'),
    ],
    'fashion_branch' => [
        'base_url' => env('SNAPP_PAY_FASHION_BASE_URL'),
        'client_id' => env('SNAPP_PAY_FASHION_CLIENT_ID'),
        'client_secret' => env('SNAPP_PAY_FASHION_CLIENT_SECRET'),
        'username' => env('SNAPP_PAY_FASHION_USERNAME'),
        'password' => env('SNAPP_PAY_FASHION_PASSWORD'),
    ],
],
```

Switch between them at runtime:

```php
use Eamirgh\SnappPay\Facades\SnappPay;

// Uses 'default' connection
$token = SnappPay::getPaymentToken($request);

// Switch to 'fashion_branch' connection
$fashionToken = SnappPay::connection('fashion_branch')->getPaymentToken($request);
```

---

## 2. On-The-Fly Dynamic Credentials

For SaaS applications where tenant credentials are stored in a database:

```php
use Eamirgh\SnappPay\Facades\SnappPay;

$tenant = Tenant::findOrFail($tenantId);

$tenantService = SnappPay::build([
    'base_url' => $tenant->snapppay_base_url,
    'client_id' => $tenant->snapppay_client_id,
    'client_secret' => $tenant->snapppay_client_secret,
    'username' => $tenant->snapppay_username,
    'password' => $tenant->snapppay_password,
    'timeout' => 30,
]);

// Full API methods are available on the dynamic instance:
$eligibility = $tenantService->getEligibility(5000000);
$token = $tenantService->getPaymentToken($request);
```
