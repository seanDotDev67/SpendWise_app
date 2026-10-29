<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SpendWise - @yield('title', 'Dashboard')</title>
    <link rel="icon" href="{{ asset('images/spendwise-logo.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            /* Same viewport-based unit as the Categories page: 1 unit = 1% of the 1728px design width */
            --sw-u: max(min(1vw, 1.547vh), 5px);
            --sw-side-w: calc(var(--sw-u) * 19);
            --sw-bg: #e8e1cc;
            --sw-cream: #fffbe8;
            --sw-sidebar: #8b9b72;
            --sw-sidebar-dark: #7e8e65;
            --sw-dark: #2F4A3A;
            --sw-card: #FAFAF8;
            --sw-card-beige: #F4F0E4;
            --sw-pink: #FFE3E3;
            --sw-pink-dark: #EB6A78;
            --sw-track: #DAD8D2;
        }

        body { min-width: 320px; background: linear-gradient(90deg, var(--sw-cream) 0, var(--sw-cream) var(--sw-side-w), var(--sw-bg) 100%) fixed; animation: sw-fade .4s ease-out; color: var(--sw-dark); font-family: 'Inter', sans-serif; }
        .serif { font-family: 'Cormorant Garamond', serif; }
        .sw-app { min-height: 100vh; }
        .sw-main { min-width: 0; }
        @media (min-width: 992px) {
            .sw-app > .sw-main {
                flex: 0 0 calc(100% - var(--sw-side-w));
                width: calc(100% - var(--sw-side-w));
                margin-left: var(--sw-side-w);
            }
        }

        /* ================= SIDEBAR ================= */
        .sw-sidebar {
            position: fixed; inset: 0 auto 0 0; z-index: 1020;
            width: var(--sw-side-w); height: 100vh; height: 100dvh; flex-shrink: 0;
            background: var(--sw-sidebar); border-radius: 0 calc(var(--sw-u) * 3.7) calc(var(--sw-u) * 3.7) 0;
            display: flex; flex-direction: column; overflow: hidden;
        }
        .sw-brand {
            height: calc(var(--sw-u) * 7.3); flex: none; display: flex; align-items: center; gap: calc(var(--sw-u) * .9);
            padding: 0 calc(var(--sw-u) * 1) 0 calc(var(--sw-u) * 1.8); background: var(--sw-sidebar-dark);
            color: #fff; text-decoration: none;
        }
        .sw-brand .logo { width: calc(var(--sw-u) * 4); height: auto; display: block; }
        .sw-brand h1 { font-size: calc(var(--sw-u) * 2.4); font-weight: 600; line-height: 1; margin: 0; color: #fff; }
        .sw-brand small { display: block; font-size: calc(var(--sw-u) * .95); color: #f3f3e8; margin-top: calc(var(--sw-u) * .25); white-space: nowrap; }
        .sw-nav { margin-top: calc(var(--sw-u) * 4.6); flex: none; }
        .sw-nav a {
            position: relative;
            display: flex; align-items: center; gap: calc(var(--sw-u) * 2.2); height: calc(var(--sw-u) * 4.6); padding-left: calc(var(--sw-u) * 3.4);
            color: #fff; text-decoration: none; font-size: calc(var(--sw-u) * 1.35); font-weight: 500;
            transition: background .25s ease;
        }
        .sw-nav a i { font-size: calc(var(--sw-u) * 1.7); }
        .sw-nav a:hover { background: rgba(255, 255, 255, .08); }
        .sw-nav a.active {
            margin-left: calc(var(--sw-u) * 1.4); padding-left: calc(var(--sw-u) * 2);
            background: var(--sw-cream); border-radius: calc(var(--sw-u) * 2.3) 0 0 calc(var(--sw-u) * 2.3);
            color: var(--sw-dark); font-weight: 600;
        }
        .sw-nav a.active::before, .sw-nav a.active::after {
            content: ""; position: absolute; right: 0; width: calc(var(--sw-u) * 3.4); height: calc(var(--sw-u) * 3.4); pointer-events: none;
        }
        .sw-nav a.active::before { top: calc(var(--sw-u) * -3.4); background: radial-gradient(circle at 0 0, transparent calc(var(--sw-u) * 3.4), var(--sw-cream) calc(var(--sw-u) * 3.4)); }
        .sw-nav a.active::after { bottom: calc(var(--sw-u) * -3.4); background: radial-gradient(circle at 0 100%, transparent calc(var(--sw-u) * 3.4), var(--sw-cream) calc(var(--sw-u) * 3.4)); }
        .sw-logout { margin: auto 0 calc(var(--sw-u) * 3); }
        .sw-logout button {
            display: flex; width: 100%; align-items: center; gap: calc(var(--sw-u) * 2.2); height: calc(var(--sw-u) * 4.6); padding: 0 0 0 calc(var(--sw-u) * 3.4);
            border: 0; background: transparent; color: #fff; text-align: left; font: inherit; font-size: calc(var(--sw-u) * 1.35); font-weight: 500;
            transition: background .25s ease;
        }
        .sw-logout button i { font-size: calc(var(--sw-u) * 1.7); }
        .sw-logout button:hover { background: rgba(255, 255, 255, .08); }
        .sw-logout button:focus-visible { outline: 3px solid rgba(250, 247, 238, .8); outline-offset: -4px; }

        /* ================= TOPBAR ================= */
        .sw-topbar { border-bottom: 4px solid rgba(221, 213, 191, .7); }
        .sw-search { background: #D8D3BF; border-radius: 8px; padding: 8px 16px; color: #5c6b55; max-width: 375px; width: 100%; }
        .sw-search input { background: transparent; border: 0; outline: 0; width: 100%; font-size: 14px; }
        .sw-avatar { width: 40px; height: 40px; border-radius: 50%; background: #D96B7A; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 500; }
        .sw-profile-link { color: inherit; text-decoration: none; border-radius: 999px; }
        .sw-profile-link:hover { color: var(--sw-dark); }
        .sw-profile-link:focus-visible { outline: 3px solid rgba(47, 74, 58, .45); outline-offset: 4px; }

        /* ================= MOBILE NAV ================= */
        .sw-mobile-nav {
            display: flex; gap: 8px; overflow-x: auto; padding: 12px 2px 8px;
            scrollbar-width: thin; -webkit-overflow-scrolling: touch;
        }
        .sw-mobile-nav a {
            display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: 8px;
            flex: 0 0 auto; padding: 8px 14px; border-radius: 999px;
            background: rgba(250, 250, 248, .72); color: var(--sw-dark); font-size: 13px; text-decoration: none;
            transition: transform .2s ease, background-color .2s ease;
        }
        .sw-mobile-nav a.active { background: var(--sw-dark); color: #fff; }
        .sw-mobile-nav a:hover { transform: translateY(-1px); }

        /* ================= SHARED COMPONENTS ================= */
        .sw-card { background: var(--sw-card); border-radius: 12px; box-shadow: 0 2px 6px rgba(0, 0, 0, .08); padding: clamp(12px, 1.3vw, 16px); min-width: 0; transition: box-shadow .2s ease; }
        .sw-card:hover { box-shadow: 0 10px 26px rgba(47, 74, 58, .1); }
        .sw-card-beige { background: var(--sw-card-beige); border-radius: 12px; box-shadow: 0 2px 6px rgba(0, 0, 0, .08); }
        .sw-stat { border-radius: 12px; padding: clamp(12px, 1.3vw, 16px); height: 100%; min-width: 0; border: 1px solid rgba(47, 74, 58, .4); transition: box-shadow .2s ease; }
        .sw-stat:hover { box-shadow: 0 10px 26px rgba(47, 74, 58, .1); }
        .sw-stat.beige { background: var(--sw-card-beige); }
        .sw-stat.pink { background: var(--sw-pink); }
        .sw-stat.selected { border: 2px solid var(--sw-pink-dark); }
        .sw-stat-icon { width: 32px; height: 32px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .sw-stat-value { font-size: clamp(1.05rem, 1.6vw, 1.45rem); font-weight: 600; margin: 4px  0 12px; letter-spacing: -.035em; overflow-wrap: anywhere; }
        .dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; border: 1px solid rgba(0, 0, 0, .05); }
        .btn-sw-dark { background: var(--sw-dark); color: #fff; border-radius: 6px; padding: 12px; box-shadow: 0 2px 4px rgba(0, 0, 0, .25); transition: background-color .2s ease; }
        .btn-sw-dark:hover { background: #263d30; color: #fff; }
        .btn-sw-light { background: #EFEBDF; color: var(--sw-dark); border-radius: 6px; padding: 12px 24px; box-shadow: 0 2px 4px rgba(0, 0, 0, .15); transition: background-color .2s ease; }
        .btn-sw-light:hover { background: #e6e1d2; color: var(--sw-dark); }
        .btn-sw-dark:focus-visible, .btn-sw-light:focus-visible, .sw-nav a:focus-visible, .sw-mobile-nav a:focus-visible { outline: 3px solid rgba(47, 74, 58, .45); outline-offset: 3px; }

        /* ================= PAGE TRANSITION ================= */
        @keyframes sw-fade { from { opacity: 0; } to { opacity: 1; } }
        @keyframes sw-page-enter { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }

        /* ===== FIT-TO-ONE-SCREEN MODE (only when page passes ['fit' => true]) ===== */
        @if ($fit ?? false)
            html, body { height: 100%; overflow: hidden; }
            .sw-viewport { height: 100vh; height: 100dvh; overflow: hidden; }
            .sw-fit { transform-origin: top left; will-change: transform; }
            @media (max-width: 1199.98px) {
                html, body { height: auto; overflow: auto; }
                .sw-viewport { height: auto; overflow: visible; }
            }
        @endif

        /* ================= RESPONSIVE TOPBAR ================= */
        @media (max-width: 991.98px) {
            .sw-sidebar { display: none !important; }
            .sw-app > .sw-main { width: 100%; margin-left: 0; }
            .sw-main { padding-inline: 16px !important; }
            .sw-topbar { flex-wrap: wrap; gap: 12px !important; }
            .sw-topbar-actions { order: 1; width: 100%; justify-content: flex-end; }
            .sw-search { order: 2; flex: 1 1 100%; max-width: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; transition-duration: .01ms !important; }
            .sw-page-content { opacity: 1; transform: none; }
        }
    </style>

    @stack('styles')
</head>

@php
    $user = auth()->user();
    $navigationItems = [
        ['Dashboard', 'dashboard', 'bi-grid-1x2-fill'],
        ['Transactions', 'transactions.page', 'bi-arrow-left-right'],
        ['Categories', 'categories.index', 'bi-tag'],
        ['Budgets', 'budgets.index', 'bi-piggy-bank'],
        ['Reports', 'reports.index', 'bi-bar-chart'],
        ['Profile', 'profile.edit', 'bi-person-circle'],
    ];
@endphp

<body>
@if ($fit ?? false)
<div class="sw-viewport">
    <div class="sw-fit" id="swFit">
@endif
        <div class="sw-app d-flex">

            @include('layouts.partials.sidebar')

            {{-- ================= MAIN ================= --}}
            <main class="sw-main flex-grow-1 px-3 px-lg-4">
                @include('layouts.partials.header', ['user' => $user])

                <section class="sw-page-content">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show mt-3 mb-0" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @yield('content')
                </section>
            </main>
        </div>
@if ($fit ?? false)
    </div>
</div>
@endif

@include('layouts.partials.footer')
</body>
</html>