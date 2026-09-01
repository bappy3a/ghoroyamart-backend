<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Division;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingAddress;
use App\Models\Thana;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrderEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Setting::set('delivery_charge_inside_dhaka', 80);
        Setting::set('delivery_charge_outside_dhaka', 150);
    }

    public function test_admin_can_edit_order_items_shipping_and_totals_after_order_is_placed(): void
    {
        $admin = $this->createAdmin();
        [$dhakaDistrict, $dhakaThana, $outsideDistrict, $outsideThana] = $this->createLocations();

        $keptProduct = $this->createProduct('Keep Product', 'KEEP-1', 7, 3, 100);
        $removedProduct = $this->createProduct('Remove Product', 'REMOVE-1', 6, 2, 30);
        $addedProduct = $this->createProduct('Add Product', 'ADD-1', 5, 0, 50);
        $customer = User::factory()->create(['user_type' => 'user']);
        $shippingAddress = ShippingAddress::create([
            'user_id' => $customer->id,
            'name' => 'Old Customer',
            'email' => 'old@example.com',
            'phone' => '01700000000',
            'division_id' => $dhakaDistrict->division_id,
            'district_id' => $dhakaDistrict->id,
            'thana_id' => $dhakaThana->id,
            'address' => 'Old address',
            'address_type' => 'home',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-EDIT-1',
            'user_id' => $customer->id,
            'customer_name' => 'Old Customer',
            'customer_email' => 'old@example.com',
            'customer_phone' => '01700000000',
            'shipping_address_id' => $shippingAddress->id,
            'subtotal' => 360,
            'tax' => 0,
            'discount' => 0,
            'shipping_cost' => 80,
            'total' => 440,
            'steadfast_cod_charger' => 4.40,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'shipping_method' => 'inside_dhaka',
            'stock_deducted_at' => now(),
        ]);
        $keptItem = $this->createOrderItem($order, $keptProduct, 3);
        $removedItem = $this->createOrderItem($order, $removedProduct, 2);

        $response = $this
            ->actingAs($admin)
            ->put(route('orders.update', $order->order_number), [
                'order_source' => 'phone',
                'customer_name' => 'Edited Customer',
                'customer_email' => 'edited@example.com',
                'customer_phone' => '01800000000',
                'shipping_district_id' => $outsideDistrict->id,
                'shipping_thana_id' => $outsideThana->id,
                'shipping_address' => 'Edited shipping address',
                'shipping_postal_code' => '4000',
                'shipping_address_type' => 'office',
                'shipping_method' => 'outside_dhaka',
                'payment_method' => 'bkash',
                'payment_status' => 'paid',
                'discount' => 25,
                'order_notes' => 'Edited by admin',
                'items' => [
                    [
                        'id' => $keptItem->id,
                        'product_id' => $keptProduct->id,
                        'product_variant_id' => null,
                        'quantity' => 2,
                    ],
                    [
                        'product_id' => $addedProduct->id,
                        'product_variant_id' => null,
                        'quantity' => 4,
                    ],
                ],
            ]);

        $response->assertRedirect(route('orders.view', $order->order_number));

        $order->refresh();
        $shippingAddress->refresh();
        $keptItem->refresh();

        $this->assertSame('Edited Customer', $order->customer_name);
        $this->assertSame('bkash', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('outside_dhaka', $order->shipping_method);
        $this->assertSame('400.00', (string) $order->subtotal);
        $this->assertSame('25.00', (string) $order->discount);
        $this->assertSame('150.00', (string) $order->shipping_cost);
        $this->assertSame('525.00', (string) $order->total);
        $this->assertSame('0.00', (string) $order->steadfast_cod_charger);
        $this->assertSame($outsideDistrict->id, $shippingAddress->district_id);
        $this->assertSame($outsideThana->id, $shippingAddress->thana_id);
        $this->assertSame('Edited shipping address', $shippingAddress->address);

        $this->assertSame(2, $keptItem->quantity);
        $this->assertDatabaseMissing('order_items', ['id' => $removedItem->id]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $addedProduct->id,
            'quantity' => 4,
            'subtotal' => 200,
        ]);
        $this->assertSame(8, $keptProduct->refresh()->quantity);
        $this->assertSame(2, $keptProduct->num_of_sale);
        $this->assertSame(8, $removedProduct->refresh()->quantity);
        $this->assertSame(0, $removedProduct->num_of_sale);
        $this->assertSame(1, $addedProduct->refresh()->quantity);
        $this->assertSame(4, $addedProduct->num_of_sale);
        $this->assertDatabaseHas('order_timelines', [
            'order_id' => $order->id,
            'status' => 'Order Edited',
        ]);
    }

    public function test_admin_edit_defaults_blank_order_source_to_website(): void
    {
        $admin = $this->createAdmin();
        [$district, $thana] = $this->createLocations();
        $product = $this->createProduct('Source Default Product', 'SOURCE-DEFAULT-1', 4, 1, 100);
        $customer = User::factory()->create(['user_type' => 'user']);
        $shippingAddress = ShippingAddress::create([
            'user_id' => $customer->id,
            'name' => 'Source Customer',
            'email' => 'source@example.com',
            'phone' => '01722222222',
            'division_id' => $district->division_id,
            'district_id' => $district->id,
            'thana_id' => $thana->id,
            'address' => 'Source address',
            'address_type' => 'home',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-EDIT-SOURCE',
            'user_id' => $customer->id,
            'order_source' => null,
            'customer_name' => 'Source Customer',
            'customer_email' => 'source@example.com',
            'customer_phone' => '01722222222',
            'shipping_address_id' => $shippingAddress->id,
            'subtotal' => 100,
            'tax' => 0,
            'discount' => 0,
            'shipping_cost' => 80,
            'total' => 180,
            'steadfast_cod_charger' => 1.80,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'shipping_method' => 'inside_dhaka',
            'stock_deducted_at' => now(),
        ]);
        $item = $this->createOrderItem($order, $product, 1);

        $this
            ->actingAs($admin)
            ->get(route('orders.edit', $order->order_number))
            ->assertOk()
            ->assertSee('<option value="website" selected>Website</option>', false);

        $response = $this
            ->actingAs($admin)
            ->put(route('orders.update', $order->order_number), [
                'order_source' => '',
                'customer_name' => 'Source Customer Edited',
                'customer_email' => 'source-edited@example.com',
                'customer_phone' => '01822222222',
                'shipping_district_id' => $district->id,
                'shipping_thana_id' => $thana->id,
                'shipping_address' => 'Source address edited',
                'shipping_address_type' => 'home',
                'shipping_method' => 'inside_dhaka',
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'pending',
                'discount' => 0,
                'items' => [
                    [
                        'id' => $item->id,
                        'product_id' => $product->id,
                        'product_variant_id' => null,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.view', $order->order_number));

        $order->refresh();

        $this->assertSame('website', $order->order_source);
    }

    public function test_admin_can_edit_discount_for_order_with_variant_item(): void
    {
        $admin = $this->createAdmin();
        [$district, $thana] = $this->createLocations();
        $product = $this->createProduct('Variant Discount Product', 'VAR-DISCOUNT-1', 10, 1, 100);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-DISCOUNT-RED',
            'combination_hash' => 'red',
            'quantity' => 10,
            'selling_price' => 120,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['user_type' => 'user']);
        $shippingAddress = ShippingAddress::create([
            'user_id' => $customer->id,
            'name' => 'Variant Customer',
            'email' => 'variant@example.com',
            'phone' => '01744444444',
            'division_id' => $district->division_id,
            'district_id' => $district->id,
            'thana_id' => $thana->id,
            'address' => 'Variant address',
            'address_type' => 'home',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-VARIANT-DISCOUNT',
            'user_id' => $customer->id,
            'customer_name' => 'Variant Customer',
            'customer_email' => 'variant@example.com',
            'customer_phone' => '01744444444',
            'shipping_address_id' => $shippingAddress->id,
            'subtotal' => 120,
            'tax' => 0,
            'discount' => 0,
            'shipping_cost' => 80,
            'total' => 200,
            'steadfast_cod_charger' => 2,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'shipping_method' => 'inside_dhaka',
            'stock_deducted_at' => now(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'variant_name' => 'Red',
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $variant->sku,
            'product_image' => null,
            'price' => $variant->selling_price,
            'regular_price' => $product->regular_price,
            'purchase_price' => $variant->purchase_price,
            'quantity' => 1,
            'subtotal' => $variant->selling_price,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('orders.update', $order->order_number), [
                'order_source' => 'phone',
                'customer_name' => 'Variant Customer',
                'customer_email' => 'variant@example.com',
                'customer_phone' => '01744444444',
                'shipping_district_id' => $district->id,
                'shipping_thana_id' => $thana->id,
                'shipping_address' => 'Variant address',
                'shipping_address_type' => 'home',
                'shipping_method' => 'inside_dhaka',
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'pending',
                'discount' => 20,
                'items' => [
                    [
                        'id' => $item->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.view', $order->order_number));

        $order->refresh();

        $this->assertSame('20.00', (string) $order->discount);
        $this->assertSame('180.00', (string) $order->total);
    }

    public function test_create_order_form_defaults_source_to_facebook_and_includes_product_image_modal(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->get(route('moderator-order-management.create'));

        $response
            ->assertOk()
            ->assertSee('<option value="facebook" selected>Facebook</option>', false)
            ->assertSee('id="product-image-modal"', false)
            ->assertSee('Preview image', false)
            ->assertSee('data-bs-dismiss="modal"', false);
    }

    public function test_moderator_can_create_order_without_thana(): void
    {
        $moderator = $this->createAdmin();
        [$district] = $this->createLocations();
        $product = $this->createProduct('No Thana Product', 'NO-THANA-1', 5, 0, 100);

        $response = $this
            ->actingAs($moderator)
            ->post(route('moderator-order-management.store'), [
                'order_source' => 'phone',
                'customer_name' => 'No Thana Customer',
                'customer_email' => 'no-thana@example.com',
                'customer_phone' => '01733333333',
                'shipping_district_id' => $district->id,
                'shipping_thana_id' => '',
                'shipping_address' => 'Shipping address without thana',
                'shipping_address_type' => 'home',
                'shipping_method' => 'inside_dhaka',
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'pending',
                'discount' => 0,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => null,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('moderator-order-management.index'));

        $shippingAddress = ShippingAddress::where('phone', '01733333333')->firstOrFail();

        $this->assertSame($district->id, $shippingAddress->district_id);
        $this->assertNull($shippingAddress->thana_id);
    }

    public function test_admin_order_create_rejects_invalid_bangladesh_phone_number(): void
    {
        $admin = $this->createAdmin();
        [$district] = $this->createLocations();
        $product = $this->createProduct('Phone Validation Product', 'PHONE-VALID-1', 5, 0, 100);

        $response = $this
            ->actingAs($admin)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'order_source' => 'phone',
                'customer_name' => 'Phone Customer',
                'customer_email' => 'phone@example.com',
                'customer_phone' => '01234567890',
                'shipping_district_id' => $district->id,
                'shipping_thana_id' => '',
                'shipping_address' => 'Phone validation address',
                'shipping_address_type' => 'home',
                'shipping_method' => 'inside_dhaka',
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'pending',
                'discount' => 0,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => null,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('orders.create'))
            ->assertSessionHasErrors('customer_phone');

        $this->assertDatabaseMissing('orders', [
            'customer_phone' => '01234567890',
        ]);
    }

    public function test_edit_button_uses_orders_update_permission(): void
    {
        $editor = User::factory()->create(['user_type' => 'admin']);
        $role = Role::create(['name' => 'Order Editor', 'guard_name' => 'web']);
        $role->givePermissionTo(['orders.all', 'orders.details', 'orders.update']);
        $editor->assignRole($role);
        $product = $this->createProduct('Visible Product', 'VISIBLE-1', 4, 1, 100);
        $order = Order::create([
            'order_number' => 'ORD-EDIT-BUTTON',
            'customer_name' => 'Button Customer',
            'customer_email' => 'button@example.com',
            'customer_phone' => '01711111111',
            'subtotal' => 100,
            'tax' => 0,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 100,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'shipping_method' => 'inside_dhaka',
        ]);
        $this->createOrderItem($order, $product, 1);

        $response = $this
            ->actingAs($editor)
            ->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee(route('orders.edit', $order->order_number), false);
    }

    private function createProduct(string $name, string $sku, int $quantity, int $sales, float $price): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'sku' => $sku,
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => $quantity,
            'stock_status' => $quantity > 0 ? 'in_stock' : 'out_of_stock',
            'regular_price' => $price + 20,
            'price' => $price,
            'unit' => 'pcs',
            'num_of_sale' => $sales,
        ]);
    }

    private function createOrderItem(Order $order, Product $product, int $quantity): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'product_image' => null,
            'price' => $product->price,
            'regular_price' => $product->regular_price,
            'purchase_price' => $product->purchase_price,
            'quantity' => $quantity,
            'subtotal' => $product->price * $quantity,
        ]);
    }

    private function createLocations(): array
    {
        $division = Division::create(['name' => 'Dhaka']);
        $dhakaDistrict = District::create(['division_id' => $division->id, 'name' => 'Dhaka']);
        $dhakaThana = Thana::create(['district_id' => $dhakaDistrict->id, 'name' => 'Dhanmondi']);
        $outsideDistrict = District::create(['division_id' => $division->id, 'name' => 'Chattogram']);
        $outsideThana = Thana::create(['district_id' => $outsideDistrict->id, 'name' => 'Kotwali']);

        return [$dhakaDistrict, $dhakaThana, $outsideDistrict, $outsideThana];
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->assignRole(Role::where('name', 'Super Admin')->first());

        return $admin;
    }
}
