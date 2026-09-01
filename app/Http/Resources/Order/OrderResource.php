<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\User\ShippingAddressResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['items', 'shippingAddress.deliveryArea', 'timelines']);

        $timelines = $this->timelines
            ->sortBy(fn ($timeline) => $timeline->getRawOriginal('date') ?: $timeline->created_at)
            ->values()
            ->map(function ($timeline) {
                $rawDate = $timeline->getRawOriginal('date');

                return [
                    'id' => $timeline->id,
                    'status' => $timeline->status,
                    'description' => $timeline->description,
                    'date' => $rawDate
                        ? Carbon::parse($rawDate)->toIso8601String()
                        : optional($timeline->created_at)?->toIso8601String(),
                ];
            })
            ->all();

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'order_status' => $this->order_status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'shipping_method' => $this->shipping_method,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'discount' => (float) $this->discount,
            'shipping_cost' => (float) $this->shipping_cost,
            'total' => (float) $this->total,
            'coupon_code' => $this->coupon_code,
            'coupon_id' => $this->coupon_id,
            'order_notes' => $this->order_notes,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'product_slug' => $item->product_slug,
                'variant_name' => $item->variant_name,
                'product_image' => $item->product_image ? api_asset($item->product_image) : null,
                'price' => (float) $item->price,
                'regular_price' => (float) $item->regular_price,
                'quantity' => (int) $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])->values()->all(),
            'shipping_address' => $this->shippingAddress
                ? (new ShippingAddressResource($this->shippingAddress))->resolve()
                : null,
            'timelines' => $timelines,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
