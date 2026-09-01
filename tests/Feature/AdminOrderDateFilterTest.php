<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrderDateFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_order_list_filters_by_single_selected_date(): void
    {
        $admin = $this->createAdmin();
        $matchingOrder = $this->createOrder('ORD-DATE-SINGLE', '2026-08-03 10:15:00');
        $this->createOrder('ORD-DATE-OTHER', '2026-08-04 10:15:00');

        $response = $this->actingAs($admin)
            ->get(route('orders.index', ['date_range' => '03 Aug, 2026']))
            ->assertOk()
            ->assertSee($matchingOrder->order_number);

        $orderNumbers = collect($response->viewData('orders')->items())->pluck('order_number');

        $this->assertTrue($orderNumbers->contains($matchingOrder->order_number));
        $this->assertFalse($orderNumbers->contains('ORD-DATE-OTHER'));
    }

    public function test_order_list_filters_by_selected_date_range(): void
    {
        $admin = $this->createAdmin();
        $this->createOrder('ORD-DATE-BEFORE', '2026-08-01 23:59:59');
        $startOrder = $this->createOrder('ORD-DATE-START', '2026-08-02 00:00:00');
        $endOrder = $this->createOrder('ORD-DATE-END', '2026-08-04 23:59:59');
        $this->createOrder('ORD-DATE-AFTER', '2026-08-05 00:00:00');

        $response = $this->actingAs($admin)
            ->get(route('orders.index', ['date_range' => '02 Aug, 2026 to 04 Aug, 2026']))
            ->assertOk()
            ->assertSee($startOrder->order_number)
            ->assertSee($endOrder->order_number);

        $orderNumbers = collect($response->viewData('orders')->items())->pluck('order_number');

        $this->assertTrue($orderNumbers->contains($startOrder->order_number));
        $this->assertTrue($orderNumbers->contains($endOrder->order_number));
        $this->assertFalse($orderNumbers->contains('ORD-DATE-BEFORE'));
        $this->assertFalse($orderNumbers->contains('ORD-DATE-AFTER'));
    }

    private function createOrder(string $orderNumber, string $createdAt): Order
    {
        return Order::unguarded(fn () => Order::create([
            'order_number' => $orderNumber,
            'customer_name' => 'Date Filter Customer',
            'customer_email' => 'date-filter@example.com',
            'customer_phone' => '01700000000',
            'subtotal' => 1000,
            'total' => 1080,
            'shipping_cost' => 80,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]));
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->assignRole(Role::where('name', 'Super Admin')->first());

        return $admin;
    }
}
