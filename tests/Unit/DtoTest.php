<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Eamirgh\SnappPay\DTOs\CallbackPayload;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\EligibilityResult;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\DTOs\PaymentTokenResult;
use Eamirgh\SnappPay\DTOs\StatusResult;
use Eamirgh\SnappPay\DTOs\TransactionResult;
use Eamirgh\SnappPay\DTOs\UpdateOrderRequest;
use Eamirgh\SnappPay\Enums\CallbackState;
use Eamirgh\SnappPay\Enums\PaymentMethodType;
use Eamirgh\SnappPay\Enums\PaymentStatus;
use Eamirgh\SnappPay\Exceptions\SnappPayValidationException;

class DtoTest extends BaseTestCase
{
    public function test_cart_item_creation_and_total(): void
    {
        $item = CartItem::make(101, 'Smartphone', 2, 50000000, 'Digital', 100);

        $this->assertSame(101, $item->id);
        $this->assertSame('Smartphone', $item->name);
        $this->assertSame(2, $item->count);
        $this->assertSame(50000000, $item->amount);
        $this->assertSame('Digital', $item->category);
        $this->assertSame(100, $item->commissionType);
        $this->assertSame(100000000, $item->totalAmount());

        $array = $item->toArray();
        $this->assertSame(101, $array['id']);
        $this->assertSame(100000000, $item->totalAmount());

        $deserialized = CartItem::fromArray($array);
        $this->assertSame('Smartphone', $deserialized->name);
        $this->assertSame(50000000, $deserialized->amount);
    }

    public function test_cart_calculation_with_shipping_and_tax_included(): void
    {
        $item1 = CartItem::make(1, 'Item 1', 1, 100000, 'Cat1');
        $item2 = CartItem::make(2, 'Item 2', 2, 200000, 'Cat2');

        $cart = Cart::make(
            cartId: 1,
            cartItems: [$item1, $item2],
            isShipmentIncluded: true,
            isTaxIncluded: true,
            shippingAmount: 50000,
            taxAmount: 20000
        );

        // 100000 + 400000 = 500000 (shipping & tax included, so not added again)
        $this->assertSame(500000, $cart->calculateTotal());
        $this->assertSame(500000, $cart->getTotalAmount());
    }

    public function test_cart_calculation_with_shipping_and_tax_excluded(): void
    {
        $item = CartItem::make(1, 'Item 1', 2, 100000, 'Cat1');

        $cart = Cart::make(
            cartId: 1,
            cartItems: [$item],
            isShipmentIncluded: false,
            isTaxIncluded: false,
            shippingAmount: 50000,
            taxAmount: 20000
        );

        // 200000 + 50000 (shipping) + 20000 (tax) = 270000
        $this->assertSame(270000, $cart->calculateTotal());
        $this->assertSame(270000, $cart->getTotalAmount());
    }

    public function test_payment_token_request_validation_valid(): void
    {
        $cart = Cart::make(
            cartId: 'c1',
            cartItems: [CartItem::make(1, 'Item', 1, 1000000, 'Cat')]
        );

        // amount = 1000000 - 100000 (discount) - 50000 (external) = 850000
        $request = PaymentTokenRequest::make(
            amount: 850000,
            cartList: [$cart],
            mobile: '09123456789',
            returnURL: 'https://myshop.com/callback',
            transactionId: '1234567', // 7 digits
            discountAmount: 100000,
            externalSourceAmount: 50000,
            forcedPaymentMethodTypes: [PaymentMethodType::INSTALLMENT]
        );

        $this->assertSame(850000, $request->amount);
        $this->assertSame('1234567', $request->transactionId);
        $payload = $request->toArray();
        $this->assertSame(['INSTALLMENT'], $payload['forcedPaymentMethodTypes']);
    }

    public function test_payment_token_request_long_transaction_id_with_letter(): void
    {
        $cart = Cart::make(cartId: 1, cartItems: [CartItem::make(1, 'Item', 1, 500000, 'Cat')]);

        // Length > 10, contains letter 'A' -> valid
        $request = PaymentTokenRequest::make(
            amount: 500000,
            cartList: [$cart],
            mobile: '09123456789',
            returnURL: 'https://myshop.com/callback',
            transactionId: 'TX12345678901'
        );

        $this->assertSame('TX12345678901', $request->transactionId);
    }

    public function test_payment_token_request_invalid_short_transaction_id(): void
    {
        $this->expectException(SnappPayValidationException::class);

        $cart = Cart::make(cartId: 1, cartItems: [CartItem::make(1, 'Item', 1, 500000, 'Cat')]);

        PaymentTokenRequest::make(
            amount: 500000,
            cartList: [$cart],
            mobile: '09123456789',
            returnURL: 'https://myshop.com/callback',
            transactionId: '123' // length < 5 -> invalid
        );
    }

    public function test_payment_token_request_invalid_long_numeric_transaction_id(): void
    {
        $this->expectException(SnappPayValidationException::class);

        $cart = Cart::make(cartId: 1, cartItems: [CartItem::make(1, 'Item', 1, 500000, 'Cat')]);

        // Length > 10 with NO letters -> invalid per SnappPay docs
        PaymentTokenRequest::make(
            amount: 500000,
            cartList: [$cart],
            mobile: '09123456789',
            returnURL: 'https://myshop.com/callback',
            transactionId: '123456789012'
        );
    }

    public function test_payment_token_request_amount_mismatch_throws(): void
    {
        $this->expectException(SnappPayValidationException::class);

        $cart = Cart::make(cartId: 1, cartItems: [CartItem::make(1, 'Item', 1, 500000, 'Cat')]);

        // Cart is 500000, but declared amount is 999999
        PaymentTokenRequest::make(
            amount: 999999,
            cartList: [$cart],
            mobile: '09123456789',
            returnURL: 'https://myshop.com/callback',
            transactionId: '12345'
        );
    }

    public function test_callback_payload_parsing(): void
    {
        $raw = [
            'transactionId' => 'TX-987654',
            'state' => 'OK',
            'amount' => 5000000,
        ];

        $callback = CallbackPayload::fromRequest($raw);

        $this->assertTrue($callback->isOk());
        $this->assertFalse($callback->isFailed());
        $this->assertSame('TX-987654', $callback->transactionId);
        $this->assertSame(5000000, $callback->amount);
        $this->assertSame(CallbackState::OK, $callback->state);
    }

    public function test_callback_payload_failed(): void
    {
        $raw = [
            'transactionId' => 'TX-987654',
            'state' => 'FAILED',
            'amount' => 5000000,
        ];

        $callback = CallbackPayload::fromRequest($raw);

        $this->assertFalse($callback->isOk());
        $this->assertTrue($callback->isFailed());
    }

    public function test_status_result_helpers(): void
    {
        $result = StatusResult::fromArray([
            'transactionId' => 'TX-1',
            'status' => 'SETTLE',
            'amount' => 200000,
        ]);

        $this->assertTrue($result->isSettled());
        $this->assertFalse($result->isVerified());
        $this->assertFalse($result->isPending());
        $this->assertSame(PaymentStatus::SETTLE, $result->status);
    }

    public function test_update_order_request_validation(): void
    {
        $cart = Cart::make(cartId: 1, cartItems: [CartItem::make(1, 'Item', 1, 300000, 'Cat')]);

        // New amount (300000) is strictly less than original (500000) -> valid
        $req = UpdateOrderRequest::make(
            paymentToken: 'tok_123',
            amount: 300000,
            cartList: [$cart],
            originalAmount: 500000
        );

        $this->assertSame(300000, $req->amount);
    }

    public function test_update_order_request_validation_throws_when_higher(): void
    {
        $this->expectException(SnappPayValidationException::class);

        $cart = Cart::make(cartId: 1, cartItems: [CartItem::make(1, 'Item', 1, 600000, 'Cat')]);

        // New amount (600000) >= original (500000) -> invalid per SnappPay docs
        UpdateOrderRequest::make(
            paymentToken: 'tok_123',
            amount: 600000,
            cartList: [$cart],
            originalAmount: 500000
        );
    }
}
