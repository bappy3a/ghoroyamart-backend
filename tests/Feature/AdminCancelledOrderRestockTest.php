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

class AdminCancelledOrderRestockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_cancelled_orders_page_only_lists_cancelled_orders(): void
    {
        $admin = $this->createAdmin();
        $cancelledOrder = $this->createOrder(['order_status' => 'cancelled', 'order_number' => 'ORD-CANCELLED-1']);
        $activeOrder = $this->createOrder(['order_status' => 'pending', 'order_number' => 'ORD-PENDING-1']);

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.cancelled'));

        $response->assertOk();
        $response->assertSee($cancelledOrder->order_number);
        $response->assertSee('Cancelled Orders');

        $orders = collect($response->viewData('orders')->items());
        $this->assertTrue($orders->contains('id', $cancelledOrder->id));
        $this->assertFalse($orders->contains('id', $activeOrder->id));
    }

    public function test_delivered_orders_page_lists_delivered_and_partial_delivered_orders(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Delivered List Product',
            'slug' => 'delivered-list-product',
            'sku' => 'DELIVERED-LIST-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 10,
            'stock_status' => 'in_stock',
            'regular_price' => 120,
            'price' => 100,
            'unit' => 'pcs',
        ]);
        $deliveredOrder = $this->createOrder(['order_status' => 'delivered', 'order_number' => 'ORD-DELIVERED-1']);
        $partialDeliveredOrder = $this->createOrder(['order_status' => 'partial_delivered', 'order_number' => 'ORD-PARTIAL-DELIVERED-1']);
        $shippedOrder = $this->createOrder(['order_status' => 'shipped', 'order_number' => 'ORD-SHIPPED-1']);

        foreach ([$deliveredOrder, $partialDeliveredOrder, $shippedOrder] as $order) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'product_sku' => $product->sku,
                'product_image' => null,
                'price' => 100,
                'regular_price' => 120,
                'quantity' => 1,
                'subtotal' => 100,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.delivered'));

        $response->assertOk();
        $response->assertSee($deliveredOrder->order_number);
        $response->assertSee($partialDeliveredOrder->order_number);
        $response->assertDontSee($shippedOrder->order_number);
        $this->assertSame(2, $response->viewData('statusCounts')['delivered']);

        $orders = collect($response->viewData('orders')->items());
        $this->assertTrue($orders->contains('id', $deliveredOrder->id));
        $this->assertTrue($orders->contains('id', $partialDeliveredOrder->id));
        $this->assertFalse($orders->contains('id', $shippedOrder->id));
    }

    public function test_orders_pages_can_filter_partial_delivered_status(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Partial Delivered Filter Product',
            'slug' => 'partial-delivered-filter-product',
            'sku' => 'PARTIAL-DELIVERED-FILTER-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 10,
            'stock_status' => 'in_stock',
            'regular_price' => 120,
            'price' => 100,
            'unit' => 'pcs',
        ]);
        $deliveredOrder = $this->createOrder(['order_status' => 'delivered', 'order_number' => 'ORD-FILTER-DELIVERED']);
        $partialDeliveredOrder = $this->createOrder(['order_status' => 'partial_delivered', 'order_number' => 'ORD-FILTER-PARTIAL']);
        $shippedOrder = $this->createOrder(['order_status' => 'shipped', 'order_number' => 'ORD-FILTER-SHIPPED']);

        foreach ([$deliveredOrder, $partialDeliveredOrder, $shippedOrder] as $order) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'product_sku' => $product->sku,
                'product_image' => null,
                'price' => 100,
                'regular_price' => 120,
                'quantity' => 1,
                'subtotal' => 100,
            ]);
        }

        $ordersResponse = $this
            ->actingAs($admin)
            ->get(route('orders.index', ['status' => 'partial_delivered']));

        $ordersResponse->assertOk();
        $ordersResponse->assertSee('Partial Delivered');
        $ordersResponse->assertSee($partialDeliveredOrder->order_number);
        $ordersResponse->assertDontSee($deliveredOrder->order_number);
        $ordersResponse->assertDontSee($shippedOrder->order_number);

        $deliveredResponse = $this
            ->actingAs($admin)
            ->get(route('orders.delivered', ['status' => 'partial_delivered']));

        $deliveredResponse->assertOk();
        $deliveredResponse->assertSee('Partial Delivered');
        $deliveredResponse->assertSee($partialDeliveredOrder->order_number);
        $deliveredResponse->assertDontSee($deliveredOrder->order_number);
        $deliveredResponse->assertDontSee($shippedOrder->order_number);
        $this->assertSame('partial_delivered', $deliveredResponse->viewData('currentStatus'));

        $deliveredOnlyResponse = $this
            ->actingAs($admin)
            ->get(route('orders.delivered', ['status' => 'delivered']));

        $deliveredOnlyResponse->assertOk();
        $deliveredOnlyResponse->assertSee($deliveredOrder->order_number);
        $deliveredOnlyResponse->assertDontSee($partialDeliveredOrder->order_number);
    }

    public function test_cancelled_orders_page_shows_cancelled_by_and_reason(): void
    {
        $admin = $this->createAdmin();
        $staff = User::factory()->create([
            'name' => 'Nadia Staff',
            'user_type' => 'admin',
        ]);

        $this->createOrder([
            'order_status' => 'cancelled',
            'order_number' => 'ORD-CANCELLED-REASON',
            'cancelled_by_type' => 'staff',
            'cancelled_by_id' => $staff->id,
            'cancellation_reason' => 'Customer requested a different delivery date.',
            'cancelled_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.cancelled'));

        $response->assertOk();
        $response->assertSee('Cancelled By');
        $response->assertSee('Staff: Nadia Staff');
        $response->assertSee('Reason: Customer requested a different delivery date.');
    }

    public function test_cancelled_order_can_be_restocked_once(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Restock Product',
            'slug' => 'restock-product',
            'sku' => 'RESTOCK-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 3,
            'stock_status' => 'out_of_stock',
            'regular_price' => 1200,
            'price' => 1000,
            'unit' => 'pcs',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'RESTOCK-1-RED',
            'combination_hash' => 'red',
            'quantity' => 4,
            'selling_price' => 1000,
        ]);
        $order = $this->createOrder([
            'order_status' => 'cancelled',
            'order_number' => 'ORD-RESTOCK-1',
            'stock_deducted_at' => now(),
        ]);

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
            'quantity' => 2,
            'subtotal' => 2000,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('orders.update-restock', $order));

        $response->assertRedirect();

        $product->refresh();
        $variant->refresh();
        $order->refresh();

        $this->assertSame(5, $product->quantity);
        $this->assertSame('in_stock', $product->stock_status);
        $this->assertSame(6, $variant->quantity);
        $this->assertNotNull($order->stock_restocked_at);
        $this->assertDatabaseHas('order_timelines', [
            'order_id' => $order->id,
            'status' => 'Stock Restocked',
        ]);

        $this
            ->actingAs($admin)
            ->post(route('orders.update-restock', $order))
            ->assertRedirect();

        $this->assertSame(5, $product->refresh()->quantity);
        $this->assertSame(6, $variant->refresh()->quantity);
    }

    public function test_non_cancelled_order_cannot_be_restocked(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder(['order_status' => 'pending']);

        $this
            ->actingAs($admin)
            ->post(route('orders.update-restock', $order))
            ->assertRedirect();

        $this->assertNull($order->fresh()->stock_restocked_at);
    }

    public function test_partial_delivered_order_can_be_restocked_from_steadfast_status(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Partial Delivered Restock Product',
            'slug' => 'partial-delivered-restock-product',
            'sku' => 'PARTIAL-RESTOCK-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 2,
            'stock_status' => 'out_of_stock',
            'regular_price' => 1200,
            'price' => 1000,
            'unit' => 'pcs',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PARTIAL-RESTOCK-1-BLUE',
            'combination_hash' => 'blue',
            'quantity' => 1,
            'selling_price' => 1000,
        ]);
        $order = $this->createOrder([
            'order_status' => 'shipped',
            'steadfast_status' => 'partial_delivered',
            'order_number' => 'ORD-PARTIAL-RESTOCK-1',
            'stock_deducted_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'variant_name' => 'Blue',
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
            ->post(route('orders.update-restock', $order))
            ->assertRedirect();

        $this->assertSame(5, $product->refresh()->quantity);
        $this->assertSame('in_stock', $product->stock_status);
        $this->assertSame(4, $variant->refresh()->quantity);
        $this->assertNotNull($order->fresh()->stock_restocked_at);
        $this->assertDatabaseHas('order_timelines', [
            'order_id' => $order->id,
            'description' => 'Partial delivered order quantities were added back to inventory.',
            'status' => 'Stock Restocked',
        ]);
    }

    public function test_partial_delivered_order_item_can_be_restocked_individually(): void
    {
        $admin = $this->createAdmin();
        $firstProduct = Product::create([
            'name' => 'Single Restock First Product',
            'slug' => 'single-restock-first-product',
            'sku' => 'SINGLE-RESTOCK-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 2,
            'stock_status' => 'out_of_stock',
            'regular_price' => 1200,
            'price' => 1000,
            'unit' => 'pcs',
            'num_of_sale' => 5,
        ]);
        $secondProduct = Product::create([
            'name' => 'Single Restock Second Product',
            'slug' => 'single-restock-second-product',
            'sku' => 'SINGLE-RESTOCK-2',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 8,
            'stock_status' => 'in_stock',
            'regular_price' => 700,
            'price' => 600,
            'unit' => 'pcs',
            'num_of_sale' => 10,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $firstProduct->id,
            'sku' => 'SINGLE-RESTOCK-1-RED',
            'combination_hash' => 'red',
            'quantity' => 1,
            'selling_price' => 1000,
        ]);
        $order = $this->createOrder([
            'order_status' => 'partial_delivered',
            'order_number' => 'ORD-SINGLE-RESTOCK-1',
            'subtotal' => 4400,
            'total' => 4400,
            'stock_deducted_at' => now(),
        ]);
        $firstItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $firstProduct->id,
            'product_variant_id' => $variant->id,
            'variant_name' => 'Red',
            'product_name' => $firstProduct->name,
            'product_slug' => $firstProduct->slug,
            'product_sku' => $firstProduct->sku,
            'product_image' => null,
            'price' => 1000,
            'regular_price' => 1200,
            'quantity' => 2,
            'subtotal' => 2000,
        ]);
        $secondItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $secondProduct->id,
            'product_name' => $secondProduct->name,
            'product_slug' => $secondProduct->slug,
            'product_sku' => $secondProduct->sku,
            'product_image' => null,
            'price' => 600,
            'regular_price' => 700,
            'quantity' => 4,
            'subtotal' => 2400,
        ]);

        $this
            ->actingAs($admin)
            ->post(route('orders.update-item-restock', [$order, $firstItem]))
            ->assertRedirect();

        $this->assertSame(4, $firstProduct->refresh()->quantity);
        $this->assertSame('in_stock', $firstProduct->stock_status);
        $this->assertSame(3, $firstProduct->num_of_sale);
        $this->assertSame(3, $variant->refresh()->quantity);
        $this->assertSame(8, $secondProduct->refresh()->quantity);
        $this->assertSame(10, $secondProduct->num_of_sale);
        $this->assertSame(2, $firstItem->fresh()->restocked_quantity);
        $this->assertSame(2, $firstItem->fresh()->cancelled_quantity);
        $this->assertSame(0, $secondItem->fresh()->restocked_quantity);
        $order->refresh();
        $this->assertSame('2400.00', (string) $order->subtotal);
        $this->assertSame('2400.00', (string) $order->total);
        $this->assertNull($order->stock_restocked_at);

        $activeDetailsResponse = $this
            ->actingAs($admin)
            ->get(route('orders.view', [
                'order' => $order->order_number,
                'item_scope' => 'active',
            ]));

        $activeDetailsResponse->assertOk();
        $activeDetailsResponse->assertSee('Single Restock First Product');
        $activeDetailsResponse->assertSee('Restocked:');
        $activeDetailsResponse->assertSee('from 2 ordered');

        $this
            ->actingAs($admin)
            ->post(route('orders.update-item-restock', [$order, $firstItem]))
            ->assertRedirect();

        $this->assertSame(4, $firstProduct->refresh()->quantity);
        $this->assertSame(3, $variant->refresh()->quantity);

        $this
            ->actingAs($admin)
            ->post(route('orders.update-item-restock', [$order, $secondItem]))
            ->assertRedirect();

        $this->assertSame(12, $secondProduct->refresh()->quantity);
        $this->assertSame(6, $secondProduct->num_of_sale);
        $order->refresh();
        $this->assertSame('0.00', (string) $order->subtotal);
        $this->assertSame('0.00', (string) $order->total);
        $this->assertNotNull($order->stock_restocked_at);
        $this->assertDatabaseHas('order_timelines', [
            'order_id' => $order->id,
            'description' => 'Single Restock First Product quantity 2 was added back to inventory.',
            'status' => 'Product Restocked',
        ]);
    }

    public function test_cancelled_order_details_show_restock_button_instead_of_status_update(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Cancelled Detail Restock Product',
            'slug' => 'cancelled-detail-restock-product',
            'sku' => 'CANCELLED-DETAIL-RESTOCK-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 0,
            'stock_status' => 'out_of_stock',
            'regular_price' => 120,
            'price' => 100,
            'unit' => 'pcs',
        ]);
        $order = $this->createOrder([
            'order_status' => 'cancelled',
            'order_number' => 'ORD-CANCELLED-DETAIL',
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'product_image' => null,
            'price' => 100,
            'regular_price' => 120,
            'quantity' => 1,
            'subtotal' => 100,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.view', $order));

        $response->assertOk();
        $response->assertSee('Cancelled orders cannot be status updated.');
        $response->assertSee('Restock');
        $response->assertSee(route('orders.update-item-restock', [$order, $item]), false);
        $response->assertDontSee('Update Order Status');
    }

    public function test_partial_delivered_order_details_show_restock_button_and_confirmation(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Partial Detail Restock Product',
            'slug' => 'partial-detail-restock-product',
            'sku' => 'PARTIAL-DETAIL-RESTOCK-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 0,
            'stock_status' => 'out_of_stock',
            'regular_price' => 120,
            'price' => 100,
            'unit' => 'pcs',
        ]);
        $order = $this->createOrder([
            'order_status' => 'partial_delivered',
            'order_number' => 'ORD-PARTIAL-DETAIL',
            'stock_deducted_at' => now(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'product_image' => null,
            'price' => 100,
            'regular_price' => 120,
            'quantity' => 2,
            'subtotal' => 200,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.view', $order));

        $response->assertOk();
        $response->assertSee('Partial Delivered');
        $response->assertSee('Restock');
        $response->assertSee('Restock product quantity?');
        $response->assertSee(route('orders.update-item-restock', [$order, $item]), false);
        $response->assertDontSee('This order is already delivered. No further status update is available.');
    }

    public function test_order_details_show_next_status_and_cancel_buttons(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'No Item Cancel Product',
            'slug' => 'no-item-cancel-product',
            'sku' => 'NO-CANCEL-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 5,
            'stock_status' => 'in_stock',
            'regular_price' => 120,
            'price' => 100,
            'unit' => 'pcs',
        ]);
        $order = $this->createOrder([
            'order_status' => 'pending',
            'order_number' => 'ORD-STATUS-BUTTONS',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'product_image' => null,
            'price' => 100,
            'regular_price' => 120,
            'quantity' => 2,
            'subtotal' => 200,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.view', $order));

        $response->assertOk();
        $response->assertSee('Confirmed');
        $response->assertSee('Cancelled');
        $response->assertSee('data-bs-target="#nextOrderStatusModal"', false);
        $response->assertSee('Confirm Update');
        $response->assertSee('name="order_status" value="confirmed"', false);
        $response->assertSee('name="order_status" value="cancelled"', false);
        $response->assertSee('name="cancellation_reason"', false);
        $response->assertSee('form="cancel-order-status-form"', false);
        $response->assertDontSee('Next Order Status');
        $response->assertDontSee('<select', false);
        $response->assertDontSee('Cancel/Return');
        $response->assertDontSee(route('orders.update-item-cancel', [$order, $order->items()->first()]), false);
    }

    public function test_order_can_be_cancelled_from_details_status_action(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Detail Cancel Product',
            'slug' => 'detail-cancel-product',
            'sku' => 'DETAIL-CANCEL-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 5,
            'stock_status' => 'in_stock',
            'regular_price' => 120,
            'price' => 100,
            'unit' => 'pcs',
        ]);
        $order = $this->createOrder([
            'order_status' => 'confirmed',
            'order_number' => 'ORD-DETAIL-CANCEL',
            'stock_deducted_at' => now(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'product_image' => null,
            'price' => 100,
            'regular_price' => 120,
            'quantity' => 2,
            'subtotal' => 200,
        ]);

        $response = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('orders.update-status', $order), [
                'order_status' => 'cancelled',
                'cancellation_reason' => 'Cancelled from test.',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Order cancelled successfully.')
            ->assertJsonPath('status', 'cancelled');

        $order->refresh();
        $item->refresh();

        $this->assertSame('cancelled', $order->order_status);
        $this->assertSame('staff', $order->cancelled_by_type);
        $this->assertSame($admin->id, $order->cancelled_by_id);
        $this->assertSame('Cancelled from test.', $order->cancellation_reason);
        $this->assertSame(2, $item->cancelled_quantity);
        $this->assertSame('cancelled', $item->item_status);
        $this->assertSame('Cancelled from test.', $item->cancellation_reason);
    }

    public function test_order_cancel_from_details_requires_cancellation_reason(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder([
            'order_status' => 'confirmed',
            'order_number' => 'ORD-DETAIL-CANCEL-REASON',
        ]);

        $response = $this
            ->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('orders.update-status', $order), [
                'order_status' => 'cancelled',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('cancellation_reason');

        $this->assertSame('confirmed', $order->fresh()->order_status);
        $this->assertNull($order->fresh()->cancellation_reason);
    }

    public function test_cancelled_order_status_cannot_be_updated(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder(['order_status' => 'cancelled']);

        $response = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('orders.update-status', $order), [
                'order_status' => 'processing',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cancelled orders cannot be status updated.');

        $this->assertSame('cancelled', $order->fresh()->order_status);
    }

    public function test_delivered_order_can_have_one_item_quantity_cancelled_without_cancelling_whole_order(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Partial Cancel Product',
            'slug' => 'partial-cancel-product',
            'sku' => 'PARTIAL-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 0,
            'stock_status' => 'out_of_stock',
            'regular_price' => 120,
            'price' => 100,
            'unit' => 'pcs',
        ]);
        $order = $this->createOrder([
            'order_status' => 'delivered',
            'order_number' => 'ORD-PARTIAL-CANCEL',
            'subtotal' => 500,
            'shipping_cost' => 80,
            'total' => 580,
            'stock_deducted_at' => now(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'product_image' => null,
            'price' => 100,
            'regular_price' => 120,
            'quantity' => 5,
            'subtotal' => 500,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('orders.update-item-cancel', [$order, $item]), [
                'cancelled_quantity' => 1,
                'cancellation_reason' => 'Customer returned one item.',
            ])
            ->assertRedirect();

        $order->refresh();
        $item->refresh();
        $product->refresh();

        $this->assertSame('delivered', $order->order_status);
        $this->assertSame(1, $item->cancelled_quantity);
        $this->assertSame(4, $item->activeQuantity());
        $this->assertSame(1, $product->quantity);
        $this->assertSame('in_stock', $product->stock_status);
        $this->assertSame('400.00', (string) $order->subtotal);
        $this->assertSame('480.00', (string) $order->total);

        $cancelledResponse = $this
            ->actingAs($admin)
            ->get(route('orders.cancelled'));

        $cancelledResponse->assertOk();
        $cancelledResponse->assertSee($order->order_number);
        $cancelledResponse->assertSee('Qty: 1');
        $cancelledResponse->assertSee('cancelled');
        $cancelledResponse->assertSee('৳100.00');

        $deliveredResponse = $this
            ->actingAs($admin)
            ->get(route('orders.delivered'));

        $deliveredResponse->assertOk();
        $deliveredResponse->assertSee($order->order_number);
        $deliveredResponse->assertSee('Qty: 4');
        $deliveredResponse->assertSee('delivered / 1 cancelled');
        $deliveredResponse->assertSee('৳480.00');
    }

    public function test_cancelled_order_details_scope_shows_only_cancelled_items(): void
    {
        $admin = $this->createAdmin();
        $cancelledProduct = Product::create([
            'name' => 'Cancelled Detail Product',
            'slug' => 'cancelled-detail-product',
            'sku' => 'CANCEL-DETAIL-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 10,
            'stock_status' => 'in_stock',
            'regular_price' => 120,
            'price' => 100,
            'unit' => 'pcs',
        ]);
        $deliveredProduct = Product::create([
            'name' => 'Delivered Detail Product',
            'slug' => 'delivered-detail-product',
            'sku' => 'DELIVERED-DETAIL-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 10,
            'stock_status' => 'in_stock',
            'regular_price' => 220,
            'price' => 200,
            'unit' => 'pcs',
        ]);
        $order = $this->createOrder([
            'order_status' => 'delivered',
            'order_number' => 'ORD-CANCELLED-SCOPE',
            'subtotal' => 300,
            'shipping_cost' => 80,
            'total' => 380,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $cancelledProduct->id,
            'product_name' => $cancelledProduct->name,
            'product_slug' => $cancelledProduct->slug,
            'product_sku' => $cancelledProduct->sku,
            'product_image' => null,
            'price' => 100,
            'regular_price' => 120,
            'quantity' => 1,
            'cancelled_quantity' => 1,
            'item_status' => 'cancelled',
            'subtotal' => 100,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $deliveredProduct->id,
            'product_name' => $deliveredProduct->name,
            'product_slug' => $deliveredProduct->slug,
            'product_sku' => $deliveredProduct->sku,
            'product_image' => null,
            'price' => 200,
            'regular_price' => 220,
            'quantity' => 1,
            'subtotal' => 200,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.view', [
                'order' => $order->order_number,
                'item_scope' => 'cancelled',
            ]));

        $response->assertOk();
        $response->assertSee('Cancelled Items');
        $response->assertSee('Cancelled Detail Product');
        $response->assertDontSee('Delivered Detail Product');
        $response->assertSee('৳100.00');
    }

    private function createOrder(array $overrides = []): Order
    {
        $customer = User::factory()->create(['user_type' => 'user']);

        return Order::create(array_merge([
            'order_number' => 'ORD-TEST-RESTOCK',
            'user_id' => $customer->id,
            'customer_name' => 'Jane Customer',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '01712345678',
            'subtotal' => 1000,
            'tax' => 0,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 1000,
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
