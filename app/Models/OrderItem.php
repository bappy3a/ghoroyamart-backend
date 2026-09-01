<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'variant_name',
        'product_name',
        'product_slug',
        'product_sku',
        'product_image',
        'price',
        'regular_price',
        'purchase_price',
        'quantity',
        'cancelled_quantity',
        'restocked_quantity',
        'item_status',
        'cancelled_by_type',
        'cancelled_by_id',
        'cancellation_reason',
        'cancelled_at',
        'subtotal',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity' => 'integer',
        'cancelled_quantity' => 'integer',
        'restocked_quantity' => 'integer',
        'cancelled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function cancellationActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function cancelledQuantity(): int
    {
        return min(max((int) $this->cancelled_quantity, 0), max((int) $this->quantity, 0));
    }

    public function activeQuantity(): int
    {
        return max((int) $this->quantity - $this->cancelledQuantity(), 0);
    }

    public function restockableCancelledQuantity(): int
    {
        return max($this->cancelledQuantity() - max((int) $this->restocked_quantity, 0), 0);
    }

    public function restockableQuantity(): int
    {
        return max((int) $this->quantity - max((int) $this->restocked_quantity, 0), 0);
    }

    public function subtotalForQuantity(int $quantity): float
    {
        return round((float) $this->price * max($quantity, 0), 2);
    }

    public function activeSubtotal(): float
    {
        return $this->subtotalForQuantity($this->activeQuantity());
    }

    public function cancelledSubtotal(): float
    {
        return $this->subtotalForQuantity($this->cancelledQuantity());
    }

    public function hasCancelledQuantity(): bool
    {
        return $this->cancelledQuantity() > 0;
    }

    public function isFullyCancelled(): bool
    {
        return $this->cancelledQuantity() >= (int) $this->quantity;
    }
}
