<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModeratorOrderReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        User::factory()->create(['user_type' => 'admin']);
    }

    public function test_report_requires_its_own_permission(): void
    {
        $staff = User::factory()->create(['user_type' => 'admin']);

        $this->actingAs($staff)
            ->get(route('moderator-order-report.index'))
            ->assertForbidden();

        $staff->givePermissionTo('moderator-order-report.show');

        $this->actingAs($staff)
            ->get(route('moderator-order-report.index'))
            ->assertOk()
            ->assertSee('Moderator Order Report');
    }

    public function test_report_counts_only_orders_created_by_moderators(): void
    {
        $staff = User::factory()->create(['user_type' => 'admin']);
        $staff->givePermissionTo('moderator-order-report.show');
        $moderator = User::factory()->create(['name' => 'Order Moderator']);
        $product = Product::create([
            'name' => 'Report Item',
            'regular_price' => 100,
            'price' => 100,
        ]);

        $createdOrder = $this->createOrder('MOD-ORDER-001', $moderator->id, 'delivered', 500);
        OrderItem::create([
            'order_id' => $createdOrder->id,
            'product_id' => $product->id,
            'product_name' => 'Report Item',
            'price' => 100,
            'quantity' => 5,
            'subtotal' => 500,
        ]);

        $this->createOrder('CUSTOMER-ORDER-001', null, 'delivered', 900);

        $this->actingAs($staff)
            ->get(route('moderator-order-report.index', [
                'from' => now()->subDay()->format('Y-m-d'),
                'to' => now()->addDay()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('Order Moderator')
            ->assertSee('MOD-ORDER-001')
            ->assertDontSee('CUSTOMER-ORDER-001')
            ->assertSee('৳500.00');
    }

    private function createOrder(string $number, ?int $creatorId, string $status, float $total): Order
    {
        return Order::create([
            'order_number' => $number,
            'created_by_id' => $creatorId,
            'order_source' => $creatorId ? 'phone' : 'website',
            'customer_name' => 'Report Customer',
            'customer_email' => 'report@example.com',
            'subtotal' => $total,
            'total' => $total,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'paid',
            'order_status' => $status,
        ]);
    }
}
