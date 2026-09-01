<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_callback_creates_a_user_and_returns_a_sanctum_token(): void
    {
        $this->mockSocialiteUser('google', 'google-123', 'New Customer', 'customer@example.com');

        $response = $this->getJson(route('social.callback', ['provider' => 'google']));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.is_new', true)
            ->assertJsonPath('data.user.email', 'customer@example.com');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('users', [
            'email' => 'customer@example.com',
            'provider' => 'google',
            'provider_id' => 'google-123',
            'user_type' => 'user',
            'status' => 'active',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_google_browser_callback_redirects_the_session_to_the_frontend(): void
    {
        config(['app.frontend_url' => 'http://localhost:3000']);
        $this->mockSocialiteUser('google', 'google-browser', 'Browser Customer', 'browser@example.com');

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('http://localhost:3000/auth?', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('google', $query['provider']);
        $this->assertSame('1', $query['is_new']);
        $this->assertSame('0', $query['profile_complete']);
        $this->assertNotEmpty($query['access_token']);

        $this->getJson(route('api.auth.me'), [
            'Authorization' => 'Bearer '.$query['access_token'],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'browser@example.com');
    }

    public function test_invalid_google_browser_callback_redirects_the_error_to_the_frontend(): void
    {
        config(['app.frontend_url' => 'http://localhost:3000']);

        $driver = Mockery::mock();
        $driver->shouldReceive('user')
            ->once()
            ->andThrow(new InvalidStateException);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($driver);

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('http://localhost:3000/auth?', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame(
            'The social login request expired or is invalid. Please try again.',
            $query['social_error'],
        );
        $this->assertArrayNotHasKey('access_token', $query);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_facebook_callback_links_an_existing_user_by_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'provider' => null,
            'provider_id' => null,
        ]);

        $this->mockSocialiteUser('facebook', 'facebook-456', 'Existing Customer', 'existing@example.com');

        $this->getJson(route('social.callback', ['provider' => 'facebook']))
            ->assertOk()
            ->assertJsonPath('data.is_new', false)
            ->assertJsonPath('data.user.id', $existing->id);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'id' => $existing->id,
            'provider' => 'facebook',
            'provider_id' => 'facebook-456',
        ]);
    }

    public function test_social_callback_does_not_issue_a_token_for_a_blocked_user(): void
    {
        User::factory()->create([
            'email' => 'blocked@example.com',
            'provider' => 'google',
            'provider_id' => 'blocked-123',
            'status' => 'blocked',
        ]);

        $this->mockSocialiteUser('google', 'blocked-123', 'Blocked Customer', 'blocked@example.com');

        $this->getJson(route('social.callback', ['provider' => 'google']))
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function mockSocialiteUser(
        string $provider,
        string $providerId,
        string $name,
        string $email,
    ): void {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn($providerId);
        $socialUser->shouldReceive('getName')->andReturn($name);
        $socialUser->shouldReceive('getEmail')->andReturn($email);
        $socialUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with($provider)
            ->andReturn($driver);
    }
}
