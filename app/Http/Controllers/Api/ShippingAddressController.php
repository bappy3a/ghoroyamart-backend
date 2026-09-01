<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\ShippingAddressResource;
use App\Models\DeliveryArea;
use App\Models\ShippingAddress;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShippingAddressController extends Controller
{
    use ApiResponse;

    /**
     * List authenticated user's shipping addresses.
     */
    public function index(Request $request)
    {
        $addresses = $request->user()
            ->addresses()
            ->with('deliveryArea')
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return $this->success(
            ShippingAddressResource::collection($addresses)->resolve(),
            null,
            'Shipping addresses fetched successfully.'
        );
    }

    /**
     * Create a shipping address for the authenticated user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->error('Please provide valid shipping address details.', $validator->errors(), null, 422);
        }

        $deliveryArea = $this->resolveActiveDeliveryArea((int) $request->input('delivery_area_id'));
        if (! $deliveryArea) {
            return $this->invalidDeliveryAreaResponse();
        }

        $user = $request->user();
        $makeDefault = $request->boolean('is_default') || ! $user->addresses()->exists();

        $address = DB::transaction(function () use ($request, $user, $deliveryArea, $makeDefault) {
            if ($makeDefault) {
                $user->addresses()->where('is_default', true)->update(['is_default' => false]);
            }

            return $user->addresses()->create($this->payload($request, $deliveryArea, $makeDefault, $user));
        });

        $address->load('deliveryArea');

        return $this->success(
            (new ShippingAddressResource($address))->resolve(),
            null,
            'Shipping address created successfully.',
            201
        );
    }

    /**
     * Show a single shipping address owned by the authenticated user.
     */
    public function show(Request $request, int $id)
    {
        $address = $this->findOwnedAddress($request, $id);

        if (! $address) {
            return $this->error('Shipping address not found.', null, null, 404);
        }

        return $this->success(
            (new ShippingAddressResource($address))->resolve(),
            null,
            'Shipping address fetched successfully.'
        );
    }

    /**
     * Update a shipping address owned by the authenticated user.
     */
    public function update(Request $request, int $id)
    {
        $address = $this->findOwnedAddress($request, $id);

        if (! $address) {
            return $this->error('Shipping address not found.', null, null, 404);
        }

        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->error('Please provide valid shipping address details.', $validator->errors(), null, 422);
        }

        $deliveryArea = $this->resolveActiveDeliveryArea((int) $request->input('delivery_area_id'));
        if (! $deliveryArea) {
            return $this->invalidDeliveryAreaResponse();
        }

        $user = $request->user();
        $makeDefault = $request->boolean('is_default') || $address->is_default;

        DB::transaction(function () use ($request, $user, $address, $deliveryArea, $makeDefault) {
            if ($makeDefault) {
                $user->addresses()
                    ->where('id', '!=', $address->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $address->update($this->payload($request, $deliveryArea, $makeDefault, $user));
        });

        $address->refresh()->load('deliveryArea');

        return $this->success(
            (new ShippingAddressResource($address))->resolve(),
            null,
            'Shipping address updated successfully.'
        );
    }

    /**
     * Delete a shipping address owned by the authenticated user.
     */
    public function destroy(Request $request, int $id)
    {
        $address = $this->findOwnedAddress($request, $id);

        if (! $address) {
            return $this->error('Shipping address not found.', null, null, 404);
        }

        DB::transaction(function () use ($request, $address) {
            $wasDefault = (bool) $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $next = $request->user()
                    ->addresses()
                    ->orderByDesc('id')
                    ->first();

                if ($next) {
                    $next->update(['is_default' => true]);
                }
            }
        });

        return $this->success(null, null, 'Shipping address deleted successfully.');
    }

    /**
     * Mark a shipping address as the user's default.
     */
    public function setDefault(Request $request, int $id)
    {
        $address = $this->findOwnedAddress($request, $id);

        if (! $address) {
            return $this->error('Shipping address not found.', null, null, 404);
        }

        DB::transaction(function () use ($request, $address) {
            $request->user()
                ->addresses()
                ->where('id', '!=', $address->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $address->update(['is_default' => true]);
        });

        $address->refresh()->load('deliveryArea');

        return $this->success(
            (new ShippingAddressResource($address))->resolve(),
            null,
            'Default shipping address updated successfully.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
            'delivery_area_id' => ['required', 'integer', 'exists:delivery_areas,id'],
            'address' => ['required', 'string', 'max:500'],
            'address_type' => ['nullable', 'in:home,office,hometown'],
            'is_default' => ['nullable', 'boolean'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, DeliveryArea $deliveryArea, bool $isDefault, $user): array
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $postalCode = trim((string) $request->input('postal_code', ''));

        return [
            'name' => trim((string) $request->input('name')),
            'email' => $email !== '' ? $email : ($user->email ?: null),
            'phone' => $this->normalizePhone((string) $request->input('phone')),
            'delivery_area_id' => $deliveryArea->id,
            'postal_code' => $postalCode !== '' ? $postalCode : $deliveryArea->post_code,
            'address' => trim((string) $request->input('address')),
            'address_type' => $request->input('address_type', 'home'),
            'is_default' => $isDefault,
        ];
    }

    private function findOwnedAddress(Request $request, int $id): ?ShippingAddress
    {
        return $request->user()
            ->addresses()
            ->with('deliveryArea')
            ->whereKey($id)
            ->first();
    }

    private function resolveActiveDeliveryArea(int $id): ?DeliveryArea
    {
        return DeliveryArea::query()->active()->find($id);
    }

    private function invalidDeliveryAreaResponse()
    {
        return $this->error('Please select a valid delivery area.', [
            'delivery_area_id' => ['The selected delivery area is invalid.'],
        ], null, 422);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '88') && strlen($digits) === 12) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }
}
