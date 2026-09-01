<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Division;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PromoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_promo_checkout_can_place_order_without_thana(): void
    {
        Setting::set('delivery_charge_inside_dhaka', 80);
        Setting::set('delivery_charge_outside_dhaka', 150);

        $division = Division::create(['name' => 'Dhaka']);
        $district = District::create(['division_id' => $division->id, 'name' => 'Dhaka']);
        $product = Product::create([
            'name' => 'Promo Product',
            'slug' => 'promo-product',
            'sku' => 'PROMO-1',
            'status' => 'published',
            'visibility' => 'public',
            'quantity' => 5,
            'stock_status' => 'in_stock',
            'regular_price' => 120,
            'price' => 100,
            'unit' => 'pcs',
        ]);
        $otpId = DB::table('order_otp_verifications')->insertGetId([
            'phone' => '01788888888',
            'otp_code' => '123456',
            'expires_at' => now()->addMinutes(5),
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'promo_checkout_otp' => [
                    'id' => $otpId,
                    'verified' => true,
                    'expires_at' => now()->addMinutes(5),
                ],
            ])
            ->postJson('/api/promo/checkout/place-order', [
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => 1,
                'price' => 100,
                'customer_name' => 'Promo Customer',
                'customer_email' => 'promo@example.com',
                'customer_phone' => '01788888888',
                'shipping_address' => 'Promo address without thana',
                'shipping_division_id' => $division->id,
                'shipping_district_id' => $district->id,
                'shipping_thana_id' => null,
                'shipping_address_type' => 'home',
                'payment_method' => 'cash_on_delivery',
                'shipping_method' => 'flat_rate',
                'shipping_cost' => 80,
                'delivery_area' => 'inside_dhaka',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $shippingAddress = ShippingAddress::where('phone', '01788888888')->firstOrFail();

        $this->assertSame($district->id, $shippingAddress->district_id);
        $this->assertNull($shippingAddress->thana_id);
    }
}
