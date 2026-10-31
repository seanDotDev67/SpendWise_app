@php
    //  views/budgets/index.blade.php
    $demo = !isset($categories);
    $r = fn($name) => Route::has($name) ? route($name) : '#';

    /*
     * Months shown in the switcher (oldest -> newest, newest = current month).
     * Each: ['key' => '2026-10', 'label' => 'October 2026', 'days' => 31, 'day' => 26, 'cur' => true]
     * Past months: day = days, cur = false.
     */
    if (!isset($months)) {
        $months = collect([2, 1, 0])->map(function ($i) {
            $d = now()->startOfMonth()->subMonths($i);
            return [
                'key' => $d->format('Y-m'),
                'label' => $d->format('F Y'),
                'days' => $d->daysInMonth,
                'day' => $i === 0 ? now()->day : $d->daysInMonth,
                'cur' => $i === 0,
            ];
        })->all();
    }

    /*
     * Categories (expense only). limit = null means "no budget yet".
     * spent = [ 'YYYY-MM' => amount, ... ]
     */
    if ($demo) {
        [$m1, $m2, $m3] = array_column($months, 'key');
        $categories = [
            ['id' => 1, 'name' => 'Food', 'icon' => 'tools-kitchen-2', 'color' => 'forest', 'limit' => 3000, 'spent' => [$m1 => 2500, $m2 => 2250, $m3 => 2400]],
            ['id' => 2, 'name' => 'Transportation', 'icon' => 'bus', 'color' => 'sage', 'limit' => 2000, 'spent' => [$m1 => 1200, $m2 => 980, $m3 => 1000]],
            ['id' => 3, 'name' => 'School', 'icon' => 'school', 'color' => 'rose', 'limit' => 1500, 'spent' => [$m1 => 1500, $m2 => 700, $m3 => 800]],
            ['id' => 4, 'name' => 'Entertainment', 'icon' => 'device-gamepad-2', 'color' => 'blush', 'limit' => 700, 'spent' => [$m1 => 650, $m2 => 550, $m3 => 600]],
            ['id' => 5, 'name' => 'Others', 'icon' => 'dots', 'color' => 'taupe', 'limit' => 400, 'spent' => [$m1 => 250, $m2 => 400, $m3 => 350]],
            ['id' => 6, 'name' => 'Health', 'icon' => 'heart', 'color' => 'gold', 'limit' => null, 'spent' => [$m1 => 0, $m2 => 0, $m3 => 0]],
        ];
    }

    $uname = auth()->check() ? auth()->user()->name : 'Sean Andrei Lapaz';
    $uemail = auth()->check() ? auth()->user()->email : 'sean.lapaz@example.com';
    $initials = collect(preg_split('/\s+/', trim($uname)))->filter()->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    $profilePhotoPath = auth()->user()?->profile_photo_path;
    $profilePhotoUrl = $profilePhotoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($profilePhotoPath) : null;

    $transactionsUrl = Route::has('transactions.page') ? route('transactions.page') : $r('transactions.index');
    $profileUrl = $r('profile.edit');
    $logoutUrl = Route::has('logout') ? route('logout') : '#';

    // [label, bootstrap icon, link, active?]  (same icons as the dashboard sidebar)
    $nav = [
        ['Dashboard', 'grid-1x2-fill', $r('dashboard'), false],
        ['Transactions', 'arrow-left-right', $transactionsUrl, false],
        ['Categories', 'tag', $r('categories.index'), false],
        ['Budgets', 'piggy-bank', $r('budgets.index'), true],
        ['Reports', 'bar-chart', $r('reports.index'), false],
        ['Profile', 'person-circle', $profileUrl, false],
    ];

    // budgets are keyed by category id: store (POST), update (PUT), destroy (DELETE)
    $storeUrl = Route::has('budgets.store') ? route('budgets.store') : '#';
    $updateUrl = Route::has('budgets.update') ? route('budgets.update', ['category' => '__ID__']) : '#';
    $destroyUrl = Route::has('budgets.destroy') ? route('budgets.destroy', ['category' => '__ID__']) : '#';

    /*
     * Savings goals. Each: ['id', 'name', 'icon', 'target', 'saved', 'deadline' (Y-m-d or null)]
     * Empty by default; the controller passes the real ones.
     */
    $goals = $goals ?? [];
    $goalStoreUrl = Route::has('goals.store') ? route('goals.store') : '#';
    $goalUpdateUrl = Route::has('goals.update') ? route('goals.update', ['goal' => '__ID__']) : '#';
    $goalDestroyUrl = Route::has('goals.destroy') ? route('goals.destroy', ['goal' => '__ID__']) : '#';
    $goalFundUrl = Route::has('goals.fund') ? route('goals.fund', ['goal' => '__ID__']) : '#';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SpendWise — Budgets</title>
    <link rel="icon" href="{{ asset('images/spendwise-logo.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>
        :root {
            /* 1rem = 1% of the Figma frame width (1728px). The whole page scales as one piece. */
            --u: max(min(1vw, 1.547vh), 5px);

            --cream: #fffbe8;
            --beige: #e8e1cc;
            --sage: #8b9b72;
            --sage-dark: #7e8e65;
            --green: #3b4a3c;
            --green-d: #2e3b30;
            --ink: #47564a;
            --muted: #8a8a78;
            --pink: #c0707d;
            --line: #e3e0d2;
        }

        * { box-sizing: border-box; }
        html { font-size: var(--u); }
        html, body { margin: 0; }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 1.3rem;
            color: var(--green);
            background: var(--cream);
            -webkit-font-smoothing: antialiased;
            animation: pageIn .4s ease-out;
        }

        @keyframes pageIn { from { opacity: 0; } to { opacity: 1; } }
        button { font-family: inherit; }

        .app {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(90deg, var(--cream) 0, var(--cream) 19rem, var(--beige) 100%);
        }

        /* ============ SIDEBAR (sticky) — copied from categories page ============ */
        .side {
            width: 19rem;
            flex: none;
            position: sticky;
            top: 0;
            height: 100vh;
            align-self: flex-start;
            background: var(--sage);
            border-radius: 0 3.7rem 3.7rem 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .side-head {
            height: 7.3rem;
            flex: none;
            display: flex;
            align-items: center;
            gap: .9rem;
            padding: 0 1rem 0 1.8rem;
            background: var(--sage-dark);
            text-decoration: none;
        }

        .side-head img { width: 4rem; height: auto; display: block; }

        .brand {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 2.4rem;
            line-height: 1;
            color: #fff;
        }

        .tagline { font-size: .95rem; color: #f3f3e8; margin-top: .25rem; white-space: nowrap; }

        .side nav { flex: none; margin-top: 4.6rem; }

        .side nav a {
            position: relative;
            display: flex;
            align-items: center;
            gap: 2.2rem;
            height: 4.6rem;
            padding-left: 3.4rem;
            color: #fff;
            font-size: 1.35rem;
            font-weight: 500;
            text-decoration: none;
            transition: background .25s ease;
        }

        .side nav a i { font-size: 1.7rem; }
        .side nav a:hover { background: rgba(255, 255, 255, .08); }

        .side nav a.act {
            margin-left: 1.4rem;
            padding-left: 2rem;
            background: var(--cream);
            border-radius: 2.3rem 0 0 2.3rem;
            color: var(--green);
            font-weight: 600;
        }

        .side nav a.act::before,
        .side nav a.act::after {
            content: "";
            position: absolute;
            right: 0;
            width: 3.4rem;
            height: 3.4rem;
        }

        .side nav a.act::before {
            top: -3.4rem;
            background: radial-gradient(circle at 0 0, transparent 3.4rem, var(--cream) 3.4rem);
        }

        .side nav a.act::after {
            bottom: -3.4rem;
            background: radial-gradient(circle at 0 100%, transparent 3.4rem, var(--cream) 3.4rem);
        }

        .logout-form { margin: auto 0 3rem; }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 2.2rem;
            width: 100%;
            height: 4.6rem;
            padding: 0 0 0 3.4rem;
            border: none;
            background: transparent;
            cursor: pointer;
            color: #fff;
            font-family: inherit;
            font-size: 1.35rem;
            font-weight: 500;
            text-align: left;
            transition: background .25s ease;
        }

        .logout-btn i { font-size: 1.7rem; }
        .logout-btn:hover { background: rgba(255, 255, 255, .08); }

        /* ============ MAIN / TOP BAR (same look as the dashboard) ============ */
        .main { flex: 1; min-width: 0; padding: 0 2rem 3.5rem 1.7rem; }

        .top {
            display: flex;
            align-items: center;
            padding: 15px 0 13px;
            border-bottom: 3px solid #dedbc9;
        }

        .search { position: relative; width: 375px; max-width: 50%; }

        .search input {
            width: 100%;
            height: 42px;
            padding: 0 16px 0 52px;
            border: none;
            border-radius: 6px;
            background: #d7d4c0;
            font-family: inherit;
            font-size: 15px;
            color: var(--green);
        }

        .search input::placeholder { color: #7a7a6a; }
        .search input:focus { outline: 2px solid rgba(59, 74, 60, .25); }

        .search .l {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            font-size: 18px;
            color: #5b6358;
        }

        .user { display: flex; align-items: center; margin-left: auto; }
        .user .bell { font-size: 24px; margin-right: 30px; }

        .user-link {
            display: flex;
            align-items: center;
            color: inherit;
            text-decoration: none;
            transition: opacity .2s ease;
        }

        .user-link:hover { opacity: .75; }
        .user-link:visited,
        .user-link:active { color: inherit; }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #c2677a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 600;
        }

        .uname { margin-left: 17px; line-height: 1.3; }
        .uname b { display: block; font-size: 17px; font-weight: 500; }
        .uname span { font-size: 15px; color: var(--ink); }
        .user .chev { margin-left: 24px; font-size: 13px; }

        /* ============ PAGE BODY ============ */
        /* the page content is scaled down a bit so it reads like the dashboard (change .78 to taste) */
        .body { margin: 0; zoom: .78; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 2.2rem; }

        .title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 3.4rem;
            line-height: 1.05;
            margin: 0;
        }

        .sub { margin: .5rem 0 0; font-size: 1.3rem; color: var(--ink); }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 1.4rem;
            height: 3.4rem;
            padding: 0 2rem;
            margin-top: 1.2rem;
            border: none;
            border-radius: .9rem;
            background: #a0af8d;
            color: #fff;
            font-size: 1.4rem;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            box-shadow: 0 .35rem .6rem rgba(59, 74, 60, .28);
            transition: all .25s ease;
        }

        .btn-add i { font-size: 2rem; }
        .btn-add:hover { background: #344D3D; transform: translateY(-.2rem); }
        .btn-add:active { transform: translateY(0); box-shadow: none; }

        .flash { margin-top: 1.4rem; padding: .9rem 1.4rem; border-radius: 1rem; font-size: 1.2rem; }
        .flash.ok { background: #e3ecd3; color: var(--green); }
        .flash.err { background: #f6e1e1; color: #a8454f; }

        /* ============ HERO (month + gauge) ============ */
        .hero {
            margin-top: 2rem;
            padding: 1.6rem 2.2rem 1.8rem;
            background: #faf9f4;
            border: 1px solid #d9d6c5;
            border-radius: 2rem;
            box-shadow: 0 .3rem 1rem rgba(0, 0, 0, .08);
        }

        .hero-top { display: flex; align-items: center; justify-content: space-between; }
        .mswitch { display: flex; align-items: center; gap: 1rem; }

        .mb {
            width: 2.8rem;
            height: 2.8rem;
            padding: 0;
            border: none;
            border-radius: 50%;
            background: #f0e2e5;
            color: var(--green);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: opacity .2s ease;
        }

        .mlabel { min-width: 15rem; text-align: center; font-size: 1.5rem; font-weight: 600; }

        .chip {
            display: inline-block;
            padding: .15rem 1rem;
            border-radius: 99px;
            font-size: 1.1rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .chip.ok { background: #e3ecd3; color: #4e6b3f; }
        .chip.near { background: #f6edc9; color: #8a6d12; }
        .chip.over { background: #f6e1e1; color: #a8454f; }
        .chip.closed { background: #ece9df; color: #6b6b5a; }

        .hero-mid { display: flex; align-items: center; gap: 4rem; margin-top: .8rem; }
        .gauge { flex: none; width: 24rem; }
        .gauge svg { display: block; width: 100%; height: auto; }

        .arc {
            fill: none;
            stroke-width: 14;
            stroke-linecap: round;
            transition: stroke-dasharray .7s cubic-bezier(.2, .8, .2, 1), stroke .3s ease;
        }

        .kvs { flex: 1; min-width: 0; }

        .kv {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: .8rem 0;
            border-bottom: 1px solid #ebe8da;
        }

        .kv:last-child { border-bottom: none; }
        .kv span { color: #6b6b5a; font-size: 1.3rem; }
        .kv b { font-size: 1.6rem; font-weight: 600; }

        .pillbar {
            margin-top: 1.2rem;
            padding: 1rem 1.4rem;
            border-radius: 1.1rem;
            background: #f6e4e4;
            color: #7a3d46;
            font-size: 1.3rem;
            line-height: 1.4;
        }

        .legend { margin-top: 1rem; font-size: 1.1rem; color: #7d7d6c; }

        /* ============ JARS ============ */
        .sec {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2.4rem 0 1rem;
            font-size: 1.1rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .sec em { font-style: normal; letter-spacing: 0; text-transform: none; }

        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.4rem; }

        .env {
            padding: 1.3rem 1.4rem 1.2rem;
            background: #faf9f4;
            border: 1px solid #d9d6c5;
            border-radius: 1.6rem;
            box-shadow: 0 .2rem .7rem rgba(0, 0, 0, .07);
            text-align: left;
            font-family: inherit;
            color: inherit;
            cursor: pointer;
            animation: rowIn .35s ease both;
            animation-delay: calc(var(--i, 0) * 45ms);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .env:hover { transform: translateY(-.2rem); }
        .env:focus-visible { outline: 2px solid rgba(59, 74, 60, .35); }
        .env.on { border-color: var(--green); box-shadow: 0 0 0 .25rem rgba(59, 74, 60, .15); }

        @keyframes rowIn {
            from { opacity: 0; transform: translateY(.8rem); }
            to { opacity: 1; transform: none; }
        }

        .env.add {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .7rem;
            min-height: 22rem;
            border: 1.5px dashed #b9b79f;
            background: transparent;
            box-shadow: none;
            color: #7d8a6c;
        }

        .env.add i { font-size: 2.6rem; }
        .env.add small { font-size: 1.1rem; }

        .env-h { display: flex; align-items: center; gap: .9rem; }

        .ic {
            width: 3.2rem;
            height: 3.2rem;
            flex: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
        }

        .en { font-size: 1.4rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .jar { position: relative; height: 11.5rem; margin: 1.2rem 0 1rem; border-radius: 1.4rem; background: #eceadf; }
        .jar.over { box-shadow: 0 0 0 .22rem #c98a94; }
        .jin { position: absolute; inset: 0; border-radius: 1.4rem; overflow: hidden; }

        .fill {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 0;
            transition: height .7s cubic-bezier(.2, .8, .2, 1);
        }

        .fill::before {
            content: "";
            position: absolute;
            left: -2%;
            right: -2%;
            top: -.8rem;
            height: 1.6rem;
            background: inherit;
            border-radius: 50%;
        }

        .pace { position: absolute; left: -.3rem; right: -.3rem; border-top: 2px dashed var(--green); opacity: .65; pointer-events: none; }

        .lvl {
            position: absolute;
            left: .8rem;
            transform: translateY(50%);
            padding: .1rem .9rem;
            border-radius: 99px;
            background: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            box-shadow: 0 .1rem .35rem rgba(0, 0, 0, .18);
            transition: bottom .7s cubic-bezier(.2, .8, .2, 1);
        }

        .amt { font-size: 1.4rem; }
        .amt b { font-weight: 600; }
        .amt span { color: #7d7d6c; font-size: 1.3rem; }
        .env-f { display: flex; justify-content: space-between; align-items: center; gap: .5rem; margin-top: .7rem; }
        .lft { font-size: 1.1rem; color: #7d7d6c; white-space: nowrap; }
        .none { grid-column: 1 / -1; padding: 2.4rem 0; text-align: center; color: #6b6b5a; }

        /* ============ EDIT PANEL ============ */
        .ep {
            margin-top: 2rem;
            padding: 2rem 2.4rem;
            background: #faf9f4;
            border: 1px solid #d9d6c5;
            border-radius: 2rem;
            box-shadow: 0 .3rem 1rem rgba(0, 0, 0, .08);
            animation: epIn .3s ease both;
        }

        @keyframes epIn { from { opacity: 0; transform: translateY(.8rem); } to { opacity: 1; transform: none; } }

        .ep-h { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.4rem; font-size: 1.5rem; font-weight: 600; }
        .ep-h .t { flex: 1; }

        .ib { border: none; background: transparent; padding: .4rem .6rem; color: #7d8a6c; font-size: 1.8rem; cursor: pointer; }

        .lab { display: block; margin-bottom: .6rem; font-size: 1.2rem; color: #6b6b5a; }

        .fin {
            height: 3.4rem;
            padding: 0 1.2rem;
            border: none;
            border-radius: .9rem;
            background: #fff;
            font-family: inherit;
            font-size: 1.3rem;
            color: #344d3d;
            box-shadow: 0 .2rem 0 #ecd2d5, 0 .4rem .8rem rgba(0, 0, 0, .04);
        }

        .fin:focus { outline: none; box-shadow: 0 .2rem 0 #c2727d, 0 0 0 3px rgba(194, 114, 125, .15); }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 1.4rem; }
        .two .fin { width: 100%; }
        .slide { display: flex; align-items: center; gap: 1.6rem; }
        .slide input[type=range] { flex: 1; accent-color: var(--green); }
        .slide .fin { width: 12rem; }
        .quick { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: 1.2rem; }

        .qk {
            padding: .35rem 1.3rem;
            border: 1px solid #d9d6c5;
            border-radius: 99px;
            background: #fff;
            color: var(--green);
            font-size: 1.2rem;
            cursor: pointer;
        }

        .qk:hover { background: #f0ebdc; }
        .pv { min-height: 2rem; margin-top: 1.4rem; font-size: 1.25rem; color: #5a5a4c; }
        .acts { display: flex; align-items: center; gap: .9rem; margin-top: 1.4rem; }

        .btn {
            height: 3.4rem;
            padding: 0 2.2rem;
            border-radius: 1rem;
            font-size: 1.3rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .25s ease;
        }

        .btn.dark { border: none; background: var(--green); color: #fff; }
        .btn.dark:hover { background: var(--green-d); transform: translateY(-.15rem); }
        .btn.line { background: transparent; color: var(--green); border: 1.5px solid var(--green); }
        .btn.ghost { background: transparent; color: #d4707e; border: 1.5px solid #e8a8b0; margin-left: auto; }
        .btn.ghost:hover { background: #fbeff1; }
        .eerr { color: #c0392b; font-size: 1.2rem; }


        /* ============ GOALS ============ */
        [hidden] { display: none !important; }
        .head-acts { display: flex; gap: 1.2rem; }
        .btn-add.alt { background: transparent; color: var(--green); border: 1.5px solid var(--green); box-shadow: none; }
        .btn-add.alt:hover, .btn-add.alt.on { background: var(--green); color: #fff; }

        .gsec { margin-top: 3rem; padding-top: .4rem; border-top: 1px solid rgba(59, 74, 60, .16); scroll-margin-top: 2rem; }
        .gsec .sec { margin-top: 1.6rem; }
        .link { border: none; background: transparent; padding: 0; color: var(--green); font-size: 1.2rem; font-weight: 600; letter-spacing: 0; text-transform: none; cursor: pointer; }
        .link:hover { text-decoration: underline; }
        .gsum { margin-bottom: 1.2rem; font-size: 1.3rem; color: var(--ink); }
        .gsum b { font-weight: 600; color: var(--green); }

        .gcard .gbar { height: .9rem; margin: 1.4rem 0 1rem; border-radius: 99px; background: #eceadf; overflow: hidden; }
        .gcard .gbar span { display: block; height: 100%; border-radius: 99px; animation: gfill .7s cubic-bezier(.2, .8, .2, 1) both; }
        @keyframes gfill { from { width: 0; } }
        .gnote { margin-top: .8rem; font-size: 1.15rem; color: #6b6b5a; }
        .gcard.done .gbar span { background: #5c7d5c !important; }

        .icons { display: flex; flex-wrap: wrap; gap: .6rem; }
        .pk {
            width: 3.2rem;
            height: 3.2rem;
            padding: 0;
            border: 1.5px solid transparent;
            border-radius: .9rem;
            background: #fff;
            color: var(--green);
            font-size: 1.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 .1rem 0 #ecd2d5;
            transition: all .2s ease;
        }
        .pk.on { background: #e6ebdb; border-color: var(--green); }

        .fundbox { padding: 1.4rem; margin-bottom: 1.6rem; border-radius: 1.4rem; background: #f0ebdc; }
        .fundrow { display: flex; align-items: center; gap: 1rem; }
        .fundrow .fin { width: 16rem; }
        .fundrow .btn { padding: 0 2rem; }
        .three { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.4rem; }
        .three .fin { width: 100%; }
        .sub-h { margin: 0 0 1rem; font-size: 1.2rem; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
        .gtop { display: flex; align-items: center; gap: .8rem; margin-top: 1.4rem; }

        /* ============ PHONE / TABLET ============ */
        @media (max-width: 991px) {
            html { font-size: 13px; }
            .app { flex-direction: column; background: linear-gradient(180deg, var(--cream), var(--beige)); }

            .side { position: static; height: auto; width: auto; border-radius: 0 0 2.4rem 2.4rem; }
            .side-head { height: 6rem; }
            .side nav { margin-top: 0; display: flex; overflow-x: auto; padding: .6rem 1rem; }
            .side nav a { flex: none; height: 3.8rem; padding: 0 1.6rem; gap: 1rem; border-radius: 99px; }
            .side nav a.act { margin-left: 0; padding-left: 1.6rem; border-radius: 99px; }
            .side nav a.act::before, .side nav a.act::after { display: none; }

            .logout-form { margin: 0 1rem .8rem; }
            .logout-btn { width: auto; height: 3.8rem; padding: 0 1.6rem; gap: 1rem; border-radius: 99px; }

            .main { padding: 0 1.4rem 3rem; }
            .top { flex-wrap: wrap; gap: 1.2rem; }
            .search { width: 100%; max-width: none; }
            .user { margin-left: 0; width: 100%; justify-content: space-between; }
            .user .bell { margin-right: 0; }

            .body { margin: 0; zoom: 1; }
            .head { flex-wrap: wrap; gap: 1rem; }
            .hero-mid { flex-direction: column; gap: 1.2rem; align-items: stretch; }
            .gauge { width: 24rem; align-self: center; }
            .grid { grid-template-columns: 1fr 1fr; }
            .two, .three { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) { .grid { grid-template-columns: 1fr; } }

        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
    @include('layouts.partials.motion-styles')
    @include('layouts.partials.theme-styles')
</head>

<body data-theme="{{ auth()->user()?->theme ?? 'system' }}" data-reduced-motion="{{ auth()->user()?->reduced_motion ? 'true' : 'false' }}">

    <div class="app">

        {{-- ================= SIDEBAR ================= --}}
        <aside class="side">
            <a class="side-head" href="{{ $r('dashboard') }}">
                <img src="{{ asset('images/spendwise-logo.png') }}" alt="">
                <div>
                    <div class="brand">SpendWise</div>
                    <div class="tagline">Spend Smart. Live Wise</div>
                </div>
            </a>
            <nav>
                @foreach ($nav as [$label, $icon, $href, $active])
                    <a href="{{ $href }}" class="{{ $active ? 'act' : '' }}">
                        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>{{ $label }}
                    </a>
                @endforeach
            </nav>

            <form method="POST" action="{{ $logoutUrl }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="bi bi-box-arrow-left" aria-hidden="true"></i>Logout
                </button>
            </form>
        </aside>

        {{-- ================= MAIN ================= --}}
        <div class="main">

            <header class="top">
                <div class="search">
                    <i class="bi bi-search l" aria-hidden="true"></i>
                    <input id="q" type="text" placeholder="Search budgets..." autocomplete="off">
                </div>
                <div class="user">
                    <i class="bi bi-bell bell" aria-hidden="true"></i>
                    <a href="{{ $profileUrl }}" class="user-link" title="Go to profile">
                        <div class="avatar">@if ($profilePhotoUrl)<img src="{{ $profilePhotoUrl }}" alt="">@else{{ $initials }}@endif</div>
                        <div class="uname"><b>{{ $uname }}</b><span>{{ $uemail }}</span></div>
                        <i class="bi bi-chevron-down chev" aria-hidden="true"></i>
                    </a>
                </div>
            </header>

            <div class="body">
                <div class="head">
                    <div>
                        <h1 class="title">Budgets</h1>
                        <p class="sub">Give every category a limit and stay on pace.</p>
                    </div>
                    <div class="head-acts">
                        <button type="button" class="btn-add alt" id="goalsBtn" aria-expanded="false"
                            aria-controls="goalsSec"><i class="ti ti-target" aria-hidden="true"></i>Goals</button>
                        <button type="button" class="btn-add" id="setb"><i class="ti ti-plus"
                                aria-hidden="true"></i>Set Budget</button>
                    </div>
                </div>

                @if (session('status'))
                    <div class="flash ok" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="flash err" role="alert">{{ $errors->first() }}</div>
                @endif

                {{-- month + gauge --}}
                <div class="hero">
                    <div class="hero-top">
                        <div class="mswitch">
                            <button type="button" class="mb" id="mprev" aria-label="Previous month"><i
                                    class="ti ti-chevron-left" aria-hidden="true"></i></button>
                            <span class="mlabel" id="mlabel"></span>
                            <button type="button" class="mb" id="mnext" aria-label="Next month"><i
                                    class="ti ti-chevron-right" aria-hidden="true"></i></button>
                        </div>
                        <span id="mtag" class="chip ok"></span>
                    </div>

                    <div class="hero-mid">
                        <div class="gauge">
                            <svg viewBox="0 0 160 96" aria-hidden="true">
                                <path class="gauge-track" d="M10 84 A70 70 0 0 1 150 84" fill="none" stroke="#e3e1d6" stroke-width="14"
                                    stroke-linecap="round"></path>
                                <path id="arc" class="arc" d="M10 84 A70 70 0 0 1 150 84" pathLength="100"
                                    style="stroke-dasharray:0 100;stroke:#3b4a3c"></path>
                                <line id="tick" x1="0" y1="0" x2="0" y2="0" stroke="#3b4a3c" stroke-width="2.5"
                                    stroke-linecap="round"></line>
                                <text id="gp" x="80" y="72" text-anchor="middle" font-size="26" font-weight="600"
                                    fill="#3b4a3c">0%</text>
                                <text class="gauge-note" x="80" y="90" text-anchor="middle" font-size="11" fill="#7d7d6c">of budget
                                    used</text>
                            </svg>
                        </div>
                        <div class="kvs">
                            <div class="kv"><span>Total budget</span><b id="hBud"></b></div>
                            <div class="kv"><span>Spent</span><b id="hSpent"></b></div>
                            <div class="kv"><span id="hLeftL">Left to spend</span><b id="hLeft"></b></div>
                        </div>
                    </div>

                    <div class="pillbar" id="pill"></div>
                    <div class="legend" id="legend">The tick on the gauge and the dashed line on each jar show where
                        you’d be today if you spent evenly.</div>
                </div>

                <div class="sec"><span>Category budgets</span><em>Tap a jar to adjust</em></div>
                <div class="grid" id="grid"></div>
                <div id="edit"></div>

                {{-- savings goals --}}
                <section class="gsec" id="goalsSec" hidden>
                    <div class="sec"><span>Savings goals</span><button type="button" class="link" id="addGoal">+ Add
                            goal</button></div>
                    <div class="gsum" id="gsum"></div>
                    <div class="grid" id="ggrid"></div>
                    <div id="gedit"></div>
                </section>
            </div>
        </div>
    </div>

    {{-- real forms the page submits when the backend is connected --}}
    <form id="budForm" method="POST" action="#" hidden>
        @csrf
        <input type="hidden" name="_method" id="fMethod" value="POST">
        <input type="hidden" name="category_id" id="fCat">
        <input type="hidden" name="amount" id="fAmt">
    </form>
    <form id="delForm" method="POST" action="#" hidden>@csrf @method('DELETE')</form>

    {{-- goal forms --}}
    <form id="goalForm" method="POST" action="#" hidden>
        @csrf
        <input type="hidden" name="_method" id="gMethod" value="POST">
        <input type="hidden" name="name" id="gfName">
        <input type="hidden" name="target" id="gfTarget">
        <input type="hidden" name="deadline" id="gfDeadline">
        <input type="hidden" name="icon" id="gfIcon">
        <input type="hidden" name="saved" id="gfSaved">
    </form>
    <form id="goalFundForm" method="POST" action="#" hidden>
        @csrf
        <input type="hidden" name="amount" id="gfAmount">
    </form>
    <form id="goalDelForm" method="POST" action="#" hidden>@csrf @method('DELETE')</form>

    <script>
        (function () {
            // ===== data from Laravel =====
            const CATS = @json($categories);
            const MONTHS = @json($months);
            const DEMO = @json($demo);
            const URLS = { store: @json($storeUrl), update: @json($updateUrl), destroy: @json($destroyUrl) };

            const PAL = {
                forest: { c: '#3b4a3c', t: '#d9e7c9', i: '#3b4a3c' },
                sage: { c: '#a9b78f', t: '#dde9cb', i: '#3b4a3c' },
                rose: { c: '#d9a4ab', t: '#f3e0e0', i: '#c0808b' },
                blush: { c: '#ebc4c9', t: '#f9e6e6', i: '#c98a94' },
                taupe: { c: '#c9c5b6', t: '#e5e3d8', i: '#7d7a6c' },
                gold: { c: '#dcc875', t: '#f4ebc3', i: '#a8861f' }
            };
            const categoryHue = color => PAL[color] || (/^#[0-9a-f]{6}$/i.test(String(color))
                ? { c: color, t: color, i: '#3b4a3c' }
                : PAL.forest);

            const $ = id => document.getElementById(id);
            const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const money = n => '₱' + Math.round(n).toLocaleString('en-PH');

            let env = CATS.map(c => Object.assign({}, c));
            let mi = MONTHS.length - 1, q = '', draft = null, confirmRm = false;

            const M = () => MONTHS[mi];
            const limitOf = e => (draft && !draft.isNew && draft.id === e.id) ? draft.limit : e.limit;
            const spentOf = e => Number((e.spent || {})[M().key]) || 0;
            const pacePct = () => M().cur ? M().day / M().days * 100 : null;

            function status(sp, lim) {
                const r = sp / lim;
                if (r > 1) return { t: 'Over by ' + money(sp - lim), k: 'over' };
                if (r === 1) return { t: 'Limit reached', k: 'over' };
                if (r >= .75) return { t: 'Near limit', k: 'near' };
                return { t: 'On track', k: 'ok' };
            }

            function totals() {
                const b = env.filter(e => limitOf(e) != null);
                const L = b.reduce((s, e) => s + limitOf(e), 0), S = b.reduce((s, e) => s + spentOf(e), 0);
                return { L: L, S: S, left: L - S, pct: L ? S / L * 100 : 0 };
            }

            // ===== hero =====
            function updateHero() {
                const t = totals(), m = M(), p = pacePct(), arc = $('arc');
                arc.style.strokeDasharray = Math.min(t.pct, 100) + ' 100';
                arc.style.stroke = t.pct > 100 ? '#c98a94' : t.pct >= 75 ? '#d9bf5a' : 'var(--arc-ok, #3b4a3c)';
                $('gp').textContent = Math.round(t.pct) + '%';
                $('hBud').textContent = money(t.L);
                $('hSpent').textContent = money(t.S);
                $('hLeftL').textContent = t.left >= 0 ? 'Left to spend' : 'Over budget by';
                $('hLeft').textContent = money(Math.abs(t.left));
                $('hLeft').style.color = t.left >= 0 ? 'var(--arc-ok, #3b4a3c)' : 'var(--arc-over, #a8454f)';

                const tick = $('tick');
                if (p === null) { tick.style.display = 'none'; }
                else {
                    const a = Math.PI * (1 - Math.min(p, 100) / 100), cx = 80, cy = 84;
                    tick.style.display = '';
                    tick.setAttribute('x1', cx + 60 * Math.cos(a)); tick.setAttribute('y1', cy - 60 * Math.sin(a));
                    tick.setAttribute('x2', cx + 81 * Math.cos(a)); tick.setAttribute('y2', cy - 81 * Math.sin(a));
                }

                const dl = m.days - m.day;
                if (!m.cur) $('pill').innerHTML = 'Month closed · you averaged <b>' + money(t.S / m.days) + '</b> per day.';
                else if (t.L === 0) $('pill').innerHTML = 'No budgets set yet. Tap <b>Set Budget</b> to start.';
                else if (t.left <= 0) $('pill').innerHTML = 'Budget used up with <b>' + dl + ' days</b> to go. Hold off on extra spending.';
                else if (dl === 0) $('pill').innerHTML = 'Last day of the month · <b>' + money(t.left) + '</b> left to spend.';
                else $('pill').innerHTML = '<b>' + money(t.left / dl) + '</b> safe to spend per day · ' + dl + ' day' + (dl === 1 ? '' : 's') + ' left this month.';

                $('mtag').textContent = m.cur ? 'Day ' + m.day + ' of ' + m.days : 'Closed';
                $('mtag').className = 'chip ' + (m.cur ? 'ok' : 'closed');
                $('legend').style.visibility = m.cur ? 'visible' : 'hidden';
            }

            // ===== jars =====
            function cardHTML(e, i) {
                const p = categoryHue(e.color), on = draft && !draft.isNew && draft.id === e.id;
                return '<div class="env' + (on ? ' on' : '') + '" data-id="' + e.id + '" role="button" tabindex="0" style="--i:' + i + '">'
                    + '<div class="env-h"><div class="ic" style="background:' + p.t + ';color:' + p.i + '"><i class="ti ti-' + esc(e.icon) + '" aria-hidden="true"></i></div><div class="en">' + esc(e.name) + '</div></div>'
                    + '<div class="jar"><div class="jin"><div class="fill" style="background:' + p.c + '"></div></div><div class="pace"></div><div class="lvl" style="bottom:6%"></div></div>'
                    + '<div class="amt"></div>'
                    + '<div class="env-f"><span class="chip"></span><span class="lft"></span></div></div>';
            }

            function paintCard(card, e) {
                const lim = limitOf(e), sp = spentOf(e), ratio = sp / lim, st = status(sp, lim), p = pacePct();
                card.querySelector('.fill').style.height = Math.min(ratio * 100, 100) + '%';
                const lv = card.querySelector('.lvl');
                lv.textContent = Math.round(ratio * 100) + '%';
                lv.style.bottom = Math.min(Math.max(ratio * 100, 6), 88) + '%';
                const pc = card.querySelector('.pace');
                if (p === null) pc.style.display = 'none';
                else { pc.style.display = ''; pc.style.bottom = Math.min(p, 100) + '%'; }
                card.querySelector('.jar').classList.toggle('over', ratio > 1);
                card.querySelector('.amt').innerHTML = '<b>' + money(sp) + '</b><span> of ' + money(lim) + '</span>';
                const ch = card.querySelector('.chip'); ch.textContent = st.t; ch.className = 'chip ' + st.k;
                card.querySelector('.lft').textContent = lim >= sp ? money(lim - sp) + ' left' : '';
            }

            function renderGrid() {
                const rows = env.filter(e => e.limit != null && (!q || e.name.toLowerCase().includes(q)));
                const noBud = env.filter(e => e.limit == null);
                let h = rows.map(cardHTML).join('');
                if (!q && noBud.length) h += '<button type="button" class="env add" id="addTile"><i class="ti ti-plus" aria-hidden="true"></i><span style="font-weight:500">Set a budget</span><small>' + noBud.length + (noBud.length === 1 ? ' category has' : ' categories have') + ' none</small></button>';
                if (!h) h = '<div class="none">No budgets match your search.</div>';
                $('grid').innerHTML = h;
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    document.querySelectorAll('#grid .env[data-id]').forEach(c => paintCard(c, env.find(e => e.id === +c.dataset.id)));
                }));
            }

            function liveUpdate() {
                updateHero();
                document.querySelectorAll('#grid .env[data-id]').forEach(c => {
                    const e = env.find(x => x.id === +c.dataset.id);
                    if (draft && !draft.isNew && draft.id === e.id) paintCard(c, e);
                });
                pv();
            }

            // ===== edit panel =====
            function pv() {
                const box = $('pv'); if (!box || !draft) return;
                if (draft.isNew) { box.textContent = 'Pick a category and a monthly limit.'; return; }
                const e = env.find(x => x.id === draft.id), sp = spentOf(e), lim = draft.limit;
                if (!(lim > 0)) { box.textContent = 'Enter a limit of at least ₱100.'; return; }
                const st = status(sp, lim);
                box.innerHTML = 'At <b>' + money(lim) + '</b>: ' + Math.round(sp / lim * 100) + '% used in ' + esc(M().label.split(' ')[0]) + ' · ' + (lim >= sp ? money(lim - sp) + ' left' : 'over by ' + money(sp - lim)) + ' · <b>' + st.t + '</b>';
            }

            function renderEdit() {
                if (!draft) { $('edit').innerHTML = ''; return; }
                let h;
                if (draft.isNew) {
                    const free = env.filter(e => e.limit == null);
                    if (!free.length) {
                        $('edit').innerHTML = '<div class="ep"><div style="color:#6b6b5a">Every category already has a budget. Add a new category first from the Categories page.</div><div class="acts"><button type="button" class="btn line" data-a="cancel" style="margin-left:0">Close</button></div></div>';
                        return;
                    }
                    h = '<div class="ep-h"><span class="t">Set a budget</span></div>'
                        + '<div class="two"><div><label class="lab" for="nc">Category</label><select id="nc" class="fin">' + free.map(e => '<option value="' + e.id + '"' + (e.id === draft.id ? ' selected' : '') + '>' + esc(e.name) + '</option>').join('') + '</select></div>'
                        + '<div><label class="lab" for="num">Monthly limit (₱)</label><input id="num" class="fin" type="number" min="100" step="100" value="' + draft.limit + '"></div></div>';
                } else {
                    const e = env.find(x => x.id === draft.id), p = categoryHue(e.color), max = Math.max(10000, Math.ceil(draft.limit / 1000) * 1000);
                    h = '<div class="ep-h"><div class="ic" style="background:' + p.t + ';color:' + p.i + '"><i class="ti ti-' + e.icon + '" aria-hidden="true"></i></div><span class="t">Adjust ' + esc(e.name) + ' budget</span><button type="button" class="ib" data-a="cancel" aria-label="Close"><i class="ti ti-x" aria-hidden="true"></i></button></div>'
                        + '<div class="slide"><input id="rng" type="range" min="0" max="' + max + '" step="100" value="' + draft.limit + '" aria-label="Monthly limit"><input id="num" class="fin" type="number" min="100" step="100" value="' + draft.limit + '"></div>'
                        + '<div class="quick"><button type="button" class="qk" data-q="-500">− ₱500</button><button type="button" class="qk" data-q="500">+ ₱500</button><button type="button" class="qk" data-q="match">Match spending</button></div>';
                }
                h += '<div class="pv" id="pv"></div>'
                    + '<div class="acts"><button type="button" class="btn dark" data-a="save">Save budget</button><button type="button" class="btn line" data-a="cancel">Cancel</button>'
                    + (draft.isNew ? '' : '<button type="button" class="btn ghost" data-a="rm">' + (confirmRm ? 'Click again to remove' : 'Remove budget') + '</button>')
                    + '<span class="eerr" id="eerr"></span></div>';
                $('edit').innerHTML = '<div class="ep">' + h + '</div>';
                pv();
            }

            function openEdit(id) {
                const e = env.find(x => x.id === id);
                if (draft && !draft.isNew && draft.id === id) { closeEdit(); return; }
                draft = { id: id, limit: e.limit, isNew: false }; confirmRm = false;
                renderGrid(); renderEdit(); updateHero();
            }

            function openNew() {
                const free = env.filter(e => e.limit == null);
                draft = { id: free.length ? free[0].id : null, limit: 1000, isNew: true }; confirmRm = false;
                renderGrid(); renderEdit(); updateHero();
                const el = $('edit'); if (el && el.scrollIntoView) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function closeEdit() { draft = null; confirmRm = false; renderGrid(); renderEdit(); updateHero(); }

            function setLimit(v) {
                draft.limit = Math.max(0, Math.round(v / 100) * 100);
                const rng = $('rng'), num = $('num');
                if (rng) { rng.max = Math.max(10000, Math.ceil(draft.limit / 1000) * 1000); rng.value = draft.limit; }
                if (num && document.activeElement !== num) num.value = draft.limit;
                liveUpdate();
            }

            function save() {
                if (!(draft.limit >= 100)) { $('eerr').textContent = 'Enter at least ₱100.'; return; }
                if (draft.id == null) return;
                if (DEMO) {                                   // preview only, nothing is saved
                    env.find(x => x.id === draft.id).limit = draft.limit;
                    draft = null; renderGrid(); renderEdit(); updateHero();
                    return;
                }
                const isNew = draft.isNew;                    // real save
                $('budForm').action = isNew ? URLS.store : URLS.update.replace('__ID__', draft.id);
                $('fMethod').value = isNew ? 'POST' : 'PUT';
                $('fCat').value = draft.id; $('fAmt').value = draft.limit;
                $('budForm').submit();
            }

            function removeBudget() {
                if (DEMO) { env.find(x => x.id === draft.id).limit = null; closeEdit(); return; }
                $('delForm').action = URLS.destroy.replace('__ID__', draft.id);
                $('delForm').submit();
            }

            // ===== events =====
            $('edit').addEventListener('input', e => {
                if (!draft) return;
                if (e.target.id === 'rng') setLimit(+e.target.value);
                if (e.target.id === 'num') {
                    const v = +e.target.value;
                    if (draft.isNew) { draft.limit = v; }
                    else {
                        draft.limit = Math.max(0, v);
                        const rng = $('rng');
                        if (rng) { rng.max = Math.max(10000, Math.ceil(draft.limit / 1000) * 1000); rng.value = draft.limit; }
                        liveUpdate();
                    }
                    $('eerr').textContent = '';
                }
            });
            $('edit').addEventListener('change', e => { if (e.target.id === 'nc' && draft) draft.id = +e.target.value; });
            $('edit').addEventListener('click', e => {
                const b = e.target.closest('button'); if (!b || !draft) return;
                if (b.dataset.q) {
                    const ex = env.find(x => x.id === draft.id);
                    if (b.dataset.q === 'match') setLimit(Math.ceil(spentOf(ex) / 100) * 100 || 100);
                    else setLimit(draft.limit + (+b.dataset.q));
                    return;
                }
                const a = b.dataset.a;
                if (a === 'cancel') closeEdit();
                if (a === 'rm') {
                    if (!confirmRm) { confirmRm = true; b.textContent = 'Click again to remove'; return; }
                    removeBudget();
                }
                if (a === 'save') save();
            });

            $('grid').addEventListener('click', e => {
                if (e.target.closest('#addTile')) { openNew(); return; }
                const c = e.target.closest('.env[data-id]'); if (c) openEdit(+c.dataset.id);
            });
            $('grid').addEventListener('keydown', e => {
                const c = e.target.closest('.env[data-id]');
                if (c && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openEdit(+c.dataset.id); }
            });
            $('setb').addEventListener('click', openNew);
            $('q').addEventListener('input', () => { q = $('q').value.trim().toLowerCase(); renderGrid(); });
            document.addEventListener('keydown', e => { if (e.key === 'Escape' && draft) closeEdit(); });

            function setMonth(i) {
                mi = Math.min(Math.max(i, 0), MONTHS.length - 1); draft = null; confirmRm = false;
                $('mlabel').textContent = M().label;
                $('mprev').style.opacity = mi > 0 ? 1 : .35;
                $('mnext').style.opacity = mi < MONTHS.length - 1 ? 1 : .35;
                const arc = $('arc'); arc.style.transition = 'none'; arc.style.strokeDasharray = '0 100'; void arc.getBoundingClientRect(); arc.style.transition = '';
                renderGrid(); renderEdit();
                requestAnimationFrame(() => requestAnimationFrame(updateHero));
            }
            $('mprev').addEventListener('click', () => setMonth(mi - 1));
            $('mnext').addEventListener('click', () => setMonth(mi + 1));

            setMonth(MONTHS.length - 1);

            // ================= GOALS =================
            const GOALS = @json($goals);
            const GOPEN = @json(session('open_goals') ? true : false);
            const GURLS = { store: @json($goalStoreUrl), update: @json($goalUpdateUrl), destroy: @json($goalDestroyUrl), fund: @json($goalFundUrl) };
            const GICONS = ['target', 'device-mobile', 'device-laptop', 'plane', 'home', 'car', 'gift', 'school', 'heart', 'camera', 'pig-money', 'shopping-bag'];
            const PKEYS = Object.keys(PAL);

            let goals = GOALS.map(g => Object.assign({}, g, { target: Number(g.target), saved: Number(g.saved), deadline: g.deadline || '' }));
            let gdraft = null, gConfirm = false, gOpen = false, gNext = 1000;

            const gHue = g => PAL[PKEYS[Math.abs(Number(g.id) || 0) % PKEYS.length]];

            function monthsLeft(d) {
                if (!d) return null;
                const end = new Date(d + 'T23:59:59'), now = new Date();
                if (end < now) return 0;
                return Math.max(1, Math.ceil((end - now) / (30.44 * 864e5)));
            }
            const fmtDate = d => new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });

            function gStatus(g) {
                if (g.saved >= g.target) return { t: 'Goal reached', k: 'ok' };
                if (monthsLeft(g.deadline) === 0) return { t: 'Past due', k: 'over' };
                return { t: Math.round(g.saved / g.target * 100) + '% saved', k: 'ok' };
            }

            function gNote(g) {
                if (g.saved >= g.target) return 'You’ve reached this goal.';
                const left = g.target - g.saved, ml = monthsLeft(g.deadline);
                if (ml === null) return 'No deadline set.';
                if (ml === 0) return 'Deadline passed · ' + money(left) + ' still to go.';
                return 'Set aside <b>' + money(left / ml) + '</b> a month · by ' + esc(fmtDate(g.deadline));
            }

            function gCardHTML(g, i) {
                const p = gHue(g), pct = Math.min(g.saved / g.target * 100, 100), st = gStatus(g);
                const on = gdraft && gdraft.id === g.id;
                return '<div class="env gcard' + (g.saved >= g.target ? ' done' : '') + (on ? ' on' : '') + '" data-gid="' + g.id + '" role="button" tabindex="0" style="--i:' + i + '">'
                    + '<div class="env-h"><div class="ic" style="background:' + p.t + ';color:' + p.i + '"><i class="ti ti-' + esc(g.icon) + '" aria-hidden="true"></i></div><div class="en">' + esc(g.name) + '</div></div>'
                    + '<div class="gbar"><span style="width:' + pct + '%;background:' + p.c + '"></span></div>'
                    + '<div class="amt"><b>' + money(g.saved) + '</b><span> of ' + money(g.target) + '</span></div>'
                    + '<div class="env-f"><span class="chip ' + st.k + '">' + st.t + '</span><span class="lft">' + (g.saved < g.target ? money(g.target - g.saved) + ' to go' : '') + '</span></div>'
                    + '<div class="gnote">' + gNote(g) + '</div></div>';
            }

            function renderGoals() {
                const sum = goals.reduce((s, g) => s + g.saved, 0), tot = goals.reduce((s, g) => s + g.target, 0);
                $('gsum').innerHTML = goals.length ? 'Saved <b>' + money(sum) + '</b> of ' + money(tot) + ' across ' + goals.length + (goals.length === 1 ? ' goal' : ' goals') + '.' : '';
                $('ggrid').innerHTML = goals.length
                    ? goals.map(gCardHTML).join('')
                    : '<button type="button" class="env add" id="gAddTile"><i class="ti ti-plus" aria-hidden="true"></i><span style="font-weight:500">Add a goal</span><small>Save up for something you want</small></button>';
            }

            function renderGEdit() {
                if (!gdraft) { $('gedit').innerHTML = ''; return; }
                const d = gdraft, isNew = !d.id, g = isNew ? null : goals.find(x => x.id === d.id);
                let h = '';
                if (g) {
                    const p = gHue(g);
                    h += '<div class="ep-h"><div class="ic" style="background:' + p.t + ';color:' + p.i + '"><i class="ti ti-' + esc(g.icon) + '" aria-hidden="true"></i></div><span class="t">' + esc(g.name) + '</span><button type="button" class="ib" data-ga="cancel" aria-label="Close"><i class="ti ti-x" aria-hidden="true"></i></button></div>';
                    h += '<div class="fundbox"><label class="lab" for="gfund">Add to this goal (₱)</label>'
                        + '<div class="fundrow"><input id="gfund" class="fin" type="number" min="1" step="100" placeholder="0" value="' + esc(d.fund) + '">'
                        + '<button type="button" class="btn dark" data-ga="fund">Add funds</button></div>'
                        + '<div class="quick"><button type="button" class="qk" data-gq="500">+ ₱500</button><button type="button" class="qk" data-gq="1000">+ ₱1,000</button><button type="button" class="qk" data-gq="5000">+ ₱5,000</button></div></div>';
                    h += '<p class="sub-h">Edit goal</p>';
                } else {
                    h += '<div class="ep-h"><span class="t">New goal</span><button type="button" class="ib" data-ga="cancel" aria-label="Close"><i class="ti ti-x" aria-hidden="true"></i></button></div>';
                }
                h += '<div class="two"><div><label class="lab" for="gname">Goal name</label><input id="gname" class="fin" type="text" maxlength="30" placeholder="Goal name" value="' + esc(d.name) + '"></div>'
                    + '<div><label class="lab" for="gtarget">Target amount (₱)</label><input id="gtarget" class="fin" type="number" min="100" step="100" value="' + esc(d.target) + '"></div></div>'
                    + '<div class="two" style="margin-top:1.4rem"><div><label class="lab" for="gdate">Deadline (optional)</label><input id="gdate" class="fin" type="date" value="' + esc(d.deadline) + '"></div>'
                    + (isNew ? '<div><label class="lab" for="gsaved">Already saved (₱, optional)</label><input id="gsaved" class="fin" type="number" min="0" step="100" value="' + esc(d.saved) + '"></div>' : '<div></div>') + '</div>'
                    + '<span class="lab" style="margin-top:1.4rem">Icon</span><div class="icons">'
                    + GICONS.map(i => '<button type="button" class="pk' + (i === d.icon ? ' on' : '') + '" data-gi="' + i + '" aria-label="' + i + '"><i class="ti ti-' + i + '" aria-hidden="true"></i></button>').join('') + '</div>'
                    + '<div class="acts"><button type="button" class="btn dark" data-ga="save">Save goal</button><button type="button" class="btn line" data-ga="cancel">Cancel</button>'
                    + (isNew ? '' : '<button type="button" class="btn ghost" data-ga="rm">' + (gConfirm ? 'Click again to delete' : 'Delete goal') + '</button>')
                    + '<span class="eerr" id="gerr"></span></div>';
                $('gedit').innerHTML = '<div class="ep">' + h + '</div>';
            }

            function gRefresh() { renderGoals(); renderGEdit(); }

            function setGoalsOpen(open) {
                gOpen = open;
                $('goalsSec').hidden = !open;
                $('goalsBtn').classList.toggle('on', open);
                $('goalsBtn').setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) $('goalsSec').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function gOpenEdit(id) {
                if (gdraft && gdraft.id === id) { gdraft = null; gConfirm = false; gRefresh(); return; }
                const g = goals.find(x => x.id === id); if (!g) return;
                gdraft = { id: g.id, name: g.name, target: g.target, deadline: g.deadline || '', icon: g.icon, saved: g.saved, fund: '' };
                gConfirm = false; gRefresh();
            }

            function gOpenNew() {
                gdraft = { id: null, name: '', target: '', deadline: '', icon: 'target', saved: '', fund: '' };
                gConfirm = false;
                if (!gOpen) setGoalsOpen(true);
                gRefresh();
                const n = $('gname'); if (n) n.focus();
            }

            function gClose() { gdraft = null; gConfirm = false; gRefresh(); }

            function gSave() {
                const name = gdraft.name.trim(), target = Number(gdraft.target), saved = Number(gdraft.saved) || 0;
                let err = '';
                if (!name) err = 'Enter a goal name.';
                else if (name.length > 30) err = 'Keep the name to 30 characters.';
                else if (!(target >= 100)) err = 'Target must be at least ₱100.';
                else if (saved < 0) err = 'Amount saved can’t be negative.';
                if (err) { $('gerr').textContent = err; return; }

                if (DEMO) {                                   // preview only, nothing is saved
                    if (gdraft.id) Object.assign(goals.find(x => x.id === gdraft.id), { name: name, target: target, deadline: gdraft.deadline, icon: gdraft.icon });
                    else goals.push({ id: gNext++, name: name, target: target, saved: saved, deadline: gdraft.deadline, icon: gdraft.icon });
                    gdraft = null; gRefresh();
                    return;
                }
                $('goalForm').action = gdraft.id ? GURLS.update.replace('__ID__', gdraft.id) : GURLS.store;   // real save
                $('gMethod').value = gdraft.id ? 'PUT' : 'POST';
                $('gfName').value = name; $('gfTarget').value = target; $('gfDeadline').value = gdraft.deadline;
                $('gfIcon').value = gdraft.icon; $('gfSaved').value = saved;
                $('goalForm').submit();
            }

            function gFund() {
                const a = Number(gdraft.fund);
                if (!(a > 0)) { $('gerr').textContent = 'Enter an amount above ₱0.'; return; }
                if (DEMO) { goals.find(x => x.id === gdraft.id).saved += a; gdraft.fund = ''; gRefresh(); return; }
                $('goalFundForm').action = GURLS.fund.replace('__ID__', gdraft.id);
                $('gfAmount').value = a;
                $('goalFundForm').submit();
            }

            function gRemove() {
                if (DEMO) { goals = goals.filter(x => x.id !== gdraft.id); gdraft = null; gConfirm = false; gRefresh(); return; }
                $('goalDelForm').action = GURLS.destroy.replace('__ID__', gdraft.id);
                $('goalDelForm').submit();
            }

            $('goalsBtn').addEventListener('click', () => setGoalsOpen(!gOpen));
            $('addGoal').addEventListener('click', gOpenNew);

            $('ggrid').addEventListener('click', e => {
                if (e.target.closest('#gAddTile')) { gOpenNew(); return; }
                const c = e.target.closest('.gcard[data-gid]'); if (c) gOpenEdit(isNaN(c.dataset.gid) ? c.dataset.gid : +c.dataset.gid);
            });
            $('ggrid').addEventListener('keydown', e => {
                const c = e.target.closest('.gcard[data-gid]');
                if (c && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); gOpenEdit(isNaN(c.dataset.gid) ? c.dataset.gid : +c.dataset.gid); }
            });

            $('gedit').addEventListener('input', e => {
                if (!gdraft) return;
                const map = { gname: 'name', gtarget: 'target', gdate: 'deadline', gsaved: 'saved', gfund: 'fund' };
                if (map[e.target.id]) { gdraft[map[e.target.id]] = e.target.value; const er = $('gerr'); if (er) er.textContent = ''; }
            });

            $('gedit').addEventListener('click', e => {
                const b = e.target.closest('button'); if (!b || !gdraft) return;
                if (b.dataset.gi) { gdraft.icon = b.dataset.gi; renderGEdit(); return; }
                if (b.dataset.gq) {
                    gdraft.fund = String((Number(gdraft.fund) || 0) + Number(b.dataset.gq));
                    const f = $('gfund'); if (f) f.value = gdraft.fund;
                    return;
                }
                const a = b.dataset.ga;
                if (a === 'cancel') gClose();
                if (a === 'save') gSave();
                if (a === 'fund') gFund();
                if (a === 'rm') {
                    if (!gConfirm) { gConfirm = true; b.textContent = 'Click again to delete'; return; }
                    gRemove();
                }
            });

            document.addEventListener('keydown', e => { if (e.key === 'Escape' && gdraft) gClose(); });

            renderGoals();
            if (GOPEN) setGoalsOpen(true);
        })();
    </script>
    @include('layouts.partials.motion')
    @include('layouts.partials.ai-companion')
</body>

</html>