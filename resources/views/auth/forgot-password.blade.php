@extends('auth.recovery-layout')
@section('title', 'Forgot password')

@section('content')
    <div class="steps" aria-hidden="true"><span class="on"></span><span></span><span></span></div>
    <img class="hero" src="{{ asset('images/spendwise-forgot-lock.png') }}" alt="">

    <h1 class="page-title">Forgot Password?</h1>
    <p class="page-sub">No worries. Enter your email and we'll send you a 6-digit code to verify it's you.</p>

    @if (session('status'))
        <div class="notice" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.code.send') }}" data-loading="Sending…">
        @csrf
        <div class="field">
            <label for="email" class="sw-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="sw-input" required autofocus autocomplete="email">
            @error('email') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn-main">Send reset link</button>
        <a href="{{ route('login') }}" class="back-link">← Back to sign in</a>
    </form>
@endsection