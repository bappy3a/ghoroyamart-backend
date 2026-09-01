<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Upazila;

class Union extends Model
{
    protected $guarded = [];
    
    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Upazila::class);
    }
}
