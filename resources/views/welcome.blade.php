<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#e4eadc">
    <title>SpendWise — Spend Smart. Live Wise</title>
    <link rel="icon" href="{{ asset('images/spendwise-logo.png') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;1,500;1,600;1,700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            /* 1u = 1% of the Figma frame width. Everything is sized in em inside .scene
       (1em = 1u), so the whole page scales as one piece and always fits the screen. */
            --u: max(min(1vw, 1.541vh), 3.2px);
            --wall: #e4eadc;
            --floor: #e8e4d2;
            --baseboard: #d6cdb1;
            --arch: #eedfe0;
            --green-dark: #3b4a3c;
            --green-darker: #2e3b30;
            --pink-text: #c18a93;
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
            background: var(--wall);
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            animation: fadeIn .5s ease-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        /* ============ STAGE ============ */
        .stage {
            position: relative;
            width: 100%;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
            background: var(--wall);
        }
        /* floor stretches the full screen width, starts where the Figma floor starts */
        .floor {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            top: calc(50% + var(--u) * 10.51);
            background: var(--floor);
            border-top: calc(var(--u) * .9) solid var(--baseboard);
            z-index: 0;
        }
        /* the Figma frame (100 x 64.9 units), centered. 1em inside = 1u */
        .scene {
            position: absolute;
            left: calc(50% - 50em);
            top: calc(50% - 32.45em);
            width: 100em;
            height: 64.9em;
            font-size: var(--u);
        }
        .scene>* {
            position: absolute;
        }
        .scene img {
            display: block;
            user-select: none;
            -webkit-user-drag: none;
        }
        /* ============ BACKGROUND DECOR ============ */
        .arch {
            left: 32.4em;
            top: 16.93em;
            width: 35.65em;
            height: 35.65em;
            border-radius: 50%;
            background: var(--arch);
            clip-path: inset(0 0 9.62em 0);
            /* cut off at the baseboard */
            z-index: 1;
        }
        .window {
            left: 16.4em;
            top: 3.03em;
            width: 12.48em;
            height: 15.86em;
            z-index: 2;
        }
        .window svg {
            display: block;
            width: 100%;
            height: 100%;
        }
        .painting {
            left: 69.3em;
            top: 5.8em;
            width: 15em;
            z-index: 2;
            filter: drop-shadow(.25em .35em .4em rgba(60, 60, 60, .25));
        }
        .chart {
            left: 83.25em;
            top: 20.8em;
            width: 10.25em;
            z-index: 2;
            filter: drop-shadow(.25em .35em .4em rgba(60, 60, 60, .25));
        }
        /* ============ TITLE ============ */
        .title-block {
            left: 0;
            top: 3.9em;
            width: 100em;
            text-align: center;
            z-index: 5;
        }
        .brand {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-weight: 600;
            font-size: 6.7em;
            line-height: 1;
            color: var(--green-dark);
        }
        .tagline {
            font-size: 1.45em;
            font-weight: 600;
            letter-spacing: .01em;
            color: var(--pink-text);
            margin-top: .3em;
        }
        /* ============ PLANT ============ */
        .plant-shadow {
            left: 10.7em;
            top: 46.1em;
            width: 7.3em;
            height: 1em;
            border-radius: 50%;
            background: rgba(120, 115, 95, .38);
            z-index: 2;
        }
        .plant {
            left: 7.8em;
            top: 22.13em;
            height: 24.7em;
            width: auto;
            z-index: 3;
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
        /* ============ PAPER + PEN ============ */
        .paper {
            left: 30.3em;
            top: 45.9em;
            width: 12.5em;
            z-index: 2;
        }
        .paper svg {
            display: block;
            width: 100%;
            height: auto;
        }
        /* ============ WALLET + COIN (hover zone) ============ */
        .wallet-zone {
            left: 42.87em;
            top: 25.95em;
            width: 15.8em;
            height: 23.6em;
            z-index: 3;
            cursor: pointer;
        }
        .wallet-zone>* {
            position: absolute;
        }
        .zone-shadow {
            left: .83em;
            top: 21.45em;
            width: 13em;
            height: 2em;
            border-radius: 50%;
            background: rgba(120, 115, 95, .4);
            z-index: 0;
        }
        /* window that hides the coin once it passes the wallet's top edge */
        .coin-window {
            left: 0;
            top: -2em;
            width: 15.8em;
            height: 12.6em;
            overflow: hidden;
            z-index: 1;
        }
        .coin-drop {
            position: absolute;
            left: 5.03em;
            top: 2em;
            width: 4.9em;
            transition: transform .65s cubic-bezier(.2, .9, .3, 1.25);
            /* coming back out (bouncy) */
        }
        .coin-float {
            animation: bob 3s ease-in-out infinite;
        }
        .coin-float img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 .25em .3em rgba(120, 100, 30, .25));
        }
        @keyframes bob {
            0%,
            100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-.55em);
            }
        }
        /* wallet + cream background that fills the cutouts */
        .wallet-wrap {
            left: 0;
            top: 9.15em;
            width: 15.8em;
            z-index: 2;
            transform-origin: bottom center;
        }
        .wallet-wrap img {
            position: relative;
            display: block;
            width: 100%;
            height: auto;
        }
        .wallet-fill {
            position: absolute;
            left: 7.8%;
            top: 13%;
            width: 78%;
            height: 75%;
            background: #f5f2e9;
            /* the cream/white seen in the Figma wallet */
            border-radius: 6% / 7%;
        }
        /* hover on the wallet / logo / coin  =>  coin drops in */
        .wallet-zone:hover .coin-drop,
        .wallet-zone.drop .coin-drop {
            transform: translateY(11.8em);
            transition: transform .5s cubic-bezier(.55, .05, .85, .45);
            /* falling (gravity) */
        }
        /* little "gulp" when the coin lands */
        .wallet-zone:hover .wallet-wrap,
        .wallet-zone.drop .wallet-wrap {
            animation: gulp .5s .42s ease-out;
        }
        @keyframes gulp {
            0% {
                transform: scale(1, 1);
            }
            35% {
                transform: scale(1.05, .94);
            }
            70% {
                transform: scale(.99, 1.03);
            }
            100% {
                transform: scale(1, 1);
            }
        }
        /* ============ COIN STACKS ============ */
        .stack-shadow {
            border-radius: 50%;
            background: rgba(120, 115, 95, .4);
            z-index: 1;
        }
        .ss-big {
            left: 69.8em;
            top: 46.25em;
            width: 7.8em;
            height: 1.1em;
        }
        .ss-right {
            left: 77.95em;
            top: 47.15em;
            width: 6.3em;
            height: 1em;
        }
        .ss-front {
            left: 73.45em;
            top: 50.35em;
            width: 5.9em;
            height: 1em;
        }
        .stack-big {
            left: 69.6em;
            top: 40em;
            width: 14.35em;
            height: auto;
            z-index: 2;
        }
        .stack-front {
            left: 73.8em;
            top: 47.2em;
            width: 5.23em;
            height: 3.51em;
            background: url('{{ asset('images/spendwise-coin-stacks.png') }}') no-repeat;
            background-size: 14.35em auto;
            background-position: -8.86em -3.98em;
            /* shows only the small stack from the PNG */
            z-index: 3;
        }
        /* ============ BUTTON + FEATURES ============ */
        .btn-start {
            left: 39.95em;
            top: 53.4em;
            width: 20.1em;
            height: 5.17em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .7em;
            border-radius: 99em;
            background: var(--green-dark);
            color: #fff;
            text-decoration: none;
            z-index: 6;
            transition: all .25s ease;
        }
        .btn-start .t {
            font-size: 1.95em;
            font-weight: 400;
            letter-spacing: .01em;
        }
        .btn-start .arrow {
            font-size: 1.95em;
            line-height: 1;
            transition: transform .25s ease;
        }
        .btn-start:hover {
            background: var(--green-darker);
            transform: translateY(-.3em);
            box-shadow: 0 .7em 1.4em rgba(59, 74, 60, .32);
        }
        .btn-start:hover .arrow {
            transform: translateX(.25em);
        }
        .btn-start:active {
            transform: translateY(0);
            box-shadow: none;
        }
        .btn-start:focus-visible {
            outline: 3px solid rgba(59, 74, 60, .4);
            outline-offset: 3px;
        }
        .features {
            left: 0;
            top: 60.85em;
            width: 100em;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.9em;
            z-index: 6;
        }
        .features .f {
            font-size: 1.45em;
            color: var(--green-dark);
        }
        .features .dot {
            width: .5em;
            height: .5em;
            border-radius: 50%;
            background: var(--green-dark);
        }
        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="stage">
        <div class="floor"></div>
        <div class="scene">
            {{-- ===== background decor ===== --}}
            <div class="arch"></div>
            <div class="window" aria-hidden="true">
                <svg viewBox="0 0 140 178" xmlns="http://www.w3.org/2000/svg">
                    <rect width="140" height="178" rx="16" fill="#fafafa" />
                    <rect x="11" y="11" width="118" height="156" rx="4" fill="#f1e2e2" />
                    <circle cx="83" cy="75" r="20" fill="#e6cd6c" />
                    <path
                        d="M11 128 C22 112 40 108 58 110 C78 112 96 98 112 102 C121 104 126 110 129 118 L129 167 L11 167 Z"
                        fill="#aab68e" />
                    <rect x="67" y="11" width="6" height="156" fill="#fafafa" />
                    <rect x="11" y="86" width="118" height="6" fill="#fafafa" />
                </svg>
            </div>
            <img class="painting" src="{{ asset('images/spendwise-painting-hills.png') }}" alt="">
            <img class="chart" src="{{ asset('images/spendwise-chart-frame.png') }}" alt="">
            {{-- ===== title ===== --}}
            <div class="title-block">
                <div class="brand">SpendWise</div>
                <div class="tagline">Spend Smart. Live Wise</div>
            </div>
            {{-- ===== plant ===== --}}
            <div class="plant-shadow"></div>
            <img class="plant" src="{{ asset('images/spendwise-plant.png') }}" alt="Plant">
            {{-- ===== paper + pen ===== --}}
            <div class="paper" aria-hidden="true">
                <svg viewBox="0 0 140 55" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="20,19 122,19 137,49 5,49" fill="#fafaf5" stroke="#d6cdb0" stroke-width="1.8"
                        stroke-linejoin="round" />
                    <line x1="34" y1="30" x2="112" y2="30" stroke="#d9d0b2" stroke-width="2" stroke-linecap="round" />
                    <line x1="30" y1="38" x2="116" y2="38" stroke="#d9d0b2" stroke-width="2" stroke-linecap="round" />
                    <line x1="44" y1="13" x2="122" y2="4" stroke="#d9a2a9" stroke-width="7" stroke-linecap="round" />
                </svg>
            </div>
            {{-- ===== wallet + coin: hover anywhere in this zone and the coin drops in ===== --}}
            <div class="wallet-zone" id="walletZone">
                <div class="zone-shadow"></div>
                <div class="coin-window">
                    <div class="coin-drop">
                        <div class="coin-float"><img src="{{ asset('images/spendwise-coin.png') }}" alt=""></div>
                    </div>
                </div>
                <div class="wallet-wrap">
                    <div class="wallet-fill"></div>
                    <img src="{{ asset('images/spendwise-logo.png') }}" alt="SpendWise wallet">
                </div>
            </div>
            {{-- ===== coin stacks ===== --}}
            <div class="stack-shadow ss-big"></div>
            <div class="stack-shadow ss-right"></div>
            <div class="stack-shadow ss-front"></div>
            <img class="stack-big" src="{{ asset('images/spendwise-coin-stacks.png') }}" alt="">
            <div class="stack-front" aria-hidden="true"></div>
            {{-- ===== call to action ===== --}}
            <a href="{{ route('login') }}" class="btn-start" id="startBtn">
                <span class="t">Get Started</span><span class="arrow">→</span>
            </a>
            <div class="features">
                <span class="f">Track Spending</span><span class="dot"></span>
                <span class="f">Set Budget</span><span class="dot"></span>
                <span class="f">Reach Goals</span>
            </div>
        </div>
    </div>
    <script>
        // Get Started: drop the coin into the wallet first, then go to the login page
        (function () {
            const btn = document.getElementById('startBtn');
            const zone = document.getElementById('walletZone');
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            btn.addEventListener('click', function (e) {
                if (reduce || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
                e.preventDefault();
                zone.classList.add('drop');
                setTimeout(function () { window.location.href = btn.href; }, 750);
            });
        })();
    </script>
</body>
</html>
```[cite: 1]