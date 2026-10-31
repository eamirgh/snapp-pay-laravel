# Exceptions

All package exceptions inherit from `Eamirgh\SnappPay\Exceptions\SnappPayException`.

```
SnappPayException (base)
├── SnappPayApiException
├── SnappPayAuthException
├── SnappPayValidationException
└── SnappPayTimeoutException
```

---

## `SnappPayApiException`
Thrown when the SnappPay API responds with `successful: false` or returns an HTTP 4xx/5xx error status code.

### Methods
- `getErrorCode(): int|string`: SnappPay specific error code (from `errorData.errorCode`).
- `getErrorData(): mixed`: Full error data payload.
- `getStatusCode(): int`: HTTP status code.
- `getRawResponse(): ?string`: Raw response body string.

---

## `SnappPayAuthException`
Thrown when OAuth credentials fail or access is denied.

- `SnappPayAuthException::invalidCredentials($details)`: Bad client ID, client secret, username, or password (`HTTP 401`).
- `SnappPayAuthException::accessDenied($details)`: Server IP is not whitelisted by SnappPay operations (`HTTP 403`).

---

## `SnappPayValidationException`
Thrown locally prior to API calls when validation rules fail.

- `SnappPayValidationException::invalidTransactionId($id)`: When `transactionId` violates length or character rules.
- `SnappPayValidationException::amountMismatch($declared, $expected)`: When cart line sums do not balance against net `amount`.
- `SnappPayValidationException::updateAmountTooHigh($new, $original)`: When post-settlement update amount is not strictly lower than the original.

---

## `SnappPayTimeoutException`
Thrown when an HTTP connection times out or the socket drops.

### Properties
- `$paymentToken`: Associated payment session token.
- `$action`: Action attempted (`'verify'`, `'settle'`, etc.).
- Inherited `$previous`: Original `Illuminate\Http\Client\ConnectionException`.
