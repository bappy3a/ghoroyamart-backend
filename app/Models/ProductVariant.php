<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image',
        'sku',
        'combination_hash',
        'quantity',
        'selling_price',
        'purchase_price',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'selling_price' => 'double',
        'purchase_price' => 'double',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class)->with(['attribute', 'value']);
    }

    public function getNameAttribute(): string
    {
        return $this->values
            ->sortBy('variant_attribute_id')
            ->map(fn (ProductVariantValue $variantValue) => $variantValue->value?->value)
            ->filter()
            ->implode(' + ');
    }
}
