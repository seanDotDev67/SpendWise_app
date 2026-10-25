@extends('auth.recovery-layout')
@section('title', 'Verify your email')

@section('content')
    <div class="steps" aria-hidden="true"><span class="on"></span><span class="on"></span><span></span></div>
    <img class="hero" src="{{ asset('images/spendwise-verify-email.png') }}" alt="">

    <h1 class="page-title">Verify Your Email</h1>
    <p class="page-sub">Please enter the 6-digit code sent to <strong>{{ $maskedEmail ?? 'your email' }}</strong></p>

    @if (session('status'))
        <div class="notice" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.code.verify') }}" id="otpForm" data-loading="Verifying…">
        @csrf
        <input type="hidden" name="code" id="code">

        <div class="otp">
            @for ($i = 0; $i < 6; $i++)
                <input type="text" class="otp-box" inputmode="numeric" maxlength="1"
                       autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i + 1 }}">
            @endfor
        </div>
        @error('code') <div class="field-error center">{{ $message }}</div> @enderror

        <button type="submit" class="btn-main">Verify</button>
    </form>

    <form method="POST" action="{{ route('password.code.resend') }}" class="resend">
        @csrf
        <span>Didn't get it?</span>
        <button type="submit" class="link-btn" id="resendBtn">Resend code</button>
    </form>

    <a href="{{ route('password.request') }}" class="back-link">← Use a different email</a>
@endsection

@push('scripts')
<script>
    (function () {
        const boxes  = Array.from(document.querySelectorAll('.otp-box'));
        const hidden = document.getElementById('code');
        const form   = document.getElementById('otpForm');
        const sync   = () => { hidden.value = boxes.map(b => b.value).join(''); };

        boxes.forEach(function (b, i) {
            b.addEventListener('input', function () {
                b.value = b.value.replace(/\D/g, '').slice(-1);
                sync();
                if (b.value && i < 5) boxes[i + 1].focus();
            });
            b.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !b.value && i > 0) { boxes[i - 1].value = ''; boxes[i - 1].focus(); sync(); }
                if (e.key === 'ArrowLeft'  && i > 0) boxes[i - 1].focus();
                if (e.key === 'ArrowRight' && i < 5) boxes[i + 1].focus();
            });
            b.addEventListener('paste', function (e) {
                e.preventDefault();
                const t = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
                t.split('').forEach(function (c, k) { boxes[k].value = c; });
                sync();
                boxes[Math.min(t.length, 5)].focus();
            });
            b.addEventListener('focus', function () { b.select(); });
        });

        form.addEventListener('submit', function (e) {
            sync();
            if (hidden.value.length < 6) {
                e.preventDefault();
                (boxes.find(b => !b.value) || boxes[0]).focus();
            }
        });
        boxes[0].focus();

        // 30-second cooldown on "Resend code"
        const rb = document.getElementById('resendBtn');
        let s = 30;
        rb.disabled = true;
        const tick = setInterval(function () {
            s--;
            if (s <= 0) { clearInterval(tick); rb.disabled = false; rb.textContent = 'Resend code'; }
            else rb.textContent = 'Resend code (' + s + 's)';
        }, 1000);
        rb.textContent = 'Resend code (30s)';
    })();
</script>
@endpush