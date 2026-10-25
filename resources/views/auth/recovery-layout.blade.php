<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f3f1">
    <title>SpendWise — @yield('title')</title>
    <link rel="icon" href="{{ asset('images/spendwise-logo.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --u: max(min(1vw, 1.553vh), 6.5px);
            --left-bg: #f5f3f1;
            --green-dark: #3b4a3c;
            --green-darker: #2e3b30;
            --pink: #e8d0d3;
            --pink-text: #c2727d;
            --pink-line: #ecd2d5;
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

        .stage {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: var(--left-bg);
            overflow: hidden;
        }
        .circle { position: absolute; border-radius: 50%; transform: translate(-50%, -50%); filter: blur(3px); pointer-events: none; }
        .c-sage-lg { width: calc(var(--u) * 21);  height: calc(var(--u) * 21);  left: 88%; top: 13%; background: #d5dacb; }
        .c-pink-sm { width: calc(var(--u) * 5.4); height: calc(var(--u) * 5.4); left: 15%; top: 16%; background: #ecd9db; }
        .c-pink-lg { width: calc(var(--u) * 27);  height: calc(var(--u) * 27);  left: -1%; top: 71%; background: #efe3e2; }
        .c-pink-bt { width: calc(var(--u) * 24);  height: calc(var(--u) * 24);  left: 92%; top: 93%; background: #eddede; }
        .c-sage-sm { width: calc(var(--u) * 5.8); height: calc(var(--u) * 5.8); left: 28%; top: 93%; background: #d4dacb; }

        .form-wrap { position: relative; z-index: 2; width: 100%; max-width: calc(var(--u) * 38.3); min-width: min(100%, 300px); }

        /* step indicator + illustration */
        .steps { display: flex; justify-content: center; gap: calc(var(--u) * .7); margin-bottom: calc(var(--u) * 1.4); }
        .steps span { width: calc(var(--u) * 2.4); height: max(calc(var(--u) * .55), 4px); border-radius: 99px; background: #e3d9d9; }
        .steps span.on { background: var(--pink-text); }
        .hero { display: block; width: calc(var(--u) * 14); min-width: 120px; height: auto; margin: 0 auto calc(var(--u) * 1.2); }

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
            line-height: 1.5;
            color: var(--ink);
            text-align: center;
            max-width: calc(var(--u) * 33);
            margin: calc(var(--u) * .9) auto calc(var(--u) * 3);
        }
        .page-sub strong { font-weight: 600; }

        .notice {
            margin: 0 0 calc(var(--u) * 2);
            padding: calc(var(--u) * 1.1) calc(var(--u) * 1.5);
            border-radius: calc(var(--u) * 1);
            background: #e6eed8;
            color: var(--green-dark);
            font-size: calc(var(--u) * 1.15);
            line-height: 1.45;
            text-align: center;
        }

        .field { margin-bottom: calc(var(--u) * 1.9); position: relative; }
        .sw-label { display: block; font-size: calc(var(--u) * 1.08); line-height: 1.2; color: var(--ink); margin: 0 0 calc(var(--u) * .4) calc(var(--u) * .7); }
        .sw-input {
            width: 100%;
            height: calc(var(--u) * 3);
            min-height: 38px;
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
        .sw-input:focus { outline: none; box-shadow: 0 calc(var(--u) * .2) 0 var(--pink-text), 0 0 0 3px rgba(194,114,125,.15); }
        input:-webkit-autofill, input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--ink);
            -webkit-box-shadow: 0 0 0 1000px #fff inset;
            transition: background-color 5000s ease-in-out 0s;
        }
        .has-toggle .sw-input { padding-right: calc(var(--u) * 3.6); }
        .toggle-pass {
            position: absolute;
            right: calc(var(--u) * 1.2);
            bottom: calc(var(--u) * .75);
            background: none; border: none; padding: 0;
            font-size: max(calc(var(--u) * 1.3), 15px);
            line-height: 1;
            color: #a5a5a5;
            cursor: pointer;
            transition: color .2s ease;
        }
        .toggle-pass:hover { color: var(--green-dark); }

        /* 6-digit code boxes */
        .otp { display: grid; grid-template-columns: repeat(6, 1fr); gap: calc(var(--u) * 1.2); margin-bottom: calc(var(--u) * 1.4); }
        .otp-box {
            width: 100%;
            height: calc(var(--u) * 5.6);
            min-height: 48px;
            padding: 0;
            border: none;
            border-radius: calc(var(--u) * 1);
            background: #fff;
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: calc(var(--u) * 2.4);
            color: var(--ink);
            box-shadow: 0 calc(var(--u) * .2) 0 var(--pink-line), 0 calc(var(--u) * .4) calc(var(--u) * .8) rgba(0,0,0,.04);
            transition: box-shadow .25s ease;
        }
        .otp-box:focus { outline: none; box-shadow: 0 calc(var(--u) * .2) 0 var(--pink-text), 0 0 0 3px rgba(194,114,125,.15); }

        .btn-main {
            display: block;
            margin: calc(var(--u) * 2.6) auto 0;
            width: calc(var(--u) * 20.6);
            min-width: 180px;
            height: calc(var(--u) * 3.6);
            min-height: 40px;
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
        .btn-main:hover { background: var(--green-darker); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(59,74,60,.28); }
        .btn-main:active { transform: translateY(0); box-shadow: none; }
        .btn-main:disabled { opacity: .75; transform: none; box-shadow: none; cursor: default; }
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

        .back-link, .link-btn {
            font-family: 'Inter', sans-serif;
            font-size: calc(var(--u) * 1.15);
            font-weight: 500;
            color: var(--pink-text);
            text-decoration: underline;
            cursor: pointer;
            transition: opacity .2s ease;
        }
        .back-link { display: block; width: fit-content; margin: calc(var(--u) * 2) auto 0; }
        .link-btn { background: none; border: none; padding: 0; }
        .back-link:hover, .link-btn:hover { opacity: .75; }
        .link-btn:disabled { opacity: .5; cursor: default; text-decoration: none; }
        .resend { display: flex; justify-content: center; align-items: center; gap: .5em; margin-top: calc(var(--u) * 2); font-size: calc(var(--u) * 1.15); color: var(--ink); }

        .field-error { color: #c0392b; font-size: calc(var(--u) * 1.0); margin: 5px 0 0 calc(var(--u) * .7); }
        .field-error.center { text-align: center; margin-left: 0; }

        .back-link:focus-visible, .link-btn:focus-visible, .btn-main:focus-visible {
            outline: 3px solid rgba(59,74,60,.35); outline-offset: 2px; border-radius: 8px;
        }
        @media (max-width: 991px) { :root { --u: 3.1vw; } .form-wrap { max-width: 440px; } }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
    </style>
</head>
<body>

<div class="stage">
    <div class="circle c-sage-lg"></div>
    <div class="circle c-pink-sm"></div>
    <div class="circle c-pink-lg"></div>
    <div class="circle c-pink-bt"></div>
    <div class="circle c-sage-sm"></div>

    <div class="form-wrap">
        @yield('content')
    </div>
</div>

@stack('scripts')

<script>
    // spinner on any form that has data-loading="..."
    document.querySelectorAll('form[data-loading]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return;
            const b = f.querySelector('.btn-main');
            if (!b) return;
            b.disabled = true;
            b.innerHTML = '<span class="spinner" aria-hidden="true"></span>' + f.dataset.loading;
        });
    });
</script>
</body>
</html>                                            