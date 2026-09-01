<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrderStockDeductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_confirming_order_does_not_deduct_stock(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Confirmed Product',
            'slug' => 'confirmed-product',
            'sku' => 'CONFIRMED-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 10,
            'stock_status' => 'in_stock',
            'regular_price' => 1200,
            'price' => 1000,
            'unit' => 'pcs',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CONFIRMED-1-RED',
            'combination_hash' => 'red',
            'quantity' => 8,
            'selling_price' => 1000,
        ]);
        $order = $this->createOrder();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'variant_name' => 'Red',
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'product_image' => null,
            'price' => 1000,
            'regular_price' => 1200,
            'quantity' => 3,
            'subtotal' => 3000,
        ]);

        $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('orders.update-status', $order), [
                'order_status' => 'confirmed',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'confirmed');

        $this->assertSame(10, $product->refresh()->quantity);
        $this->assertSame(8, $variant->refresh()->quantity);
        $this->assertNull($order->refresh()->stock_deducted_at);
    }

    private function createOrder(array $overrides = []): Order
    {
        $customer = User::factory()->create(['user_type' => 'user']);

        return Order::create(array_merge([
            'order_number' => 'ORD-STOCK-TEST',
            'user_id' => $customer->id,
            'customer_name' => 'Jane Customer',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '01712345678',
            'subtotal' => 3000,
            'tax' => 0,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 3000,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'shipping_method' => 'standard',
        ], $overrides));
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->assignRole(Role::where('name', 'Super Admin')->first());

        return $admin;
    }
}
