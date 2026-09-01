<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\AuthUserCollection;
use App\Models\DeliveryArea;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    private const OTP_TTL_MINUTES = 5;

    private const RESEND_COOLDOWN_SECONDS = 30;

    private const MAX_RESENDS_PER_HOUR = 5;

    /**
     * Send login/register OTP to a mobile number.
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
        ], [
            'phone.regex' => 'Please enter a valid 11 digit Bangladesh mobile number.',
        ]);

        if ($validator->fails()) {
            return $this->error('Valid mobile number is required.', $validator->errors(), null, 422);
        }

        $phone = $this->normalizePhone($request->input('phone'));
        $user = $this->findOrCreateUserByPhone($phone);
        $isNew = $user->wasRecentlyCreated;
        try {
            $this->dispatchOtp($user, $phone, isResend: false);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, null, 429);
        } catch (\Throwable $e) {
            Log::error('Auth OTP send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to send OTP. Please try again.', null, null, 500);
        }

        return $this->success([
            'phone' => $phone,
            'is_new' => $isNew,
            'resend_after' => self::RESEND_COOLDOWN_SECONDS,
            'expires_in' => self::OTP_TTL_MINUTES * 60,
        ], null, 'OTP sent successfully.');
    }

    /**
     * Resend OTP with cooldown + hourly rate limit.
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
        ], [
            'phone.regex' => 'Please enter a valid 11 digit Bangladesh mobile number.',
        ]);

        if ($validator->fails()) {
            return $this->error('Valid mobile number is required.', $validator->errors(), null, 422);
        }

        $phone = $this->normalizePhone($request->input('phone'));
        $user = User::query()->where('phone', $phone)->first();

        if (! $user) {
            return $this->error('No OTP request found for this number. Please send OTP first.', null, null, 404);
        }

        try {
            $this->dispatchOtp($user, $phone, isResend: true);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, null, 429);
        } catch (\Throwable $e) {
            Log::error('Auth OTP resend failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to resend OTP. Please try again.', null, null, 500);
        }

        return $this->success([
            'phone' => $phone,
            'resend_after' => self::RESEND_COOLDOWN_SECONDS,
            'expires_in' => self::OTP_TTL_MINUTES * 60,
        ], null, 'OTP resent successfully.');
    }

    /**
     * Verify OTP and return Sanctum token + user profile.
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'phone.regex' => 'Please enter a valid 11 digit Bangladesh mobile number.',
            'otp.regex' => 'OTP must be a 6 digit code.',
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide valid OTP details.', $validator->errors(), null, 422);
        }

        $phone = $this->normalizePhone($request->input('phone'));
        $otp = (string) $request->input('otp');

        $user = User::query()->where('phone', $phone)->first();

        if (! $user || ! $user->verification_code) {
            return $this->error('No OTP request found. Please send OTP first.', null, null, 404);
        }

        if (! Cache::has($this->otpExpiresKey($phone))) {
            $user->update(['verification_code' => null]);

            return $this->error('OTP expired. Please resend OTP.', null, null, 422);
        }

        if (! hash_equals((string) $user->verification_code, $otp)) {
            return $this->error('Invalid OTP code.', null, null, 422);
        }

        $user->update([
            'verification_code' => null,
            'phone_verified_at' => $user->phone_verified_at ?? now(),
            'status' => $user->status === 'inactive' ? 'active' : $user->status,
        ]);

        Cache::forget($this->otpExpiresKey($phone));
        Cache::forget($this->resendCooldownKey($phone));

        $token = $user->createToken('auth-token')->plainTextToken;
        $user = $user->fresh(['defaultAddress.deliveryArea']);
        $profileComplete = $this->isProfileComplete($user);

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'profile_complete' => $profileComplete,
            'is_new' => blank(trim((string) $user->name)) && blank(trim((string) $user->email)),
            'user' => (new AuthUserCollection($user))->resolve(),
        ], null, 'OTP verified successfully.');
    }

    /**
     * Current authenticated user.
     */
    public function me(Request $request)
    {
        $user = $request->user()->loadMissing('defaultAddress.deliveryArea');

        return $this->success([
            'profile_complete' => $this->isProfileComplete($user),
            'user' => (new AuthUserCollection($user))->resolve(),
        ], null, 'User fetched successfully.');
    }

    /**
     * Update profile details after OTP login.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'delivery_area_id' => ['required', 'integer', 'exists:delivery_areas,id'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide valid profile details.', $validator->errors(), null, 422);
        }

        $deliveryArea = DeliveryArea::query()
            ->active()
            ->find($request->input('delivery_area_id'));

        if (! $deliveryArea) {
            return $this->error('Please select a valid delivery area.', [
                'delivery_area_id' => ['The selected delivery area is invalid.'],
            ], null, 422);
        }

        $email = strtolower(trim((string) $request->input('email', '')));

        $user->update([
            'name' => trim((string) $request->input('name')),
            'email' => $email !== '' ? $email : null,
            'gender' => $request->input('gender') ?: null,
            'date_of_birth' => $request->input('date_of_birth') ?: null,
        ]);

        $addressPayload = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'delivery_area_id' => $deliveryArea->id,
            'postal_code' => $deliveryArea->post_code,
            'address' => trim((string) $request->input('address')),
            'address_type' => 'home',
            'is_default' => true,
        ];

        $defaultAddress = $user->defaultAddress;
        if ($defaultAddress) {
            $defaultAddress->update($addressPayload);
        } else {
            $user->addresses()->where('is_default', true)->update(['is_default' => false]);
            $user->addresses()->create($addressPayload);
        }

        $user = $user->fresh(['defaultAddress.deliveryArea']);

        return $this->success([
            'profile_complete' => $this->isProfileComplete($user),
            'user' => (new AuthUserCollection($user))->resolve(),
        ], null, 'Profile updated successfully.');
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return $this->success(null, null, 'Signed out successfully.');
    }

    private function dispatchOtp(User $user, string $phone, bool $isResend): void
    {
        $cooldownKey = $this->resendCooldownKey($phone);
        $hourlyKey = $this->resendHourlyKey($phone);
        if ($isResend && Cache::has($cooldownKey)) {
            $retryAfter = max(1, (int) Cache::get($cooldownKey) - time());

            throw new \RuntimeException("Please wait {$retryAfter} seconds before requesting another OTP.");
        }

        Cache::add($hourlyKey, 0, now()->addHour());
        $hourlyCount = (int) Cache::increment($hourlyKey);

        if ($hourlyCount > self::MAX_RESENDS_PER_HOUR) {
            throw new \RuntimeException('OTP rate limit exceeded for this mobile number. Please try again after an hour.');
        }

        send_verification_code($user);

        Cache::put($this->otpExpiresKey($phone), true, now()->addMinutes(self::OTP_TTL_MINUTES));
        Cache::put($cooldownKey, time() + self::RESEND_COOLDOWN_SECONDS, self::RESEND_COOLDOWN_SECONDS);
    }

    private function findOrCreateUserByPhone(string $phone): User
    {
        $existing = User::query()->where('phone', $phone)->first();

        if ($existing) {
            return $existing;
        }

        return User::query()->create([
            'name' => '',
            'phone' => $phone,
            'username' => username_generator('user-' . $phone),
            'user_type' => 'user',
            'status' => 'active',
            'password' => bcrypt(Str::random(32)),
        ]);
    }

    private function isProfileComplete(User $user): bool
    {
        $user->loadMissing('defaultAddress.deliveryArea');

        $hasName = filled(trim((string) $user->name));
        $hasDeliveryArea = filled($user->defaultAddress?->delivery_area_id);
        $hasAddress = filled(trim((string) ($user->defaultAddress?->address ?? '')));

        return $hasName && $hasDeliveryArea && $hasAddress;
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

    private function otpExpiresKey(string $phone): string
    {
        return "auth_otp_expires:{$phone}";
    }

    private function resendCooldownKey(string $phone): string
    {
        return "auth_otp_cooldown:{$phone}";
    }

    private function resendHourlyKey(string $phone): string
    {
        return "auth_otp_hourly:{$phone}";
    }
}
