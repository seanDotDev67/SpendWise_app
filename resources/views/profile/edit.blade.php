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
        .profile-intro { gap: 12px; padding: 18px; }
        .profile-avatar { width: 50px; height: 50px; flex-basis: 50px; font-size: 23px; }
        .profile-account { display: none; }
        .profile-page .sw-card { padding: 18px; }
    }
</style>
@endpush

@section('content')
    <div class="profile-page">
        <header class="profile-intro">
            <div class="profile-avatar" aria-hidden="true">{{ collect(explode(' ', trim($user->name)))->filter()->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))->take(2)->implode('') }}</div>
            <div>
                <div class="profile-eyebrow">Account settings</div>
                <h1 class="profile-title serif fw-semibold">{{ __('Your profile') }}</h1>
                <p class="profile-subtitle">{{ __('Manage your personal details and account security.') }}</p>
            </div>
            <div class="profile-account"><i class="bi bi-shield-check" aria-hidden="true"></i>Account protected</div>
        </header>
        <div class="profile-settings">
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
@endsection
