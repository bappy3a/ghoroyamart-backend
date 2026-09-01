<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('defaultAddress.deliveryArea');

        $deliveryArea = $this->defaultAddress?->deliveryArea;
        $district = $deliveryArea?->district_name ?? '';
        $address = $this->defaultAddress?->address ?? '';
        $profileComplete = filled(trim((string) $this->name))
            && filled($this->defaultAddress?->delivery_area_id)
            && filled(trim((string) $address));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_verified_at' => $this->phone_verified_at,
            'email_verified_at' => $this->email_verified_at,
            'avatar' => $this->avatar ? api_asset($this->avatar) : null,
            'cover_photo' => $this->cover_photo ? api_asset($this->cover_photo) : null,
            'bio' => $this->bio,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'delivery_area_id' => $this->defaultAddress?->delivery_area_id,
            'delivery_area' => $deliveryArea?->name,
            'district' => $district,
            'address' => $address,
            'status' => $this->status,
            'profile_complete' => $profileComplete,
        ];
    }
}
