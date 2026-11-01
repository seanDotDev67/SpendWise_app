<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionCookieTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_sets_session_cookie_and_csrf_token(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertCookie(config('session.cookie'));
        $this->assertStringContainsString('name="_token"', $response->getContent());
    }

    public function test_csrf_protection_is_enforced_when_token_missing(): void
    {
        $this->app['env'] = 'production';

        $this->post('/login', ['email' => 'a@b.test', 'password' => 'x'])
            ->assertStatus(419);
    }

    public function test_entry_files_have_no_output_before_php_tag(): void
    {
        foreach (['bootstrap/app.php', 'routes/web.php', 'routes/auth.php', 'config/session.php'] as $file) {
            $this->assertStringStartsWith('<?php', file_get_contents(base_path($file)), "$file emits output before <?php");
        }
    }
}
