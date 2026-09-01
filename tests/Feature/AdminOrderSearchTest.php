<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderTimeline;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        User::factory()->create(['user_type' => 'admin']);
    }

    public function test_search_permission_grants_only_the_search_order_flow(): void
    {
        $staff = User::factory()->create(['user_type' => 'admin']);
        $staff->givePermissionTo('orders.search');
        $order = $this->createOrder();

        $this->actingAs($staff)->get(route('orders.search'))->assertOk()->assertSee('Search Order');
        $this->actingAs($staff)->get(route('orders.search.details', $order))->assertOk()->assertSee($order->order_number);
        $this->actingAs($staff)->get(route('orders.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('orders.view', $order))->assertForbidden();
    }

    public function test_exact_order_number_search_redirects_to_the_restricted_details_page(): void
    {
        $staff = User::factory()->create(['user_type' => 'admin']);
        $staff->givePermissionTo('orders.search');
        $order = $this->createOrder();

        $this->actingAs($staff)
            ->get(route('orders.search', ['order_number' => $order->order_number]))
            ->assertRedirect(route('orders.search.details', $order));

        $this->actingAs($staff)
            ->get(route('orders.search', ['order_number' => 'ORD-MISSING']))
            ->assertOk()
            ->assertSee('Order not found.');
    }

    public function test_only_a_pending_order_can_be_cancelled_from_search(): void
    {
        $staff = User::factory()->create(['user_type' => 'admin']);
        $staff->givePermissionTo('orders.search');
        $pending = $this->createOrder();
        $confirmed = $this->createOrder([
            'order_number' => 'ORD-SEARCH-CONFIRMED',
            'order_status' => 'confirmed',
        ]);

        $this->actingAs($staff)
            ->put(route('orders.search.cancel', $pending))
            ->assertRedirect(route('orders.search.details', $pending));

        $pending->refresh();
        $this->assertSame('cancelled', $pending->order_status);
        $this->assertSame('staff', $pending->cancelled_by_type);
        $this->assertSame($staff->id, $pending->cancelled_by_id);
        $this->assertDatabaseHas('order_timelines', [
            'order_id' => $pending->id,
            'status' => 'Order Cancelled',
        ]);

        $details = $this->actingAs($staff)->get(route('orders.search.details', $confirmed));
        $details->assertOk()->assertDontSee('Cancel Order');

        $this->actingAs($staff)->put(route('orders.search.cancel', $confirmed));
        $this->assertSame('confirmed', $confirmed->fresh()->order_status);
    }

    public function test_restricted_details_page_shows_order_timeline(): void
    {
        $staff = User::factory()->create(['user_type' => 'admin']);
        $staff->givePermissionTo('orders.search');
        $order = $this->createOrder();

        OrderTimeline::create([
            'order_id' => $order->id,
            'updated_by' => $staff->id,
            'status' => 'Order Pending',
            'description' => 'Order was placed successfully.',
            'date' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('orders.search.details', $order))
            ->assertOk()
            ->assertSee('Order Timeline')
            ->assertSee('Order Pending')
            ->assertSee('Order was placed successfully.')
            ->assertSee('Updated by: '.$staff->name);
    }

    private function createOrder(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-SEARCH-PENDING',
            'customer_name' => 'Search Customer',
            'customer_email' => 'search@example.com',
            'customer_phone' => '01700000000',
            'subtotal' => 1000,
            'total' => 1080,
            'shipping_cost' => 80,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ], $attributes));
    }
}
