# SnappPay Facade API Reference

The `Eamirgh\SnappPay\Facades\SnappPay` facade provides convenient access to all service methods.

---

### `getEligibility`
```php
public static function getEligibility(int $amount, array $paymentMethodTypes = []): EligibilityResult
```
Queries merchant and cart installment eligibility.
- **Parameters**:
  - `$amount` (int): Cart amount in IRR.
  - `$paymentMethodTypes` (array): Optional array of `PaymentMethodType` or strings (`'POSTPAID'`, `'INSTALLMENT'`, `'FINANCING'`).
- **Returns**: `EligibilityResult` (`isEligible()`, `$titleMessage`, `$description`).

---

### `getPaymentToken`
```php
public static function getPaymentToken(PaymentTokenRequest $request): PaymentTokenResult
```
Requests a payment token and redirect URL using a complete itemized cart.
- **Parameters**: `PaymentTokenRequest $request`.
- **Returns**: `PaymentTokenResult` (`$paymentToken`, `$paymentPageUrl`, `$transactionId`).

---

### `requestPaymentFor`
```php
public static function requestPaymentFor(SnappPayable $payable, ?array $forcedPaymentMethodTypes = null): PaymentTokenResult
```
Requests a payment token directly for an application domain model (e.g. `Order`, `Invoice`) implementing the `SnappPayable` interface contract.
- **Parameters**:
  - `$payable` (`SnappPayable`): The domain model instance.
  - `$forcedPaymentMethodTypes` (`?array`): Optional payment method restrictions.
- **Returns**: `PaymentTokenResult`.

---

### `quickToken`
```php
public static function quickToken(
    int $amount,
    string $mobile,
    string $returnURL,
    string $transactionId,
    array $items = [],
    int $discountAmount = 0,
    int $externalSourceAmount = 0,
    ?array $forcedPaymentMethodTypes = null
): PaymentTokenResult
```
Shortcut method to initiate a payment token for simple baskets without constructing full Cart models manually.

---

### `verify`
```php
public static function verify(string $paymentToken): TransactionResult
```
Verifies a payment once after receiving a successful callback (`state = 'OK'`).

---

### `settle`
```php
public static function settle(string $paymentToken): TransactionResult
```
Settles an order after successful verification to finalize the transaction and capture funds.

---

### `verifyWithResilience`
```php
public static function verifyWithResilience(string $paymentToken, ?int $maxRetries = null): TransactionResult
```
Executes the Section 11 Verify Management workflow: handles 30s timeouts by falling back to status inquiry and auto-retrying if status is `PENDING`.

---

### `settleWithResilience`
```php
public static function settleWithResilience(string $paymentToken, ?int $maxRetries = null): TransactionResult
```
Executes the Section 12 Settle Management workflow: checks payment status on network timeout or false response to ensure idempotent settlement.

---

### `verifyAndSettle`
```php
public static function verifyAndSettle(string $paymentToken): TransactionResult
```
Executes `verifyWithResilience()` followed by `settleWithResilience()`. The recommended standard for callback handlers.

---

### `revert`
```php
public static function revert(string $paymentToken): TransactionResult
```
Reverts an order before settlement if merchant fulfillment fails.

---

### `cancel`
```php
public static function cancel(string $paymentToken): TransactionResult
```
Permanently cancels an order in `SETTLE` status.

---

### `update`
```php
public static function update(UpdateOrderRequest $request): TransactionResult
```
Updates cart items or order total for settled orders (new total must be strictly less than original).

---

### `getStatus`
```php
public static function getStatus(string $paymentToken): StatusResult
```
Inspects current payment lifecycle status (`SETTLE`, `VERIFY`, `PENDING`, `CANCEL`, `REVERT`).

---

### `parseCallback`
```php
public static function parseCallback(Request|array $request): CallbackPayload
```
Extracts and validates inbound POST callback parameters (`transactionId`, `state`, `amount`).

---

### `connection`
```php
public static function connection(?string $name = null): SnappPayService
```
Switches to a named connection configured in `config/snapppay.php`.

---

### `build`
```php
public static function build(array $customConfig): SnappPayService
```
Instantiates an isolated SnappPay service with custom credentials at runtime.
