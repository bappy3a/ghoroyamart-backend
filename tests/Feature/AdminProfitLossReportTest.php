<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProfitLossReportTest extends TestCase
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
            ->get(route('profit-loss-report.index'))
            ->assertForbidden();

        $staff->givePermissionTo('profit-loss-report.show');

        $this->actingAs($staff)
            ->get(route('profit-loss-report.index'))
            ->assertOk()
            ->assertSee('Profit / Loss Report');
    }

    public function test_report_calculates_delivered_order_profit_from_snapshotted_cost(): void
    {
        $staff = User::factory()->create(['user_type' => 'admin']);
        $staff->givePermissionTo('profit-loss-report.show');
        $product = Product::create([
            'name' => 'Report Product',
            'purchase_price' => 90,
            'regular_price' => 200,
            'price' => 200,
        ]);

        $delivered = $this->createOrder('ORD-REPORT-DELIVERED', 'delivered', 400, 20);
        OrderItem::create([
            'order_id' => $delivered->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 200,
            'regular_price' => 200,
            'purchase_price' => 75,
            'quantity' => 2,
            'subtotal' => 400,
        ]);

        $partialDelivered = $this->createOrder(
            'ORD-REPORT-PARTIAL',
            'partial_delivered',
            600,
            50,
            shipping: 80,
            steadfastCodCharge: 3.30,
            steadfastDeliveryCharges: 30,
        );
        OrderItem::create([
            'order_id' => $partialDelivered->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 300,
            'purchase_price' => 100,
            'quantity' => 2,
            'cancelled_quantity' => 1,
            'subtotal' => 600,
        ]);

        $pending = $this->createOrder('ORD-REPORT-PENDING', 'pending', 1000, 0);
        OrderItem::create([
            'order_id' => $pending->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 1000,
            'purchase_price' => 10,
            'quantity' => 1,
            'subtotal' => 1000,
        ]);

        $response = $this->actingAs($staff)
            ->get(route('profit-loss-report.index', [
                'from' => now()->subDay()->format('Y-m-d'),
                'to' => now()->addDay()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('ORD-REPORT-DELIVERED')
            ->assertSee('ORD-REPORT-PARTIAL')
            ->assertDontSee('ORD-REPORT-PENDING')
            ->assertSee('৳380.00')
            ->assertSee('৳150.00')
            ->assertSee('৳230.00')
            ->assertSee('৳30.00')
            ->assertSee('৳3.30')
            ->assertSee('৳196.70');

        $summary = $response->viewData('summary');

        $this->assertSame(2, $summary->orders);
        $this->assertSame(3, $summary->units);
        $this->assertSame(710.0, $summary->net_sales);
        $this->assertSame(250.0, $summary->cost);
        $this->assertSame(30.0, $summary->steadfast_delivery_charges);
        $this->assertSame(3.3, $summary->steadfast_cod_charger);
        $this->assertSame(426.7, $summary->profit);
    }

    public function test_report_exports_filtered_rows_as_csv(): void
    {
        $staff = User::factory()->create(['user_type' => 'admin']);
        $staff->givePermissionTo('profit-loss-report.show');
        $product = Product::create([
            'name' => 'CSV Product',
            'purchase_price' => 60,
            'regular_price' => 150,
            'price' => 150,
        ]);

        $included = $this->createOrder(
            'ORD-CSV-INCLUDED',
            'delivered',
            300,
            20,
            shipping: 50,
            steadfastCodCharge: 2.50,
            steadfastDeliveryCharges: 25,
        );
        OrderItem::create([
            'order_id' => $included->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 150,
            'purchase_price' => 60,
            'quantity' => 2,
            'subtotal' => 300,
        ]);

        $pending = $this->createOrder('ORD-CSV-PENDING', 'pending', 500, 0);
        OrderItem::create([
            'order_id' => $pending->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 500,
            'purchase_price' => 100,
            'quantity' => 1,
            'subtotal' => 500,
        ]);

        $outsideRange = $this->createOrder('ORD-CSV-OLD', 'delivered', 200, 0);
        $outsideRange->forceFill([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ])->save();
        OrderItem::create([
            'order_id' => $outsideRange->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 200,
            'purchase_price' => 50,
            'quantity' => 1,
            'subtotal' => 200,
        ]);

        $response = $this->actingAs($staff)
            ->get(route('profit-loss-report.index', [
                'from' => now()->subDay()->format('Y-m-d'),
                'to' => now()->addDay()->format('Y-m-d'),
                'status' => 'delivered',
                'export' => 'csv',
            ]))
            ->assertOk()
            ->assertDownload('profit-loss-report-'.now()->subDay()->format('Y-m-d').'-to-'.now()->addDay()->format('Y-m-d').'.csv');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Profit / Loss Report', $csv);
        $this->assertStringContainsString('ORD-CSV-INCLUDED', $csv);
        $this->assertStringContainsString('330.00', $csv);
        $this->assertStringContainsString('120.00', $csv);
        $this->assertStringContainsString('182.50', $csv);
        $this->assertStringNotContainsString('ORD-CSV-PENDING', $csv);
        $this->assertStringNotContainsString('ORD-CSV-OLD', $csv);
    }

    private function createOrder(
        string $number,
        string $status,
        float $subtotal,
        float $discount,
        float $shipping = 0,
        float $tax = 0,
        float $steadfastCodCharge = 0,
        float $steadfastDeliveryCharges = 0,
    ): Order {
        return Order::create([
            'order_number' => $number,
            'customer_name' => 'Report Customer',
            'customer_email' => 'report@example.com',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'shipping_cost' => $shipping,
            'total' => $subtotal + $tax - $discount + $shipping,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'paid',
            'order_status' => $status,
            'steadfast_cod_charger' => $steadfastCodCharge,
            'steadfast_delivery_charges' => $steadfastDeliveryCharges,
        ]);
    }
}
