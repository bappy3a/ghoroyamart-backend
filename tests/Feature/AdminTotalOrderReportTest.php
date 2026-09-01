<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTotalOrderReportTest extends TestCase
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
            ->get(route('total-order-report.index'))
            ->assertForbidden();

        $staff->givePermissionTo('total-order-report.show');

        $this->actingAs($staff)
            ->get(route('total-order-report.index'))
            ->assertOk()
            ->assertSee('Total Order Report');
    }

    public function test_report_counts_returned_as_restocked_item_quantity(): void
    {
        $staff = User::factory()->create(['user_type' => 'admin']);
        $staff->givePermissionTo('total-order-report.show');
        $product = Product::create([
            'name' => 'Returned Qty Product',
            'regular_price' => 100,
            'price' => 100,
        ]);

        $returnedOrder = $this->createOrder('TOTAL-RETURNED-QTY-1', 'delivered', 500);
        OrderItem::create([
            'order_id' => $returnedOrder->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100,
            'quantity' => 5,
            'restocked_quantity' => 2,
            'subtotal' => 500,
        ]);

        $statusOnlyReturnedOrder = $this->createOrder('TOTAL-STATUS-RETURNED-1', 'returned', 300);
        OrderItem::create([
            'order_id' => $statusOnlyReturnedOrder->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100,
            'quantity' => 3,
            'restocked_quantity' => 0,
            'subtotal' => 300,
        ]);

        $partialDeliveredOrder = $this->createOrder('TOTAL-PARTIAL-DELIVERED-1', 'partial_delivered', 200);
        OrderItem::create([
            'order_id' => $partialDeliveredOrder->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100,
            'quantity' => 2,
            'subtotal' => 200,
        ]);

        $response = $this->actingAs($staff)
            ->get(route('total-order-report.index', [
                'from' => now()->subDay()->format('Y-m-d'),
                'to' => now()->addDay()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('Returned Qty');

        $summary = $response->viewData('summary');
        $dailyReport = collect($response->viewData('paginatedDailyReport')->items());
        $orderDay = $dailyReport->firstWhere('orders', 3);

        $this->assertSame(3, $summary->orders);
        $this->assertSame(10, $summary->units);
        $this->assertSame(2, $summary->delivered);
        $this->assertSame(2, $summary->returned);
        $this->assertSame(1, $summary->active_orders);
        $this->assertSame(2, $orderDay->delivered);
        $this->assertSame(2, $orderDay->returned);
        $this->assertSame(1, $orderDay->active_orders);
    }

    private function createOrder(string $number, string $status, float $total): Order
    {
        return Order::create([
            'order_number' => $number,
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
