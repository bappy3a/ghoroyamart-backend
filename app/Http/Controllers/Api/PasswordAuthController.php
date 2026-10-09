<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\AuthUserCollection;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Mobile number + password authentication (register, login, forgot/reset password).
 */
class PasswordAuthController extends Controller
{
    use ApiResponse;

    private const PHONE_REGEX = '/^01[3-9][0-9]{8}$/';

    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOCK_MINUTES = 15;

    private const RESET_OTP_TTL_MINUTES = 5;

    private const RESET_COOLDOWN_SECONDS = 30;

    private const RESET_MAX_OTP_ATTEMPTS = 5;

    public function register(Request $request)
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:'.self::PHONE_REGEX, 'unique:users,phone'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ], [
            'phone.regex' => 'Please enter a valid 11 digit Bangladesh mobile number.',
            'phone.unique' => 'This mobile number is already registered. Please login.',
        ]);

        if ($validator->fails()) {
            return $this->error('Registration failed. Please check your details.', $validator->errors(), null, 422);
        }

        $phone = $request->input('phone');

        $user = User::query()->create([
            'name' => trim($request->input('name')),
            'phone' => $phone,
            'username' => username_generator('user-'.$phone),
            'user_type' => 'user',
            'status' => 'active',
            'password' => $request->input('password'),
        ]);

        return $this->tokenResponse($user, 'Registration successful.', 201);
    }

    public function login(Request $request)
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:'.self::PHONE_REGEX],
            'password' => ['required', 'string'],
        ], [
            'phone.regex' => 'Please enter a valid 11 digit Bangladesh mobile number.',
        ]);

        if ($validator->fails()) {
            return $this->error('Mobile number and password are required.', $validator->errors(), null, 422);
        }

        $user = User::query()->where('phone', $request->input('phone'))->first();

        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $minutes = max(1, (int) ceil(now()->diffInSeconds($user->locked_until, true) / 60));

            return $this->error("Too many failed attempts. Try again in {$minutes} minute(s).", null, null, 429);
        }

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            if ($user) {
                $attempts = ((int) $user->failed_login_attempts) + 1;
                $user->forceFill([
                    'failed_login_attempts' => $attempts >= self::MAX_LOGIN_ATTEMPTS ? 0 : $attempts,
                    'locked_until' => $attempts >= self::MAX_LOGIN_ATTEMPTS ? now()->addMinutes(self::LOCK_MINUTES) : null,
                ])->save();
            }

            return $this->error('Invalid mobile number or password.', null, null, 401);
        }

        if ($user->status !== 'active') {
            return $this->error('Your account is not active. Please contact support.', null, null, 403);
        }

        $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();

        return $this->tokenResponse($user, 'Login successful.');
    }

    /**
     * Send a password reset OTP. Always returns the same response to avoid revealing registered numbers.
     */
    public function forgotPassword(Request $request)
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:'.self::PHONE_REGEX],
        ], [
            'phone.regex' => 'Please enter a valid 11 digit Bangladesh mobile number.',
        ]);

        if ($validator->fails()) {
            return $this->error('Valid mobile number is required.', $validator->errors(), null, 422);
        }

        $phone = $request->input('phone');
        $user = User::query()->where('phone', $phone)->first();

        if ($user && $user->status === 'active') {
            $cooldownKey = "pwd_reset_cooldown:{$phone}";

            if (Cache::has($cooldownKey)) {
                $retryAfter = max(1, (int) Cache::get($cooldownKey) - time());

                return $this->error("Please wait {$retryAfter} seconds before requesting another OTP.", null, null, 429);
            }

            try {
                send_verification_code($user);
                Cache::put("pwd_reset_expires:{$phone}", true, now()->addMinutes(self::RESET_OTP_TTL_MINUTES));
                Cache::forget("pwd_reset_attempts:{$phone}");
                Cache::put($cooldownKey, time() + self::RESET_COOLDOWN_SECONDS, self::RESET_COOLDOWN_SECONDS);
            } catch (\Throwable $e) {
                Log::error('Password reset OTP failed', ['phone' => $phone, 'error' => $e->getMessage()]);

                return $this->error('Failed to send OTP. Please try again.', null, null, 500);
            }
        }

        return $this->success([
            'phone' => $phone,
            'resend_after' => self::RESET_COOLDOWN_SECONDS,
            'expires_in' => self::RESET_OTP_TTL_MINUTES * 60,
        ], null, 'If this number is registered, an OTP has been sent.');
    }

    public function resetPassword(Request $request)
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'regex:'.self::PHONE_REGEX],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ], [
            'phone.regex' => 'Please enter a valid 11 digit Bangladesh mobile number.',
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide valid details.', $validator->errors(), null, 422);
        }

        $phone = $request->input('phone');
        $user = User::query()->where('phone', $phone)->first();
        $expiresKey = "pwd_reset_expires:{$phone}";
        $attemptsKey = "pwd_reset_attempts:{$phone}";

        if (! $user || ! Cache::has($expiresKey) || blank($user->verification_code)) {
            return $this->error('OTP expired or invalid. Please request a new one.', null, null, 422);
        }

        Cache::add($attemptsKey, 0, now()->addMinutes(self::RESET_OTP_TTL_MINUTES));
        if ((int) Cache::increment($attemptsKey) > self::RESET_MAX_OTP_ATTEMPTS) {
            Cache::forget($expiresKey);
            $user->update(['verification_code' => null]);

            return $this->error('Too many invalid attempts. Please request a new OTP.', null, null, 429);
        }

        if (! hash_equals((string) $user->verification_code, (string) $request->input('otp'))) {
            return $this->error('Invalid OTP code.', null, null, 422);
        }

        $user->forceFill([
            'password' => $request->input('password'),
            'verification_code' => null,
            'phone_verified_at' => $user->phone_verified_at ?? now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $user->tokens()->delete();
        Cache::forget($expiresKey);
        Cache::forget($attemptsKey);
        Cache::forget("pwd_reset_cooldown:{$phone}");

        return $this->success(null, null, 'Password reset successful. Please login with your new password.');
    }

    private function tokenResponse(User $user, string $message, int $code = 200)
    {
        $user = $user->fresh(['defaultAddress.deliveryArea']);
        $resource = (new AuthUserCollection($user))->resolve();

        return $this->success([
            'token' => $user->createToken('auth-token')->plainTextToken,
            'token_type' => 'Bearer',
            'profile_complete' => $resource['profile_complete'],
            'user' => $resource,
        ], null, $message, $code);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }
}
