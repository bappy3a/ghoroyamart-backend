<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDeliveryReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_order_list_shows_delivery_receipt_print_link(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder();

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee('Print Delivery Receipt', false);
        $response->assertSee(route('orders.delivery-receipt.print', $order), false);
    }

    public function test_admin_can_print_delivery_receipt_with_customer_address_and_product_totals(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder();

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.delivery-receipt.print', $order));

        $response->assertOk();
        $response->assertSee('Delivery Receipt');
        $response->assertSee($order->order_number);
        $response->assertSee('Jane Customer');
        $response->assertSee('House 12, Road 4, Banani');
        $response->assertSee('Receipt Product');
        $response->assertSee('SKU-RECEIPT-1');
        $response->assertSee('Qty');
        $response->assertSee('Delivery Charge');
        $response->assertSee('Tk 80.00');
        $response->assertSee('Tk 1,580.00');
        $response->assertSee('window.print()', false);
    }

    public function test_order_details_page_shows_thermal_invoice_print_link(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder();

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.view', $order->order_number));

        $response->assertOk();
        $response->assertSee('Thermal Printer Invoice');
        $response->assertSee(route('orders.thermal-invoice.print', $order), false);
    }

    public function test_admin_can_print_thermal_invoice_like_sticker_layout(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder();

        $response = $this
            ->actingAs($admin)
            ->get(route('orders.thermal-invoice.print', $order));

        $response->assertOk();
        $response->assertSee('Thermal Invoice');
        $response->assertSee('Order ID :');
        $response->assertSee('Parcel ID :');
        $response->assertSee('Invoice To:');
        $response->assertSee('Jane Customer');
        $response->assertSee('House 12, Road 4, Banani');
        $response->assertSee('Product Name');
        $response->assertSee('Varient');
        $response->assertSee('Receipt Product');
        $response->assertSee('SKU-RECEIPT-1');
        $response->assertSee('Delivery Fee');
        $response->assertSee('Sub Total');
        $response->assertSee('window.print()', false);
    }

    private function createOrder(): Order
    {
        $customer = User::factory()->create(['user_type' => 'user']);
        $address = ShippingAddress::create([
            'user_id' => $customer->id,
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'phone' => '01712345678',
            'address' => 'House 12, Road 4, Banani',
            'postal_code' => '1213',
            'address_type' => 'home',
        ]);

        $product = Product::create([
            'name' => 'Receipt Product',
            'slug' => 'receipt-product',
            'sku' => 'SKU-RECEIPT-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 10,
            'stock_status' => 'in_stock',
            'regular_price' => 800,
            'price' => 750,
            'unit' => 'pcs',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-RECEIPT-1',
            'user_id' => $customer->id,
            'customer_name' => 'Jane Customer',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '01712345678',
            'shipping_address_id' => $address->id,
            'subtotal' => 1500,
            'tax' => 0,
            'discount' => 0,
            'shipping_cost' => 80,
            'total' => 1580,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'shipping_method' => 'standard',
            'order_notes' => 'Call before delivery.',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_name' => 'Black',
            'product_name' => 'Receipt Product',
            'product_slug' => 'receipt-product',
            'product_sku' => 'SKU-RECEIPT-1',
            'product_image' => null,
            'price' => 750,
            'regular_price' => 800,
            'quantity' => 2,
            'subtotal' => 1500,
        ]);

        return $order->load('items');
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->assignRole(Role::where('name', 'Super Admin')->first());

        return $admin;
    }
}
