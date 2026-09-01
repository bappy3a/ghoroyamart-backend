<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\AuthUserCollection;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    use ApiResponse;

    private const PROVIDERS = ['google', 'facebook'];

    public function redirect(string $provider): RedirectResponse|SymfonyRedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        $driver = Socialite::driver($provider);

        if ($provider === 'facebook') {
            $driver->scopes(['email']);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider): JsonResponse|RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            if (! $request->expectsJson()) {
                return $this->frontendRedirect([
                    'social_error' => 'The social login request expired or is invalid. Please try again.',
                ]);
            }

            return $this->error('The social login request expired or is invalid. Please try again.', null, null, 422);
        } catch (Throwable $exception) {
            Log::warning('Social login failed.', [
                'provider' => $provider,
                'error' => $exception->getMessage(),
            ]);

            $message = 'Unable to sign in with '.ucfirst($provider).'. Please try again.';

            if (! $request->expectsJson()) {
                return $this->frontendRedirect(['social_error' => $message]);
            }

            return $this->error($message, null, null, 422);
        }

        $providerId = trim((string) $socialUser->getId());
        $email = strtolower(trim((string) $socialUser->getEmail()));

        if ($providerId === '') {
            if (! $request->expectsJson()) {
                return $this->frontendRedirect([
                    'social_error' => 'The social provider did not return an account identifier.',
                ]);
            }

            return $this->error('The social provider did not return an account identifier.', null, null, 422);
        }

        $isNew = false;

        $user = DB::transaction(function () use ($provider, $providerId, $email, $socialUser, &$isNew): User {
            $user = User::query()
                ->where('provider', $provider)
                ->where('provider_id', $providerId)
                ->first();

            if (! $user && $email !== '') {
                $user = User::query()->where('email', $email)->first();
            }

            if (! $user) {
                $isNew = true;
                $name = trim((string) $socialUser->getName());

                $user = User::query()->create([
                    'name' => $name !== '' ? $name : ucfirst($provider).' user',
                    'username' => username_generator($email !== '' ? Str::before($email, '@') : $provider.'-'.$providerId),
                    'email' => $email !== '' ? $email : null,
                    'email_verified_at' => $email !== '' ? now() : null,
                    'password' => Str::random(40),
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'avatar' => $socialUser->getAvatar(),
                    'user_type' => 'user',
                    'status' => 'active',
                ]);
            } else {
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'email_verified_at' => $email !== '' ? ($user->email_verified_at ?? now()) : $user->email_verified_at,
                    'avatar' => $user->avatar ?: $socialUser->getAvatar(),
                ]);
            }

            return $user;
        });

        if ($user->status !== 'active') {
            if (! $request->expectsJson()) {
                return $this->frontendRedirect([
                    'social_error' => 'This account is not active. Please contact support.',
                ]);
            }

            return $this->error('This account is not active. Please contact support.', null, null, 403);
        }
        $token = $user->createToken("{$provider}-auth-token")->plainTextToken;
        $user = $user->fresh(['defaultAddress.deliveryArea']);
        $profileComplete = $this->isProfileComplete($user);

        if (! $request->expectsJson()) {
            return $this->frontendRedirect([
                'access_token' => $token,
                'provider' => $provider,
                'profile_complete' => $profileComplete ? '1' : '0',
                'is_new' => $isNew ? '1' : '0',
            ]);
        }

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'profile_complete' => $profileComplete,
            'is_new' => $isNew,
            'user' => (new AuthUserCollection($user))->resolve(),
        ], null, 'Signed in with '.ucfirst($provider).' successfully.');
    }

    private function frontendRedirect(array $parameters): RedirectResponse
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/').'/auth';
        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        return redirect()->away($frontendUrl.'?'.$query);
    }

    private function isProfileComplete(User $user): bool
    {
        $user->loadMissing('defaultAddress.deliveryArea');

        return filled(trim((string) $user->name))
            && filled($user->defaultAddress?->delivery_area_id)
            && filled(trim((string) ($user->defaultAddress?->address ?? '')));
    }
}
