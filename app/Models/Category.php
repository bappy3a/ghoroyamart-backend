<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'icon',
        'image',
        'icon_class',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_image',
        'is_active',
        'is_featured',
        'is_popular',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? api_asset($this->icon) : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? api_asset($this->image) : null;
    }

    public function getMetaImageUrlAttribute(): ?string
    {
        return $this->meta_image ? api_asset($this->meta_image) : null;
    }
}
