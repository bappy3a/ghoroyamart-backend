<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingAddressResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('deliveryArea');

        $deliveryArea = $this->deliveryArea;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'delivery_area_id' => $this->delivery_area_id,
            'delivery_area' => $deliveryArea?->name,
            'district' => $deliveryArea?->district_name,
            'postal_code' => $this->postal_code ?? $deliveryArea?->post_code,
            'address' => $this->address,
            'address_type' => $this->address_type,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
