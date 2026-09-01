<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'discount_type',
        'discount_value',
        'minimum_order_amount',
        'maximum_discount_amount',
        'product_ids',
        'valid_from',
        'valid_to',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'product_ids' => 'array',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get products associated with this coupon (for product_wise type)
     */
    public function products()
    {
        if (!$this->product_ids || !is_array($this->product_ids)) {
            return collect();
        }
        
        return Product::whereIn('id', $this->product_ids)->get();
    }

    /**
     * Check if coupon applies to a specific product
     */
    public function appliesToProduct(int $productId): bool
    {
        if ($this->type !== 'product_wise') {
            return false;
        }

        if (!$this->product_ids || !is_array($this->product_ids)) {
            return false;
        }

        return in_array($productId, $this->product_ids);
    }

    /**
     * Check if coupon is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($now->lt($this->valid_from) || $now->gt($this->valid_to)) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if coupon can be used for a given order amount
     */
    public function canBeUsedForOrder(float $orderAmount): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->type === 'order_based' && $this->minimum_order_amount) {
            return $orderAmount >= $this->minimum_order_amount;
        }

        return true;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = ($amount * $this->discount_value) / 100;
            
            // Apply maximum discount limit if set
            if ($this->maximum_discount_amount && $discount > $this->maximum_discount_amount) {
                $discount = $this->maximum_discount_amount;
            }
            
            return round($discount, 2);
        }

        // Fixed discount
        return min($this->discount_value, $amount);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        if (!$this->is_active) {
            return 'bg-danger-subtle text-danger';
        }

        if (!$this->isValid()) {
            return 'bg-warning-subtle text-warning';
        }

        return 'bg-success-subtle text-success';
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if (!$this->isValid()) {
            return 'Expired';
        }

        return 'Active';
    }
}
