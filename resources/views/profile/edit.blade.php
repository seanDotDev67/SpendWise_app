@extends('layouts.app')

@section('title', 'Profile')

@push('styles')
<style>
    .profile-page { max-width: 1040px; margin: 24px auto 40px; }
    .profile-intro {
        display: flex; align-items: center; gap: 18px; margin-bottom: 24px;
        padding: 24px 28px; border: 1px solid rgba(47, 74, 58, .1); border-radius: 16px;
        background: linear-gradient(115deg, #FAFAF8 0%, #F4F0E4 100%);
        box-shadow: 0 4px 16px rgba(47, 74, 58, .06);
    }
    .profile-avatar {
        display: grid; width: 62px; height: 62px; flex: 0 0 62px; place-items: center;
        border-radius: 50%; background: var(--sw-dark); color: #fff;
        font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 600;
    }
    .profile-eyebrow { margin-bottom: 3px; color: #71806C; font-size: 11px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; }
    .profile-title { margin: 0; color: var(--sw-dark); font-size: clamp(1.8rem, 3vw, 2.35rem); line-height: 1; }
    .profile-subtitle { margin: 7px 0 0; color: #6D756B; font-size: 13px; }
    .profile-account { margin-left: auto; color: #52724E; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .profile-account i { margin-right: 5px; }
    .profile-settings { display: grid; gap: 16px; }
    .profile-page .sw-card { padding: 24px 28px; border: 1px solid rgba(47, 74, 58, .09); border-radius: 14px; }
    .profile-photo-row { display: flex; align-items: center; gap: 20px; margin-top: 20px; }
    .profile-avatar-large { width: 88px; height: 88px; flex-basis: 88px; font-size: 34px; box-shadow: 0 0 0 5px rgba(127, 148, 104, .12); }
    .profile-photo-actions { flex: 1; min-width: 0; }
    .profile-photo-form { display: grid; justify-items: start; gap: 8px; }
    .profile-photo-form .profile-input { max-width: 420px; }
    .profile-theme-fieldset { min-width: 0; padding: 0; border: 0; }
    .profile-theme-fieldset legend { float: none; width: auto; margin-bottom: 8px; }
    .profile-theme-options { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
    .profile-theme-option { cursor: pointer; }
    .profile-theme-option input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .profile-theme-card {
        display: grid; min-height: 112px; align-content: start; gap: 5px; padding: 14px;
        border: 1px solid #D9D8CE; border-radius: 10px; background: #fff; color: #47584B;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }
    .profile-theme-card i { color: #66815d; font-size: 18px; }
    .profile-theme-card strong { font-size: 13px; }
    .profile-theme-card small, .profile-motion-option small { color: #72796F; font-size: 11px; line-height: 1.4; }
    .profile-theme-option input:checked + .profile-theme-card { border-color: #52724E; box-shadow: 0 0 0 3px rgba(127, 148, 104, .15); }
    .profile-theme-option input:focus-visible + .profile-theme-card { outline: 3px solid rgba(127, 148, 104, .4); outline-offset: 2px; }
    .profile-motion-option {
        display: flex; align-items: center; justify-content: space-between; gap: 14px;
        padding: 14px 0; border-top: 1px solid rgba(47, 74, 58, .1);
    }
    .profile-motion-option span { display: grid; gap: 4px; }
    .profile-motion-option strong { color: #47584B; font-size: 13px; }
    .profile-motion-option input { width: 18px; height: 18px; accent-color: #52724E; }
    .profile-section > header { padding-bottom: 16px; border-bottom: 1px solid rgba(47, 74, 58, .1); }
    .profile-section > header h2 { margin: 0; color: var(--sw-dark); font-size: 17px; font-weight: 600; }
    .profile-section > header p { margin: 5px 0 0; color: #72796F; font-size: 13px; }
    .profile-form { display: grid; gap: 16px; margin-top: 20px; }
    .profile-form .profile-field { display: grid; gap: 6px; }
    .profile-page .profile-label { color: #47584B; font-size: 12px; font-weight: 600; }
    .profile-page .profile-input {
        width: 100%; max-width: 560px; min-height: 44px; padding: 10px 12px;
        border: 1px solid #D9D8CE; border-radius: 8px; background: #fff;
        color: var(--sw-dark); font: inherit; font-size: 14px; box-shadow: none;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .profile-page .profile-input:focus { border-color: var(--sw-sidebar); outline: 0; box-shadow: 0 0 0 3px rgba(127, 148, 104, .18); }
    .profile-page .profile-error { margin: 0; padding: 0; color: #B54752; font-size: 12px; list-style: none; }
    .profile-form-actions { display: flex; align-items: center; gap: 12px; margin-top: 2px; }
    .profile-page .profile-primary,
    .profile-page .profile-danger,
    .profile-page .profile-secondary {
        display: inline-flex; min-height: 40px; align-items: center; justify-content: center;
        padding: 9px 16px; border: 1px solid transparent; border-radius: 8px;
        font: inherit; font-size: 13px; font-weight: 600; letter-spacing: 0; text-transform: none;
        box-shadow: none; transition: background-color .2s ease, border-color .2s ease;
    }
    .profile-page .profile-primary { background: var(--sw-dark); color: #fff; }
    .profile-page .profile-primary:hover { background: #263D30; }
    .profile-page .profile-danger { background: #B54752; color: #fff; }
    .profile-page .profile-danger:hover { background: #963A44; }
    .profile-page .profile-secondary { border-color: #D7D8D0; background: #fff; color: #465347; }
    .profile-page .profile-secondary:hover { background: #F4F4EF; }
    .profile-saved { color: #52724E; font-size: 13px; }
    .profile-danger-section { border-color: rgba(181, 71, 82, .2) !important; }
    .profile-danger-section .profile-section-header { border-bottom-color: rgba(181, 71, 82, .15); }
    .profile-danger-section .profile-section-header h2 { color: #963A44; }
    .profile-page .profile-danger-section p { max-width: 720px; color: #72796F; font-size: 13px; }
    @media (max-width: 575.98px) {
        .profile-page { margin-block: 12px 28px; }
        .profile-intro { flex-wrap: wrap; gap: 12px; padding: 18px; }
        .profile-avatar { width: 50px; height: 50px; flex-basis: 50px; font-size: 23px; }
        .profile-account { display: none; }
        .profile-page .sw-card { padding: 18px; }
        .profile-photo-row { align-items: flex-start; }
        .profile-avatar-large { width: 64px; height: 64px; flex-basis: 64px; }
        .profile-theme-options { grid-template-columns: 1fr; }
        .profile-theme-card { min-height: 0; grid-template-columns: 24px 1fr; align-items: center; }
        .profile-theme-card small { grid-column: 2; }
    }
</style>
@endpush

@section('content')
    @php
        $profilePhotoUrl = $user->profile_photo_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo_path)
            : null;
        $profileInitials = collect(explode(' ', trim($user->name)))
            ->filter()
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    @endphp
    <div class="profile-page">
        <header class="profile-intro">
            <div class="profile-avatar" aria-hidden="true">
                @if ($profilePhotoUrl)
                    <img src="{{ $profilePhotoUrl }}" alt="">
                @else
                    {{ $profileInitials }}
                @endif
            </div>
            <div>
                <div class="profile-eyebrow">Your personal space</div>
                <h1 class="profile-title serif fw-semibold">{{ $user->name }}</h1>
                <p class="profile-subtitle">{{ $user->email }}</p>
            </div>
            <div class="profile-account"><i class="bi bi-shield-check" aria-hidden="true"></i>Private to your account</div>
        </header>
        @if (session('status') === 'profile-photo-updated')
            <div class="alert alert-success" role="status">Your profile photo was updated.</div>
        @elseif (session('status') === 'profile-photo-deleted')
            <div class="alert alert-success" role="status">Your profile photo was removed.</div>
        @endif
        <div class="profile-settings">
        <section class="sw-card profile-section">
            <header>
                <h2>Profile photo</h2>
                <p>Add a photo so your SpendWise account feels like yours. JPG, PNG, or WebP up to 2 MB.</p>
            </header>
            <div class="profile-photo-row">
                <div class="profile-avatar profile-avatar-large" aria-hidden="true">
                    @if ($profilePhotoUrl)
                        <img id="profilePhotoPreview" src="{{ $profilePhotoUrl }}" alt="">
                    @else
                        <span id="profilePhotoInitials">{{ $profileInitials }}</span>
                        <img id="profilePhotoPreview" src="" alt="" hidden>
                    @endif
                </div>
                <div class="profile-photo-actions">
                    <form method="post" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="profile-photo-form">
                        @csrf
                        <label for="profilePhotoInput" class="profile-label">Choose an image</label>
                        <input id="profilePhotoInput" name="photo" type="file" class="profile-input" accept="image/jpeg,image/png,image/webp" required>
                        <x-input-error class="profile-error" :messages="$errors->get('photo')" />
                        <div class="profile-form-actions">
                            <button type="submit" class="profile-primary">Upload photo</button>
                            @if ($profilePhotoUrl)
                                <button type="submit" form="deleteProfilePhoto" class="profile-secondary">Remove photo</button>
                            @endif
                        </div>
                    </form>
                    <form id="deleteProfilePhoto" method="post" action="{{ route('profile.photo.delete') }}" hidden>
                        @csrf
                        @method('delete')
                    </form>
                </div>
            </div>
        </section>

        <section class="sw-card profile-section">
            <header>
                <h2>Appearance &amp; accessibility</h2>
                <p>Choose how SpendWise looks and feels on your devices.</p>
            </header>
            <form method="post" action="{{ route('profile.preferences') }}" class="profile-form">
                @csrf
                @method('patch')
                <fieldset class="profile-theme-fieldset">
                    <legend class="profile-label">Theme</legend>
                    <div class="profile-theme-options">
                        @foreach ([
                            'system' => ['System', 'Follow your device appearance', 'bi-circle-half'],
                            'light' => ['Light', 'A bright, calm workspace', 'bi-sun'],
                            'dark' => ['Dark', 'A softer look in low light', 'bi-moon-stars'],
                        ] as $themeValue => [$themeLabel, $themeDescription, $themeIcon])
                            <label class="profile-theme-option">
                                <input type="radio" name="theme" value="{{ $themeValue }}" @checked(old('theme', $user->theme) === $themeValue)>
                                <span class="profile-theme-card">
                                    <i class="bi {{ $themeIcon }}" aria-hidden="true"></i>
                                    <strong>{{ $themeLabel }}</strong>
                                    <small>{{ $themeDescription }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error class="profile-error" :messages="$errors->get('theme')" />
                </fieldset>
                <label class="profile-motion-option">
                    <span>
                        <strong>Reduce motion</strong>
                        <small>Limit animations and transitions throughout the app.</small>
                    </span>
                    <input type="checkbox" name="reduced_motion" value="1" @checked(old('reduced_motion', $user->reduced_motion))>
                </label>
                <x-input-error class="profile-error" :messages="$errors->get('reduced_motion')" />
                <div class="profile-form-actions">
                    <button type="submit" class="profile-primary">Save preferences</button>
                    @if (session('status') === 'preferences-updated')
                        <span class="profile-saved" role="status">Preferences saved.</span>
                    @endif
                </div>
            </form>
        </section>

        <div class="sw-card">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="sw-card">
            @include('profile.partials.update-password-form')
        </div>
        <div class="sw-card profile-danger-section">
            @include('profile.partials.delete-user-form')
        </div>
        </div>
    </div>
    <script>
        (() => {
            const input = document.getElementById('profilePhotoInput');
            const preview = document.getElementById('profilePhotoPreview');
            const initials = document.getElementById('profilePhotoInitials');
            let previewUrl = null;
            if (!input || !preview) return;
            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) return;
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                previewUrl = URL.createObjectURL(file);
                preview.src = previewUrl;
                preview.hidden = false;
                if (initials) initials.hidden = true;
            });
            window.addEventListener('beforeunload', () => {
                if (previewUrl) URL.revokeObjectURL(previewUrl);
            });
        })();
    </script>
@endsection
