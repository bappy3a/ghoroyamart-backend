<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'landing_page_slug',
        'sku',
        'short_description',
        'description',
        'how_to_use',
        'good_to_know',
        'warranty',
        'status',
        'visibility',
        'published_at',
        'thumbnail_image',
        'images',
        'video_media',
        'quantity',
        'stock_status',
        'product_location',
        'minimum_order_quantity',
        'maximum_order_quantity',
        'low_stock_alert',
        'purchase_price',
        'regular_price',
        'price',
        'discount_amount',
        'discount_percentage',
        'discount_start_date',
        'discount_end_date',
        'is_discounted',
        'is_featured',
        'is_new',
        'is_best_seller',
        'brand_id',
        'category_id',
        'unit',
        'tax_rate',
        'created_by_id',
        'approved_by_id',
        'updated_by_id',
        'deleted_by_id',
        'num_of_sale',
        'num_of_views',
        'num_of_reviews',
        'reviews_avg',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_image',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'discount_start_date' => 'date',
        'discount_end_date' => 'date',
        'quantity' => 'integer',
        'product_location' => 'string',
        'num_of_sale' => 'integer',
        'num_of_views' => 'integer',
        'num_of_reviews' => 'integer',
        'is_discounted' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_best_seller' => 'boolean',
        'purchase_price' => 'double',
        'regular_price' => 'double',
        'price' => 'double',
        'tax_rate' => 'double',
        'discount_percentage' => 'double',
        'discount_amount' => 'double',
        'reviews_avg' => 'double',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected function images(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => collect(json_decode($value ?: '[]'))->map(function ($image) {
                return api_asset($image);
            })->toArray()
        );
    }
    protected function thumbnailImage(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? api_asset($value): null
        );
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variantQuantity(): int
    {
        return (int) $this->variants()->sum('quantity');
    }

    public function promotionLandingPages(): BelongsToMany
    {
        return $this->belongsToMany(PromotionLandingPage::class, 'promotion_landing_page_product')
            ->withTimestamps();
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function getThumbnailImageUrlAttribute(): ?string
    {
        return $this->thumbnail_image ? api_asset($this->thumbnail_image) : null;
    }

    public function getGalleryImagesAttribute($value)
    {
        $images = $this->getRawOriginal('images');

        return $images ? json_decode($images, true) : [];
    }

    public function setGalleryImagesAttribute($value)
    {
        $this->attributes['images'] = is_array($value) ? json_encode($value) : $value;
    }
}
