<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reset_code_can_be_emailed_and_used_to_change_the_password(): void
    {
        $user = User::factory()->create();
        $code = null;

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $message, $callback) use (&$code): bool {
                if (! preg_match('/verification code is (\d{6})/', $message, $matches)) {
                    return false;
                }

                $code = $matches[1];

                return true;
            });

        $this->post(route('password.code.send'), ['email' => $user->email])
            ->assertRedirect(route('password.code.verify.show'))
            ->assertSessionHas('reset_email', $user->email)
            ->assertSessionHas('status');

        $this->assertNotNull($code);

        $this->post(route('password.code.verify'), ['code' => $code])
            ->assertRedirect(route('password.code.reset.show'));

        $this->post(route('password.code.reset'), [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
