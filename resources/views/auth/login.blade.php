<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#e9efdc">
    <title>SpendWise — Log in</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='14' fill='%232f4a3c'/><rect x='16' y='36' width='7' height='14' rx='2' fill='%23f2c7c7'/><rect x='27' y='28' width='7' height='22' rx='2' fill='%23f2c7c7'/><rect x='38' y='20' width='7' height='30' rx='2' fill='%23ffffff'/></svg>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;1,500;1,600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* ============ DESIGN TOKENS ============ */
        :root {
            --sw-sage: #e9efdc;
            --sw-cream: #f8f3ec;
            --sw-green: #5c7d5c;
            --sw-green-dark: #2f4a3c;
            --sw-green-darker: #243b2f;
            --sw-pink: #f2c7c7;
            --sw-pink-dark: #e5aeae;
            --sw-pink-text: #d98f8f;
            --sw-floor: #e7dcc8;
            --sw-ink: #3d3d3d;
            --sw-muted: #6b6b6b;
            --sw-line: #e3dccf;
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: var(--sw-ink);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            animation: fadeIn .4s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        ::selection { background: var(--sw-pink); color: #4a3a3a; }

        /* ============ LAYOUT ============ */
        .sw-split { display: flex; min-height: 100vh; position: relative; }

        /* ---------- LEFT PANEL ---------- */
        .left-panel {
            width: 38%;
            background: var(--sw-sage);
            position: relative;
            padding: 10vh 3.5rem 0 4rem;
        }
        .left-content { max-width: 340px; position: relative; z-index: 2; }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.4rem, 3vw, 3rem);
            color: #2e2e2e;
            line-height: 1.1;
            letter-spacing: .01em;
        }
        .tagline { color: var(--sw-pink-text); font-size: .95rem; letter-spacing: .04em; }
        .divider { border: 0; border-top: 1px solid rgba(0,0,0,.14); margin: 3.2vh 0; max-width: 320px; }
        .newhere { font-family: 'Playfair Display', serif; color: var(--sw-green-dark); font-size: 1.5rem; }
        .newhere-p { font-size: .9rem; color: var(--sw-muted); max-width: 240px; line-height: 1.6; }

        .btn-signup {
            background: var(--sw-pink);
            color: #4a3a3a;
            border: none;
            border-radius: 10px;
            padding: .6rem 1.9rem;
            font-weight: 500;
            letter-spacing: .02em;
            transition: all .25s ease;
        }
        .btn-signup:hover {
            background: var(--sw-pink-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0,0,0,.12);
            color: #4a3a3a;
        }
        .btn-signup:active { transform: translateY(0); box-shadow: none; }

        .floor {
            position: absolute;
            bottom: 0; left: 0;
            width: 100%; height: 14px;
            background: var(--sw-floor);
            z-index: 1;
        }
        .plant-wrap {
            position: absolute;
            bottom: 9px; left: 58%;
            transform: translateX(-50%);
            z-index: 2;
            filter: drop-shadow(0 6px 8px rgba(0,0,0,.08));
        }
        .plant { transform-origin: bottom center; }
        .plant:hover { animation: sway 1.2s ease-in-out infinite; }
        @keyframes sway {
            0%, 100% { transform: rotate(0deg); }
            30% { transform: rotate(-3deg); }
            70% { transform: rotate(3deg); }
        }

        /* ---------- WAVE ---------- */
        .wave {
            position: absolute;
            top: 0;
            left: calc(38% - 70px);
            width: 140px; height: 100%;
            z-index: 3;
            pointer-events: none;
        }

        /* ---------- RIGHT PANEL ---------- */
        .right-panel {
            width: 62%;
            background: var(--sw-cream);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4vh 2rem 5vh;
        }
        .blob { position: absolute; border-radius: 50%; filter: blur(44px); pointer-events: none; z-index: 0; }
        .blob-pink-lg     { width: 320px; height: 320px; background: #f3cdcd; bottom: -100px; right: -80px; opacity: .55; }
        .blob-pink-sm     { width: 100px; height: 100px; background: #f3cdcd; top: 12vh; left: 15%; opacity: .5; }
        .blob-green       { width: 120px; height: 120px; background: #c2d1b8; bottom: -40px; left: 42%; opacity: .5; }
        .blob-pink-corner { width: 140px; height: 140px; background: #f3cdcd; top: -50px; right: 6%; opacity: .45; }

        .form-wrap { position: relative; z-index: 2; width: 100%; max-width: 340px; }

        .welcome {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            color: var(--sw-pink-text);
            font-size: clamp(2rem, 2.6vw, 2.6rem);
        }
        .welcome-sub { font-size: .9rem; color: var(--sw-muted); line-height: 1.6; }

        /* ---------- FORM ---------- */
        .sw-label {
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--sw-muted);
            margin-bottom: 8px;
        }
        .check-label { font-size: .82rem; color: #555; }

        .input-icon { position: relative; }
        .input-icon > .bi {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--sw-muted);
            pointer-events: none;
        }
        .input-icon .form-control {
            height: 46px;
            padding-left: 2.6rem;
            border-radius: 10px;
            border: 1px solid var(--sw-line);
            background: #fff;
            font-size: .9rem;
            transition: all .25s ease;
        }
        .input-icon .form-control:hover { border-color: #cfc5b4; }
        .input-icon .form-control:focus {
            border-color: var(--sw-green-dark);
            box-shadow: 0 0 0 .25rem rgba(47,74,60,.1);
        }
        .input-icon.has-toggle .form-control { padding-right: 2.8rem; }

        /* Chrome autofill fix — no more blue/yellow boxes */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--sw-ink);
            -webkit-box-shadow: 0 0 0 1000px #fff inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        .toggle-pass {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: #9a9a9a;
            padding: 0;
            transition: color .2s ease;
        }
        .toggle-pass:hover { color: var(--sw-green-dark); }

        .btn-sw-dark {
            background: var(--sw-green-dark);
            color: #fff;
            border: none;
            border-radius: 10px;
            height: 46px;
            width: 100%;
            font-weight: 500;
            letter-spacing: .03em;
            transition: all .25s ease;
        }
        .btn-sw-dark:hover {
            background: var(--sw-green-darker);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(47,74,60,.3);
        }
        .btn-sw-dark:active { transform: translateY(0); box-shadow: none; }
        .btn-sw-dark:disabled { opacity: .75; transform: none; box-shadow: none; }

        .divider-or {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: .78rem;
            color: #8a8a8a;
            margin: 14px 0;
        }
        .divider-or::before, .divider-or::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--sw-line);
        }

        .btn-google {
            background: var(--sw-pink);
            color: #4a3a3a;
            border: none;
            border-radius: 10px;
            height: 44px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 500;
            letter-spacing: .02em;
            transition: all .25s ease;
        }
        .btn-google:hover {
            background: var(--sw-pink-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0,0,0,.12);
        }
        .btn-google:active { transform: translateY(0); box-shadow: none; }

        .forgot {
            font-size: .8rem;
            color: #4a4a4a;
            text-decoration: none;
            transition: all .2s ease;
        }
        .forgot:hover { color: var(--sw-green-dark); text-decoration: underline; }

        .form-check-input:checked { background-color: var(--sw-green-dark); border-color: var(--sw-green-dark); }
        .form-check-input:hover { border-color: var(--sw-green-dark); cursor: pointer; }

        .field-error { color: #c0392b; font-size: .75rem; margin-top: 5px; }
        .sw-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 0;
            border-radius: 10px;
            font-size: .82rem;
            padding: .65rem .9rem;
        }

        .sw-footer {
            position: absolute;
            bottom: 14px; left: 0; right: 0;
            text-align: center;
            font-size: .72rem;
            color: #a89f92;
            z-index: 2;
            letter-spacing: .03em;
        }

        /* Keyboard accessibility */
        .btn:focus-visible, .toggle-pass:focus-visible, .forgot:focus-visible {
            outline: 3px solid rgba(47,74,60,.35);
            outline-offset: 2px;
            border-radius: 6px;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 991px) {
            .left-panel, .wave { display: none; }
            .right-panel { width: 100%; }
        }
        @media (max-height: 700px) {
            .left-panel { padding-top: 5vh; }
            .brand { font-size: 2.2rem; }
            .divider { margin: 2vh 0; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

<div class="sw-split">

    {{-- ================= LEFT PANEL ================= --}}
    <div class="left-panel">
        <div class="left-content">
            <div class="mb-3">
                <svg width="88" height="70" viewBox="0 0 70 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="4" y="6" width="62" height="44" rx="8" fill="#2f4a3c"/>
                    <rect x="48" y="22" width="18" height="12" rx="4" fill="#e9efdc"/>
                    <circle cx="57" cy="28" r="2.5" fill="#2f4a3c"/>
                    <rect x="14" y="32" width="6" height="10" rx="1.5" fill="#f2c7c7"/>
                    <rect x="23" y="26" width="6" height="16" rx="1.5" fill="#f2c7c7"/>
                    <rect x="32" y="20" width="6" height="22" rx="1.5" fill="#ffffff"/>
                </svg>
            </div>

            <div class="brand">SpendWise</div>
            <div class="tagline mt-1">Spend Smart. Live Wise</div>

            <hr class="divider">

            <div class="newhere">New here?</div>
            <p class="newhere-p mt-2">Create an account and start tracking your spending.</p>

            <a href="{{ route('register') }}" class="btn btn-signup mt-2">Sign up</a>
        </div>

        <div class="floor"></div>

        <div class="plant-wrap">
            <svg class="plant" width="120" height="150" viewBox="0 0 96 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M48,86 C48,62 42,50 30,40" stroke="#5c7d5c" stroke-width="3.5" stroke-linecap="round"/>
                <path d="M48,86 C48,58 56,46 66,36" stroke="#5c7d5c" stroke-width="3.5" stroke-linecap="round"/>
                <path d="M48,86 L48,58" stroke="#5c7d5c" stroke-width="3.5" stroke-linecap="round"/>
                <ellipse cx="27" cy="37" rx="12" ry="6.5" fill="#7d9b76" transform="rotate(-42 27 37)"/>
                <ellipse cx="69" cy="33" rx="12" ry="6.5" fill="#7d9b76" transform="rotate(38 69 33)"/>
                <ellipse cx="48" cy="50" rx="7" ry="11" fill="#6b8a64"/>
                <path d="M30,86 L66,86 L60,116 L36,116 Z" fill="#e2a3a3"/>
                <rect x="26" y="82" width="44" height="9" rx="4" fill="#d49393"/>
            </svg>
        </div>
    </div>

    {{-- ================= WAVE ================= --}}
    <svg class="wave" viewBox="0 0 140 1000" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M54,0 C30,120 90,240 50,360 C18,470 95,590 52,710 C22,820 85,910 48,1000 L0,1000 L0,0 Z" fill="#e9efdc"/>
        <path d="M54,0 C30,120 90,240 50,360 C18,470 95,590 52,710 C22,820 85,910 48,1000 L140,1000 L140,0 Z" fill="#5c7d5c"/>
        <path d="M88,0 C64,120 124,240 84,360 C52,470 129,590 86,710 C56,820 119,910 82,1000 L140,1000 L140,0 Z" fill="#f8f3ec"/>
        <path d="M0,986 L48,986 L48,1000 L0,1000 Z" fill="#e7dcc8"/>
    </svg>

    {{-- ================= RIGHT PANEL ================= --}}
    <div class="right-panel">
        <div class="blob blob-pink-lg"></div>
        <div class="blob blob-pink-sm"></div>
        <div class="blob blob-green"></div>
        <div class="blob blob-pink-corner"></div>

        <div class="form-wrap">
            <h1 class="welcome text-center mb-2">Welcome Back!</h1>
            <p class="welcome-sub text-center mb-4">Log in to see where your money went this month.</p>

            @if (session('status'))
                <div class="alert alert-success sw-alert mb-3">
                    <i class="bi bi-check-circle"></i>{{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger sw-alert mb-3">
                    <i class="bi bi-exclamation-circle"></i>{{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <div class="mb-3">
                    <label for="email" class="sw-label d-block">Email</label>
                    <div class="input-icon">
                        <i class="bi bi-person"></i>
                        <input id="email" type="email" name="email"
                               value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               required autocomplete="username" autofocus>
                    </div>
                    @error('email') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="sw-label d-block">Password</label>
                    <div class="input-icon has-toggle">
                        <i class="bi bi-lock"></i>
                        <input id="password" type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required autocomplete="current-password">
                        <button type="button" class="toggle-pass" id="togglePassword" aria-label="Toggle password visibility" aria-pressed="false">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    @error('password') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label check-label" for="remember">Remember me</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="forgot">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-sw-dark" id="loginBtn">Log in</button>

                <div class="divider-or">or</div>

                <button type="button" class="btn btn-google">
                    <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.5 6.1 29.5 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.7-.4-3.9z"/>
                        <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.5 6.1 29.5 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
                        <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/>
                        <path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.2 5.7l6.2 5.2C36.9 40.2 44 34 44 24c0-1.3-.1-2.7-.4-3.9z"/>
                    </svg>
                    Continue with Google
                </button>

                <div class="text-center mt-3 d-lg-none">
                    <span class="check-label">New here?</span>
                    <a href="{{ route('register') }}" class="forgot fw-semibold">Create an account</a>
                </div>
            </form>
        </div>

        <div class="sw-footer">© {{ date('Y') }} SpendWise — Spend Smart. Live Wise</div>
    </div>

</div>

<script>
    // Password visibility toggle
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        icon.className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
        this.setAttribute('aria-pressed', String(!showing));
    });

    // Loading state — prevents double submit
    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Logging in…';
    });
</script>

</body>
</html>