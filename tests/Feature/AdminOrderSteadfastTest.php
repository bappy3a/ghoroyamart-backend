<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Division;
use App\Models\Order;
use App\Models\ShippingAddress;
use App\Models\Thana;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrderSteadfastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_processing_status_creates_steadfast_consignment(): void
    {
        config()->set('services.steadfast.api_key', 'test-api-key');
        config()->set('services.steadfast.secret_key', 'test-secret-key');
        config()->set('services.steadfast.base_url', 'https://portal.packzy.com/api/v1');

        Http::fake([
            'https://portal.packzy.com/api/v1/create_order' => Http::response([
                'status' => 200,
                'message' => 'Consignment has been created successfully.',
                'consignment' => [
                    'consignment_id' => 1424107,
                    'invoice' => 'ORD-TEST-1',
                    'tracking_code' => '15BAEB8A',
                    'status' => 'in_review',
                ],
            ]),
        ]);

        $admin = $this->createAdmin();
        $order = $this->createOrder(['order_status' => 'confirmed']);

        $response = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('orders.update-status', $order), [
                'order_status' => 'processing',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'processing')
            ->assertJsonPath('steadfast.consignment_id', 1424107)
            ->assertJsonPath('steadfast.tracking_code', '15BAEB8A');

        Http::assertSent(fn ($request) => $request->url() === 'https://portal.packzy.com/api/v1/create_order'
            && $request->hasHeader('Api-Key', 'test-api-key')
            && $request->hasHeader('Secret-Key', 'test-secret-key')
            && $request['invoice'] === 'ORD-TEST-1'
            && $request['recipient_name'] === 'Jane Customer'
            && $request['recipient_phone'] === '01712345678'
            && $request['recipient_address'] === 'Flat# A1, House# 17/1, Road# 3/A, Dhanmondi, Dhaka-1209'
            && $request['cod_amount'] === 1500.0
            && $request['delivery_type'] === 0);

        $order->refresh();

        $this->assertSame('processing', $order->order_status);
        $this->assertSame(1424107, $order->steadfast_consignment_id);
        $this->assertSame('15BAEB8A', $order->steadfast_tracking_code);
        $this->assertSame('in_review', $order->steadfast_status);
        $this->assertNotNull($order->steadfast_order_placed_at);
    }

    public function test_confirmed_orders_can_be_bulk_moved_to_packaging_with_steadfast(): void
    {
        config()->set('services.steadfast.api_key', 'test-api-key');
        config()->set('services.steadfast.secret_key', 'test-secret-key');
        config()->set('services.steadfast.base_url', 'https://portal.packzy.com/api/v1');

        Http::fake([
            'https://portal.packzy.com/api/v1/create_order/bulk-order' => Http::response([
                [
                    'invoice' => 'ORD-BULK-PACK-1',
                    'recipient_name' => 'Jane Customer',
                    'recipient_address' => 'Flat# A1, House# 17/1, Road# 3/A, Dhanmondi, Dhaka-1209',
                    'recipient_phone' => '01712345678',
                    'cod_amount' => '1500.00',
                    'note' => null,
                    'consignment_id' => 11543968,
                    'tracking_code' => 'B025A038',
                    'status' => 'success',
                ],
                [
                    'invoice' => 'ORD-BULK-PACK-2',
                    'recipient_name' => 'Jane Customer',
                    'recipient_address' => 'Flat# A1, House# 17/1, Road# 3/A, Dhanmondi, Dhaka-1209',
                    'recipient_phone' => '01712345678',
                    'cod_amount' => '1500.00',
                    'note' => null,
                    'consignment_id' => 11543969,
                    'tracking_code' => 'B025A1DC',
                    'status' => 'success',
                ],
            ]),
        ]);

        $admin = $this->createAdmin();
        $firstOrder = $this->createOrder([
            'order_number' => 'ORD-BULK-PACK-1',
            'order_status' => 'confirmed',
        ]);
        $secondOrder = $this->createOrder([
            'order_number' => 'ORD-BULK-PACK-2',
            'order_status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)->post(route('orders.update-bulk-packaging'), [
            'order_ids' => [$firstOrder->id, $secondOrder->id],
        ]);

        $response->assertRedirect(route('orders.confirmed'));

        Http::assertSent(function ($request) {
            $bulkData = data_get($request->data(), 'data');
            $payloads = is_string($bulkData) ? json_decode($bulkData, true) : $bulkData;

            return $request->url() === 'https://portal.packzy.com/api/v1/create_order/bulk-order'
                && $request->hasHeader('Api-Key', 'test-api-key')
                && $request->hasHeader('Secret-Key', 'test-secret-key')
                && is_array($payloads)
                && count($payloads) === 2
                && $payloads[0]['invoice'] === 'ORD-BULK-PACK-1'
                && $payloads[0]['recipient_phone'] === '01712345678'
                && $payloads[0]['recipient_address'] === 'Flat# A1, House# 17/1, Road# 3/A, Dhanmondi, Dhaka-1209'
                && (float) $payloads[0]['cod_amount'] === 1500.0;
        });

        $this->assertSame('processing', $firstOrder->fresh()->order_status);
        $this->assertSame(11543968, $firstOrder->fresh()->steadfast_consignment_id);
        $this->assertSame('B025A038', $firstOrder->fresh()->steadfast_tracking_code);
        $this->assertSame('processing', $secondOrder->fresh()->order_status);
        $this->assertSame(11543969, $secondOrder->fresh()->steadfast_consignment_id);

        foreach ([$firstOrder, $secondOrder] as $order) {
            $this->assertDatabaseHas('order_timelines', [
                'order_id' => $order->id,
                'updated_by' => $admin->id,
                'status' => 'Order Processing',
                'description' => 'Your order is processing',
            ]);
        }
    }

    public function test_bulk_packaging_keeps_failed_steadfast_orders_confirmed(): void
    {
        config()->set('services.steadfast.api_key', 'test-api-key');
        config()->set('services.steadfast.secret_key', 'test-secret-key');
        config()->set('services.steadfast.base_url', 'https://portal.packzy.com/api/v1');

        Http::fake([
            'https://portal.packzy.com/api/v1/create_order/bulk-order' => Http::response([
                [
                    'invoice' => 'ORD-BULK-OK',
                    'consignment_id' => 11543968,
                    'tracking_code' => 'B025A038',
                    'status' => 'success',
                ],
                [
                    'invoice' => 'ORD-BULK-FAIL',
                    'consignment_id' => null,
                    'tracking_code' => null,
                    'status' => 'error',
                ],
            ]),
        ]);

        $admin = $this->createAdmin();
        $successfulOrder = $this->createOrder([
            'order_number' => 'ORD-BULK-OK',
            'order_status' => 'confirmed',
        ]);
        $failedOrder = $this->createOrder([
            'order_number' => 'ORD-BULK-FAIL',
            'order_status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)->post(route('orders.update-bulk-packaging'), [
            'order_ids' => [$successfulOrder->id, $failedOrder->id],
        ]);

        $response->assertRedirect(route('orders.confirmed'));

        $this->assertSame('processing', $successfulOrder->fresh()->order_status);
        $this->assertSame(11543968, $successfulOrder->fresh()->steadfast_consignment_id);
        $this->assertSame('confirmed', $failedOrder->fresh()->order_status);
        $this->assertNull($failedOrder->fresh()->steadfast_consignment_id);
        $this->assertDatabaseMissing('order_timelines', [
            'order_id' => $failedOrder->id,
            'status' => 'Order Processing',
        ]);
    }

    public function test_confirmed_list_shows_bulk_packaging_controls(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder([
            'order_number' => 'ORD-BULK-VIEW',
            'order_status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->get(route('orders.confirmed'))
            ->assertOk()
            ->assertSee(route('orders.update-bulk-packaging'), false)
            ->assertSee('Move to Packaging')
            ->assertSee('value="'.$order->id.'"', false);
    }

    public function test_cash_on_delivery_order_stores_one_percent_steadfast_cod_charge(): void
    {
        $order = $this->createOrder([
            'payment_method' => 'cash_on_delivery',
            'total' => 1500,
        ]);

        $order->update([
            'steadfast_cod_charger' => Order::steadfastCodChargeFor($order->payment_method, $order->total),
        ]);

        $this->assertSame('15.00', $order->refresh()->steadfast_cod_charger);
    }

    public function test_non_cod_order_has_zero_steadfast_cod_charge(): void
    {
        $this->assertSame(0.0, Order::steadfastCodChargeFor('bkash', 1500));
    }

    public function test_existing_steadfast_consignment_is_not_created_again(): void
    {
        Http::fake();

        $admin = $this->createAdmin();
        $order = $this->createOrder([
            'order_status' => 'processing',
            'steadfast_consignment_id' => 1424107,
            'steadfast_tracking_code' => '15BAEB8A',
            'steadfast_status' => 'in_review',
        ]);

        $response = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('orders.update-status', $order), [
                'order_status' => 'shipped',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('steadfast.consignment_id', 1424107);

        Http::assertNothingSent();
    }

    public function test_order_status_is_not_updated_when_steadfast_fails(): void
    {
        config()->set('services.steadfast.api_key', 'test-api-key');
        config()->set('services.steadfast.secret_key', 'test-secret-key');
        config()->set('services.steadfast.base_url', 'https://portal.packzy.com/api/v1');

        Http::fake([
            'https://portal.packzy.com/api/v1/create_order' => Http::response([
                'status' => 400,
                'message' => 'Invalid recipient phone.',
            ]),
        ]);

        $admin = $this->createAdmin();
        $order = $this->createOrder(['order_status' => 'confirmed']);

        $response = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('orders.update-status', $order), [
                'order_status' => 'processing',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Steadfast order failed: Invalid recipient phone.')
            ->assertJsonPath('status', 'confirmed');

        $order->refresh();

        $this->assertSame('confirmed', $order->order_status);
        $this->assertNull($order->steadfast_consignment_id);
        $this->assertDatabaseMissing('order_timelines', [
            'order_id' => $order->id,
            'status' => 'Order Processing',
        ]);
    }

    public function test_http_error_body_is_shown_when_steadfast_rejects_create_order(): void
    {
        config()->set('services.steadfast.api_key', 'test-api-key');
        config()->set('services.steadfast.secret_key', 'test-secret-key');
        config()->set('services.steadfast.base_url', 'https://portal.packzy.com/api/v1');

        Http::fake([
            'https://portal.packzy.com/api/v1/create_order' => Http::response('Account is not active!', 401),
        ]);

        $admin = $this->createAdmin();
        $order = $this->createOrder(['order_status' => 'confirmed']);

        $response = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('orders.update-status', $order), [
                'order_status' => 'processing',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'message' => 'Steadfast order failed: Steadfast returned HTTP 401: Account is not active!',
            ]);

        $order->refresh();

        $this->assertSame('confirmed', $order->order_status);
        $this->assertNull($order->steadfast_consignment_id);
    }

    private function createOrder(array $overrides = []): Order
    {
        $customer = User::factory()->create(['user_type' => 'user']);
        $division = Division::create(['name' => 'Dhaka']);
        $district = District::create([
            'division_id' => $division->id,
            'name' => 'Dhaka',
        ]);
        $thana = Thana::create([
            'district_id' => $district->id,
            'name' => 'Dhanmondi',
            'zip_code' => '1209',
        ]);
        $shippingAddress = ShippingAddress::create([
            'user_id' => $customer->id,
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'phone' => '+88 01712-345678',
            'division_id' => $division->id,
            'district_id' => $district->id,
            'thana_id' => $thana->id,
            'address' => 'Flat# A1, House# 17/1, Road# 3/A',
            'address_type' => 'home',
        ]);

        return Order::create(array_merge([
            'order_number' => 'ORD-TEST-1',
            'user_id' => $customer->id,
            'customer_name' => 'Jane Customer',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '01712345678',
            'shipping_address_id' => $shippingAddress->id,
            'subtotal' => 1500,
            'tax' => 0,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 1500,
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
