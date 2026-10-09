<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutOrderService
{
    public function __construct(
        private readonly OrderStockService $orderStockService,
        private readonly CheckoutPricingService $pricing,
    ) {}

    /**
     * Create an order (auth or guest) from cart items. Throws RuntimeException for client-facing errors.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function place(
        array $items,
        ShippingAddress $shippingAddress,
        ?User $user,
        array $customer,
        ?string $couponCode = null,
        string $paymentMethod = 'cash_on_delivery',
        ?string $orderNotes = null,
        ?callable $afterCreate = null,
    ): Order {
        return DB::transaction(function () use ($items, $shippingAddress, $user, $customer, $couponCode, $paymentMethod, $orderNotes, $afterCreate) {
            $quote = $this->pricing->quote($items, $shippingAddress, $couponCode, $user);
            $coupon = $quote['coupon_model'];

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user?->id,
                'order_source' => 'website',
                'customer_name' => $customer['name'] ?: 'Customer',
                'customer_email' => $customer['email'] ?: '',
                'customer_phone' => $customer['phone'],
                'shipping_address_id' => $shippingAddress->id,
                'subtotal' => $quote['subtotal'],
                'tax' => $quote['tax'],
                'discount' => $quote['discount'],
                'shipping_cost' => $quote['shipping_cost'],
                'total' => $quote['total'],
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'steadfast_cod_charger' => Order::steadfastCodChargeFor($paymentMethod, $quote['total']),
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'shipping_method' => $quote['shipping_method'],
                'order_notes' => $orderNotes !== null && trim($orderNotes) !== '' ? trim($orderNotes) : null,
            ]);

            $quantityByProduct = [];

            foreach ($quote['prepared_items'] as $item) {
                $product = $item['product'];
                $variant = $item['variant'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'variant_name' => $variant?->name,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'product_sku' => $variant?->sku ?: $product->sku,
                    'product_image' => $variant?->image ?: $product->getRawOriginal('thumbnail_image'),
                    'price' => $item['price'],
                    'regular_price' => $variant && $variant->regular_price !== null
                        ? $variant->regular_price
                        : $product->regular_price,
                    'purchase_price' => $variant?->purchase_price ?? $product->purchase_price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);

                $this->orderStockService->deduct($product->id, $variant?->id, $item['quantity']);

                $quantityByProduct[$product->id] = ($quantityByProduct[$product->id] ?? 0) + $item['quantity'];
            }

            $order->update(['stock_deducted_at' => now()]);

            foreach ($quantityByProduct as $productId => $quantity) {
                Product::whereKey($productId)->increment('num_of_sale', $quantity);
            }

            if ($coupon) {
                $coupon->increment('used_count');
            }

            OrderTimeline::create([
                'order_id' => $order->id,
                'updated_by' => $user?->id,
                'description' => $user ? 'Order placed from website checkout.' : 'Order placed from website guest checkout.',
                'status' => 'Order Pending',
                'date' => now(),
            ]);

            if ($afterCreate) {
                $afterCreate($order);
            }

            return $order->load(['items', 'shippingAddress.deliveryArea', 'coupon']);
        });
    }
}
