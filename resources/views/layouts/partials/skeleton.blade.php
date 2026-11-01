{{--
    Page-loading skeleton. Each <template> mirrors the layout of the page the user is navigating to
    (same grid, card count and proportions), so the placeholder matches what will appear.
    The overlay sits below the top bar, so the sidebar and header stay in place.
--}}
@php
    $skeletonPaths = [
        'dashboard' => parse_url(route('dashboard'), PHP_URL_PATH),
        'transactions' => parse_url(route('transactions.page'), PHP_URL_PATH),
        'categories' => parse_url(route('categories.index'), PHP_URL_PATH),
        'budgets' => parse_url(route('budgets.index'), PHP_URL_PATH),
        'reports' => parse_url(route('reports.index'), PHP_URL_PATH),
        'profile' => parse_url(route('profile.edit'), PHP_URL_PATH),
    ];
@endphp
<style>
    .sw-skeleton { position: absolute; left: 0; right: 0; top: 0; z-index: 1010; display: none; overflow: hidden; min-height: 100vh; background: var(--sk-bg); --sk-bg: #e8e1cc; --sk-base: #d8d2ba; --sk-card: #faf9f4; --sk-shine: rgba(255, 255, 255, .6); }
    .sw-skeleton.is-active { display: block; animation: sw-sk-in .15s ease-out; }
    body[data-theme="dark"] .sw-skeleton { --sk-bg: #141a16; --sk-base: #2b3a30; --sk-card: #1c241f; --sk-shine: rgba(255, 255, 255, .08); }
    @media (prefers-color-scheme: dark) { body[data-theme="system"] .sw-skeleton { --sk-bg: #141a16; --sk-base: #2b3a30; --sk-card: #1c241f; --sk-shine: rgba(255, 255, 255, .08); } }

    .sk { display: block; position: relative; overflow: hidden; flex: none; background: var(--sk-base); border-radius: 8px; }
    .sk::after { content: ""; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, var(--sk-shine), transparent); animation: sw-sk-shimmer 1.3s infinite; }
    .sk.round { border-radius: 50%; }
    .sk.pill { border-radius: 99px; }
    .sk-card { min-width: 0; padding: 16px; background: var(--sk-card); border-radius: 12px; box-shadow: 0 2px 6px rgba(0, 0, 0, .08); }
    .sk-card.beige { background: #f4f0e4; }
    .sk-card.pink { background: #ffe3e3; }
    body[data-theme="dark"] .sk-card.beige, body[data-theme="dark"] .sk-card.pink { background: var(--sk-card); }
    .sk-flex { display: flex; align-items: center; gap: 12px; }
    .sk-between { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .sk-col { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
    .sk-mt { margin-top: 16px; }

    /* Dashboard */
    .skd { display: grid; grid-template-columns: minmax(0, 3fr) minmax(0, 1fr); gap: 16px 24px; margin-top: 8px; }
    .skd-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
    .skd-pair { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 16px; }
    .skd-pair.bottom { grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); }
    @media (max-width: 1199px) { .skd { grid-template-columns: 1fr; } }
    @media (max-width: 991px) { .skd-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .skd-pair, .skd-pair.bottom { grid-template-columns: 1fr; } }

    /* Transactions */
    .skt { padding: 26px 28px 36px; }
    .skt-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 15px; margin: 22px 0 18px; }
    .skt-overview { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); gap: 18px; margin-bottom: 18px; }
    .skt .sk-card { padding: 20px; border-radius: 14px; }
    @media (max-width: 991px) { .skt-overview { grid-template-columns: 1fr; } .skt { padding: 22px 14px; } }
    @media (max-width: 640px) { .skt-summary { grid-template-columns: 1fr; } }

    /* Reports */
    .skr { margin-top: 16px; }
    .skr-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin: 16px 0 12px; }
    .skr-charts { display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(0, .92fr); gap: 12px; margin-bottom: 12px; }
    @media (max-width: 991px) { .skr-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } .skr-charts { grid-template-columns: 1fr; } }

    /* Profile */
    .skp { max-width: 1040px; margin: 24px auto 40px; display: grid; gap: 16px; }
    .skp .sk-card { padding: 24px 28px; border-radius: 14px; }
    .skp-themes { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
    @media (max-width: 640px) { .skp .sk-card { padding: 18px; } .skp-themes { grid-template-columns: 1fr; } }

    /* Categories + Budgets use the same rem scale and zoom as those pages */
    .sk-u { font-size: max(min(1vw, 1.547vh), 5px); zoom: .78; }
    @media (max-width: 991px) { .sk-u { font-size: 13px; } }
    .skc-split { display: grid; grid-template-columns: minmax(0, 26.2fr) minmax(0, 36.5fr); column-gap: 1.9rem; margin: 2.5rem 0 0 -.7rem; align-items: start; }
    .skb-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.4rem; }
    @media (max-width: 991px) { .skc-split { grid-template-columns: 1fr; margin-left: 0; } .skb-grid { grid-template-columns: 1fr; } }

    @keyframes sw-sk-shimmer { to { transform: translateX(100%); } }
    @keyframes sw-sk-in { from { opacity: 0; } to { opacity: 1; } }
    @media (prefers-reduced-motion: reduce) { .sk::after { animation: none; } }
    body[data-reduced-motion="true"] .sk::after { animation: none; }
</style>

<div class="sw-skeleton" id="swSkeleton" role="status" aria-live="polite" aria-label="Loading page"></div>

{{-- ================= Dashboard ================= --}}
<template data-sk="dashboard">
    <div class="skd">
        <div>
            <div class="sk" style="width:300px;max-width:70%;height:34px"></div>
            <div class="sk" style="width:260px;max-width:60%;height:14px;margin:10px 0 16px"></div>
            <div class="skd-stats">
                @foreach (['beige', 'pink', 'pink', 'beige'] as $tone)
                    <div class="sk-card {{ $tone }}" style="height:132px">
                        <div class="sk round" style="width:32px;height:32px"></div>
                        <div class="sk" style="width:70%;height:12px;margin-top:12px"></div>
                        <div class="sk" style="width:55%;height:22px;margin-top:10px"></div>
                        <div class="sk" style="width:80%;height:9px;margin-top:12px"></div>
                    </div>
                @endforeach
            </div>
            <div class="skd-pair">
                <div class="sk-card" style="height:240px">
                    <div class="sk" style="width:60%;height:20px"></div>
                    <div class="sk" style="width:40%;height:9px;margin-top:8px"></div>
                    <div class="sk-flex" style="gap:24px;margin-top:22px">
                        <div class="sk round" style="width:130px;height:130px"></div>
                        <div class="sk-col" style="flex:1">
                            @for ($i = 0; $i < 4; $i++)<div class="sk" style="height:11px;width:{{ 90 - $i * 12 }}%"></div>@endfor
                        </div>
                    </div>
                </div>
                <div class="sk-card" style="height:240px">
                    <div class="sk" style="width:55%;height:20px"></div>
                    @for ($i = 0; $i < 4; $i++)
                        <div class="sk-flex" style="margin-top:16px">
                            <div class="sk round" style="width:16px;height:16px"></div>
                            <div class="sk-col" style="flex:1">
                                <div class="sk" style="height:10px;width:100%"></div>
                                <div class="sk pill" style="height:7px;width:{{ 90 - $i * 18 }}%"></div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
            <div class="skd-pair bottom">
                <div class="sk-card beige" style="height:300px">
                    <div class="sk-flex">
                        <div class="sk round" style="width:32px;height:32px"></div>
                        <div class="sk-col" style="flex:1"><div class="sk" style="height:16px;width:60%"></div><div class="sk" style="height:9px;width:35%"></div></div>
                    </div>
                    @for ($i = 0; $i < 3; $i++)
                        <div class="sk-between" style="margin-top:22px"><div class="sk" style="height:11px;width:25%"></div><div class="sk" style="height:11px;width:25%"></div></div>
                        <div class="sk pill" style="height:10px;width:100%;margin-top:8px"></div>
                    @endfor
                </div>
                <div class="sk-card beige" style="height:300px">
                    <div class="sk-flex">
                        <div class="sk round" style="width:32px;height:32px"></div>
                        <div class="sk-col" style="flex:1"><div class="sk" style="height:16px;width:45%"></div><div class="sk" style="height:9px;width:60%"></div></div>
                        <div class="sk pill" style="height:20px;width:110px"></div>
                    </div>
                    <div class="sk" style="height:18px;width:50%;margin-top:26px"></div>
                    <div class="sk" style="height:11px;width:95%;margin-top:14px"></div>
                    <div class="sk" style="height:11px;width:80%;margin-top:8px"></div>
                    <div class="sk" style="height:90px;width:100%;margin-top:18px;border-radius:10px"></div>
                </div>
            </div>
        </div>
        <div>
            <div class="sk-card" style="height:250px">
                <div class="sk" style="height:20px;width:100%;border-radius:5px"></div>
                <div style="display:grid;grid-template-columns:repeat(8,1fr);gap:8px;margin-top:16px">
                    @for ($i = 0; $i < 40; $i++)<div class="sk round" style="height:16px;width:16px"></div>@endfor
                </div>
            </div>
            <div class="sk-card sk-mt" style="height:190px">
                <div class="sk" style="width:50%;height:20px"></div>
                <div class="sk" style="height:38px;margin-top:16px;border-radius:10px"></div>
                <div class="sk" style="height:38px;margin-top:8px;border-radius:10px"></div>
                <div class="sk" style="height:38px;margin-top:8px;border-radius:10px"></div>
            </div>
            <div class="sk-card sk-mt">
                <div class="sk-between"><div class="sk" style="width:60%;height:20px"></div><div class="sk" style="width:20%;height:11px"></div></div>
                @for ($i = 0; $i < 5; $i++)
                    <div class="sk-between" style="margin-top:16px">
                        <div class="sk-col" style="flex:1"><div class="sk" style="height:11px;width:70%"></div><div class="sk" style="height:8px;width:40%"></div></div>
                        <div class="sk" style="height:11px;width:22%"></div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</template>

{{-- ================= Transactions ================= --}}
<template data-sk="transactions">
    <div class="skt">
        <div class="sk-between" style="align-items:flex-start">
            <div class="sk-col" style="gap:10px">
                <div class="sk" style="width:130px;height:11px"></div>
                <div class="sk" style="width:260px;max-width:100%;height:32px"></div>
                <div class="sk" style="width:380px;max-width:100%;height:12px"></div>
            </div>
            <div class="sk" style="width:170px;height:44px;border-radius:12px"></div>
        </div>
        <div class="skt-summary">
            @for ($i = 0; $i < 3; $i++)
                <div class="sk-card" style="height:112px">
                    <div class="sk-between"><div class="sk" style="width:90px;height:11px"></div><div class="sk round" style="width:32px;height:32px"></div></div>
                    <div class="sk" style="width:140px;height:26px;margin-top:14px"></div>
                    <div class="sk" style="width:100px;height:10px;margin-top:12px"></div>
                </div>
            @endfor
        </div>
        <div class="skt-overview">
            <div class="sk-card">
                <div class="sk" style="width:140px;height:20px"></div>
                <div class="sk" style="height:260px;margin-top:17px;border-radius:18px"></div>
                <div class="sk-flex" style="margin-top:16px"><div class="sk round" style="width:34px;height:34px"></div><div class="sk" style="width:60%;height:12px"></div></div>
            </div>
            <div class="sk-card">
                <div class="sk-between"><div class="sk" style="width:170px;height:20px"></div><div class="sk round" style="width:34px;height:34px"></div></div>
                @for ($i = 0; $i < 4; $i++)
                    <div class="sk-flex" style="margin-top:20px">
                        <div class="sk round" style="width:40px;height:40px"></div>
                        <div class="sk-col" style="flex:1"><div class="sk" style="height:10px;width:35%"></div><div class="sk" style="height:16px;width:55%"></div></div>
                    </div>
                @endfor
            </div>
        </div>
        <div class="sk-card">
            <div class="sk" style="width:190px;height:20px"></div>
            <div class="sk-flex" style="margin-top:16px;flex-wrap:wrap">
                <div class="sk" style="flex:1;min-width:180px;height:42px;border-radius:10px"></div>
                <div class="sk" style="width:140px;height:42px;border-radius:10px"></div>
                <div class="sk" style="width:140px;height:42px;border-radius:10px"></div>
            </div>
            @for ($i = 0; $i < 6; $i++)
                <div class="sk-flex" style="margin-top:18px">
                    <div class="sk round" style="width:42px;height:42px"></div>
                    <div class="sk-col" style="flex:1"><div class="sk" style="height:13px;width:40%"></div><div class="sk" style="height:9px;width:25%"></div></div>
                    <div class="sk" style="height:14px;width:90px"></div>
                </div>
            @endfor
        </div>
    </div>
</template>

{{-- ================= Reports ================= --}}
<template data-sk="reports">
    <div class="skr">
        <div class="sk-between" style="align-items:flex-start;flex-wrap:wrap">
            <div class="sk-col" style="gap:8px">
                <div class="sk" style="width:140px;height:10px"></div>
                <div class="sk" style="width:180px;height:30px"></div>
                <div class="sk" style="width:320px;max-width:100%;height:11px"></div>
            </div>
            <div class="sk-flex">
                <div class="sk" style="width:130px;height:36px;border-radius:10px"></div>
                <div class="sk" style="width:130px;height:36px;border-radius:10px"></div>
                <div class="sk" style="width:80px;height:36px;border-radius:10px"></div>
            </div>
        </div>
        <div class="skr-summary">
            @for ($i = 0; $i < 4; $i++)
                <div class="sk-card" style="height:92px;padding:14px">
                    <div class="sk" style="width:55%;height:10px"></div>
                    <div class="sk" style="width:70%;height:22px;margin-top:12px"></div>
                    <div class="sk" style="width:40%;height:9px;margin-top:10px"></div>
                </div>
            @endfor
        </div>
        @for ($row = 0; $row < 2; $row++)
            <div class="skr-charts">
                <div class="sk-card" style="padding:14px;height:270px">
                    <div class="sk" style="width:200px;max-width:70%;height:18px"></div>
                    <div class="sk" style="width:130px;height:9px;margin-top:8px"></div>
                    <div class="sk" style="height:170px;margin-top:16px;border-radius:10px"></div>
                </div>
                <div class="sk-card" style="padding:14px;height:270px">
                    <div class="sk" style="width:170px;max-width:70%;height:18px"></div>
                    <div class="sk" style="width:110px;height:9px;margin-top:8px"></div>
                    @for ($i = 0; $i < 4; $i++)
                        <div class="sk-flex" style="margin-top:16px"><div class="sk round" style="width:12px;height:12px"></div><div class="sk" style="height:10px;flex:1"></div><div class="sk" style="width:60px;height:10px"></div></div>
                    @endfor
                </div>
            </div>
        @endfor
        <div class="skr-charts">
            <div class="sk-card" style="padding:14px">
                <div class="sk" style="width:180px;height:18px"></div>
                @for ($i = 0; $i < 5; $i++)
                    <div class="sk-between" style="margin-top:14px"><div class="sk" style="width:30%;height:11px"></div><div class="sk" style="width:20%;height:11px"></div><div class="sk" style="width:15%;height:11px"></div></div>
                @endfor
            </div>
            <div class="sk-card" style="padding:14px">
                <div class="sk" style="width:140px;height:18px"></div>
                <div class="sk" style="width:120px;height:24px;margin-top:16px"></div>
                <div class="sk pill" style="height:10px;margin-top:12px"></div>
                <div class="sk" style="width:90%;height:10px;margin-top:14px"></div>
            </div>
        </div>
    </div>
</template>

{{-- ================= Profile ================= --}}
<template data-sk="profile">
    <div class="skp">
        <div class="sk-flex" style="gap:16px;flex-wrap:wrap">
            <div class="sk round" style="width:64px;height:64px"></div>
            <div class="sk-col" style="flex:1;gap:8px"><div class="sk" style="width:130px;height:10px"></div><div class="sk" style="width:240px;max-width:100%;height:28px"></div><div class="sk" style="width:200px;max-width:100%;height:11px"></div></div>
            <div class="sk pill" style="width:170px;height:28px"></div>
        </div>
        <div class="sk-card">
            <div class="sk" style="width:140px;height:17px"></div>
            <div class="sk" style="width:60%;height:12px;margin-top:8px"></div>
            <div class="sk-flex" style="gap:20px;margin-top:22px">
                <div class="sk round" style="width:88px;height:88px"></div>
                <div class="sk-flex"><div class="sk" style="width:130px;height:40px;border-radius:10px"></div><div class="sk" style="width:90px;height:40px;border-radius:10px"></div></div>
            </div>
        </div>
        <div class="sk-card">
            <div class="sk" style="width:220px;height:17px"></div>
            <div class="sk" style="width:55%;height:12px;margin-top:8px"></div>
            <div class="skp-themes" style="margin-top:20px">
                @for ($i = 0; $i < 3; $i++)<div class="sk" style="height:76px;border-radius:12px"></div>@endfor
            </div>
            <div class="sk" style="width:120px;height:40px;margin-top:20px;border-radius:10px"></div>
        </div>
        @foreach ([2, 3] as $fields)
            <div class="sk-card">
                <div class="sk" style="width:200px;height:17px"></div>
                <div class="sk" style="width:55%;height:12px;margin-top:8px"></div>
                @for ($i = 0; $i < $fields; $i++)
                    <div class="sk" style="width:90px;height:11px;margin-top:20px"></div>
                    <div class="sk" style="height:42px;margin-top:8px;border-radius:10px"></div>
                @endfor
                <div class="sk" style="width:120px;height:40px;margin-top:20px;border-radius:10px"></div>
            </div>
        @endforeach
        <div class="sk-card">
            <div class="sk" style="width:160px;height:17px"></div>
            <div class="sk" style="width:70%;height:12px;margin-top:10px"></div>
            <div class="sk" style="width:140px;height:40px;margin-top:20px;border-radius:10px"></div>
        </div>
    </div>
</template>

{{-- ================= Categories ================= --}}
<template data-sk="categories">
    <div class="sk-u">
        <div class="sk-between" style="align-items:flex-start;margin-top:2.2rem">
            <div class="sk-col" style="gap:.8rem">
                <div class="sk" style="width:24rem;height:3.4rem"></div>
                <div class="sk" style="width:46rem;max-width:100%;height:1.3rem"></div>
            </div>
            <div class="sk" style="width:17rem;height:3.4rem;margin-top:1.2rem;border-radius:.9rem"></div>
        </div>
        <div class="sk-between" style="margin-top:2rem"><div class="sk" style="width:22rem;height:1.4rem"></div><div class="sk" style="width:10rem;height:1.4rem"></div></div>
        <div style="display:flex;gap:.4rem;margin-top:.6rem;height:1.9rem">
            @foreach ([5, 3, 2, 1] as $flex)<div class="sk" style="flex:{{ $flex }};height:100%;border-radius:1.2rem 1.2rem .3rem .3rem"></div>@endforeach
        </div>
        <div class="skc-split">
            <div>
                <div class="sk-flex" style="gap:.8rem;margin-left:.7rem"><div class="sk pill" style="width:9rem;height:2.2rem"></div><div class="sk pill" style="width:9rem;height:2.2rem"></div></div>
                <div class="sk-between" style="margin:2rem .8rem 1rem .7rem"><div class="sk" style="width:10rem;height:1.1rem"></div><div class="sk" style="width:2rem;height:1.1rem"></div></div>
                @for ($i = 0; $i < 6; $i++)
                    <div class="sk-flex" style="gap:1.4rem;padding:.9rem 1.4rem .9rem .9rem;margin-bottom:.7rem">
                        <div class="sk round" style="width:3.6rem;height:3.6rem"></div>
                        <div class="sk-col" style="flex:1;gap:.6rem"><div class="sk" style="height:1.4rem;width:50%"></div><div class="sk pill" style="height:.6rem;width:{{ 90 - $i * 10 }}%"></div></div>
                        <div class="sk" style="width:7rem;height:1.45rem"></div>
                    </div>
                @endfor
            </div>
            <div class="sk-card" style="min-height:39rem;padding:0;border-radius:2rem;box-shadow:0 .4rem 1.4rem rgba(0,0,0,.18)">
                <div class="sk-flex" style="gap:1.5rem;height:5.3rem;padding:0 1.8rem 0 2rem">
                    <div class="sk round" style="width:4.4rem;height:4.4rem"></div>
                    <div class="sk" style="width:20rem;height:3rem"></div>
                </div>
                <div style="padding:1.4rem 2rem">
                    <div class="sk" style="width:12rem;height:1.2rem"></div>
                    <div class="sk" style="height:6rem;margin-top:1rem;border-radius:1.2rem"></div>
                    <div class="sk" style="width:14rem;height:1.2rem;margin-top:2rem"></div>
                    @for ($i = 0; $i < 3; $i++)<div class="sk" style="height:4rem;margin-top:1rem;border-radius:1rem"></div>@endfor
                </div>
            </div>
        </div>
    </div>
</template>

{{-- ================= Budgets ================= --}}
<template data-sk="budgets">
    <div class="sk-u">
        <div class="sk-between" style="align-items:flex-start;margin-top:2.2rem">
            <div class="sk-col" style="gap:.8rem">
                <div class="sk" style="width:18rem;height:3.4rem"></div>
                <div class="sk" style="width:40rem;max-width:100%;height:1.3rem"></div>
            </div>
            <div class="sk-flex" style="gap:1.2rem;margin-top:1.2rem">
                <div class="sk" style="width:13rem;height:3.4rem;border-radius:.9rem"></div>
                <div class="sk" style="width:17rem;height:3.4rem;border-radius:.9rem"></div>
            </div>
        </div>
        <div class="sk-card" style="margin-top:2rem;padding:1.6rem 2.2rem 1.8rem;border-radius:2rem;box-shadow:0 .3rem 1rem rgba(0,0,0,.08)">
            <div class="sk-between">
                <div class="sk-flex" style="gap:1rem"><div class="sk round" style="width:2.8rem;height:2.8rem"></div><div class="sk" style="width:15rem;height:1.5rem"></div><div class="sk round" style="width:2.8rem;height:2.8rem"></div></div>
                <div class="sk pill" style="width:9rem;height:2rem"></div>
            </div>
            <div class="sk-flex" style="gap:4rem;margin-top:.8rem;flex-wrap:wrap">
                <div class="sk" style="width:24rem;height:14.4rem;border-radius:12rem 12rem 0 0"></div>
                <div class="sk-col" style="flex:1;min-width:20rem;gap:0">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="sk-between" style="padding:.8rem 0"><div class="sk" style="width:12rem;height:1.3rem"></div><div class="sk" style="width:10rem;height:1.6rem"></div></div>
                    @endfor
                </div>
            </div>
            <div class="sk" style="height:4.2rem;margin-top:1.2rem;border-radius:1.1rem"></div>
            <div class="sk" style="width:70%;height:1.1rem;margin-top:1rem"></div>
        </div>
        <div class="sk-between" style="margin:2.4rem 0 1rem"><div class="sk" style="width:16rem;height:1.1rem"></div><div class="sk" style="width:14rem;height:1.1rem"></div></div>
        <div class="skb-grid">
            @for ($i = 0; $i < 6; $i++)
                <div class="sk-card" style="padding:1.3rem 1.4rem 1.2rem;border-radius:1.6rem;box-shadow:0 .2rem .7rem rgba(0,0,0,.07)">
                    <div class="sk-flex" style="gap:1.2rem">
                        <div class="sk round" style="width:3.6rem;height:3.6rem"></div>
                        <div class="sk-col" style="flex:1;gap:.6rem"><div class="sk" style="height:1.4rem;width:60%"></div><div class="sk" style="height:1.1rem;width:40%"></div></div>
                    </div>
                    <div class="sk pill" style="height:.9rem;margin-top:1.4rem"></div>
                    <div class="sk-between" style="margin-top:1rem"><div class="sk" style="width:35%;height:1.1rem"></div><div class="sk" style="width:25%;height:1.1rem"></div></div>
                </div>
            @endfor
        </div>
    </div>
</template>

<script>
    (function () {
        var overlay = document.getElementById('swSkeleton');
        if (!overlay) return;

        var pages = @json($skeletonPaths);
        var norm = function (p) { return p.replace(/\/+$/, '') || '/'; };
        var byPath = {};
        Object.keys(pages).forEach(function (key) { byPath[norm(pages[key])] = key; });
        // /transactions is the resource index route and renders the same page as /transactions/page.
        byPath[norm(pages.transactions.replace(/\/page$/, ''))] = 'transactions';

        var container = document.querySelector('.sw-main') || document.querySelector('.main');
        var header = document.querySelector('.sw-topbar') || document.querySelector('.top');

        var show = function (path) {
            var key = byPath[norm(path)];
            var tpl = key && document.querySelector('template[data-sk="' + key + '"]');
            if (!tpl || !container) return false;

            var style = getComputedStyle(container);
            if (style.position === 'static') container.style.position = 'relative';
            if (overlay.parentNode !== container) container.appendChild(overlay);
            overlay.style.left = style.paddingLeft;
            overlay.style.right = style.paddingRight;
            overlay.style.top = (header ? header.offsetTop + header.offsetHeight : 0) + 'px';

            overlay.innerHTML = '';
            overlay.appendChild(tpl.content.cloneNode(true));
            overlay.classList.add('is-active');
            window.scrollTo(0, 0);
            return true;
        };

        window.addEventListener('pageshow', function () { overlay.classList.remove('is-active'); });

        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var a = e.target.closest && e.target.closest('a[href]');
            if (!a || a.target === '_blank' || a.hasAttribute('download') || a.origin !== location.origin) return;
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
            if (a.hasAttribute('data-bs-toggle') || a.hasAttribute('onclick')) return;
            if (a.pathname === location.pathname && a.search === location.search) return;
            show(a.pathname);
        });

        document.addEventListener('submit', function (e) {
            var f = e.target;
            if (!f || f.target === '_blank') return;
            // Let handlers that cancel the submit (AJAX forms) opt out first.
            setTimeout(function () {
                if (e.defaultPrevented) return;
                var url = new URL(f.getAttribute('action') || location.href, location.href);
                if (url.origin !== location.origin) return;
                if (f.method.toLowerCase() === 'get' || byPath[norm(url.pathname)]) { show(url.pathname); return; }
                // A POST to a sub-route (e.g. /profile/photo) normally returns to the page it came from.
                if (url.pathname.split('/')[1] === location.pathname.split('/')[1]) show(location.pathname);
            }, 0);
        });
    })();
</script>
