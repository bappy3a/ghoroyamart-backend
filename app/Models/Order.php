<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'created_by_id',
        'order_source',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address_id',
        'subtotal',
        'tax',
        'discount',
        'shipping_cost',
        'total',
        'coupon_id',
        'coupon_code',
        'payment_method',
        'payment_status',
        'order_status',
        'cancelled_by_type',
        'cancelled_by_id',
        'cancellation_reason',
        'cancelled_at',
        'shipping_method',
        'steadfast_consignment_id',
        'steadfast_tracking_code',
        'steadfast_status',
        'steadfast_response',
        'steadfast_order_placed_at',
        'steadfast_cod_charger',
        'steadfast_delivery_charges',
        'stock_deducted_at',
        'stock_restocked_at',
        'order_notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'steadfast_cod_charger' => 'decimal:2',
        'steadfast_delivery_charges' => 'decimal:2',
        'steadfast_response' => 'array',
        'steadfast_order_placed_at' => 'datetime',
        'stock_deducted_at' => 'datetime',
        'stock_restocked_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function cancellationActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(ShippingAddress::class, 'shipping_address_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(OrderItem::class)
            ->whereColumn('cancelled_quantity', '<', 'quantity');
    }

    public function cancelledItems(): HasMany
    {
        return $this->hasMany(OrderItem::class)
            ->where('cancelled_quantity', '>', 0);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(OrderTimeline::class)->latest();
    }

    public function cancellationReasonText(): ?string
    {
        if ($this->cancellation_reason) {
            return $this->cancellation_reason;
        }

        if (! $this->order_notes) {
            return null;
        }

        if (preg_match('/Cancellation Reason:\s*(.*?)(?:\n\nPrevious Notes:|$)/s', $this->order_notes, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function hasItemCancellations(): bool
    {
        return $this->items->contains(fn (OrderItem $item) => $item->hasCancelledQuantity());
    }

    public function hasPartialDeliveredStatus(): bool
    {
        return in_array('partial_delivered', [
            strtolower((string) $this->order_status),
            strtolower((string) $this->steadfast_status),
        ], true);
    }

    public function isStockRestockableStatus(): bool
    {
        return $this->order_status === 'cancelled' || $this->hasPartialDeliveredStatus();
    }

    public function displayItems(?string $scope = null): Collection
    {
        $items = $this->items;

        return match ($scope) {
            'cancelled' => $this->hasItemCancellations()
                ? $items->filter(fn (OrderItem $item) => $item->hasCancelledQuantity())->values()
                : ($this->order_status === 'cancelled' ? $items : collect()),
            'active' => $items->filter(fn (OrderItem $item) => $item->activeQuantity() > 0)->values(),
            default => $items,
        };
    }

    public function displayQuantityForItem(OrderItem $item, ?string $scope = null): int
    {
        if ($scope === 'cancelled') {
            return $this->hasItemCancellations()
                ? $item->cancelledQuantity()
                : (int) $item->quantity;
        }

        if ($scope === 'active') {
            return $item->activeQuantity();
        }

        return (int) $item->quantity;
    }

    public function displaySubtotalForItem(OrderItem $item, ?string $scope = null): float
    {
        return match ($scope) {
            'cancelled' => $this->hasItemCancellations()
                ? $item->cancelledSubtotal()
                : (float) $item->subtotal,
            'active' => $item->activeSubtotal(),
            default => (float) $item->subtotal,
        };
    }

    public function displaySubtotal(?string $scope = null): float
    {
        if (! in_array($scope, ['active', 'cancelled'], true)) {
            return (float) $this->subtotal;
        }

        return $this->displayItems($scope)
            ->sum(fn (OrderItem $item) => $this->displaySubtotalForItem($item, $scope));
    }

    public function displayTax(?string $scope = null): float
    {
        return $scope === 'cancelled' || $this->displaySubtotal($scope) <= 0
            ? 0
            : (float) $this->tax;
    }

    public function displayDiscount(?string $scope = null): float
    {
        if ($scope === 'cancelled') {
            return 0;
        }

        $discount = (float) $this->discount;
        $subtotalWithTax = $this->displaySubtotal($scope) + $this->displayTax($scope);

        return min($discount, $subtotalWithTax);
    }

    public function displayShippingCost(?string $scope = null): float
    {
        return $scope === 'cancelled' || $this->displaySubtotal($scope) <= 0
            ? 0
            : (float) $this->shipping_cost;
    }

    public function displayTotal(?string $scope = null): float
    {
        if (! in_array($scope, ['active', 'cancelled'], true)) {
            return (float) $this->total;
        }

        $subtotal = $this->displaySubtotal($scope);

        if ($scope === 'cancelled') {
            return $subtotal;
        }

        return max(0, $subtotal + $this->displayTax($scope) - $this->displayDiscount($scope) + $this->displayShippingCost($scope));
    }

    public static function steadfastCodChargeFor(?string $paymentMethod, float|int|string|null $total): float
    {
        if ($paymentMethod !== 'cash_on_delivery') {
            return 0.0;
        }

        return round(max(0, (float) $total) * 0.01, 2);
    }

    public function cancelledByLabel(): string
    {
        return match ($this->cancelled_by_type) {
            'staff' => $this->cancellationActor?->name
                ? 'Staff: '.$this->cancellationActor->name
                : 'Staff',
            'customer' => 'Customer',
            'courier' => 'Courier',
            default => $this->fallbackCancelledByLabel(),
        };
    }

    protected function fallbackCancelledByLabel(): string
    {
        $cancelTimeline = $this->timelines
            ->first(fn (OrderTimeline $timeline) => str_contains(strtolower($timeline->status.' '.$timeline->description), 'cancel'));

        if ($cancelTimeline?->updater) {
            return 'Staff: '.$cancelTimeline->updater->name;
        }

        if ($this->cancellationReasonText()) {
            return 'Customer';
        }

        $courierStatus = strtolower((string) $this->steadfast_status);
        if (str_contains($courierStatus, 'cancel') || str_contains($courierStatus, 'return')) {
            return 'Courier';
        }

        return 'Unknown';
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'AGO-'.Str::upper(Str::random(6));
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
