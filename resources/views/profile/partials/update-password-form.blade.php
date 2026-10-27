<section class="profile-section">
    <header class="profile-section-header">
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="profile-form">
        @csrf
        @method('put')

        <div class="profile-field">
            <x-input-label for="update_password_current_password" :value="__('Current Password')" class="profile-label" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="profile-input" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="profile-error" />
        </div>

        <div class="profile-field">
            <x-input-label for="update_password_password" :value="__('New Password')" class="profile-label" />
            <x-text-input id="update_password_password" name="password" type="password" class="profile-input" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="profile-error" />
        </div>

        <div class="profile-field">
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" class="profile-label" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="profile-input" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="profile-error" />
        </div>

        <div class="profile-form-actions">
            <x-primary-button class="profile-primary">{{ __('Update password') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="profile-saved"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
