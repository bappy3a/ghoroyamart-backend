<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoPromotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'video_url',
        'product_id',
        'is_active',
        'order_number',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_number' => 'integer',
    ];

    /**
     * Get the product that owns the video promotion.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
