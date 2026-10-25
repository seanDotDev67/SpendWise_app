@extends('auth.recovery-layout')
@section('title', 'Create new password')

@section('content')
    <div class="steps" aria-hidden="true"><span class="on"></span><span class="on"></span><span class="on"></span></div>
    <img class="hero" src="{{ asset('images/spendwise-new-password.png') }}" alt="">

    <h1 class="page-title">Create New Password</h1>
    <p class="page-sub">Your new password must be different from your previously used password.</p>

    <form method="POST" action="{{ route('password.code.reset') }}" data-loading="Saving…">
        @csrf

        <div class="field has-toggle">
            <label for="password" class="sw-label">New password</label>
            <input id="password" type="password" name="password" class="sw-input" required autofocus autocomplete="new-password">
            <button type="button" class="toggle-pass" data-target="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
            @error('password') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field has-toggle">
            <label for="password_confirmation" class="sw-label">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="sw-input" required autocomplete="new-password">
            <button type="button" class="toggle-pass" data-target="password_confirmation" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>

        <button type="submit" class="btn-main">Save password</button>
    </form>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.toggle-pass').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.dataset.target);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            btn.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    });
</script>
@endpush