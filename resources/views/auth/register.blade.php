<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f3f1">
    <title>SpendWise — Create account</title>
    <link rel="icon" href="{{ asset('images/spendwise-logo.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>
        :root {
            /* 1u = 1% of the Figma frame. Everything is sized in u, so the whole page
               scales as one piece and always fits the screen (no overflow, no overlap). */
            --u: max(min(1vw, 1.553vh), 6.5px);

            --left-bg: #f5f3f1;
            --right-bg: #eef4e2;
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

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            height: 100%;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--ink);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            animation: fadeIn .4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        ::selection {
            background: var(--pink);
        }

        /* ============ STAGE ============ */
        .stage {
            position: relative;
            height: 100vh;
            min-height: 560px;
            background: var(--left-bg);
            overflow: hidden;
        }

        .layer {
            position: absolute;
            inset: 0;
        }

        .layer-left {
            background: var(--left-bg);
            clip-path: url(#clipLeft);
            display: flex;
            align-items: center;
        }

        .layer-right {
            background: var(--right-bg);
            clip-path: url(#clipRight);
        }

        /* ============ LEFT: circles ============ */
        .circle {
            position: absolute;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            filter: blur(3px);
            pointer-events: none;
        }

        .c-sage-lg {
            width: calc(var(--u) * 21);
            height: calc(var(--u) * 21);
            left: 57.5%;
            top: 13%;
            background: #d5dacb;
        }

        .c-pink-sm {
            width: calc(var(--u) * 5.4);
            height: calc(var(--u) * 5.4);
            left: 15%;
            top: 16%;
            background: #ecd9db;
        }

        .c-pink-lg {
            width: calc(var(--u) * 27);
            height: calc(var(--u) * 27);
            left: -1%;
            top: 71%;
            background: #efe3e2;
        }

        .c-pink-bt {
            width: calc(var(--u) * 24);
            height: calc(var(--u) * 24);
            left: 61%;
            top: 93%;
            background: #eddede;
        }

        .c-sage-sm {
            width: calc(var(--u) * 5.8);
            height: calc(var(--u) * 5.8);
            left: 28%;
            top: 93%;
            background: #d4dacb;
        }

        /* ============ LEFT: form (sized like the Figma) ============ */
        .form-area {
            position: relative;
            z-index: 2;
            width: 62%;
            display: flex;
            justify-content: center;
        }

        .form-wrap {
            width: calc(var(--u) * 38.3);
        }

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

        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: calc(var(--u) * 5);
        }

        .field {
            margin-bottom: calc(var(--u) * 1.8);
            position: relative;
        }

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
            box-shadow: 0 calc(var(--u) * .2) 0 var(--pink-line), 0 calc(var(--u) * .4) calc(var(--u) * .8) rgba(0, 0, 0, .04);
            transition: box-shadow .25s ease;
        }

        .sw-input:hover {
            box-shadow: 0 calc(var(--u) * .2) 0 #e3c3c8, 0 calc(var(--u) * .4) calc(var(--u) * .8) rgba(0, 0, 0, .05);
        }

        .sw-input:focus {
            outline: none;
            box-shadow: 0 calc(var(--u) * .2) 0 var(--pink-text), 0 0 0 3px rgba(194, 114, 125, .15);
        }

        .has-toggle .sw-input {
            padding-right: calc(var(--u) * 3.6);
        }

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

        .toggle-pass:hover {
            color: var(--green-dark);
        }

        .terms {
            display: flex;
            align-items: center;
            gap: calc(var(--u) * 1.5);
            margin: calc(var(--u) * .6) 0 calc(var(--u) * 2.9) calc(var(--u) * 1.1);
        }

        .terms input {
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

        .terms input:checked {
            background: var(--green-dark) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 8.5l3 3 6-7' fill='none' stroke='white' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/80% no-repeat;
            border-color: var(--green-dark);
        }

        .terms label {
            font-size: calc(var(--u) * 1.08);
            color: #444;
            cursor: pointer;
        }

        .tos-link {
            color: var(--pink-text);
            font-weight: 500;
            text-decoration: underline;
            transition: opacity .2s ease;
        }

        .tos-link:hover {
            opacity: .75;
        }

        .btn-create {
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

        .btn-create:hover {
            background: var(--green-darker);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 74, 60, .28);
        }

        .btn-create:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .btn-create:disabled {
            opacity: .75;
            transform: none;
            box-shadow: none;
            cursor: default;
        }

        .or-row {
            display: flex;
            align-items: center;
            gap: calc(var(--u) * 1.2);
            margin: calc(var(--u) * 1.6) 0;
        }

        .or-row::before,
        .or-row::after {
            content: '';
            flex: 1;
            border-top: 1px solid #ddd6d2;
        }

        .or-row span {
            font-size: calc(var(--u) * 1.05);
            color: #999;
        }

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

        .btn-google:hover {
            background: #e0c0c4;
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .1);
        }

        .btn-google:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .btn-google svg {
            width: calc(var(--u) * 1.7);
            height: auto;
        }

        .spinner {
            display: inline-block;
            width: 1em;
            height: 1em;
            margin-right: .5em;
            vertical-align: -.15em;
            border: 2px solid rgba(255, 255, 255, .4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .field-error {
            color: #c0392b;
            font-size: calc(var(--u) * .95);
            margin: 4px 0 0 calc(var(--u) * .7);
        }

        .mobile-signin {
            display: none;
            text-align: center;
            margin-top: 1.1rem;
            font-size: .9rem;
            color: #555;
        }

        /* ============ RIGHT: brand (sized like the Figma) ============ */
        .brand-content {
            position: absolute;
            left: 82.6%;
            top: calc(var(--u) * 11.5);
            transform: translateX(-50%);
            width: calc(var(--u) * 28);
            text-align: center;
            z-index: 2;
        }

        .logo {
            width: calc(var(--u) * 8.2);
            height: auto;
            display: block;
            margin: 0 auto calc(var(--u) * 1.2);
        }

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

        .divider {
            border: 0;
            border-top: 1px solid #d3d9c6;
            margin: calc(var(--u) * 2.6) 0 calc(var(--u) * 3.6);
        }

        .already {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 500;
            font-size: calc(var(--u) * 2.6);
            line-height: 1.1;
            color: #3a4a3a;
        }

        .already-p {
            font-size: calc(var(--u) * 1.42);
            line-height: 1.35;
            color: #444;
            text-align: right;
            max-width: calc(var(--u) * 20.5);
            margin: calc(var(--u) * 1.5) calc(var(--u) * 3) 0 auto;
        }

        .btn-signin {
            display: flex;
            align-items: center;
            justify-content: center;
            width: calc(var(--u) * 11.9);
            height: calc(var(--u) * 4);
            margin: calc(var(--u) * 2.1) calc(var(--u) * 2.1) 0 auto;
            border-radius: calc(var(--u) * 1.6);
            background: var(--pink);
            color: #333;
            font-size: calc(var(--u) * 1.65);
            text-decoration: none;
            transition: all .25s ease;
        }

        .btn-signin:hover {
            background: #e0c0c4;
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .1);
        }

        .btn-signin:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .floor {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: calc(var(--u) * 7.5);
            background: var(--floor);
            border-top: calc(var(--u) * .8) solid var(--floor-edge);
            z-index: 1;
        }

        .plant-wrap {
            position: absolute;
            left: 77%;
            bottom: calc(var(--u) * 3.6);
            transform: translateX(-50%);
            z-index: 2;
            filter: drop-shadow(0 6px 8px rgba(0, 0, 0, .08));
        }

        .plant {
            display: block;
            height: calc(var(--u) * 15);
            width: auto;
            transform-origin: bottom center;
        }

        .plant:hover {
            animation: sway 1.2s ease-in-out infinite;
        }

        @keyframes sway {

            0%,
            100% {
                transform: rotate(0deg);
            }

            30% {
                transform: rotate(-3deg);
            }

            70% {
                transform: rotate(3deg);
            }
        }

        /* ============ WAVE LINE (thin S-curve like the Figma) ============ */
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

        .toggle-pass:focus-visible,
        .tos-link:focus-visible,
        .btn-signin:focus-visible,
        .btn-create:focus-visible,
        .btn-google:focus-visible {
            outline: 3px solid rgba(59, 74, 60, .35);
            outline-offset: 2px;
            border-radius: 8px;
        }

        /* ============ MOBILE / TABLET ============ */
        @media (max-width: 991px) {
            :root {
                --u: 3.1vw;
            }

            .stage {
                height: auto;
                min-height: 100vh;
            }

            .layer-left {
                position: relative;
                min-height: 100vh;
                clip-path: none;
                padding: 3rem 0;
                justify-content: center;
            }

            .form-area {
                width: 100%;
                padding: 0 1.5rem;
            }

            .form-wrap {
                width: 100%;
                max-width: 440px;
            }

            .layer-right,
            .wave-line {
                display: none;
            }

            .mobile-signin {
                display: block;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition: none !important;
            }
        }

        /* ============ TERMS & PRIVACY MODAL ============ */
        .legal-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(52, 77, 61, .5);
        }

        .legal-overlay.open {
            display: flex;
            animation: legalFade .25s ease-out;
        }

        @keyframes legalFade {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .legal-modal {
            display: flex;
            flex-direction: column;
            width: min(calc(var(--u) * 30.3), 92vw);
            min-width: 300px;
            max-height: 88vh;
            padding: calc(var(--u) * 1.5) calc(var(--u) * 2.3) calc(var(--u) * 1.6);
            background: var(--left-bg);
            border-radius: calc(var(--u) * 2.2);
            box-shadow: 0 12px 40px rgba(0, 0, 0, .28);
            animation: legalPop .3s cubic-bezier(.2, .8, .3, 1);
        }

        @keyframes legalPop {
            from {
                transform: translateY(14px) scale(.97);
                opacity: 0;
            }

            to {
                transform: none;
                opacity: 1;
            }
        }

        .legal-title {
            margin: 0 0 calc(var(--u) * 1);
            text-align: center;
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-weight: 600;
            font-size: calc(var(--u) * 2.75);
            line-height: 1.1;
            color: #2d3a30;
        }

        /* tabs */
        .legal-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #f0e2e5;
            border-radius: 999px;
            margin-bottom: calc(var(--u) * 1.4);
        }

        .legal-tab {
            height: calc(var(--u) * 3.6);
            border: none;
            border-radius: 999px;
            background: transparent;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: max(calc(var(--u) * 1.5), 13px);
            cursor: pointer;
            transition: background .25s ease, color .25s ease;
        }

        .legal-tab.active {
            background: var(--green-dark);
        }

        /* content */
        .legal-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding-right: calc(var(--u) * .6);
            scrollbar-width: thin;
            scrollbar-color: #cfc9bf transparent;
        }

        .legal-item {
            margin-bottom: calc(var(--u) * 1.15);
        }

        .legal-item h3 {
            margin: 0 0 calc(var(--u) * .25);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: max(calc(var(--u) * 1.3), 13px);
            color: #344d3d;
        }

        .legal-item p {
            margin: 0 0 0 calc(var(--u) * 1);
            font-size: max(calc(var(--u) * 1), 12px);
            line-height: 1.4;
            color: #666;
        }

        /* footer buttons */
        .legal-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: calc(var(--u) * 1.4);
            margin-top: calc(var(--u) * 1);
            padding-top: calc(var(--u) * 1.4);
            border-top: 1px solid #d9d3cc;
        }

        .legal-btn {
            height: calc(var(--u) * 3.6);
            border-radius: calc(var(--u) * 1.2);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: max(calc(var(--u) * 1.4), 13px);
            cursor: pointer;
            transition: all .25s ease;
        }

        .legal-btn-close {
            background: transparent;
            border: 1.5px solid #e8a8b0;
            color: #d4707e;
        }

        .legal-btn-close:hover {
            background: #fbeff1;
        }

        .legal-btn-agree {
            background: var(--green-dark);
            border: 1.5px solid var(--green-dark);
            color: #fff;
        }

        .legal-btn-agree:hover {
            background: var(--green-darker);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(59, 74, 60, .25);
        }

        .legal-tab:focus-visible,
        .legal-btn:focus-visible {
            outline: 3px solid rgba(59, 74, 60, .35);
            outline-offset: 2px;
        }
    </style>
</head>

<body>

    {{-- Clip shapes: normalized 0–1, cut along the CENTER of the wave --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
        <defs>
            <clipPath id="clipRight" clipPathUnits="objectBoundingBox">
                <path
                    d="M0.609,0 C0.612,0.1094 0.656,0.1484 0.656,0.2891 C0.656,0.4141 0.594,0.4063 0.594,0.5234 C0.594,0.6563 0.719,0.6563 0.719,0.7891 C0.719,0.8906 0.680,0.9531 0.656,1 L1,1 L1,0 Z" />
            </clipPath>
            <clipPath id="clipLeft" clipPathUnits="objectBoundingBox">
                <path
                    d="M0.609,0 C0.612,0.1094 0.656,0.1484 0.656,0.2891 C0.656,0.4141 0.594,0.4063 0.594,0.5234 C0.594,0.6563 0.719,0.6563 0.719,0.7891 C0.719,0.8906 0.680,0.9531 0.656,1 L0,1 L0,0 Z" />
            </clipPath>
        </defs>
    </svg>

    <div class="stage">

        {{-- ================= LEFT: FORM ================= --}}
        <div class="layer layer-left">
            <div class="circle c-sage-lg"></div>
            <div class="circle c-pink-sm"></div>
            <div class="circle c-pink-lg"></div>
            <div class="circle c-pink-bt"></div>
            <div class="circle c-sage-sm"></div>

            <div class="form-area">
                <div class="form-wrap">
                    <h1 class="page-title">Create an Account</h1>
                    <p class="page-sub">Start tracking your spending in minutes.</p>

                    <form method="POST" action="{{ route('register') }}" id="registerForm">
                        @csrf

                        {{-- Backend expects ONE field called "name" — JS merges first + last on submit --}}
                        <input type="hidden" name="name" id="name" value="{{ old('name') }}">

                        <div class="row-2">
                            <div class="field">
                                <label for="first_name" class="sw-label">First name</label>
                                <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}"
                                    class="sw-input" required autofocus autocomplete="given-name">
                            </div>
                            <div class="field">
                                <label for="last_name" class="sw-label">Last name</label>
                                <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}"
                                    class="sw-input" required autocomplete="family-name">
                            </div>
                        </div>
                        @error('name')
                        <div class="field-error">{{ $message }}</div> @enderror

                        <div class="field">
                            <label for="email" class="sw-label">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="sw-input"
                                required autocomplete="email">
                            @error('email')
                            <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="field has-toggle">
                            <label for="password" class="sw-label">Password</label>
                            <input id="password" type="password" name="password" class="sw-input" required
                                autocomplete="new-password">
                            <button type="button" class="toggle-pass" data-target="password"
                                aria-label="Toggle password visibility" aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('password')
                            <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="field has-toggle">
                            <label for="password_confirmation" class="sw-label">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                class="sw-input" required autocomplete="new-password">
                            <button type="button" class="toggle-pass" data-target="password_confirmation"
                                aria-label="Toggle password visibility" aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <div class="terms">
                            <input type="checkbox" name="terms" id="terms" required>
                            <label for="terms">
                                I agree to the <a href="#" class="tos-link">Terms of Service</a> and <a href="#"
                                    class="tos-link">Privacy Policy</a>
                            </label>
                        </div>
                        @error('terms')
                        <div class="field-error">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="btn-create" id="registerBtn">Create account</button>

                        <div class="or-row"><span>or</span></div>

                        <a href="{{ route('auth.google.redirect') }}" class="btn-google">
                            Continue with Google
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                        </a>

                        <div class="mobile-signin">
                            Already have an account?
                            <a href="{{ route('login') }}" class="tos-link">Sign in</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ================= RIGHT: BRAND ================= --}}
        <div class="layer layer-right">
            <div class="brand-content">
                <img class="logo" src="{{ asset('images/spendwise-logo.png') }}" alt="SpendWise logo">

                <div class="brand">SpendWise</div>
                <div class="tagline">Spend Smart. Live Wise</div>

                <hr class="divider">

                <div class="already">Already have an account?</div>
                <p class="already-p">Spend smart. Live wise. Let's check your month.</p>

                <a href="{{ route('login') }}" class="btn-signin">Sign in</a>
            </div>

            <div class="floor"></div>

            <div class="plant-wrap">
                <img class="plant" src="{{ asset('images/spendwise-plant.png') }}" alt="">
            </div>
        </div>

        {{-- ================= WAVE ================= --}}
        <svg class="wave-line" viewBox="0 0 1000 640" preserveAspectRatio="none" aria-hidden="true">
            <path
                d="M607,-20 L609,0 C612,70 656,95 656,185 C656,265 594,260 594,335 C594,420 719,420 719,505 C719,570 680,610 656,640 L640,660" />
        </svg>

    </div>

    {{-- ================= TERMS & PRIVACY MODAL ================= --}}
    <div class="legal-overlay" id="legalOverlay" aria-hidden="true">
        <div class="legal-modal" role="dialog" aria-modal="true" aria-labelledby="legalTitle">
            <h2 class="legal-title" id="legalTitle">Terms and Privacy</h2>

            <div class="legal-tabs" role="tablist">
                <button type="button" class="legal-tab active" role="tab" data-tab="terms"
                    aria-selected="true">Terms</button>
                <button type="button" class="legal-tab" role="tab" data-tab="privacy"
                    aria-selected="false">Privacy</button>
            </div>

            <div class="legal-body">
                {{-- TERMS --}}
                <div class="legal-panel" data-panel="terms">
                    <div class="legal-item">
                        <h3>1. Using SpendWise</h3>
                        <p>SpendWise helps you track income, expenses, budgets, and goals. You agree to use it only for
                            your own personal budgeting.</p>
                    </div>
                    <div class="legal-item">
                        <h3>2. Your account</h3>
                        <p>Keep your password private. You are responsible for activity under your account, so tell us
                            if you think someone else has access.</p>
                    </div>
                    <div class="legal-item">
                        <h3>3. Your financial data</h3>
                        <p>You enter your own transactions and budgets. SpendWise does not connect to your bank, and
                            nothing here is financial advice.</p>
                    </div>
                    <div class="legal-item">
                        <h3>4. SpendWise AI</h3>
                        <p>Insights are generated from the numbers you record. They are suggestions only and may not
                            always be accurate.</p>
                    </div>
                    <div class="legal-item">
                        <h3>5. Acceptable use</h3>
                        <p>Do not try to break, copy, or misuse the app, or use it to harm other people.</p>
                    </div>
                    <div class="legal-item">
                        <h3>6. Changes</h3>
                        <p>We may update these terms as the app grows. We will let you know about important changes.</p>
                    </div>
                </div>

                {{-- PRIVACY --}}
                <div class="legal-panel" data-panel="privacy" hidden>
                    <div class="legal-item">
                        <h3>What we collect</h3>
                        <p>Your name, email, and the transactions, budgets, and goals you add yourself.</p>
                    </div>
                    <div class="legal-item">
                        <h3>How we use it</h3>
                        <p>To show your dashboard, reports, and AI insights. We do not sell your data.</p>
                    </div>
                    <div class="legal-item">
                        <h3>How we protect it</h3>
                        <p>Passwords are stored encrypted, and your data is only visible to you when you are logged in.
                        </p>
                    </div>
                    <div class="legal-item">
                        <h3>Your choices</h3>
                        <p>You can edit your profile, export your records, or delete your account and data anytime.</p>
                    </div>
                    <div class="legal-item">
                        <h3>Contact</h3>
                        <p>Questions? Reach the SpendWise team through the Help section in your profile.</p>
                    </div>
                </div>
            </div>

            <div class="legal-actions">
                <button type="button" class="legal-btn legal-btn-close" id="legalClose">Close</button>
                <button type="button" class="legal-btn legal-btn-agree" id="legalAgree">I agree</button>
            </div>
        </div>
    </div>
    <script>
        // Eye toggles for both password fields
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
            btn.innerHTML = '<span class="spinner" aria-hidden="true"></span>Creating account…';
        });
    </script>

    <script>
        (function () {
            const overlay = document.getElementById('legalOverlay');
            const modal = overlay.querySelector('.legal-modal');
            const tabs = overlay.querySelectorAll('.legal-tab');
            const panels = overlay.querySelectorAll('.legal-panel');
            const body = overlay.querySelector('.legal-body');
            const checkbox = document.getElementById('terms');
            let lastFocus = null;

            function showTab(name) {
                tabs.forEach(function (t) {
                    const on = t.dataset.tab === name;
                    t.classList.toggle('active', on);
                    t.setAttribute('aria-selected', String(on));
                });
                panels.forEach(function (p) { p.hidden = p.dataset.panel !== name; });
                body.scrollTop = 0;
            }

            function openModal(tab) {
                lastFocus = document.activeElement;
                showTab(tab);
                overlay.classList.add('open');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                document.getElementById('legalAgree').focus();
            }

            function closeModal() {
                overlay.classList.remove('open');
                overlay.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                if (lastFocus) lastFocus.focus();
            }

            // Hook the two existing links: 1st = Terms of Service, 2nd = Privacy Policy
            const links = document.querySelectorAll('.terms .tos-link');
            if (links[0]) links[0].addEventListener('click', function (e) { e.preventDefault(); openModal('terms'); });
            if (links[1]) links[1].addEventListener('click', function (e) { e.preventDefault(); openModal('privacy'); });

            tabs.forEach(function (t) { t.addEventListener('click', function () { showTab(t.dataset.tab); }); });

            document.getElementById('legalClose').addEventListener('click', closeModal);
            document.getElementById('legalAgree').addEventListener('click', function () {
                if (checkbox) checkbox.checked = true;   // ticks "I agree to the Terms..." for them
                closeModal();
            });

            // click the dark backdrop to close
            overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });

            // Esc closes, Tab stays inside the modal
            document.addEventListener('keydown', function (e) {
                if (!overlay.classList.contains('open')) return;
                if (e.key === 'Escape') { closeModal(); return; }
                if (e.key === 'Tab') {
                    const f = modal.querySelectorAll('button');
                    const first = f[0], last = f[f.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            });
        })();
    </script>

</body>

</html>