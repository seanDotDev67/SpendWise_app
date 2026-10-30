<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk()
            ->assertSee('sw-sidebar')
            ->assertSee('position: fixed', false)
            ->assertSee('sw-topbar')
            ->assertSee('profile-intro')
            ->assertSee('Your personal space')
            ->assertSee('Save changes')
            ->assertSee('Update password')
            ->assertSee('Profile Information')
            ->assertSee('Profile photo')
            ->assertSee('Appearance & accessibility')
            ->assertSee('Reduce motion')
            ->assertSee('Update Password')
            ->assertSee('Delete Account');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_upload_replace_and_remove_their_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.photo.update'), [
                'photo' => $this->fakePng('portrait.png'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-photo-updated');

        $firstPath = $user->refresh()->profile_photo_path;
        $this->assertNotNull($firstPath);
        Storage::disk('public')->assertExists($firstPath);

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($firstPath), false);

        $this->post(route('profile.photo.update'), [
            'photo' => $this->fakePng('new-portrait.png'),
        ])->assertRedirect(route('profile.edit'));

        $secondPath = $user->refresh()->profile_photo_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->delete(route('profile.photo.delete'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-photo-deleted');

        $this->assertNull($user->refresh()->profile_photo_path);
        Storage::disk('public')->assertMissing($secondPath);
    }

    public function test_profile_photo_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->createWithContent('payload.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->refresh()->profile_photo_path);
    }

    public function test_user_can_save_theme_and_accessibility_preferences_across_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.preferences'), [
                'theme' => 'dark',
                'reduced_motion' => '1',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'preferences-updated');

        $user->refresh();
        $this->assertSame('dark', $user->theme);
        $this->assertTrue($user->reduced_motion);

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('data-theme="dark" data-reduced-motion="true"', false)
            ->assertSee('value="dark" checked', false);

        $this->get(route('budgets.index'))
            ->assertOk()
            ->assertSee('data-theme="dark" data-reduced-motion="true"', false);

        $this->patch(route('profile.preferences'), ['theme' => 'light'])
            ->assertRedirect(route('profile.edit'));
        $this->assertSame('light', $user->refresh()->theme);
        $this->assertFalse($user->reduced_motion);
    }

    public function test_dark_theme_styles_cover_charts_navigation_and_standalone_pages(): void
    {
        $user = User::factory()->create(['theme' => 'dark']);

        foreach (['reports.index', 'budgets.index', 'categories.index', 'dashboard', 'transactions.page'] as $route) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('body[data-theme="dark"] .sw-nav a.active { background-color', false)
                ->assertSee('body[data-theme="system"] .report-grid-line { stroke', false)
                ->assertSee('body[data-theme="dark"] .report-donut-hole { background', false)
                ->assertSee('body[data-theme="dark"] .gauge-track { stroke', false)
                ->assertSee('body[data-theme="dark"] .swv-card { border-color', false);
        }
    }

    public function test_preferences_reject_unsupported_themes(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('profile.edit'))
            ->patch(route('profile.preferences'), ['theme' => 'neon'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('theme');
    }

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/r4sAAAAASUVORK5CYII=', true)
        );
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
