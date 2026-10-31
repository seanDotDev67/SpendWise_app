@php
    //  views/categories/index.blade.php
    $demo = !isset($categories);
    $r = fn($name) => Route::has($name) ? route($name) : '#';

    $months = $months ?? collect(range(5, 0))->map(fn($i) => now()->subMonths($i)->format('M'))->all();

    if ($demo) {
        $categories = [
            ['id' => 1, 'name' => 'Food', 'type' => 'expense', 'color' => 'forest', 'icon' => 'tools-kitchen-2', 'count' => 4, 'total' => 2400, 'locked' => true, 'history' => [2100, 2650, 2300, 2500, 2250], 'recent' => [['date' => 'Oct 25', 'description' => 'Milk tea with friends', 'amount' => 180], ['date' => 'Oct 20', 'description' => 'Grocery run', 'amount' => 1020], ['date' => 'Oct 11', 'description' => 'Jollibee dinner', 'amount' => 550]]],
            ['id' => 2, 'name' => 'Transportation', 'type' => 'expense', 'color' => 'sage', 'icon' => 'bus', 'count' => 3, 'total' => 1000, 'locked' => true, 'history' => [900, 1100, 950, 1200, 980], 'recent' => [['date' => 'Oct 19', 'description' => 'Grab ride to campus', 'amount' => 320], ['date' => 'Oct 9', 'description' => 'Jeepney fare', 'amount' => 280], ['date' => 'Oct 7', 'description' => 'Weekly fare', 'amount' => 400]]],
            ['id' => 3, 'name' => 'School', 'type' => 'expense', 'color' => 'rose', 'icon' => 'school', 'count' => 2, 'total' => 800, 'locked' => true, 'history' => [1200, 600, 400, 1500, 700], 'recent' => [['date' => 'Oct 22', 'description' => 'Printing and bookbinding', 'amount' => 350], ['date' => 'Oct 8', 'description' => 'Notebook and pens', 'amount' => 450]]],
            ['id' => 4, 'name' => 'Entertainment', 'type' => 'expense', 'color' => 'blush', 'icon' => 'device-gamepad-2', 'count' => 2, 'total' => 600, 'locked' => true, 'history' => [500, 750, 400, 650, 550], 'recent' => [['date' => 'Oct 24', 'description' => 'Spotify Premium', 'amount' => 150], ['date' => 'Oct 17', 'description' => 'Movie night', 'amount' => 450]]],
            ['id' => 5, 'name' => 'Others', 'type' => 'expense', 'color' => 'taupe', 'icon' => 'dots', 'count' => 2, 'total' => 350, 'locked' => true, 'history' => [300, 200, 450, 250, 400], 'recent' => [['date' => 'Oct 13', 'description' => 'Gift for a friend', 'amount' => 250], ['date' => 'Oct 3', 'description' => 'Phone load', 'amount' => 100]]],
            ['id' => 6, 'name' => 'Allowance', 'type' => 'income', 'color' => 'forest', 'icon' => 'coin', 'count' => 1, 'total' => 8000, 'locked' => true, 'history' => [8000, 8000, 8000, 8000, 8000], 'recent' => [['date' => 'Oct 1', 'description' => 'Monthly allowance', 'amount' => 8000]]],
            ['id' => 7, 'name' => 'Salary', 'type' => 'income', 'color' => 'gold', 'icon' => 'briefcase', 'count' => 1, 'total' => 2000, 'locked' => true, 'history' => [1500, 2200, 1800, 2500, 2000], 'recent' => [['date' => 'Oct 15', 'description' => 'Part-time job', 'amount' => 2000]]],
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

    // [label, bootstrap icon (same as layouts/app), link, active?]
    $nav = [
        ['Dashboard', 'grid-1x2-fill', $r('dashboard'), false],
        ['Transactions', 'arrow-left-right', $transactionsUrl, false],
        ['Categories', 'tag', $r('categories.index'), true],
        ['Budgets', 'piggy-bank', $r('budgets.index'), false],
        ['Reports', 'bar-chart', $r('reports.index'), false],
        ['Profile', 'person-circle', $profileUrl, false],
    ];

    $storeUrl = Route::has('categories.store') ? route('categories.store') : '#';
    $updateUrl = Route::has('categories.update') ? route('categories.update', ['category' => '__ID__']) : '#';
    $destroyUrl = Route::has('categories.destroy') ? route('categories.destroy', ['category' => '__ID__']) : '#';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SpendWise — Categories</title>
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

        /* ============ SIDEBAR (sticky) ============ */
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

        /* Logout: pushed to the bottom, same padding/gap as the nav links so everything lines up */
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

        /* breakdown bar */
        .bar-head { display: flex; justify-content: space-between; align-items: baseline; margin-top: 2rem; font-size: 1.4rem; }
        .bar-head b { font-weight: 500; }
        .seg { display: flex; gap: .4rem; height: 1.9rem; margin-top: .6rem; }

        .seg button {
            min-width: .8rem;
            height: 100%;
            padding: 0;
            border: none;
            border-radius: 1.2rem 1.2rem .3rem .3rem;
            opacity: var(--o, 1);
            transform-origin: bottom;
            cursor: pointer;
            animation: segIn .55s cubic-bezier(.2, .8, .2, 1) both;
            animation-delay: calc(var(--i, 0) * 60ms);
            transition: opacity .25s ease, transform .25s ease;
        }

        .seg button:hover { transform: translateY(-.2rem); }
        .seg .empty { flex: 1; background: #dcdacb; border-radius: 1.2rem 1.2rem .3rem .3rem; }

        @keyframes segIn {
            from { opacity: 0; transform: scaleY(.2); }
            to { opacity: var(--o, 1); transform: none; }
        }

        /* list + panel (flexible: the panel's right edge lines up with the bar above) */
        .split {
            display: grid;
            grid-template-columns: minmax(0, 26.2fr) minmax(0, 36.5fr);
            column-gap: 1.9rem;
            align-items: start;
            margin: 2.5rem 0 0 -.7rem;
        }

        .pills { display: flex; gap: .8rem; margin-left: .7rem; }

        .pills button {
            height: 2.2rem;
            padding: 0 1.7rem;
            border: 1px solid #ecdcdc;
            border-radius: 99px;
            background: #f6eded;
            color: #c4a3a8;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .25s ease;
        }

        .pills button.on { background: var(--green); border-color: var(--green); color: #fff; }

        .sec {
            display: flex;
            justify-content: space-between;
            margin: 2rem .8rem 1rem .7rem;
            font-size: 1.1rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .row {
            display: flex;
            align-items: center;
            gap: 1.4rem;
            padding: .9rem 1.4rem .9rem .9rem;
            margin-bottom: .7rem;
            border: 1px solid transparent;
            border-radius: 1.4rem;
            cursor: pointer;
            animation: rowIn .35s ease both;
            animation-delay: calc(var(--i, 0) * 45ms);
            transition: background .25s ease, border-color .25s ease, box-shadow .25s ease;
        }

        .row:hover { background: rgba(255, 255, 255, .5); }
        .row.on { background: #fff; border-color: #cfcfc3; box-shadow: 0 .3rem .8rem rgba(0, 0, 0, .12); }
        .row:focus-visible { outline: 2px solid rgba(59, 74, 60, .35); }

        @keyframes rowIn {
            from { opacity: 0; transform: translateX(-.8rem); }
            to { opacity: 1; transform: none; }
        }

        .ic {
            width: 3.6rem;
            height: 3.6rem;
            flex: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.9rem;
        }

        .mid { flex: 1; min-width: 0; }
        .nm { font-size: 1.4rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .track { height: .6rem; margin-top: .6rem; border-radius: 99px; background: #dddccf; overflow: hidden; }

        .track span {
            display: block;
            height: 100%;
            border-radius: 99px;
            animation: fillIn .7s cubic-bezier(.2, .8, .2, 1) both;
            animation-delay: calc(var(--i, 0) * 45ms + 120ms);
        }

        @keyframes fillIn { from { width: 0; } }

        .amt { margin-left: auto; font-size: 1.45rem; font-weight: 500; }
        .none { padding: 2rem .8rem; color: var(--muted); }

        .panel {
            min-height: 39rem;
            background: #fbfaf6;
            border-radius: 2rem;
            box-shadow: 0 .4rem 1.4rem rgba(0, 0, 0, .18);
            overflow: hidden;
        }

        .panel .in { animation: swapIn .32s ease both; }
        .panel .in.noanim { animation: none; }

        @keyframes swapIn {
            from { opacity: 0; transform: translateY(.9rem); }
            to { opacity: 1; transform: none; }
        }

        .ph { display: flex; align-items: center; gap: 1.5rem; height: 5.3rem; padding: 0 1.8rem 0 2rem; }

        .pic {
            width: 4.4rem;
            height: 4.4rem;
            flex: none;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
        }

        .pname {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 3rem;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* badge: light green outline pill, like the reference */
        .ptype {
            margin-left: auto;
            padding: .25rem 1rem;
            border-radius: 99px;
            background: rgba(139, 155, 114, .28);
            border: 1px solid rgba(139, 155, 114, .35);
            color: #6b7d52;
            font-size: 1rem;
            font-weight: 500;
        }

        .hic {
            border: none;
            background: transparent;
            color: var(--green);
            opacity: .7;
            font-size: 1.8rem;
            padding: .5rem;
            border-radius: 50%;
            cursor: pointer;
            line-height: 1;
            transition: all .2s ease;
        }

        .hic:hover { opacity: 1; background: rgba(255, 255, 255, .55); }
        .pb { padding: 1.6rem 2.6rem 2.4rem 3.2rem; }
        .tiles { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem; }
        .tile { background: #f0ebdc; border-radius: 1.3rem; padding: .9rem 1.2rem; }
        .tile small { display: block; font-size: 1.1rem; color: var(--muted); }
        .tile b { display: block; margin-top: .2rem; font-size: 1.4rem; font-weight: 500; }
        .lab { display: block; margin: 1.6rem 0 .8rem; font-size: 1.2rem; color: #7d7d6c; }
        .pb .lab:first-child { margin-top: 0; }

        /* chart: wider gaps between bars */
        .chart { display: flex; align-items: flex-end; gap: 1rem; height: 8rem; }

        .chart .b {
            flex: 1;
            border-radius: 1rem 1rem .4rem .4rem;
            opacity: .45;
            transform-origin: bottom;
            animation: barUp .6s cubic-bezier(.2, .8, .2, 1) both;
            animation-delay: calc(var(--i, 0) * 50ms);
        }

        .chart .b.cur { opacity: 1; }

        @keyframes barUp { from { transform: scaleY(0); } to { transform: none; } }

        .mons { display: flex; gap: 1rem; margin-top: .6rem; }
        .mons span { flex: 1; text-align: center; font-size: 1.2rem; color: var(--muted); }
        .mons span.cur { color: var(--green); }

        .rec {
            display: grid;
            grid-template-columns: 5.6rem 1fr auto;
            gap: 1rem;
            padding: .7rem 0;
            border-top: 1px solid var(--line);
            font-size: 1.2rem;
        }

        .rec .d { color: var(--muted); }
        .rec .d, .rec .a { white-space: nowrap; }
        .rec .t { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rec .a { color: var(--pink); }
        .rec .a.inc { color: #5c7d5c; }
        .msg { min-height: 2rem; margin-top: 1rem; font-size: 1.2rem; color: var(--pink); }
        .msg .mini { margin-left: .8rem; }

        /* form inside the panel */
        .fin {
            width: 100%;
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
        .fin:disabled { opacity: .6; }
        .sws { display: flex; gap: .9rem; }

        .sw {
            width: 2.4rem;
            height: 2.4rem;
            padding: 0;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .12);
            transition: transform .2s ease;
        }

        .sw:hover { transform: scale(1.12); }
        .sw.on { box-shadow: 0 0 0 .2rem #fbfaf6, 0 0 0 .35rem var(--green); }
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
        .acts { display: flex; gap: .9rem; margin-top: 2rem; }

        .btn {
            flex: 1;
            height: 3.4rem;
            border-radius: 1rem;
            font-size: 1.3rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .25s ease;
        }

        .btn.dark { border: none; background: var(--green); color: #fff; }
        .btn.dark:hover { background: var(--green-d); transform: translateY(-.15rem); }
        .btn.ghost { background: transparent; color: #d4707e; border: 1.5px solid #e8a8b0; }
        .btn.ghost:hover { background: #fbeff1; }

        .mini {
            height: auto;
            padding: .3rem 1rem;
            border-radius: .8rem;
            border: 1.5px solid var(--green);
            background: transparent;
            color: var(--green);
            font-size: 1.1rem;
            cursor: pointer;
        }

        .mini.danger { background: #c0707d; border-color: #c0707d; color: #fff; }
        .ferr { min-height: 2rem; margin-top: 1rem; font-size: 1.2rem; color: #c0392b; }
        .empty-panel { padding: 4rem 3rem; text-align: center; color: var(--muted); }
        .empty-panel .btn { flex: none; width: 16rem; margin-top: 1.4rem; }

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
            .split { grid-template-columns: 1fr; margin-left: 0; row-gap: 2rem; }
            .mid { width: auto; flex: 1; }
        }

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
                    <input id="q" type="text" placeholder="Search categories..." autocomplete="off">
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
                        <h1 class="title">Categories</h1>
                        <p class="sub">Organize where your money comes from and where it goes</p>
                    </div>
                    <button type="button" class="btn-add" id="add"><i class="ti ti-plus" aria-hidden="true"></i>Add
                        Category</button>
                </div>

                @if (session('status'))
                    <div class="flash ok" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="flash err" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="bar-head"><span id="barLabel">Expenses this month</span><b id="barTotal"></b></div>
                <div class="seg" id="seg"></div>

                <div class="split">
                    <div>
                        <div class="pills" id="tabs">
                            <button type="button" data-t="expense" class="on">Expense</button>
                            <button type="button" data-t="income">Income</button>
                        </div>
                        <div class="sec"><span id="secName">Expenses</span><span id="secCount">0</span></div>
                        <div id="list"></div>
                    </div>
                    <div class="panel" id="panel"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- real forms the page submits when the backend is connected --}}
    <form id="catForm" method="POST" action="#" hidden>
        @csrf
        <input type="hidden" name="_method" id="fMethod" value="POST">
        <input type="hidden" name="_cat_id" id="fId">
        <input type="hidden" name="name" id="fName">
        <input type="hidden" name="type" id="fType">
        <input type="hidden" name="color" id="fColor">
        <input type="hidden" name="icon" id="fIcon">
    </form>
    <form id="delForm" method="POST" action="#" hidden>@csrf @method('DELETE')</form>

    <script>
        (function () {
            // ===== data from Laravel =====
            const CATS = @json($categories);
            const MONTHS = @json($months);
            const DEMO = @json($demo);
            const SELECTED = @json(session('selected_id'));
            const ERR = @json($errors->any() ? $errors->first() : null);
            const OLD = @json(old());
            const URLS = { store: @json($storeUrl), update: @json($updateUrl), destroy: @json($destroyUrl) };

            const PAL = {
                forest: { c: '#3b4a3c', t: '#d9e7c9', i: '#3b4a3c' },
                sage: { c: '#b7c3a3', t: '#dde9cb', i: '#3b4a3c' },
                rose: { c: '#d9b9bd', t: '#f3e0e0', i: '#c0808b' },
                blush: { c: '#e8d3d4', t: '#f9e6e6', i: '#c98a94' },
                taupe: { c: '#cfcdc3', t: '#e5e3d8', i: '#7d7a6c' },
                gold: { c: '#dcc875', t: '#f4ebc3', i: '#a8861f' }
            };
            const ICONS = ['tools-kitchen-2', 'bus', 'school', 'device-gamepad-2', 'shopping-bag', 'home', 'gift', 'heart', 'briefcase', 'coin', 'pig-money', 'dots'];

            const $ = id => document.getElementById(id);
            const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const money = n => '₱' + Number(n).toLocaleString('en-PH', { maximumFractionDigits: 2 });

            let data = CATS.map(c => Object.assign({}, c));
            let tab = 'expense', sel = null, mode = 'view', draft = null, msg = '', confirmDel = false, q = '', nextId = 1000;

            const typeTotal = t => data.filter(c => c.type === t).reduce((s, c) => s + Number(c.total), 0);
            const isOn = c => c.id === sel && !(mode === 'form' && draft && !draft.id);
            const firstOf = t => (data.find(c => c.type === t) || {}).id || null;

            // ===== top breakdown bar =====
            function renderBar() {
                const list = data.filter(c => c.type === tab && c.total > 0);
                const grand = typeTotal(tab);
                $('barLabel').textContent = tab === 'expense' ? 'Expenses this month' : 'Income this month';
                $('barTotal').textContent = money(typeTotal(tab));
                $('seg').innerHTML = list.length ? list.map((c, i) =>
                    '<button type="button" data-id="' + c.id + '" data-tip-title="' + esc(c.name) + '" data-tip-color="' + esc((PAL[c.color] || PAL.forest).c) + '" data-tip-rows="' + esc(JSON.stringify([{ l: 'Amount', v: money(c.total) }, { l: 'Share', v: (grand > 0 ? Math.round(c.total / grand * 1000) / 10 : 0) + '%' }])) + '" aria-label="' + esc(c.name) + ': ' + money(c.total) + '" style="flex:' + c.total + ' 1 0;background:' + (PAL[c.color] || PAL.forest).c + ';--i:' + i + ';--o:' + (isOn(c) ? 1 : .55) + '"></button>'
                ).join('') : '<span class="empty"></span>';
            }

            function renderTabs() {
                document.querySelectorAll('#tabs button').forEach(b => b.classList.toggle('on', b.dataset.t === tab));
            }

            // ===== list =====
            function renderList() {
                const all = data.filter(c => c.type === tab);
                const rows = all.filter(c => !q || c.name.toLowerCase().includes(q));
                const tot = typeTotal(tab);
                $('secName').textContent = tab === 'expense' ? 'Expenses' : 'Income';
                $('secCount').textContent = all.length;
                $('list').innerHTML = rows.length ? rows.map((c, i) => {
                    const p = PAL[c.color] || PAL.forest, pct = tot ? Math.round(c.total / tot * 100) : 0;
                    return '<div class="row' + (isOn(c) ? ' on' : '') + '" data-id="' + c.id + '" role="button" tabindex="0" style="--i:' + i + '">'
                        + '<div class="ic" style="background:' + p.t + ';color:' + p.i + '"><i class="ti ti-' + c.icon + '" aria-hidden="true"></i></div>'
                        + '<div class="mid"><div class="nm">' + esc(c.name) + '</div><div class="track"><span style="width:' + pct + '%;background:' + p.c + '"></span></div></div>'
                        + '<div class="amt">' + money(c.total) + '</div></div>';
                }).join('') : '<div class="none">' + (all.length ? 'No categories match your search.' : 'No categories here yet.') + '</div>';
            }

            // ===== detail panel =====
            function viewHTML(c) {
                const p = PAL[c.color] || PAL.forest, inc = c.type === 'income';
                const tot = typeTotal(c.type), share = tot ? Math.round(c.total / tot * 100) : 0, avg = c.count ? Math.round(c.total / c.count) : 0;
                const series = c.history.concat([Number(c.total)]), max = Math.max.apply(null, series.concat([1]));
                const bars = series.map((v, i) => '<div class="b' + (i === 5 ? ' cur' : '') + '" style="height:' + Math.max(v / max * 100, 4) + '%;background:' + p.c + ';--i:' + i + '"></div>').join('');
                const mons = series.map((v, i) => '<span' + (i === 5 ? ' class="cur"' : '') + '>' + esc(MONTHS[i] || '') + '</span>').join('');
                const recent = c.recent.length ? c.recent.map(t =>
                    '<div class="rec"><span class="d">' + esc(t.date) + '</span><span class="t">' + esc(t.description) + '</span><span class="a' + (inc ? ' inc' : '') + '">' + (inc ? '+ ' : '- ') + money(t.amount) + '</span></div>'
                ).join('') : '<div class="rec" style="grid-template-columns:1fr;color:#8a8a78">No transactions yet.</div>';
                const note = confirmDel
                    ? 'Delete “' + esc(c.name) + '”?<button class="mini danger" data-act="delyes">Yes, delete</button><button class="mini" data-act="delno">Cancel</button>'
                    : esc(msg);
                return '<div class="ph" style="background:' + p.t + '">'
                    + '<div class="pic" style="color:' + p.i + '"><i class="ti ti-' + c.icon + '" aria-hidden="true"></i></div>'
                    + '<div class="pname">' + esc(c.name) + '</div>'
                    + '<span class="ptype">' + (inc ? 'Income' : 'Expense') + '</span>'
                    + '<button class="hic" data-act="edit" aria-label="Edit ' + esc(c.name) + '"><i class="ti ti-edit" aria-hidden="true"></i></button>'
                    + '<button class="hic" data-act="del" aria-label="Delete ' + esc(c.name) + '"><i class="ti ti-trash" aria-hidden="true"></i></button></div>'
                    + '<div class="pb"><div class="tiles">'
                    + '<div class="tile"><small>Transactions</small><b>' + c.count + '</b></div><div class="tile"><small>This month</small><b>' + money(c.total) + '</b></div>'
                    + '<div class="tile"><small>Average</small><b>' + money(avg) + '</b></div><div class="tile"><small>Share of ' + (inc ? 'income' : 'expenses') + '</small><b>' + share + '%</b></div></div>'
                    + '<span class="lab">Last 6 months</span><div class="chart">' + bars + '</div><div class="mons">' + mons + '</div>'
                    + '<span class="lab" style="margin-bottom:.2rem">Recent</span>' + recent
                    + '<div class="msg">' + note + '</div></div>';
            }

            function formHTML() {
                const p = PAL[draft.color] || PAL.forest;
                const cur = draft.id && data.find(x => x.id === draft.id), locked = cur && cur.locked;
                return '<div class="ph" style="background:' + p.t + '"><div class="pic" style="color:' + p.i + '"><i class="ti ti-' + draft.icon + '" aria-hidden="true"></i></div><div class="pname">' + (draft.id ? 'Edit category' : 'New category') + '</div></div>'
                    + '<div class="pb"><label class="lab" for="fnameIn">Name</label><input id="fnameIn" class="fin" type="text" maxlength="20" placeholder="Groceries" value="' + esc(draft.name) + '">'
                    + '<label class="lab" for="ftypeIn">Type</label><select id="ftypeIn" class="fin"' + (locked ? ' disabled title="Type can’t change while it has transactions"' : '') + '><option value="expense"' + (draft.type === 'expense' ? ' selected' : '') + '>Expense</option><option value="income"' + (draft.type === 'income' ? ' selected' : '') + '>Income</option></select>'
                    + '<span class="lab">Color</span><div class="sws">' + Object.keys(PAL).map(h => '<button type="button" class="sw' + (h === draft.color ? ' on' : '') + '" data-h="' + h + '" aria-label="' + h + '" style="background:' + PAL[h].c + '"></button>').join('') + '</div>'
                    + '<span class="lab">Icon</span><div class="icons">' + ICONS.map(i => '<button type="button" class="pk' + (i === draft.icon ? ' on' : '') + '" data-i="' + i + '" aria-label="' + i + '"><i class="ti ti-' + i + '" aria-hidden="true"></i></button>').join('') + '</div>'
                    + '<div class="acts"><button type="button" class="btn dark" data-act="save">Save</button><button type="button" class="btn ghost" data-act="cancel">Cancel</button></div>'
                    + '<div class="ferr" id="ferr"></div></div>';
            }

            function emptyHTML() {
                return '<div class="empty-panel"><i class="ti ti-tag" style="font-size:3.2rem" aria-hidden="true"></i><p>No ' + tab + ' categories yet.</p><button type="button" class="btn dark" data-act="new">Add one</button></div>';
            }

            function renderPanel(noAnim) {
                const c = data.find(x => x.id === sel);
                const html = mode === 'form' ? formHTML() : (c ? viewHTML(c) : emptyHTML());
                $('panel').innerHTML = '<div class="in' + (noAnim ? ' noanim' : '') + '">' + html + '</div>';
            }

            function paint() {
                document.querySelectorAll('#list .row').forEach(r => r.classList.toggle('on', isOn({ id: +r.dataset.id })));
                document.querySelectorAll('#seg button').forEach(b => b.style.setProperty('--o', isOn({ id: +b.dataset.id }) ? 1 : .55));
            }
            function renderAll() { renderTabs(); renderBar(); renderList(); renderPanel(); }

            // ===== actions =====
            function pick(id) { sel = id; mode = 'view'; draft = null; msg = ''; confirmDel = false; paint(); renderPanel(); }

            function setTab(t) {
                tab = t; mode = 'view'; draft = null; msg = ''; confirmDel = false;
                sel = firstOf(t);
                renderAll();
            }

            function startForm(c) {
                mode = 'form'; msg = ''; confirmDel = false;
                draft = c ? { id: c.id, name: c.name, type: c.type, color: c.color, icon: c.icon }
                    : { id: null, name: '', type: tab, color: 'forest', icon: 'tools-kitchen-2' };
                paint(); renderPanel();
                const n = $('fnameIn'); if (n) n.focus();
            }

            function cancelForm() { mode = 'view'; draft = null; if (!data.find(c => c.id === sel)) sel = firstOf(tab); paint(); renderPanel(); }

            function save() {
                const name = draft.name.trim(); let err = '';
                if (!name) err = 'Enter a category name.';
                else if (name.length > 20) err = 'Keep the name to 20 characters.';
                else if (data.some(c => c.type === draft.type && c.id !== draft.id && c.name.toLowerCase() === name.toLowerCase())) err = 'You already have a ' + draft.type + ' category with that name.';
                if (err) { $('ferr').textContent = err; return; }

                if (DEMO) {                                   // preview only, nothing is saved
                    if (draft.id) {
                        Object.assign(data.find(c => c.id === draft.id), { name: name, type: draft.type, color: draft.color, icon: draft.icon });
                        sel = draft.id;
                    } else {
                        const id = nextId++;
                        data.push({ id: id, name: name, type: draft.type, color: draft.color, icon: draft.icon, count: 0, total: 0, locked: false, history: [0, 0, 0, 0, 0], recent: [] });
                        sel = id;
                    }
                    tab = draft.type; mode = 'view'; draft = null; renderAll();
                    return;
                }
                $('catForm').action = draft.id ? URLS.update.replace('__ID__', draft.id) : URLS.store;   // real save
                $('fMethod').value = draft.id ? 'PUT' : 'POST';
                $('fId').value = draft.id || '';
                $('fName').value = name; $('fType').value = draft.type; $('fColor').value = draft.color; $('fIcon').value = draft.icon;
                $('catForm').submit();
            }

            function remove() {
                if (DEMO) { data = data.filter(c => c.id !== sel); sel = firstOf(tab); confirmDel = false; msg = ''; renderAll(); return; }
                $('delForm').action = URLS.destroy.replace('__ID__', sel);
                $('delForm').submit();
            }

            // ===== events =====
            $('add').addEventListener('click', () => startForm(null));
            $('tabs').addEventListener('click', e => { const b = e.target.closest('button'); if (b && b.dataset.t !== tab) setTab(b.dataset.t); });

            // search: filters the current tab, and jumps to the other tab if that's where the match is
            $('q').addEventListener('input', () => {
                q = $('q').value.trim().toLowerCase();
                if (q && !data.some(c => c.type === tab && c.name.toLowerCase().includes(q))) {
                    const m = data.find(c => c.name.toLowerCase().includes(q));
                    if (m) { tab = m.type; sel = m.id; mode = 'view'; draft = null; msg = ''; confirmDel = false; renderAll(); return; }
                }
                renderList();
            });

            $('list').addEventListener('click', e => { const r = e.target.closest('.row'); if (r) pick(+r.dataset.id); });
            $('list').addEventListener('keydown', e => {
                const r = e.target.closest('.row');
                if (r && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); pick(+r.dataset.id); }
            });
            $('seg').addEventListener('click', e => { const b = e.target.closest('button'); if (b) pick(+b.dataset.id); });

            const initialQuery = new URLSearchParams(window.location.search).get('q');
            if (initialQuery) { $('q').value = initialQuery; $('q').dispatchEvent(new Event('input')); }

            $('panel').addEventListener('input', e => { if (e.target.id === 'fnameIn') { draft.name = e.target.value; $('ferr').textContent = ''; } });
            $('panel').addEventListener('change', e => { if (e.target.id === 'ftypeIn') draft.type = e.target.value; });
            $('panel').addEventListener('click', e => {
                const b = e.target.closest('button'); if (!b) return;
                if (b.dataset.h) { draft.color = b.dataset.h; renderPanel(true); return; }
                if (b.dataset.i) { draft.icon = b.dataset.i; renderPanel(true); return; }
                const a = b.dataset.act, c = data.find(x => x.id === sel);
                if (a === 'new') startForm(null);
                if (a === 'edit' && c) startForm(c);
                if (a === 'cancel') cancelForm();
                if (a === 'save') save();
                if (a === 'del' && c) {
                    if (c.locked) { msg = c.name + ' still has transactions, so it can’t be deleted yet.'; confirmDel = false; }
                    else { confirmDel = true; msg = ''; }
                    renderPanel(true);
                }
                if (a === 'delno') { confirmDel = false; renderPanel(true); }
                if (a === 'delyes') remove();
            });
            document.addEventListener('keydown', e => { if (e.key === 'Escape' && mode === 'form') cancelForm(); });

            // ===== start =====
            const chosen = SELECTED && data.find(c => c.id === +SELECTED);
            if (chosen) { tab = chosen.type; sel = chosen.id; }
            else { const f = data.find(c => c.type === 'expense') || data[0]; if (f) { tab = f.type; sel = f.id; } }
            if (ERR && OLD && OLD.name !== undefined) {         // validation failed: reopen the form with what they typed
                draft = { id: OLD._cat_id ? +OLD._cat_id : null, name: OLD.name || '', type: OLD.type || 'expense', color: OLD.color || 'forest', icon: OLD.icon || 'tools-kitchen-2' };
                mode = 'form'; tab = draft.type;
            }
            renderAll();
        })();
    </script>
    @include('layouts.partials.motion')
    @include('layouts.partials.ai-companion')
</body>

</html>