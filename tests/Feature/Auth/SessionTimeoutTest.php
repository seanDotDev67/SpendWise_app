<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_lifetime_is_fifteen_minutes_by_default(): void
    {
        $this->assertSame(15, (int) config('session.lifetime'));
    }

    public function test_login_without_remember_does_not_set_remember_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $this->assertStringNotContainsString('remember_web', implode(';', array_map(fn ($c) => $c->getName(), $response->headers->getCookies())));
    }

    public function test_login_with_remember_sets_remember_cookie_and_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => '1']);

        $this->assertAuthenticatedAs($user);
        $names = array_map(fn ($c) => $c->getName(), $response->headers->getCookies());
        $this->assertTrue(collect($names)->contains(fn ($n) => str_starts_with($n, 'remember_web')));
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_expired_session_form_post_redirects_to_login_with_message_and_csrf_stays_enabled(): void
    {
        $this->app['env'] = 'production';

        $response = $this->post('/login', ['email' => 'a@b.c', 'password' => 'x']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_for_protected_page(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
