<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrderBulkConfirmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_selected_pending_orders_can_be_bulk_confirmed(): void
    {
        $admin = $this->createAdmin();
        $firstOrder = $this->createOrder(['order_number' => 'ORD-BULK-1']);
        $secondOrder = $this->createOrder(['order_number' => 'ORD-BULK-2']);

        $response = $this->actingAs($admin)->post(route('orders.update-bulk-confirm'), [
            'order_ids' => [$firstOrder->id, $secondOrder->id],
        ]);

        $response->assertRedirect(route('orders.pending'));

        $this->assertSame('confirmed', $firstOrder->fresh()->order_status);
        $this->assertSame('confirmed', $secondOrder->fresh()->order_status);

        foreach ([$firstOrder, $secondOrder] as $order) {
            $this->assertDatabaseHas('order_timelines', [
                'order_id' => $order->id,
                'updated_by' => $admin->id,
                'status' => 'Order Confirmed',
                'description' => 'Your order is confirmed',
            ]);
        }
    }

    public function test_bulk_confirmation_is_atomic_when_an_order_is_not_pending(): void
    {
        $admin = $this->createAdmin();
        $pendingOrder = $this->createOrder(['order_number' => 'ORD-BULK-PENDING']);
        $confirmedOrder = $this->createOrder([
            'order_number' => 'ORD-BULK-CONFIRMED',
            'order_status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)->post(route('orders.update-bulk-confirm'), [
            'order_ids' => [$pendingOrder->id, $confirmedOrder->id],
        ]);

        $response->assertRedirect(route('orders.pending'));

        $this->assertSame('pending', $pendingOrder->fresh()->order_status);
        $this->assertSame('confirmed', $confirmedOrder->fresh()->order_status);
        $this->assertDatabaseMissing('order_timelines', [
            'order_id' => $pendingOrder->id,
            'status' => 'Order Confirmed',
        ]);
    }

    public function test_pending_list_shows_bulk_confirmation_controls(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder(['order_number' => 'ORD-BULK-VIEW']);

        $this->actingAs($admin)
            ->get(route('orders.pending'))
            ->assertOk()
            ->assertSee(route('orders.update-bulk-confirm'), false)
            ->assertSee('Move to Confirmed')
            ->assertSee('value="'.$order->id.'"', false);
    }

    private function createOrder(array $overrides = []): Order
    {
        $customer = User::factory()->create(['user_type' => 'user']);

        return Order::create(array_merge([
            'order_number' => 'ORD-BULK-TEST',
            'user_id' => $customer->id,
            'customer_name' => 'Bulk Customer',
            'customer_email' => 'bulk@example.com',
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
        $admin->assignRole(Role::where('name', 'Super Admin')->firstOrFail());

        return $admin;
    }
}
