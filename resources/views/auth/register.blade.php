<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f3ec">
    <title>SpendWise — Create account</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='14' fill='%232f4a3c'/><rect x='16' y='36' width='7' height='14' rx='2' fill='%23f2c7c7'/><rect x='27' y='28' width='7' height='22' rx='2' fill='%23f2c7c7'/><rect x='38' y='20' width='7' height='30' rx='2' fill='%23ffffff'/></svg>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;1,500;1,600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* ============ DESIGN TOKENS (same as login) ============ */
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

        /* ============ LAYOUT (mirrored: form LEFT, brand RIGHT) ============ */
        .sw-split { display: flex; min-height: 100vh; position: relative; }

        /* ---------- LEFT: FORM PANEL (62%) ---------- */
        .form-panel {
            width: 62%;
            background: var(--sw-cream);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4vh 2rem 5vh;
        }
        .blob { position: absolute; border-radius: 50%; filter: blur(44px); pointer-events: none; z-index: 0; }
        .blob-pink-lg     { width: 320px; height: 320px; background: #f3cdcd; bottom: -100px; left: -80px; opacity: .55; }
        .blob-pink-sm     { width: 100px; height: 100px; background: #f3cdcd; top: 10vh; left: 16%; opacity: .5; }
        .blob-green       { width: 120px; height: 120px; background: #c2d1b8; bottom: -40px; left: 32%; opacity: .5; }
        .blob-pink-corner { width: 140px; height: 140px; background: #f3cdcd; top: -50px; right: 8%; opacity: .45; }

        .form-wrap { position: relative; z-index: 2; width: 100%; max-width: 420px; }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            color: var(--sw-pink-text);
            font-size: clamp(2rem, 2.6vw, 2.6rem);
        }
        .page-sub { font-size: .9rem; color: var(--sw-muted); line-height: 1.6; }

        .sw-label {
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;   /* delete this line if you want sentence-case like the mockup */
            color: var(--sw-muted);
            margin-bottom: 8px;
        }
        .check-label { font-size: .82rem; color: #555; }

        .sw-input {
            height: 46px;
            border-radius: 10px;
            border: 1px solid var(--sw-line);
            background: #fff;
            font-size: .9rem;
            transition: all .25s ease;
        }
        .sw-input:hover { border-color: #cfc5b4; }
        .sw-input:focus {
            border-color: var(--sw-green-dark);
            box-shadow: 0 0 0 .25rem rgba(47,74,60,.1);
        }
        .has-toggle .sw-input { padding-right: 2.8rem; }

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

        .tos-link {
            color: var(--sw-pink-text);
            text-decoration: underline;
            transition: color .2s ease;
        }
        .tos-link:hover { color: var(--sw-pink-dark); }

        .btn-sw-dark {
            background: var(--sw-green-dark);
            color: #fff;
            border: none;
            border-radius: 10px;
            height: 46px;
            min-width: 210px;
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

        .field-error { color: #c0392b; font-size: .75rem; margin-top: 5px; }

        .sw-footer {
            position: absolute;
            bottom: 14px; left: 0; right: 0;
            text-align: center;
            font-size: .72rem;
            color: #a89f92;
            z-index: 2;
            letter-spacing: .03em;
        }

        /* ---------- WAVE (same shape, colors flipped) ---------- */
        .wave {
            position: absolute;
            top: 0;
            left: calc(62% - 70px);
            width: 140px; height: 100%;
            z-index: 3;
            pointer-events: none;
        }

        /* ---------- RIGHT: BRAND PANEL (38%) ---------- */
        .brand-panel {
            width: 38%;
            background: var(--sw-sage);
            position: relative;
            padding: 10vh 3rem 0 3.5rem;
            text-align: center;
        }
        .brand-content { max-width: 320px; margin: 0 auto; position: relative; z-index: 2; }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.4rem, 3vw, 3rem);
            color: #2e2e2e;
            line-height: 1.1;
            letter-spacing: .01em;
        }
        .tagline { color: var(--sw-pink-text); font-size: .95rem; letter-spacing: .04em; }
        .divider { border: 0; border-top: 1px solid rgba(0,0,0,.14); margin: 3.2vh auto; max-width: 280px; }
        .already { font-family: 'Playfair Display', serif; color: var(--sw-green-dark); font-size: 1.5rem; }
        .already-p { font-size: .9rem; color: var(--sw-muted); max-width: 240px; margin: 0 auto; line-height: 1.6; }

        .btn-signin {
            background: var(--sw-pink);
            color: #4a3a3a;
            border: none;
            border-radius: 10px;
            padding: .6rem 2.2rem;
            font-weight: 500;
            letter-spacing: .02em;
            transition: all .25s ease;
        }
        .btn-signin:hover {
            background: var(--sw-pink-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0,0,0,.12);
            color: #4a3a3a;
        }
        .btn-signin:active { transform: translateY(0); box-shadow: none; }

        .floor {
            position: absolute;
            bottom: 0; left: 0;
            width: 100%; height: 14px;
            background: var(--sw-floor);
            z-index: 1;
        }
        .plant-wrap {
            position: absolute;
            bottom: 9px; left: 42%;
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

        /* Keyboard accessibility */
        .btn:focus-visible, .toggle-pass:focus-visible, .tos-link:focus-visible {
            outline: 3px solid rgba(47,74,60,.35);
            outline-offset: 2px;
            border-radius: 6px;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 991px) {
            .brand-panel, .wave { display: none; }
            .form-panel { width: 100%; }
        }
        @media (max-height: 700px) {
            .brand-panel { padding-top: 5vh; }
            .brand { font-size: 2.2rem; }
            .divider { margin: 2vh auto; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

<div class="sw-split">

    {{-- ================= LEFT: FORM PANEL ================= --}}
    <div class="form-panel">
        <div class="blob blob-pink-lg"></div>
        <div class="blob blob-pink-sm"></div>
        <div class="blob blob-green"></div>
        <div class="blob blob-pink-corner"></div>

        <div class="form-wrap">
            <h1 class="page-title text-center mb-2">Create an Account</h1>
            <p class="page-sub text-center mb-4">Start tracking your spending in minutes.</p>

            <form method="POST" action="{{ route('register') }}" id="registerForm">
                @csrf

                {{-- Backend expects ONE field called "name" — JS merges these two below on submit --}}
                <input type="hidden" name="name" id="name" value="{{ old('name') }}">

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label for="first_name" class="sw-label d-block">First name</label>
                        <input id="first_name" type="text" name="first_name"
                               value="{{ old('first_name') }}"
                               class="form-control sw-input" required autofocus autocomplete="given-name">
                    </div>
                    <div class="col-6">
                        <label for="last_name" class="sw-label d-block">Last name</label>
                        <input id="last_name" type="text" name="last_name"
                               value="{{ old('last_name') }}"
                               class="form-control sw-input" required autocomplete="family-name">
                    </div>
                </div>
                @error('name') <div class="field-error mb-3">{{ $message }}</div> @enderror

                <div class="mb-3">
                    <label for="email" class="sw-label d-block">Email</label>
                    <input id="email" type="email" name="email"
                           value="{{ old('email') }}"
                           class="form-control sw-input @error('email') is-invalid @enderror"
                           required autocomplete="email">
                    @error('email') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="sw-label d-block">Password</label>
                    <div class="position-relative has-toggle">
                        <input id="password" type="password" name="password"
                               class="form-control sw-input @error('password') is-invalid @enderror"
                               required autocomplete="new-password">
                        <button type="button" class="toggle-pass" data-target="password" aria-label="Toggle password visibility" aria-pressed="false">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="sw-label d-block">Confirm Password</label>
                    <div class="position-relative has-toggle">
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               class="form-control sw-input" required autocomplete="new-password">
                        <button type="button" class="toggle-pass" data-target="password_confirmation" aria-label="Toggle password visibility" aria-pressed="false">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
                    <label class="form-check-label check-label" for="terms">
                        I agree to the <a href="#" class="tos-link">Terms of Service</a> and <a href="#" class="tos-link">Privacy Policy</a>
                    </label>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-sw-dark" id="registerBtn">Create account</button>
                </div>

                <div class="text-center mt-3 d-lg-none">
                    <span class="check-label">Already have an account?</span>
                    <a href="{{ route('login') }}" class="tos-link fw-semibold">Sign in</a>
                </div>
            </form>
        </div>

        <div class="sw-footer">© {{ date('Y') }} SpendWise — Spend Smart. Live Wise</div>
    </div>

    {{-- ================= WAVE (same geometry as login, fills swapped) ================= --}}
    <svg class="wave" viewBox="0 0 140 1000" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M54,0 C30,120 90,240 50,360 C18,470 95,590 52,710 C22,820 85,910 48,1000 L0,1000 L0,0 Z" fill="#f8f3ec"/>
        <path d="M54,0 C30,120 90,240 50,360 C18,470 95,590 52,710 C22,820 85,910 48,1000 L140,1000 L140,0 Z" fill="#5c7d5c"/>
        <path d="M88,0 C64,120 124,240 84,360 C52,470 129,590 86,710 C56,820 119,910 82,1000 L140,1000 L140,0 Z" fill="#e9efdc"/>
        <path d="M82,986 L140,986 L140,1000 L82,1000 Z" fill="#e7dcc8"/>
    </svg>

    {{-- ================= RIGHT: BRAND PANEL ================= --}}
    <div class="brand-panel">
        <div class="brand-content">
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

            <div class="already">Already have an account?</div>
            <p class="already-p mt-2">Spend smart. Live wise. Let's check your month.</p>

            <a href="{{ route('login') }}" class="btn btn-signin mt-2">Sign in</a>
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

</div>

<script>
    // Eye toggles — works for both password fields
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

    // Merge First + Last into the hidden "name" field, then show loading state
    document.getElementById('registerForm').addEventListener('submit', function () {
        const first = document.getElementById('first_name').value.trim();
        const last = document.getElementById('last_name').value.trim();
        document.getElementById('name').value = [first, last].filter(Boolean).join(' ');

        const btn = document.getElementById('registerBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Creating account…';
    });
</script>

</body>
</html>