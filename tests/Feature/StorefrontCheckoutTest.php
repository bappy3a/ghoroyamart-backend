<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StorefrontCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_place_checkout_order(): void
    {
        Setting::set('delivery_charge_inside_dhaka', 80);
        Setting::set('delivery_charge_outside_dhaka', 150);

        $user = User::factory()->create([
            'user_type' => 'user',
            'phone' => '01711111111',
            'name' => 'Checkout User',
            'email' => 'checkout@example.com',
        ]);

        $deliveryArea = DeliveryArea::create([
            'id' => 9001,
            'name' => 'Banani',
            'district_id' => 1,
            'district_name' => 'Dhaka City',
            'post_code' => '1213',
            'status' => true,
        ]);

        $address = ShippingAddress::create([
            'user_id' => $user->id,
            'name' => 'Checkout User',
            'email' => 'checkout@example.com',
            'phone' => '01711111111',
            'delivery_area_id' => $deliveryArea->id,
            'postal_code' => '1213',
            'address' => 'House 42, Road 11, Banani',
            'address_type' => 'home',
            'is_default' => true,
        ]);

        $product = Product::create([
            'name' => 'Storefront Product',
            'slug' => 'storefront-product',
            'sku' => 'SF-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 10,
            'stock_status' => 'in_stock',
            'regular_price' => 500,
            'price' => 400,
            'unit' => 'pcs',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/checkout', [
            'shipping_address_id' => $address->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
            'payment_method' => 'cash_on_delivery',
            'order_notes' => 'Please call before delivery',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subtotal', 800)
            ->assertJsonPath('data.shipping_cost', 80)
            ->assertJsonPath('data.total', 880)
            ->assertJsonPath('data.payment_method', 'cash_on_delivery')
            ->assertJsonPath('data.shipping_method', 'inside_dhaka')
            ->assertJsonPath('data.items.0.quantity', 2);

        $order = Order::where('order_number', $response->json('data.order_number'))->firstOrFail();

        $this->assertMatchesRegularExpression('/^AGO-[A-Z0-9]{6}$/', $order->order_number);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('website', $order->order_source);
        $this->assertSame('pending', $order->order_status);
        $this->assertNotNull($order->stock_deducted_at);
        $this->assertSame(8, $product->refresh()->quantity);
        $this->assertSame(2, $product->num_of_sale);
        $this->assertDatabaseHas('order_timelines', [
            'order_id' => $order->id,
            'status' => 'Order Pending',
        ]);
    }

    public function test_dhaka_sub_urban_uses_outside_delivery_charge(): void
    {
        Setting::set('delivery_charge_inside_dhaka', 80);
        Setting::set('delivery_charge_outside_dhaka', 150);

        $user = User::factory()->create([
            'user_type' => 'user',
            'phone' => '01744444444',
        ]);

        $deliveryArea = DeliveryArea::create([
            'id' => 9004,
            'name' => 'Savar',
            'district_id' => 3,
            'district_name' => 'Dhaka Sub-Urban',
            'status' => true,
        ]);

        $address = ShippingAddress::create([
            'user_id' => $user->id,
            'name' => 'Suburban User',
            'phone' => '01744444444',
            'delivery_area_id' => $deliveryArea->id,
            'address' => 'Savar address line',
            'address_type' => 'home',
            'is_default' => true,
        ]);

        $product = Product::create([
            'name' => 'Suburban Product',
            'slug' => 'suburban-product',
            'sku' => 'SUB-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 5,
            'stock_status' => 'in_stock',
            'regular_price' => 200,
            'price' => 100,
            'unit' => 'pcs',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/checkout/preview', [
            'shipping_address_id' => $address->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.shipping_method', 'outside_dhaka')
            ->assertJsonPath('data.shipping_cost', 150)
            ->assertJsonPath('data.district_name', 'Dhaka Sub-Urban')
            ->assertJsonPath('data.delivery_charges.inside_dhaka', 80)
            ->assertJsonPath('data.delivery_charges.outside_dhaka', 150);
    }

    public function test_coupon_can_be_applied_and_removed(): void
    {
        Setting::set('delivery_charge_inside_dhaka', 80);
        Setting::set('delivery_charge_outside_dhaka', 150);

        $user = User::factory()->create([
            'user_type' => 'user',
            'phone' => '01755555555',
        ]);

        $deliveryArea = DeliveryArea::create([
            'id' => 9005,
            'name' => 'Gulshan',
            'district_id' => 1,
            'district_name' => 'Dhaka City',
            'status' => true,
        ]);

        $address = ShippingAddress::create([
            'user_id' => $user->id,
            'name' => 'Coupon User',
            'phone' => '01755555555',
            'delivery_area_id' => $deliveryArea->id,
            'address' => 'Gulshan address',
            'address_type' => 'home',
            'is_default' => true,
        ]);

        $product = Product::create([
            'name' => 'Coupon Product',
            'slug' => 'coupon-product',
            'sku' => 'CPN-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 10,
            'stock_status' => 'in_stock',
            'regular_price' => 1000,
            'price' => 1000,
            'unit' => 'pcs',
        ]);

        $coupon = Coupon::create([
            'code' => 'SAVE10',
            'name' => 'Save 10%',
            'type' => 'order_based',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_order_amount' => 500,
            'valid_from' => now()->subDay()->toDateString(),
            'valid_to' => now()->addMonth()->toDateString(),
            'is_active' => true,
            'used_count' => 0,
        ]);

        Sanctum::actingAs($user);

        $items = [['product_id' => $product->id, 'quantity' => 1]];

        $this->postJson('/api/checkout/coupon/apply', [
            'code' => 'save10',
            'shipping_address_id' => $address->id,
            'items' => $items,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.discount', 100)
            ->assertJsonPath('data.shipping_cost', 80)
            ->assertJsonPath('data.total', 980)
            ->assertJsonPath('data.coupon.code', 'SAVE10');

        $this->postJson('/api/checkout/coupon/remove', [
            'shipping_address_id' => $address->id,
            'items' => $items,
        ])
            ->assertOk()
            ->assertJsonPath('data.discount', 0)
            ->assertJsonPath('data.coupon', null)
            ->assertJsonPath('data.total', 1080);

        $this->postJson('/api/checkout', [
            'shipping_address_id' => $address->id,
            'coupon_code' => 'SAVE10',
            'items' => $items,
        ])
            ->assertCreated()
            ->assertJsonPath('data.discount', 100)
            ->assertJsonPath('data.coupon_code', 'SAVE10')
            ->assertJsonPath('data.total', 980);

        $this->assertSame(1, $coupon->refresh()->used_count);
    }

    public function test_checkout_requires_variant_when_product_has_variants(): void
    {
        $user = User::factory()->create([
            'user_type' => 'user',
            'phone' => '01722222222',
        ]);

        $deliveryArea = DeliveryArea::create([
            'id' => 9002,
            'name' => 'Agrabad',
            'district_id' => 2,
            'district_name' => 'Chittagong',
            'status' => true,
        ]);

        $address = ShippingAddress::create([
            'user_id' => $user->id,
            'name' => 'Variant User',
            'phone' => '01722222222',
            'delivery_area_id' => $deliveryArea->id,
            'address' => 'Outside Dhaka address line',
            'address_type' => 'home',
            'is_default' => true,
        ]);

        $product = Product::create([
            'name' => 'Variant Product',
            'slug' => 'variant-product',
            'sku' => 'VP-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 5,
            'stock_status' => 'in_stock',
            'regular_price' => 300,
            'price' => 250,
            'unit' => 'pcs',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VP-1-RED',
            'combination_hash' => 'red',
            'quantity' => 5,
            'selling_price' => 260,
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VP-1-BLUE',
            'combination_hash' => 'blue',
            'quantity' => 5,
            'selling_price' => 270,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', [
            'shipping_address_id' => $address->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_checkout_matches_variant_by_name_and_auto_selects_single_variant(): void
    {
        Setting::set('delivery_charge_inside_dhaka', 80);
        Setting::set('delivery_charge_outside_dhaka', 150);

        $user = User::factory()->create([
            'user_type' => 'user',
            'phone' => '01766666666',
        ]);

        $deliveryArea = DeliveryArea::create([
            'id' => 9006,
            'name' => 'Banani',
            'district_id' => 1,
            'district_name' => 'Dhaka City',
            'status' => true,
        ]);

        $address = ShippingAddress::create([
            'user_id' => $user->id,
            'name' => 'Match User',
            'phone' => '01766666666',
            'delivery_area_id' => $deliveryArea->id,
            'address' => 'Banani address',
            'address_type' => 'home',
            'is_default' => true,
        ]);

        $product = Product::create([
            'name' => 'Asher Dixon',
            'slug' => 'asher-dixon',
            'sku' => 'AD-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 5,
            'stock_status' => 'in_stock',
            'regular_price' => 300,
            'price' => 250,
            'unit' => 'pcs',
        ]);

        $onlyVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'AD-1-ONLY',
            'combination_hash' => 'only',
            'quantity' => 5,
            'selling_price' => 255,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', [
            'shipping_address_id' => $address->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.items.0.product_variant_id', $onlyVariant->id)
            ->assertJsonPath('data.items.0.price', 255);
    }

    public function test_user_can_list_and_view_own_orders(): void
    {
        Setting::set('delivery_charge_inside_dhaka', 80);
        Setting::set('delivery_charge_outside_dhaka', 150);

        $user = User::factory()->create([
            'user_type' => 'user',
            'phone' => '01733333333',
            'name' => 'Order Viewer',
            'email' => 'viewer@example.com',
        ]);

        $deliveryArea = DeliveryArea::create([
            'id' => 9003,
            'name' => 'Gulshan',
            'district_id' => 1,
            'district_name' => 'Dhaka City',
            'status' => true,
        ]);

        $address = ShippingAddress::create([
            'user_id' => $user->id,
            'name' => 'Order Viewer',
            'email' => 'viewer@example.com',
            'phone' => '01733333333',
            'delivery_area_id' => $deliveryArea->id,
            'address' => 'Gulshan Avenue',
            'address_type' => 'office',
            'is_default' => true,
        ]);

        $product = Product::create([
            'name' => 'Listed Product',
            'slug' => 'listed-product',
            'sku' => 'LP-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 20,
            'stock_status' => 'in_stock',
            'regular_price' => 100,
            'price' => 90,
            'unit' => 'pcs',
        ]);

        Sanctum::actingAs($user);

        $place = $this->postJson('/api/checkout', [
            'shipping_address_id' => $address->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $orderNumber = $place->json('data.order_number');

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.orders.0.order_number', $orderNumber);

        $this->getJson('/api/orders/'.$orderNumber)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_number', $orderNumber)
            ->assertJsonPath('data.shipping_address.id', $address->id)
            ->assertJsonPath('data.timelines.0.status', 'Order Pending')
            ->assertJsonStructure([
                'data' => [
                    'created_at',
                    'timelines' => [
                        ['id', 'status', 'description', 'date'],
                    ],
                ],
            ]);

        $this->get('/api/orders/'.$orderNumber.'/invoice')
            ->assertOk()
            ->assertSee('Invoice Receipt', false)
            ->assertSee($orderNumber, false);
    }
}
