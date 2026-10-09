<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderResource;
use App\Models\DeliveryArea;
use App\Models\ShippingAddress;
use App\Services\CheckoutOrderService;
use App\Services\CheckoutPricingService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Checkout without an account.
 */
class GuestCheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CheckoutOrderService $orders,
        private readonly CheckoutPricingService $pricing,
    ) {}

    public function preview(Request $request)
    {
        return $this->quoteResponse($request, $request->input('coupon_code'), 'Checkout totals calculated successfully.');
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:50']]);

        return $this->quoteResponse($request, $request->input('code'), 'Coupon applied successfully.', true);
    }

    public function removeCoupon(Request $request)
    {
        return $this->quoteResponse($request, null, 'Coupon removed successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->itemRules() + [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_delivery_area_id' => ['required', 'integer', 'exists:delivery_areas,id'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_address_type' => ['nullable', 'in:home,office,hometown'],
            'payment_method' => ['nullable', 'in:cash_on_delivery'],
            'order_notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ], [
            'items.required' => 'Your cart is empty.',
            'items.min' => 'Your cart is empty.',
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide valid checkout details.', $validator->errors(), null, 422);
        }

        $phone = trim((string) $request->input('customer_phone'));
        $area = DeliveryArea::query()->active()->find($request->input('shipping_delivery_area_id'));
        if (! $area) {
            return $this->error('The selected delivery area is invalid.', [
                'shipping_delivery_area_id' => ['The selected delivery area is invalid.'],
            ], null, 422);
        }

        try {
            $order = DB::transaction(function () use ($request, $area, $phone) {
                $shippingAddress = ShippingAddress::create([
                    'user_id' => null,
                    'name' => $request->input('customer_name'),
                    'email' => $request->input('customer_email'),
                    'phone' => $phone,
                    'delivery_area_id' => $area->id,
                    'address' => $request->input('shipping_address'),
                    'address_type' => $request->input('shipping_address_type', 'home'),
                    'postal_code' => $request->input('shipping_postal_code'),
                    'is_default' => false,
                ]);
                $shippingAddress->setRelation('deliveryArea', $area);

                return $this->orders->place(
                    $request->input('items', []),
                    $shippingAddress,
                    null,
                    [
                        'name' => $request->input('customer_name'),
                        'email' => $request->input('customer_email'),
                        'phone' => $phone,
                    ],
                    $request->input('coupon_code'),
                    $request->input('payment_method', 'cash_on_delivery'),
                    $request->input('order_notes'),
                );
            });

            return $this->success((new OrderResource($order))->resolve(), null, 'Order placed successfully.', 201);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, null, 422);
        } catch (Throwable $e) {
            Log::error('Guest checkout failed.', ['error' => $e->getMessage()]);

            return $this->error('Failed to place order. Please try again.', null, null, 500);
        }
    }

    private function quoteResponse(Request $request, ?string $couponCode, string $message, bool $requireCoupon = false)
    {
        $validator = Validator::make($request->all(), $this->itemRules() + [
            'shipping_delivery_area_id' => ['nullable', 'integer', 'exists:delivery_areas,id'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide valid checkout details.', $validator->errors(), null, 422);
        }

        $address = null;
        if ($request->filled('shipping_delivery_area_id')) {
            $area = DeliveryArea::query()->active()->find($request->input('shipping_delivery_area_id'));
            if (! $area) {
                return $this->error('The selected delivery area is invalid.', null, null, 422);
            }
            $address = (new ShippingAddress(['delivery_area_id' => $area->id]))->setRelation('deliveryArea', $area);
        }

        try {
            $quote = $this->pricing->quote($request->input('items', []), $address, $couponCode);

            if ($requireCoupon && ! $quote['coupon']) {
                return $this->error('Invalid coupon code.', null, null, 422);
            }

            return $this->success([
                'subtotal' => $quote['subtotal'],
                'discount' => $quote['discount'],
                'shipping_cost' => $quote['shipping_cost'],
                'shipping_method' => $quote['shipping_method'],
                'tax' => $quote['tax'],
                'total' => $quote['total'],
                'district_name' => $quote['district_name'],
                'delivery_charges' => $quote['delivery_charges'],
                'coupon' => $quote['coupon'],
            ], null, $message);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, null, 422);
        } catch (Throwable $e) {
            return $this->error('Failed to calculate checkout totals.', null, null, 500);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function itemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.variant_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
