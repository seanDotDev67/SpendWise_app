<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f3f1">
    <title>SpendWise — Log in</title>
    <link rel="icon" href="{{ asset('images/spendwise-logo.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            /* 1u = 1% of the Figma frame. Everything is sized in u, so the whole page
               scales as one piece and always fits the screen (no overflow, no overlap). */
            --u: max(min(1vw, 1.553vh), 6.5px);

            --left-bg: #eef4e2;    /* green — brand side (login has brand on the LEFT) */
            --right-bg: #f5f3f1;   /* cream — form side */
            --green-dark: #3b4a3c;
            --green-darker: #2e3b30;
            --olive: #7b8c5f;
            --pink: #e8d0d3;
            --pink-text: #c2727d;
            --pink-line: #ecd2d5;
            --floor: #e8e3d1;
            --floor-edge: #d9d3b5;
            --ink: #344d3d;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--ink);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            animation: fadeIn .4s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        ::selection { background: var(--pink); }

        /* ============ STAGE ============ */
        .stage {
            position: relative;
            height: 100vh;
            min-height: 560px;
            background: var(--right-bg);
            overflow: hidden;
        }

        /* Background layers — CLIPPED, contain NO content */
        .layer { position: absolute; inset: 0; }
        .layer-left  { background: var(--left-bg);  clip-path: url(#clipLeft); }
        .layer-right { background: var(--right-bg); clip-path: url(#clipRight); }

        /* Decor layers — clipped decorations that follow the wave cut */
        .decor-left  { position: absolute; inset: 0; clip-path: url(#clipLeft);  z-index: 1; }
        .decor-right { position: absolute; inset: 0; clip-path: url(#clipRight); z-index: 1; }

        /* ============ CIRCLES (form side, mirrored from register) ============ */
        .circle { position: absolute; border-radius: 50%; transform: translate(-50%, -50%); filter: blur(3px); pointer-events: none; }
        .c-sage-lg { width: calc(var(--u) * 21);  height: calc(var(--u) * 21);  left: 42.5%; top: 13%; background: #d5dacb; }
        .c-pink-sm { width: calc(var(--u) * 5.4); height: calc(var(--u) * 5.4); left: 85%;   top: 16%; background: #ecd9db; }
        .c-pink-lg { width: calc(var(--u) * 27);  height: calc(var(--u) * 27);  left: 101%;  top: 71%; background: #efe3e2; }
        .c-pink-bt { width: calc(var(--u) * 24);  height: calc(var(--u) * 24);  left: 39%;   top: 93%; background: #eddede; }
        .c-sage-sm { width: calc(var(--u) * 5.8); height: calc(var(--u) * 5.8); left: 72%;   top: 93%; background: #d4dacb; }

        /* ============ FORM (OUTSIDE the clip — can never be cut away) ============ */
        .form-area {
            position: absolute;
            top: 0; bottom: 0;
            left: 40%; right: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 4;
        }
        .form-wrap { width: calc(var(--u) * 38.3); }

        .page-title {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-weight: 600;
            color: var(--pink-text);
            font-size: calc(var(--u) * 3.8);
            line-height: 1;
            text-align: center;
            margin: 0;
        }
        .page-sub {
            font-size: calc(var(--u) * 1.37);
            color: #344d3d;
            text-align: center;
            margin: calc(var(--u) * .5) 0 calc(var(--u) * 4.4);
        }

        .field { margin-bottom: calc(var(--u) * 1.8); position: relative; }
        .sw-label {
            display: block;
            font-size: calc(var(--u) * 1.08);
            line-height: 1.2;
            color: var(--ink);
            margin: 0 0 calc(var(--u) * .4) calc(var(--u) * .7);
        }
        .sw-input {
            width: 100%;
            height: calc(var(--u) * 3);
            padding: 0 calc(var(--u) * 1.2);
            border: none;
            border-radius: calc(var(--u) * .8);
            background: #fff;
            font-family: 'Inter', sans-serif;
            font-size: calc(var(--u) * 1.15);
            color: var(--ink);
            box-shadow: 0 calc(var(--u) * .2) 0 var(--pink-line), 0 calc(var(--u) * .4) calc(var(--u) * .8) rgba(0,0,0,.04);
            transition: box-shadow .25s ease;
        }
        .sw-input:hover { box-shadow: 0 calc(var(--u) * .2) 0 #e3c3c8, 0 calc(var(--u) * .4) calc(var(--u) * .8) rgba(0,0,0,.05); }
        .sw-input:focus {
            outline: none;
            box-shadow: 0 calc(var(--u) * .2) 0 var(--pink-text), 0 0 0 3px rgba(194,114,125,.15);
        }
        .has-toggle .sw-input { padding-right: calc(var(--u) * 3.6); }

        input:-webkit-autofill,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--ink);
            -webkit-box-shadow: 0 0 0 1000px #fff inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        .toggle-pass {
            position: absolute;
            right: calc(var(--u) * 1.2);
            bottom: calc(var(--u) * .75);
            background: none;
            border: none;
            padding: 0;
            font-size: calc(var(--u) * 1.3);
            line-height: 1;
            color: #a5a5a5;
            cursor: pointer;
            transition: color .2s ease;
        }
        .toggle-pass:hover { color: var(--green-dark); }

        .remember {
            display: flex;
            align-items: center;
            gap: calc(var(--u) * 1.2);
            margin: calc(var(--u) * .6) 0 calc(var(--u) * 2.9) calc(var(--u) * 1.1);
        }
        .remember input {
            appearance: none;
            -webkit-appearance: none;
            width: calc(var(--u) * 1.15);
            height: calc(var(--u) * 1.15);
            margin: 0;
            border: 1px solid #777;
            border-radius: 2px;
            background: #fff;
            cursor: pointer;
            flex-shrink: 0;
        }
        .remember input:checked {
            background: var(--green-dark) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 8.5l3 3 6-7' fill='none' stroke='white' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/80% no-repeat;
            border-color: var(--green-dark);
        }
        .remember label { font-size: calc(var(--u) * 1.08); color: #444; cursor: pointer; }

        .btn-login {
            display: block;
            margin: 0 auto;
            width: calc(var(--u) * 20.6);
            height: calc(var(--u) * 3.6);
            border: none;
            border-radius: calc(var(--u) * 1);
            background: var(--green-dark);
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: calc(var(--u) * 1.2);
            font-weight: 500;
            cursor: pointer;
            transition: all .25s ease;
        }
        .btn-login:hover {
            background: var(--green-darker);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59,74,60,.28);
        }
        .btn-login:active { transform: translateY(0); box-shadow: none; }
        .btn-login:disabled { opacity: .75; transform: none; box-shadow: none; cursor: default; }
        .spinner {
            display: inline-block;
            width: 1em; height: 1em;
            margin-right: .5em;
            vertical-align: -.15em;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .or-row {
            display: flex;
            align-items: center;
            gap: calc(var(--u) * 1.5);
            margin: calc(var(--u) * 2.2) 0;
        }
        .or-row::before, .or-row::after {
            content: "";
            flex: 1;
            border-top: 1px solid #ddd6d2;
        }
        .or-row span { font-size: calc(var(--u) * 1.05); color: #999; }

        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: calc(var(--u) * 1);
            margin: 0 auto;
            width: calc(var(--u) * 20.6);
            height: calc(var(--u) * 3.6);
            border: none;
            border-radius: calc(var(--u) * 1);
            background: var(--pink);
            color: #333;
            font-family: 'Inter', sans-serif;
            font-size: calc(var(--u) * 1.2);
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all .25s ease;
        }
        .btn-google:hover { background: #e0c0c4; transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,.1); }
        .btn-google:active { transform: translateY(0); box-shadow: none; }
        .btn-google svg { width: calc(var(--u) * 1.7); height: auto; }

        .forgot { text-align: center; margin-top: calc(var(--u) * 2.2); }
        .forgot a {
            font-size: calc(var(--u) * 1.15);
            color: #666;
            text-decoration: none;
            transition: color .2s ease;
        }
        .forgot a:hover { color: var(--pink-text); text-decoration: underline; }

        .field-error { color: #c0392b; font-size: calc(var(--u) * .95); margin: 4px 0 0 calc(var(--u) * .7); }
        .form-status {
            text-align: center;
            color: #4a7c4e;
            font-size: calc(var(--u) * 1.1);
            margin: 0 0 calc(var(--u) * 1.8);
        }
        .form-error {
            text-align: center;
            color: #c0392b;
            font-size: calc(var(--u) * 1.1);
            margin: 0 0 calc(var(--u) * 1.8);
        }
        .mobile-signup { display: none; text-align: center; margin-top: 1.1rem; font-size: .9rem; color: #555; }

        /* ============ BRAND (left side, OUTSIDE the clip) ============ */
        .brand-content {
            position: absolute;
            left: 17.4%;
            top: calc(var(--u) * 11.5);
            transform: translateX(-50%);
            width: calc(var(--u) * 28);
            text-align: center;
            z-index: 2;
        }
        .logo { width: calc(var(--u) * 8.2); height: auto; display: block; margin: 0 auto calc(var(--u) * 1.2); }
        .brand {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
            font-size: calc(var(--u) * 5.4);
            line-height: 1;
            color: #2d3a30;
        }
        .tagline {
            font-size: calc(var(--u) * 1.9);
            font-weight: 300;
            letter-spacing: .02em;
            color: #c4929a;
            margin-top: calc(var(--u) * .4);
        }
        .divider { border: 0; border-top: 1px solid #d3d9c6; margin: calc(var(--u) * 2.6) 0 calc(var(--u) * 3.6); }
        .new-here {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
            font-size: calc(var(--u) * 2.6);
            line-height: 1.1;
            color: #3a4a3a;
            text-align: left;   /* ← CHANGED: stops it centering */
            margin: 0;
        }
        .new-here-p {
            font-size: calc(var(--u) * 1.42);
            line-height: 1.35;
            color: #444;
            text-align: left;
            max-width: calc(var(--u) * 20.5);
            margin: calc(var(--u) * 1.5) 0 0 0;   /* ← CHANGED: was ...auto 0 calc(var(--u) * 3) */
        }
        .btn-signup {
            display: flex;
            align-items: center;
            justify-content: center;
            width: calc(var(--u) * 11.9);
            height: calc(var(--u) * 4);
            margin: calc(var(--u) * 2.1) auto 0 0;   /* ← CHANGED: was ...auto 0 calc(var(--u) * 2.1) */
            border-radius: calc(var(--u) * 1.6);
            background: var(--pink);
            color: #333;
            font-size: calc(var(--u) * 1.65);
            text-decoration: none;
            transition: all .25s ease;
        }
        .btn-signup:hover { background: #e0c0c4; transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,.1); }
        .btn-signup:active { transform: translateY(0); box-shadow: none; }

        /* Floor + plant live in the clipped green decor layer (like register) */
        .floor {
            position: absolute;
            left: 0; bottom: 0;
            width: 100%;
            height: calc(var(--u) * 7.5);
            background: var(--floor);
            border-top: calc(var(--u) * .8) solid var(--floor-edge);
        }
        .plant-wrap {
            position: absolute;
            left: 23%;
            bottom: calc(var(--u) * 3.6);
            transform: translateX(-50%);
            filter: drop-shadow(0 6px 8px rgba(0,0,0,.08));
        }
        .plant { display: block; height: calc(var(--u) * 15); width: auto; transform-origin: bottom center; }
        .plant:hover { animation: sway 1.2s ease-in-out infinite; }
        @keyframes sway {
            0%, 100% { transform: rotate(0deg); }
            30% { transform: rotate(-3deg); }
            70% { transform: rotate(3deg); }
        }

        /* ============ WAVE LINE (mirrored to the LEFT side, like register flipped) ============ */
        .wave-line {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 5;
            pointer-events: none;
            overflow: visible;
        }
        .wave-line path {
            fill: none;
            stroke: var(--olive);
            stroke-width: calc(var(--u) * 1.7);
            stroke-linecap: butt;
            stroke-linejoin: round;
            vector-effect: non-scaling-stroke;
        }

        .toggle-pass:focus-visible, .forgot a:focus-visible, .btn-signup:focus-visible, .btn-login:focus-visible, .btn-google:focus-visible {
            outline: 3px solid rgba(59,74,60,.35);
            outline-offset: 2px;
            border-radius: 8px;
        }

        /* ============ MOBILE / TABLET ============ */
        @media (max-width: 991px) {
            :root { --u: 3.1vw; }
            .stage { height: auto; min-height: 100vh; }
            .layer-left, .layer-right, .decor-left, .decor-right, .wave-line, .brand-content { display: none; }
            .form-area { position: relative; inset: auto; left: auto; right: auto; width: 100%; padding: 3rem 1.5rem; }
            .form-wrap { width: 100%; max-width: 440px; }
            .mobile-signup { display: block; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

{{-- Clip shapes: mirrored from register — wave now sits at ~34% (left side) --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
        <clipPath id="clipRight" clipPathUnits="objectBoundingBox">
            <path d="M0.391,0 C0.388,0.1094 0.344,0.1484 0.344,0.2891 C0.344,0.4141 0.406,0.4063 0.406,0.5234 C0.406,0.6563 0.281,0.6563 0.281,0.7891 C0.281,0.8906 0.320,0.9531 0.344,1 L1,1 L1,0 Z"/>
        </clipPath>
        <clipPath id="clipLeft" clipPathUnits="objectBoundingBox">
            <path d="M0.391,0 C0.388,0.1094 0.344,0.1484 0.344,0.2891 C0.344,0.4141 0.406,0.4063 0.406,0.5234 C0.406,0.6563 0.281,0.6563 0.281,0.7891 C0.281,0.8906 0.320,0.9531 0.344,1 L0,1 L0,0 Z"/>
        </clipPath>
    </defs>
</svg>

<div class="stage">

    {{-- ===== Background layers (clipped, NO content inside) ===== --}}
    <div class="layer layer-left"></div>
    <div class="layer layer-right"></div>

    {{-- ===== Green-side decorations: floor + plant (clipped with the wave) ===== --}}
    <div class="decor-left">
        <div class="floor"></div>
        <div class="plant-wrap">
            <img class="plant" src="{{ asset('images/spendwise-plant.png') }}" alt="SpendWise plant">
        </div>
    </div>

    {{-- ===== Cream-side decorations: circles (clipped with the wave) ===== --}}
    <div class="decor-right">
        <div class="circle c-sage-lg"></div>
        <div class="circle c-pink-sm"></div>
        <div class="circle c-pink-lg"></div>
        <div class="circle c-pink-bt"></div>
        <div class="circle c-sage-sm"></div>
    </div>

    {{-- ===== Brand content (OUTSIDE the clip — never cut) ===== --}}
    <div class="brand-content">
        <img class="logo" src="{{ asset('images/spendwise-logo.png') }}" alt="SpendWise logo">

        <div class="brand">SpendWise</div>
        <div class="tagline">Spend Smart. Live Wise</div>

        <hr class="divider">

        <div class="new-here">New here?</div>
        <p class="new-here-p">Create an account and start tracking your spending.</p>

        <a href="{{ route('register') }}" class="btn-signup">Sign up</a>
    </div>

    {{-- ===== Form (OUTSIDE the clip — never cut) ===== --}}
    <div class="form-area">
        <div class="form-wrap">
            <h1 class="page-title">Welcome Back!</h1>
            <p class="page-sub">Log in to see where your money went this month.</p>

            @if (session('status'))
                <div class="form-status">{{ session('status') }}</div>
            @endif
            @if ($errors->has('google'))
                <div class="form-error">{{ $errors->first('google') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <div class="field">
                    <label for="email" class="sw-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="sw-input" required autofocus autocomplete="username">
                    @error('email') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field has-toggle">
                    <label for="password" class="sw-label">Password</label>
                    <input id="password" type="password" name="password"
                           class="sw-input" required autocomplete="current-password">
                    <button type="button" class="toggle-pass" data-target="password" aria-label="Toggle password visibility" aria-pressed="false">
                        <i class="bi bi-eye"></i>
                    </button>
                    @error('password') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="remember">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">Log in</button>

                <div class="or-row"><span>or</span></div>

                <a href="{{ route('auth.google.redirect') }}" class="btn-google">
                    Continue with Google
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                </a>

                <div class="forgot">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"class="tos-link">Forgot Password?</a>
                    @endif
                </div>

                <div class="mobile-signup">
                    New here?
                    <a href="{{ route('register') }}" style="color: var(--pink-text); font-weight: 500; text-decoration: underline;">Create an account</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Wave line (mirrored — sits on the LEFT at ~34%) ===== --}}
    <svg class="wave-line" viewBox="0 0 1000 640" preserveAspectRatio="none" aria-hidden="true">
        <path d="M393,-20 L391,0 C388,70 344,95 344,185 C344,265 406,260 406,335 C406,420 281,420 281,505 C281,570 320,610 344,640 L360,660"/>
    </svg>

</div>

<script>
    // Eye toggle for the password field
    document.querySelectorAll('.toggle-pass').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.dataset.target);
            const icon = btn.querySelector('i');
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            icon.className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
            btn.setAttribute('aria-pressed', String(!showing));
        });
    });

    // Show loading state on submit
    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" aria-hidden="true"></span>Logging in…';
    });
</script>

</body>
</html>