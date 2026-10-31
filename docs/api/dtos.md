# Data Transfer Objects (DTOs)

All DTOs implement `Arrayable` and `JsonSerializable`.

---

## `CartItem`
`Eamirgh\SnappPay\DTOs\CartItem`

Represents an individual order item line:
- `id`: `int|string`
- `name`: `string`
- `count`: `int`
- `amount`: `int` (unit price in IRR)
- `category`: `string`
- `commissionType`: `int` (default `100`)

### Methods
- `CartItem::make(...)`: Factory method.
- `CartItem::adapt(mixed $item): CartItem`: Polymorphic adapter that converts `SnappPayCartItemInterface` instances, raw arrays, or existing `CartItem` objects.
- `CartItem::fromContract(SnappPayCartItemInterface $item): CartItem`: Converts an interface model.
- `CartItem::fromArray(array $data)`: Deserialization.
- `totalAmount(): int`: Returns `count * amount`.
- `toArray(): array`: Serializes to SnappPay JSON payload format.

---

## `Cart`
`Eamirgh\SnappPay\DTOs\Cart`

Represents a cart container holding items:
- `cartId`: `int|string`
- `cartItems`: `array<CartItem>`
- `isShipmentIncluded`: `bool` (default `true`)
- `isTaxIncluded`: `bool` (default `true`)
- `shippingAmount`: `int` (default `0`)
- `taxAmount`: `int` (default `0`)
- `totalAmount`: `?int` (auto-calculated if null)

### Methods
- `Cart::make(...)`: Factory method.
- `Cart::adapt(mixed $cart): Cart`: Polymorphic adapter that converts `SnappPayCartInterface` instances, raw arrays, or existing `Cart` objects.
- `Cart::fromContract(SnappPayCartInterface $cart): Cart`: Converts a cart model implementing `SnappPayCartInterface`.
- `Cart::fromArray(array $data)`: Deserialization.
- `addItem(CartItem|SnappPayCartItemInterface $item): self`: Appends an item.
- `calculateTotal(): int`: Computes total based on SnappPay arithmetic rules.
- `getTotalAmount(): int`: Returns pre-set total or computes dynamically.

### Calculation Formula
```
[count] × [amount] + [shippingAmount if not included] + [taxAmount if not included]
```

---

## `PaymentTokenRequest`
`Eamirgh\SnappPay\DTOs\PaymentTokenRequest`

The complete request payload for initiating payment:
- `amount`: `int` (Net order amount)
- `cartList`: `array<Cart>`
- `mobile`: `string`
- `returnURL`: `string`
- `transactionId`: `string`
- `discountAmount`: `int`
- `externalSourceAmount`: `int`
- `forcedPaymentMethodTypes`: `?array`

### Validation
- Validates `transactionId` requirements (5-10 digits, or >10 chars with letter).
- Validates that `sum(carts) - discountAmount - externalSourceAmount === amount`.

---

## `CallbackPayload`
`Eamirgh\SnappPay\DTOs\CallbackPayload`

Represents inbound data from SnappPay's POST callback:
- `transactionId`: `string`
- `state`: `CallbackState` (`OK` or `FAILED`)
- `amount`: `int`
- `isOk(): bool`
- `isFailed(): bool`

---

## `StatusResult`
`Eamirgh\SnappPay\DTOs\StatusResult`

Represents payment status inquiry response:
- `transactionId`: `string`
- `status`: `PaymentStatus` (`SETTLE`, `CANCEL`, `VERIFY`, `PENDING`, `REVERT`)
- `amount`: `int`
- `isSettled(): bool`
- `isVerified(): bool`
- `isPending(): bool`
- `isCancelled(): bool`
- `isReverted(): bool`

---

## `UpdateOrderRequest`
`Eamirgh\SnappPay\DTOs\UpdateOrderRequest`

Payload for updating orders post-settlement:
- `paymentToken`: `string`
- `amount`: `int` (New total, strictly less than original)
- `cartList`: `array<Cart>` (Excludes removed items)
- `discountAmount`: `int`
- `externalSourceAmount`: `int`
