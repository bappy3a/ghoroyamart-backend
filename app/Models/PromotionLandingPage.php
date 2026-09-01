<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionLandingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'headline',
        'subheadline',
        'content',
        'cta_text',
        'cta_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_landing_page_product')
            ->withTimestamps();
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(PromotionGalleryImage::class)
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc');
    }

    public function adminGalleryImages(): HasMany
    {
        return $this->hasMany(PromotionGalleryImage::class)
            ->orderBy('sort_order', 'asc');
    }
}
