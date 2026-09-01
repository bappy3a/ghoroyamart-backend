<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Slider extends Model
{
    use HasFactory;

    /**
     * Storefront Hero mapping:
     * subtitle → eyebrow, title → title, description → copy,
     * button_text → cta, button_link → cta_link, image → image
     */
    protected $fillable = [
        'subtitle',
        'title',
        'description',
        'price_text',
        'price_value',
        'text',
        'button_text',
        'button_link',
        'image',
        'thumbnail_image',
        'alt_text',
        'status',
        'is_active',
        'published_at',
        'sort_order',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
