<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderResource;
use App\Models\ShippingAddress;
use App\Services\CheckoutOrderService;
use App\Services\CheckoutPricingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CheckoutOrderService $orders,
        private readonly CheckoutPricingService $pricing,
    ) {}

    /**
     * Place a storefront order for the authenticated user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->checkoutRules(), $this->checkoutMessages());

        if ($validator->fails()) {
            return $this->error('Please provide valid checkout details.', $validator->errors(), null, 422);
        }

        $user = $request->user();
        $shippingAddress = $this->ownedShippingAddress($request, (int) $request->input('shipping_address_id'));

        if ($shippingAddress instanceof JsonResponse) {
            return $shippingAddress;
        }

        try {
            $order = $this->orders->place(
                $request->input('items', []),
                $shippingAddress,
                $user,
                [
                    'name' => $shippingAddress->name ?: $user->name,
                    'email' => $shippingAddress->email ?: $user->email,
                    'phone' => $shippingAddress->phone ?: $user->phone,
                ],
                $request->input('coupon_code'),
                $request->input('payment_method', 'cash_on_delivery'),
                $request->input('order_notes'),
            );

            return $this->success(
                (new OrderResource($order))->resolve(),
                null,
                'Order placed successfully.',
                201
            );
        } catch (Throwable $exception) {
            if ($this->isClientFacingError($exception)) {
                return $this->error($exception->getMessage(), null, null, 422);
            }

            Log::error('Storefront checkout failed.', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
                'request' => $request->except(['_token']),
            ]);

            return $this->error('Failed to place order. Please try again.', null, null, 500);
        }
    }

    /**
     * Apply a coupon and return updated checkout totals.
     */
    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:50'],
            'shipping_address_id' => ['nullable', 'integer', 'exists:shipping_addresses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.variant_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide a valid coupon and cart items.', $validator->errors(), null, 422);
        }

        $shippingAddress = null;
        if ($request->filled('shipping_address_id')) {
            $resolved = $this->ownedShippingAddress($request, (int) $request->input('shipping_address_id'));
            if ($resolved instanceof JsonResponse) {
                return $resolved;
            }
            $shippingAddress = $resolved;
        }

        try {
            $quote = $this->pricing->quote(
                $request->input('items', []),
                $shippingAddress,
                $request->input('code'),
                $request->user(),
            );

            if (! $quote['coupon']) {
                return $this->error('Invalid coupon code.', null, null, 422);
            }

            return $this->success(
                $this->quotePayload($quote),
                null,
                'Coupon applied successfully.'
            );
        } catch (Throwable $exception) {
            if ($this->isClientFacingError($exception)) {
                return $this->error($exception->getMessage(), null, null, 422);
            }

            return $this->error('Failed to apply coupon.', null, null, 500);
        }
    }

    /**
     * Remove coupon and return totals without discount.
     */
    public function removeCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address_id' => ['nullable', 'integer', 'exists:shipping_addresses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.variant_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide valid cart items.', $validator->errors(), null, 422);
        }

        $shippingAddress = null;
        if ($request->filled('shipping_address_id')) {
            $resolved = $this->ownedShippingAddress($request, (int) $request->input('shipping_address_id'));
            if ($resolved instanceof JsonResponse) {
                return $resolved;
            }
            $shippingAddress = $resolved;
        }

        try {
            $quote = $this->pricing->quote(
                $request->input('items', []),
                $shippingAddress,
                null,
                $request->user(),
            );

            return $this->success(
                $this->quotePayload($quote),
                null,
                'Coupon removed successfully.'
            );
        } catch (Throwable $exception) {
            if ($this->isClientFacingError($exception)) {
                return $this->error($exception->getMessage(), null, null, 422);
            }

            return $this->error('Failed to remove coupon.', null, null, 500);
        }
    }

    /**
     * Preview shipping / totals when address or cart changes.
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address_id' => ['nullable', 'integer', 'exists:shipping_addresses,id'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.variant_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide valid checkout details.', $validator->errors(), null, 422);
        }

        $shippingAddress = null;
        if ($request->filled('shipping_address_id')) {
            $resolved = $this->ownedShippingAddress($request, (int) $request->input('shipping_address_id'));
            if ($resolved instanceof JsonResponse) {
                return $resolved;
            }
            $shippingAddress = $resolved;
        }

        try {
            $quote = $this->pricing->quote(
                $request->input('items', []),
                $shippingAddress,
                $request->input('coupon_code'),
                $request->user(),
            );

            return $this->success(
                $this->quotePayload($quote),
                null,
                'Checkout totals calculated successfully.'
            );
        } catch (Throwable $exception) {
            if ($this->isClientFacingError($exception)) {
                return $this->error($exception->getMessage(), null, null, 422);
            }

            return $this->error('Failed to calculate checkout totals.', null, null, 500);
        }
    }

    /**
     * Delivery charge settings for the storefront.
     */
    public function deliveryCharges()
    {
        $charges = $this->pricing->deliveryCharges();

        return $this->success([
            'inside_dhaka' => $charges['inside_dhaka'],
            'outside_dhaka' => $charges['outside_dhaka'],
            'inside_district' => 'Dhaka City',
        ], null, 'Delivery charges fetched successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function quotePayload(array $quote): array
    {
        return [
            'subtotal' => $quote['subtotal'],
            'discount' => $quote['discount'],
            'shipping_cost' => $quote['shipping_cost'],
            'shipping_method' => $quote['shipping_method'],
            'tax' => $quote['tax'],
            'total' => $quote['total'],
            'district_name' => $quote['district_name'],
            'delivery_charges' => $quote['delivery_charges'],
            'coupon' => $quote['coupon'],
        ];
    }

    /**
     * @return ShippingAddress|JsonResponse
     */
    private function ownedShippingAddress(Request $request, int $id)
    {
        $shippingAddress = ShippingAddress::query()
            ->with('deliveryArea')
            ->where('user_id', $request->user()->id)
            ->whereKey($id)
            ->first();

        if (! $shippingAddress) {
            return $this->error('Shipping address not found.', [
                'shipping_address_id' => ['The selected shipping address is invalid.'],
            ], null, 422);
        }

        if (! $shippingAddress->delivery_area_id || ! $shippingAddress->deliveryArea) {
            return $this->error('Please update your shipping address with a delivery area.', [
                'shipping_address_id' => ['A valid delivery area is required.'],
            ], null, 422);
        }

        return $shippingAddress;
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutRules(): array
    {
        return [
            'shipping_address_id' => ['required', 'integer', 'exists:shipping_addresses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.variant_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'in:cash_on_delivery'],
            'order_notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function checkoutMessages(): array
    {
        return [
            'shipping_address_id.required' => 'Please select a shipping address.',
            'items.required' => 'Your cart is empty.',
            'items.min' => 'Your cart is empty.',
        ];
    }

    private function isClientFacingError(Throwable $exception): bool
    {
        return $exception instanceof \RuntimeException;
    }
}
