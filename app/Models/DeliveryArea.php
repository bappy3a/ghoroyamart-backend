<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeliveryArea extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'big_parcel' => 'boolean',
            'status' => 'boolean',
            'ps_type' => 'integer',
            'hub_id' => 'integer',
            'district_id' => 'integer',
            'admin_id' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeForDistrict(Builder $query, int $districtId): Builder
    {
        return $query->where('district_id', $districtId);
    }
}
