<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Tests\Feature;

use Eamirgh\SnappPay\Contracts\SnappPayCartInterface;
use Eamirgh\SnappPay\Contracts\SnappPayCartItemInterface;
use Eamirgh\SnappPay\Contracts\SnappPayClientInterface;
use Eamirgh\SnappPay\Contracts\SnappPayGateway;
use Eamirgh\SnappPay\Contracts\SnappPayable;
use Eamirgh\SnappPay\Contracts\TokenManagerInterface;
use Eamirgh\SnappPay\DTOs\Cart;
use Eamirgh\SnappPay\DTOs\CartItem;
use Eamirgh\SnappPay\DTOs\PaymentTokenRequest;
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\Services\SnappPayManager;
use Eamirgh\SnappPay\Tests\TestCase;
use Eamirgh\SnappPay\Traits\HasSnappPayCart;
use Eamirgh\SnappPay\Traits\HasSnappPayCartItem;
use Eamirgh\SnappPay\Traits\HasSnappPayPayment;
use Illuminate\Support\Facades\Http;

class ContractIntegrationTest extends TestCase
{
    public function test_service_container_resolves_gateway_interface(): void
    {
        $gateway = $this->app->make(SnappPayGateway::class);

        $this->assertInstanceOf(SnappPayGateway::class, $gateway);
        $this->assertInstanceOf(SnappPayManager::class, $gateway);
    }

    public function test_service_container_resolves_client_and_token_interfaces(): void
    {
        $client = $this->app->make(SnappPayClientInterface::class);
        $tokenManager = $this->app->make(TokenManagerInterface::class);

        $this->assertInstanceOf(SnappPayClientInterface::class, $client);
        $this->assertInstanceOf(TokenManagerInterface::class, $tokenManager);
    }

    public function test_cart_item_contract_adaptation(): void
    {
        $product = new DummyProduct();
        $product->id = 55;
        $product->name = 'Wireless Mouse';
        $product->quantity = 3;
        $product->unit_price = 500000;
        $product->category = 'Electronics';

        $cartItem = CartItem::adapt($product);

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertSame(55, $cartItem->id);
        $this->assertSame('Wireless Mouse', $cartItem->name);
        $this->assertSame(3, $cartItem->count);
        $this->assertSame(500000, $cartItem->amount);
        $this->assertSame(1500000, $cartItem->totalAmount());
        $this->assertSame(100, $cartItem->commissionType);
    }

    public function test_cart_contract_adaptation(): void
    {
        $item1 = new DummyProduct();
        $item1->id = 1;
        $item1->name = 'Shirt';
        $item1->quantity = 2;
        $item1->unit_price = 1000000;

        $item2 = new DummyProduct();
        $item2->id = 2;
        $item2->name = 'Hat';
        $item2->quantity = 1;
        $item2->unit_price = 500000;

        $basket = new DummyBasket();
        $basket->id = 'BASKET-100';
        $basket->items = [$item1, $item2];
        $basket->is_shipment_included = false;
        $basket->shipping_amount = 100000;
        $basket->is_tax_included = true;

        $cartDto = Cart::adapt($basket);

        $this->assertInstanceOf(Cart::class, $cartDto);
        $this->assertSame('BASKET-100', $cartDto->cartId);
        $this->assertCount(2, $cartDto->cartItems);
        $this->assertFalse($cartDto->isShipmentIncluded);
        $this->assertSame(100000, $cartDto->shippingAmount);

        // 2 * 1,000,000 + 1 * 500,000 + 100,000 (shipping) = 2,600,000 IRR
        $this->assertSame(2600000, $cartDto->getTotalAmount());
    }

    public function test_payment_token_request_accepts_cart_contracts(): void
    {
        $item = new DummyProduct();
        $item->id = 1;
        $item->name = 'Item';
        $item->quantity = 1;
        $item->unit_price = 1000000;

        $basket = new DummyBasket();
        $basket->id = 'BASKET-99';
        $basket->items = [$item];

        // Passing contract model directly to cartList array
        $request = PaymentTokenRequest::make(
            amount: 1000000,
            cartList: [$basket],
            mobile: '09121234567',
            returnURL: 'https://shop.test/cb',
            transactionId: 'ORD-CONTRACT-01'
        );

        $this->assertSame(1000000, $request->amount);
        $this->assertInstanceOf(Cart::class, $request->cartList[0]);
        $this->assertSame('BASKET-99', $request->cartList[0]->cartId);
    }

    public function test_request_payment_for_payable_model(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/token' => Http::response([
                'successful' => true,
                'response' => [
                    'paymentToken' => 'tok_payable_1',
                    'paymentPageUrl' => 'https://snapppay.ir/pay/tok_payable_1',
                ],
            ], 200),
        ]);

        $order = new DummyOrder();
        $order->id = 789;
        $order->total_amount = 3000000;
        $order->customer_mobile = '09129999999';
        $order->discount_amount = 500000;
        $order->external_source_amount = 0;
        $order->return_url = 'https://myshop.test/cb';

        $result = SnappPay::requestPaymentFor($order);

        $this->assertSame('tok_payable_1', $result->paymentToken);
        $this->assertSame('https://snapppay.ir/pay/tok_payable_1', $result->paymentPageUrl);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.snapppay.test/api/online/payment/v1/token'
                && $request['amount'] === 3000000
                && $request['mobile'] === '09129999999'
                && $request['discountAmount'] === 500000
                && $request['returnURL'] === 'https://myshop.test/cb';
        });
    }

    public function test_request_payment_for_payable_with_basket_contract_relation(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.test/api/online/payment/v1/token' => Http::response([
                'successful' => true,
                'response' => [
                    'paymentToken' => 'tok_basket_rel',
                    'paymentPageUrl' => 'https://snapppay.ir/pay/tok_basket_rel',
                ],
            ], 200),
        ]);

        $item1 = new DummyProduct();
        $item1->id = 10;
        $item1->name = 'Product 10';
        $item1->quantity = 1;
        $item1->unit_price = 1500000;

        $basket = new DummyBasket();
        $basket->id = 'BASKET-REL-1';
        $basket->items = [$item1];

        $order = new DummyOrder();
        $order->id = 888;
        $order->total_amount = 1500000;
        $order->customer_mobile = '09127777777';
        $order->return_url = 'https://myshop.test/cb';
        $order->cart = $basket;

        $result = SnappPay::requestPaymentFor($order);

        $this->assertSame('tok_basket_rel', $result->paymentToken);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.snapppay.test/api/online/payment/v1/token'
                && $request['cartList'][0]['cartId'] === 'BASKET-REL-1'
                && $request['cartList'][0]['cartItems'][0]['name'] === 'Product 10';
        });
    }
}

class DummyOrder implements SnappPayable
{
    use HasSnappPayPayment;

    public int $id = 1;
    public int $total_amount = 0;
    public string $customer_mobile = '';
    public int $discount_amount = 0;
    public int $external_source_amount = 0;
    public string $return_url = '';
    public mixed $cart = null;

    public function getKey()
    {
        return $this->id;
    }
}

class DummyBasket implements SnappPayCartInterface
{
    use HasSnappPayCart;

    public string|int $id = 1;
    public array $items = [];
    public bool $is_shipment_included = true;
    public bool $is_tax_included = true;
    public int $shipping_amount = 0;
    public int $tax_amount = 0;
    public ?int $total_amount = null;

    public function getKey()
    {
        return $this->id;
    }
}

class DummyProduct implements SnappPayCartItemInterface
{
    use HasSnappPayCartItem;

    public int $id = 1;
    public string $name = '';
    public int $quantity = 1;
    public int $unit_price = 0;
    public string $category = 'General';
    public int $commission_type = 100;

    public function getKey()
    {
        return $this->id;
    }
}
