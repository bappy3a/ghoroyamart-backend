<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SteadfastWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_status_webhook_updates_order_and_creates_timeline(): void
    {
        $order = $this->createOrder([
            'order_status' => 'shipped',
            'steadfast_consignment_id' => 12345,
        ]);

        $response = $this->postJson(route('api.steadfast.webhook'), [
            'notification_type' => 'delivery_status',
            'consignment_id' => 12345,
            'invoice' => 'ORD-WEBHOOK-1',
            'cod_amount' => 1500.00,
            'status' => 'Delivered',
            'delivery_charge' => 100.00,
            'tracking_message' => 'Your package has been delivered successfully.',
            'updated_at' => '2025-03-02 12:45:30',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();

        $this->assertSame('delivered', $order->order_status);
        $this->assertSame('delivered', $order->steadfast_status);
        $this->assertSame('100.00', $order->steadfast_delivery_charges);
        $this->assertSame('delivery_status', $order->steadfast_response['latest_webhook']['notification_type']);

        $this->assertDatabaseHas('order_timelines', [
            'order_id' => $order->id,
            'updated_by' => null,
            'status' => 'Steadfast Delivery Status: Delivered',
            'description' => "Your package has been delivered successfully.\nConsignment ID: 12345\nInvoice: ORD-WEBHOOK-1\nCOD Amount: 1500.00\nDelivery Charge: 100.00",
            'date' => '2025-03-02 12:45:30',
        ]);
    }

    public function test_tracking_update_webhook_marks_order_as_shipped_and_creates_timeline(): void
    {
        $order = $this->createOrder([
            'order_status' => 'processing',
            'steadfast_consignment_id' => 12345,
            'steadfast_status' => 'pending',
        ]);

        $response = $this->postJson(route('api.steadfast.webhook'), [
            'notification_type' => 'tracking_update',
            'consignment_id' => 12345,
            'invoice' => 'ORD-WEBHOOK-1',
            'tracking_message' => 'Package arrived at the sorting center.',
            'updated_at' => '2025-03-02 13:15:00',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();

        $this->assertSame('shipped', $order->order_status);
        $this->assertSame('pending', $order->steadfast_status);

        $this->assertDatabaseHas('order_timelines', [
            'order_id' => $order->id,
            'status' => 'Steadfast Tracking Update',
            'description' => "Package arrived at the sorting center.\nConsignment ID: 12345\nInvoice: ORD-WEBHOOK-1",
            'date' => '2025-03-02 13:15:00',
        ]);
    }

    public function test_partial_delivered_webhook_saves_order_status_as_partial_delivered(): void
    {
        $order = $this->createOrder([
            'order_status' => 'shipped',
            'steadfast_consignment_id' => 12345,
        ]);

        $response = $this->postJson(route('api.steadfast.webhook'), [
            'notification_type' => 'delivery_status',
            'consignment_id' => 12345,
            'invoice' => 'ORD-WEBHOOK-1',
            'cod_amount' => 1500.00,
            'status' => 'partial_delivered',
            'delivery_charge' => 100.00,
            'tracking_message' => 'Consignment was partially delivered.',
            'updated_at' => '2025-03-02 12:45:30',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();

        $this->assertSame('partial_delivered', $order->order_status);
        $this->assertSame('partial_delivered', $order->steadfast_status);
    }

    public function test_webhook_can_match_order_by_invoice_when_consignment_was_not_saved_yet(): void
    {
        $order = $this->createOrder([
            'order_status' => 'confirmed',
            'steadfast_consignment_id' => null,
        ]);

        $response = $this->postJson(route('api.steadfast.webhook'), [
            'notification_type' => 'delivery_status',
            'consignment_id' => 98765,
            'invoice' => 'ORD-WEBHOOK-1',
            'cod_amount' => 1500.00,
            'status' => 'pending',
            'delivery_charge' => 100.00,
            'tracking_message' => 'Consignment is pending.',
            'updated_at' => '2025-03-02 12:45:30',
        ]);

        $response->assertOk();

        $order->refresh();

        $this->assertSame(98765, $order->steadfast_consignment_id);
        $this->assertSame('processing', $order->order_status);
        $this->assertSame('pending', $order->steadfast_status);
    }

    public function test_configured_webhook_secret_is_required(): void
    {
        config()->set('services.steadfast.webhook_secret', 'secret-value');

        $this->createOrder(['steadfast_consignment_id' => 12345]);

        $this->postJson(route('api.steadfast.webhook'), [
            'notification_type' => 'tracking_update',
            'consignment_id' => 12345,
            'invoice' => 'ORD-WEBHOOK-1',
            'tracking_message' => 'Package arrived at the sorting center.',
            'updated_at' => '2025-03-02 13:15:00',
        ])->assertUnauthorized();

        $this->withHeader('X-Steadfast-Webhook-Secret', 'secret-value')
            ->postJson(route('api.steadfast.webhook'), [
                'notification_type' => 'tracking_update',
                'consignment_id' => 12345,
                'invoice' => 'ORD-WEBHOOK-1',
                'tracking_message' => 'Package arrived at the sorting center.',
                'updated_at' => '2025-03-02 13:15:00',
            ])
            ->assertOk();
    }

    private function createOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-WEBHOOK-1',
            'customer_name' => 'Jane Customer',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '01712345678',
            'subtotal' => 1500,
            'tax' => 0,
            'discount' => 0,
            'shipping_cost' => 100,
            'total' => 1600,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'order_status' => 'processing',
            'shipping_method' => 'standard',
        ], $overrides));
    }
}
