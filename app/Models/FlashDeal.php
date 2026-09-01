<?php

namespace App\Models;

use App\Http\Resources\Product\ProductsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FlashDeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'banner_image',
        'background_color',
        'text_color',
        'start_date',
        'end_date',
        'product_ids',
        'discount_percentage',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['products'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($flashDeal) {
            if (empty($flashDeal->slug)) {
                $flashDeal->slug = Str::slug($flashDeal->title);
            }
        });

        static::updating(function ($flashDeal) {
            if ($flashDeal->isDirty('title') && empty($flashDeal->slug)) {
                $flashDeal->slug = Str::slug($flashDeal->title);
            }
        });
    }

    /**
     * Get products associated with this flash deal (for API / serialization).
     */
    public function getProductsAttribute()
    {
        if (! $this->product_ids || ! is_array($this->product_ids)) {
            return [];
        }

        $products = Product::whereIn('id', $this->product_ids)->where('status', 'published')->get();

        return (new ProductsCollection($products))->resolve();
    }

    /**
     * Check if flash deal is currently active
     */
    public function isActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        return $now->gte($this->start_date) && $now->lte($this->end_date);
    }

    /**
     * Check if flash deal is upcoming
     */
    public function isUpcoming(): bool
    {
        return now()->lt($this->start_date);
    }

    /**
     * Check if flash deal is expired
     */
    public function isExpired(): bool
    {
        return now()->gt($this->end_date);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        if (! $this->is_active) {
            return 'bg-danger-subtle text-danger';
        }

        if ($this->isExpired()) {
            return 'bg-secondary-subtle text-secondary';
        }

        if ($this->isUpcoming()) {
            return 'bg-info-subtle text-info';
        }

        if ($this->isActive()) {
            return 'bg-success-subtle text-success';
        }

        return 'bg-warning-subtle text-warning';
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        if ($this->isUpcoming()) {
            return 'Upcoming';
        }

        if ($this->isActive()) {
            return 'Active';
        }

        return 'Unknown';
    }

    /**
     * Get banner image URL
     */
    public function getBannerImageUrlAttribute(): ?string
    {
        return $this->banner_image ? api_asset($this->banner_image) : null;
    }
}
