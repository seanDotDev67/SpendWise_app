<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_uri_defaults_to_the_application_url(): void
    {
        $expectedRedirect = env('GOOGLE_REDIRECT_URI')
            ?: rtrim((string) config('app.url'), '/').'/auth/google/callback';

        $this->assertSame($expectedRedirect, config('services.google.redirect'));
    }

    public function test_google_login_redirects_to_the_provider(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com');
    }

    public function test_verified_google_user_can_register_and_sign_in(): void
    {
        $this->mockGoogleUser(GoogleUser::fake([
            'id' => 'google-user-123',
            'name' => 'Google User',
            'email' => 'google@example.com',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'google@example.com',
            'google_id' => 'google-user-123',
        ]);
        $this->assertNotNull(User::where('email', 'google@example.com')->value('email_verified_at'));
    }

    public function test_verified_google_email_links_an_existing_password_account(): void
    {
        $user = User::factory()->create(['email' => 'google@example.com']);
        $this->mockGoogleUser(GoogleUser::fake([
            'id' => 'google-user-123',
            'email' => 'google@example.com',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-user-123',
        ]);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->mockGoogleUser(GoogleUser::fake([
            'email' => 'google@example.com',
            'email_verified' => false,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_invalid_google_state_returns_a_recoverable_login_error(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andThrow(new InvalidStateException);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    private function mockGoogleUser(GoogleUser $googleUser): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
