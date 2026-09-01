<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Support\Collection;

class CheckoutPricingService
{
    /**
     * Inside-Dhaka delivery when district_name is exactly "Dhaka City".
     */
    public function isInsideDhaka(?string $districtName): bool
    {
        return strcasecmp(trim((string) $districtName), 'Dhaka City') === 0;
    }

    /**
     * @return array{inside_dhaka: float, outside_dhaka: float}
     */
    public function deliveryCharges(): array
    {
        return [
            'inside_dhaka' => (float) Setting::get('delivery_charge_inside_dhaka', 80),
            'outside_dhaka' => (float) Setting::get('delivery_charge_outside_dhaka', 150),
        ];
    }

    /**
     * @return array{0: string, 1: float}
     */
    public function resolveShipping(?ShippingAddress $shippingAddress): array
    {
        $charges = $this->deliveryCharges();
        $districtName = trim((string) ($shippingAddress?->deliveryArea?->district_name ?? ''));
        $shippingMethod = $this->isInsideDhaka($districtName)
            ? 'inside_dhaka'
            : 'outside_dhaka';

        return [$shippingMethod, $charges[$shippingMethod]];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, array{product: Product, variant: ?ProductVariant, quantity: int, price: float, subtotal: float}>
     */
    public function prepareOrderItems(array $items): Collection
    {
        $rows = collect($items)
            ->filter(fn ($item) => ! empty($item['product_id']))
            ->values();

        if ($rows->isEmpty()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        $productIds = $rows->pluck('product_id')->map(fn ($id) => (int) $id)->unique();
        $variantIds = $rows->pluck('product_variant_id')->filter()->map(fn ($id) => (int) $id)->unique();

        $products = Product::query()
            ->with([
                'variants' => fn ($q) => $q->where('is_active', true)
                    ->with(['values.value']),
            ])
            ->whereIn('id', $productIds)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->get()
            ->keyBy('id');

        $variants = ProductVariant::query()
            ->with(['values.value'])
            ->whereIn('id', $variantIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return $rows->map(function ($item) use ($products, $variants) {
            $product = $products->get((int) $item['product_id']);

            if (! $product) {
                throw new \RuntimeException('One of the selected products is unavailable.');
            }

            $variant = null;
            if (! empty($item['product_variant_id'])) {
                $variant = $variants->get((int) $item['product_variant_id']);

                if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                    // Variant id may be stale — fall through to label / single-variant resolution.
                    $variant = null;
                }
            }

            if (! $variant && $product->variants->isNotEmpty()) {
                $variant = $this->matchProductVariant(
                    $product->variants,
                    $item['variant_name'] ?? $item['variant'] ?? null,
                );

                if (! $variant && $product->variants->count() === 1) {
                    $variant = $product->variants->first();
                }
            }

            if (! $variant && $product->variants->isNotEmpty()) {
                throw new \RuntimeException('Please select a variant for '.$product->name.'.');
            }

            $quantity = (int) $item['quantity'];
            $price = (float) ($variant?->selling_price ?: $product->price);

            return [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $price * $quantity,
            ];
        });
    }

    /**
     * Match a cart variant label like "Red / XL" or "Red + XL" to an active product variant.
     *
     * @param  \Illuminate\Support\Collection<int, ProductVariant>  $variants
     */
    private function matchProductVariant($variants, mixed $label): ?ProductVariant
    {
        $label = trim((string) $label);
        if ($label === '') {
            return null;
        }

        $wanted = collect(preg_split('/\s*[\/+|]\s*/', $label) ?: [])
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter()
            ->unique()
            ->values();

        if ($wanted->isEmpty()) {
            return null;
        }

        foreach ($variants as $variant) {
            $values = $variant->values
                ->map(fn ($row) => strtolower(trim((string) ($row->value?->value ?? ''))))
                ->filter()
                ->unique()
                ->values();

            if ($values->count() !== $wanted->count()) {
                continue;
            }

            if ($wanted->diff($values)->isEmpty()) {
                return $variant;
            }
        }

        $normalizedLabel = strtolower(str_replace([' / ', '|'], ' + ', $label));
        foreach ($variants as $variant) {
            if (strtolower((string) $variant->name) === $normalizedLabel) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, array{product: Product, variant: ?ProductVariant, quantity: int, price: float, subtotal: float}>  $preparedItems
     * @return array{coupon: ?Coupon, discount: float, eligible_subtotal: float}
     */
    public function resolveCoupon(?string $code, Collection $preparedItems, ?User $user = null): array
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return [
                'coupon' => null,
                'discount' => 0.0,
                'eligible_subtotal' => (float) $preparedItems->sum('subtotal'),
            ];
        }

        $coupon = Coupon::query()->whereRaw('UPPER(code) = ?', [$code])->first();

        if (! $coupon) {
            throw new \RuntimeException('Invalid coupon code.');
        }

        if (! $coupon->isValid()) {
            throw new \RuntimeException('This coupon is expired or inactive.');
        }

        if ($user && $coupon->usage_limit_per_user) {
            $userUsage = Order::query()
                ->where('user_id', $user->id)
                ->where('coupon_id', $coupon->id)
                ->where('order_status', '!=', 'cancelled')
                ->count();

            if ($userUsage >= $coupon->usage_limit_per_user) {
                throw new \RuntimeException('You have already used this coupon the maximum number of times.');
            }
        }

        $orderSubtotal = (float) $preparedItems->sum('subtotal');

        if ($coupon->type === 'order_based') {
            if ($coupon->minimum_order_amount && $orderSubtotal < (float) $coupon->minimum_order_amount) {
                throw new \RuntimeException(
                    'Minimum order amount for this coupon is ৳'.number_format((float) $coupon->minimum_order_amount, 2).'.'
                );
            }

            $eligibleSubtotal = $orderSubtotal;
        } else {
            $eligibleSubtotal = (float) $preparedItems
                ->filter(fn ($item) => $coupon->appliesToProduct((int) $item['product']->id))
                ->sum('subtotal');

            if ($eligibleSubtotal <= 0) {
                throw new \RuntimeException('This coupon does not apply to the items in your cart.');
            }
        }

        $discount = min($coupon->calculateDiscount($eligibleSubtotal), $eligibleSubtotal);

        return [
            'coupon' => $coupon,
            'discount' => $discount,
            'eligible_subtotal' => $eligibleSubtotal,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{
     *   subtotal: float,
     *   discount: float,
     *   shipping_cost: float,
     *   shipping_method: string|null,
     *   tax: float,
     *   total: float,
     *   delivery_charges: array{inside_dhaka: float, outside_dhaka: float},
     *   district_name: string|null,
     *   coupon: array<string, mixed>|null
     * }
     */
    public function quote(
        array $items,
        ?ShippingAddress $shippingAddress = null,
        ?string $couponCode = null,
        ?User $user = null,
    ): array {
        $preparedItems = $this->prepareOrderItems($items);
        $subtotal = (float) $preparedItems->sum('subtotal');
        $couponResult = $this->resolveCoupon($couponCode, $preparedItems, $user);
        $discount = $couponResult['discount'];
        $tax = 0.0;

        $shippingMethod = null;
        $shippingCost = 0.0;
        $districtName = null;

        if ($shippingAddress) {
            $districtName = $shippingAddress->deliveryArea?->district_name;
            [$shippingMethod, $shippingCost] = $this->resolveShipping($shippingAddress);
        }

        $total = max(0, $subtotal + $tax - $discount + $shippingCost);
        /** @var Coupon|null $coupon */
        $coupon = $couponResult['coupon'];

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shipping_cost' => round($shippingCost, 2),
            'shipping_method' => $shippingMethod,
            'tax' => round($tax, 2),
            'total' => round($total, 2),
            'delivery_charges' => $this->deliveryCharges(),
            'district_name' => $districtName,
            'coupon' => $coupon ? [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'type' => $coupon->type,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float) $coupon->discount_value,
                'discount_amount' => round($discount, 2),
            ] : null,
            'prepared_items' => $preparedItems,
            'coupon_model' => $coupon,
        ];
    }
}
