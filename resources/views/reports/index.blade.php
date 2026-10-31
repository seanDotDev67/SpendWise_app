@extends('layouts.app')

@section('title', 'Reports')

@push('styles')
<style>
    .sw-reports {
        --report-ink: #2F4A3A;
        --report-muted: #78847d;
        --report-border: rgba(47, 74, 58, .12);
        color: var(--report-ink);
        padding: 18px 8px 28px;
    }

    .reports-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 292px;
        align-items: start;
        gap: 16px;
    }

    .report-main { min-width: 0; }
    .report-heading, .report-card-heading, .report-ai-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .report-heading { margin-bottom: 16px; }
    .report-eyebrow {
        color: #66815d;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 1.4px;
    }
    .report-heading h1 {
        margin: 2px 0 2px;
        font-size: clamp(25px, 3vw, 32px);
        line-height: 1.1;
    }
    .report-heading p, .report-card-subtitle {
        margin: 0;
        color: var(--report-muted);
        font-size: 11px;
    }
    .report-date-form {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 7px;
        padding: 8px;
        background: rgba(250, 250, 248, .78);
        border: 1px solid var(--report-border);
        border-radius: 10px;
    }
    .report-date-form label {
        display: grid;
        gap: 3px;
        color: var(--report-muted);
        font-size: 9px;
    }
    .report-date-form input {
        width: 125px;
        min-height: 32px;
        padding: 4px 7px;
        border: 1px solid var(--report-border);
        border-radius: 6px;
        background: #fff;
        color: var(--report-ink);
        font: inherit;
        font-size: 10px;
    }
    .report-date-form button {
        min-height: 32px;
        padding: 5px 10px;
        border: 0;
        border-radius: 6px;
        background: var(--report-ink);
        color: #fff;
        font: inherit;
        font-size: 10px;
        font-weight: 600;
    }
    .report-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }
    .report-stat, .report-card, .report-ai-panel {
        min-width: 0;
        border: 1px solid var(--report-border);
        border-radius: 12px;
        background: rgba(250, 250, 248, .88);
        box-shadow: 0 2px 7px rgba(47, 74, 58, .06);
    }
    .report-stat { padding: 12px; }
    .report-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        color: var(--report-muted);
        font-size: 10px;
    }
    .report-stat-icon {
        display: grid;
        width: 27px;
        height: 27px;
        flex: 0 0 27px;
        place-items: center;
        border-radius: 50%;
        background: #e5eee2;
        color: #2f704f;
        font-size: 12px;
    }
    .report-stat-value {
        margin-top: 6px;
        overflow-wrap: anywhere;
        font-size: clamp(15px, 1.6vw, 20px);
        font-weight: 700;
        letter-spacing: -.4px;
    }
    .report-stat-note { color: var(--report-muted); font-size: 9px; }
    .report-card { padding: 14px; }
    .report-card h2 {
        margin: 0;
        font-family: 'Cormorant Garamond', serif;
        font-size: 18px;
        font-weight: 700;
    }
    .report-card-subtitle { margin-top: 2px; }
    .report-chart-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(0, .92fr);
        gap: 12px;
        margin-bottom: 12px;
    }
    .report-bar-legend, .report-line-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        color: var(--report-muted);
        font-size: 9px;
    }
    .report-legend-item { display: inline-flex; align-items: center; gap: 5px; }
    .report-legend-dot { width: 8px; height: 8px; border-radius: 50%; }
    .income-color { background: #24835e; }
    .expense-color { background: #e47782; }
    .net-color { background: #6885c8; }
    .report-bars {
        display: flex;
        height: 154px;
        align-items: end;
        gap: 8px;
        overflow-x: auto;
        padding: 12px 3px 0;
    }
    .report-month-bars {
        display: flex;
        min-width: 32px;
        height: 100%;
        flex: 1 0 32px;
        flex-direction: column;
        align-items: center;
        justify-content: end;
        gap: 5px;
        color: var(--report-muted);
        font-size: 8px;
    }
    .report-bar-pair {
        display: flex;
        width: 100%;
        height: 128px;
        align-items: end;
        justify-content: center;
        gap: 3px;
        border-bottom: 1px solid var(--report-border);
    }
    .report-bar {
        width: 9px;
        min-height: 0;
        border-radius: 4px 4px 0 0;
    }
    .report-bar.income { background: #24835e; }
    .report-bar.expenses { background: #e47782; }
    .report-chart-empty {
        display: grid;
        min-height: 145px;
        place-items: center;
        color: var(--report-muted);
        font-size: 11px;
        text-align: center;
    }
    .report-category-chart {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-top: 12px;
    }
    .report-donut {
        position: relative;
        width: clamp(95px, 10vw, 120px);
        height: clamp(95px, 10vw, 120px);
        flex: 0 0 auto;
        border-radius: 50%;
    }
    .report-donut-hole {
        position: absolute;
        inset: 25%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #fafaf8;
        text-align: center;
    }
    .report-donut-hole strong { font-size: 11px; }
    .report-donut-hole span { color: var(--report-muted); font-size: 8px; }
    .report-category-legend {
        display: grid;
        min-width: 0;
        flex: 1;
        gap: 7px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .report-category-legend li {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 6px;
        font-size: 9px;
    }
    .report-category-legend .report-category-name {
        min-width: 0;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .report-category-legend .report-category-percent { color: var(--report-muted); }
    .report-category-swatch {
        width: 8px;
        height: 8px;
        flex: 0 0 8px;
        border-radius: 50%;
    }
    .report-trend-svg { display: block; width: 100%; height: 155px; margin-top: 7px; overflow: visible; }
    .report-grid-line { stroke: rgba(47, 74, 58, .12); stroke-width: .6; }
    .report-trend-line { fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; vector-effect: non-scaling-stroke; }
    .report-legend-row { display: flex; gap: 10px; font-size: 9px; }
    .report-cf-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 8px; }
    .report-cf-summary div { background: rgba(47, 74, 58, .05); border-radius: 8px; padding: 6px 8px; display: flex; flex-direction: column; gap: 2px; min-width: 0; }
    .report-cf-summary span { font-size: 8px; color: var(--report-muted); text-transform: uppercase; letter-spacing: .04em; }
    .report-cf-summary strong { font-size: 10px; overflow-wrap: anywhere; }
    .report-cf-summary .pos, .report-cf-val.pos, .report-cf-label .pos { color: #24835e; }
    .report-cf-summary .neg, .report-cf-val.neg, .report-cf-label .neg { color: #e47782; }
    .report-cf { display: flex; gap: 10px; height: 180px; margin-top: 12px; padding: 0 4px; position: relative; }
    .report-cf-col { flex: 1 1 0; min-width: 0; max-width: 90px; margin: 0 auto; display: flex; flex-direction: column; }
    .report-cf-top, .report-cf-bottom { display: flex; flex-direction: column; align-items: center; }
    .report-cf-top { justify-content: flex-end; border-bottom: 1px dashed rgba(47, 74, 58, .35); }
    .report-cf-bottom { justify-content: flex-start; }
    .report-cf-bar { display: block; width: 62%; min-height: 3px; transition: height .3s; }
    .report-cf-bar.pos { background: linear-gradient(180deg, #3fae83, #24835e); border-radius: 6px 6px 0 0; }
    .report-cf-bar.neg { background: linear-gradient(180deg, #e47782, #f0a1a8); border-radius: 0 0 6px 6px; }
    .report-cf-val { font-size: 9px; font-weight: 700; padding: 2px 0; white-space: nowrap; }
    .report-cf-labels { display: flex; gap: 10px; margin-top: 6px; padding: 0 4px; }
    .report-cf-label { flex: 1 1 0; min-width: 0; max-width: 90px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; font-size: 8px; color: var(--report-muted); }
    .report-cf-label strong { font-size: 9px; color: inherit; }
    .report-trend-line.income { stroke: #24835e; }
    .report-trend-line.expenses { stroke: #e47782; }
    .report-trend-line.net { stroke: #6885c8; }
    .report-trend-dates {
        display: flex;
        justify-content: space-between;
        gap: 4px;
        color: var(--report-muted);
        font-size: 8px;
    }
    .report-top-list { display: grid; gap: 10px; margin-top: 12px; }
    .report-top-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 5px 8px; font-size: 10px; }
    .report-top-name { overflow: hidden; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
    .report-top-amount { color: var(--report-muted); }
    .report-top-track { grid-column: 1 / -1; height: 5px; overflow: hidden; border-radius: 999px; background: #e8e8df; }
    .report-top-fill { height: 100%; border-radius: inherit; }
    .report-recent { margin-top: 12px; }
    .report-recent-heading { margin-bottom: 8px; }
    .report-goal-figures { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 8px; margin-top: 14px; }
    .report-goal-figures strong { font-size: 22px; }
    .report-goal-figures span { color: var(--report-muted); font-size: 11px; }
    .report-goal-track { height: 10px; margin-top: 10px; }
    .report-goal-note { margin: 12px 0 0; color: var(--report-muted); font-size: 11px; line-height: 1.5; }
    .report-goal-item + .report-goal-item { margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--report-border); }
    .report-recent-link { color: var(--report-ink); font-size: 10px; text-decoration: none; }
    .report-recent-link:hover { text-decoration: underline; }
    .report-table-wrap { overflow-x: auto; }
    .report-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .report-table th { padding: 7px 6px; color: var(--report-muted); font-size: 9px; font-weight: 500; text-align: left; }
    .report-table td { padding: 8px 6px; border-top: 1px solid var(--report-border); white-space: nowrap; }
    .report-table .report-amount { text-align: right; font-weight: 600; }
    .report-type-pill { display: inline-block; padding: 3px 7px; border-radius: 999px; background: #e5f2e9; color: #24744f; font-size: 8px; }
    .report-type-pill.expense { background: #fff0ef; color: #c6535f; }
    .report-empty { padding: 22px 10px; color: var(--report-muted); font-size: 11px; text-align: center; }

    .report-ai-panel { position: sticky; top: 12px; overflow: hidden; }
    .report-ai-heading { padding: 12px; background: #e9eee5; }
    .report-ai-title { display: flex; align-items: center; gap: 9px; }
    .report-ai-actions { display: flex; align-items: center; gap: 5px; }
    .report-ai-new {
        padding: 5px 8px;
        border: 1px solid var(--report-border);
        border-radius: 7px;
        background: #fff;
        color: var(--report-ink);
        font: inherit;
        font-size: 9px;
    }
    .report-ai-mark {
        display: grid;
        width: 32px;
        height: 32px;
        place-items: center;
        border-radius: 10px;
        background: #2f704f;
        color: #fff;
    }
    .report-ai-title h2 { margin: 0; font-size: 13px; font-weight: 700; }
    .report-ai-title p { margin: 2px 0 0; color: var(--report-muted); font-size: 9px; }
    .report-ai-collapse {
        display: none;
        border: 0;
        background: transparent;
        color: var(--report-ink);
    }
    .report-ai-body { padding: 11px; }
    .report-ai-welcome, .report-chat-bubble {
        border: 1px solid var(--report-border);
        border-radius: 11px;
        background: #fff;
        padding: 10px;
        font-size: 10px;
        line-height: 1.55;
        overflow-wrap: anywhere;
        white-space: pre-wrap;
    }
    .report-ai-welcome strong { display: block; margin-bottom: 3px; }
    .report-ai-section-label { margin: 12px 0 6px; font-size: 9px; font-weight: 700; }
    .report-ai-suggestions { display: grid; gap: 5px; }
    .report-ai-suggestions[hidden] { display: none; }
    .report-ai-suggestion {
        padding: 7px 9px;
        border: 1px solid var(--report-border);
        border-radius: 999px;
        background: #fff;
        color: var(--report-ink);
        font: inherit;
        font-size: 9px;
        text-align: left;
    }
    .report-ai-suggestion:hover { background: #eef3eb; }
    .report-chat-log {
        display: grid;
        max-height: 240px;
        gap: 7px;
        overflow-y: auto;
        margin: 10px 0;
    }
    .report-chat-bubble { max-width: 96%; }
    .report-chat-bubble.md { white-space: normal; }
    .report-chat-bubble.md p { margin: 0; }
    .report-chat-bubble.md p + p, .report-chat-bubble.md p + ul, .report-chat-bubble.md p + ol,
    .report-chat-bubble.md ul + p, .report-chat-bubble.md ol + p { margin-top: 8px; }
    .report-chat-bubble.md ul, .report-chat-bubble.md ol { margin: 6px 0 0; padding-left: 20px; }
    .report-chat-bubble.md li + li { margin-top: 3px; }
    .report-chat-bubble.md code { padding: 1px 5px; border-radius: 5px; background: rgba(47, 112, 79, 0.12); font-size: 0.92em; }
    .report-chat-bubble.user { justify-self: end; border-color: #2f704f; background: #2f704f; color: #fff; }
    .report-chat-bubble.error { border-color: #e47782; color: #a53e49; }
    .report-chat-loading { color: var(--report-muted); font-size: 9px; }
    .report-chat-form { display: flex; gap: 6px; }
    .report-chat-form input {
        min-width: 0;
        min-height: 38px;
        flex: 1;
        padding: 8px 10px;
        border: 1px solid var(--report-border);
        border-radius: 8px;
        font: inherit;
        font-size: 10px;
    }
    .report-chat-form button {
        width: 38px;
        border: 0;
        border-radius: 8px;
        background: #2f704f;
        color: #fff;
    }
    .report-ai-note { margin: 9px 0 0; color: var(--report-muted); font-size: 8px; line-height: 1.45; }
    .report-ai-config-warning { margin: 8px 0 0; color: #a53e49; font-size: 9px; line-height: 1.45; }
    .report-focus { grid-column: 1 / -1; background: #f3f0e5; }

    @media (max-width: 1199.98px) {
        .reports-layout { grid-template-columns: minmax(0, 1fr); }
        .report-ai-panel { position: static; }
        .report-ai-collapse { display: inline-grid; width: 30px; height: 30px; place-items: center; }
        .report-ai-panel.collapsed .report-ai-body { display: none; }
        .report-ai-panel.collapsed .report-ai-collapse i { transform: rotate(180deg); }
    }
    @media (max-width: 760px) {
        .sw-reports { padding-inline: 0; }
        .report-heading { align-items: flex-start; flex-direction: column; }
        .report-date-form { width: 100%; }
        .report-date-form label { flex: 1; }
        .report-date-form input { width: 100%; }
        .report-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .report-chart-grid { grid-template-columns: minmax(0, 1fr); }
        .report-category-chart { justify-content: flex-start; }
    }
</style>
@endpush

@section('content')
<div class="sw-reports">
    <div class="reports-layout">
        <main class="report-main">
            <div class="report-heading">
                <div>
                    <div class="report-eyebrow"><i class="bi bi-bar-chart-line-fill" aria-hidden="true"></i> FINANCIAL REPORTS</div>
                    <h1 class="serif fw-semibold">Reports</h1>
                    <p>Understand your income, spending, and cash flow.</p>
                </div>
                <form class="report-date-form" method="GET" action="{{ route('reports.index') }}">
                    <label>
                        From
                        <input type="date" name="start_date" value="{{ $startDate }}" required>
                    </label>
                    <label>
                        To
                        <input type="date" name="end_date" value="{{ $endDate }}" required>
                    </label>
                    <button type="submit"><i class="bi bi-calendar-check" aria-hidden="true"></i> Apply</button>
                </form>
            </div>

            <section class="report-summary-grid" aria-label="Financial summary">
                @foreach ([
                    ['Total Income', $report['income'], 'bi-arrow-down-left', '#24835e'],
                    ['Total Expenses', $report['expenses'], 'bi-arrow-up-right', '#df7580'],
                    ['Net Cash Flow', $report['net'], 'bi-wallet2', '#7283d1'],
                ] as [$label, $amount, $icon, $color])
                    <article class="report-stat">
                        <div class="report-stat-top">
                            <span>{{ $label }}</span>
                            <span class="report-stat-icon" style="background: {{ $color }}18; color: {{ $color }}">
                                <i class="bi {{ $icon }}" aria-hidden="true"></i>
                            </span>
                        </div>
                        <div class="report-stat-value">₱{{ number_format($amount, 2) }}</div>
                        <div class="report-stat-note">Selected date range</div>
                    </article>
                @endforeach
                <article class="report-stat">
                    <div class="report-stat-top">
                        <span>Total Transactions</span>
                        <span class="report-stat-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                    </div>
                    <div class="report-stat-value">{{ number_format($report['transactionCount']) }}</div>
                    <div class="report-stat-note">Selected date range</div>
                </article>
            </section>

            <div class="report-chart-grid">
                <section class="report-card" aria-labelledby="income-expenses-title">
                    <div class="report-card-heading">
                        <div>
                            <h2 id="income-expenses-title">Income vs. Expenses</h2>
                            <p class="report-card-subtitle">Monthly totals in the selected range</p>
                        </div>
                        <div class="report-bar-legend" aria-label="Chart legend">
                            <span class="report-legend-item"><i class="report-legend-dot income-color"></i> Income</span>
                            <span class="report-legend-item"><i class="report-legend-dot expense-color"></i> Expenses</span>
                        </div>
                    </div>
                    @if ($report['transactionCount'] === 0)
                        <div class="report-chart-empty">No transactions recorded in this date range.</div>
                    @else
                        <div class="report-bars" role="img" aria-label="Monthly income and expense comparison">
                            @foreach ($report['incomeExpenseBars'] as $month)
                                @php
                                    $monthNet = (float) $month['income'] - (float) $month['expenses'];
                                    $monthRows = [
                                        ['k' => 'income', 'l' => 'Income', 'v' => '₱'.number_format($month['income'], 2)],
                                        ['k' => 'expenses', 'l' => 'Expenses', 'v' => '₱'.number_format($month['expenses'], 2)],
                                        ['l' => 'Net', 'v' => ($monthNet < 0 ? '-' : '').'₱'.number_format(abs($monthNet), 2)],
                                    ];
                                @endphp
                                <div class="report-month-bars" data-tip-title="{{ $month['label'] }}" data-tip-rows="{{ json_encode($monthRows) }}">
                                    <div class="report-bar-pair">
                                        <span class="report-bar income" style="height: {{ $month['incomeHeight'] }}%"></span>
                                        <span class="report-bar expenses" style="height: {{ $month['expenseHeight'] }}%"></span>
                                    </div>
                                    <span>{{ \Carbon\Carbon::createFromFormat('Y-m', $month['key'])->format('M') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="report-card" aria-labelledby="category-chart-title">
                    <div class="report-card-heading">
                        <div>
                            <h2 id="category-chart-title">Expenses by Category</h2>
                            <p class="report-card-subtitle">Share of recorded expenses</p>
                        </div>
                    </div>
                    @if ($report['categories']->isEmpty())
                        <div class="report-chart-empty">No expenses recorded in this date range.</div>
                    @else
                        <div class="report-category-chart">
                            <div class="report-donut" role="img" aria-label="Expense share by category"
                                 style="background: {{ $report['categoryGradient'] }}"
                                 data-hl-group="cat"
                                 data-donut="{{ json_encode($report['categories']->map(fn ($c) => ['n' => $c['name'], 'v' => (float) $c['spent'], 'a' => '₱'.number_format($c['spent'], 2), 'p' => $c['percentage'], 'c' => $c['color']])->values()) }}">
                                <div class="report-donut-hole">
                                    <strong>₱{{ number_format($report['expenses'], 2) }}</strong>
                                    <span>Total expenses</span>
                                </div>
                            </div>
                            <ul class="report-category-legend">
                                @foreach ($report['categories']->take(5) as $category)
                                    <li data-hl="cat:{{ $loop->index }}">
                                        <span class="report-category-swatch" style="background: {{ $category['color'] }}"></span>
                                        <span class="report-category-name">{{ $category['name'] }}</span>
                                        <span class="report-category-percent">{{ $category['percentage'] }}%</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>

                <section class="report-card" aria-labelledby="monthly-trend-title">
                    <div class="report-card-heading">
                        <div>
                            <h2 id="monthly-trend-title">Monthly Cash Flow</h2>
                            <p class="report-card-subtitle">Income, expenses, and net cash flow</p>
                        </div>
                        <div class="report-legend-row">
                            <span class="report-legend-item"><i class="report-legend-dot income-color"></i> Surplus</span>
                            <span class="report-legend-item"><i class="report-legend-dot expense-color"></i> Deficit</span>
                        </div>
                    </div>
                    @if ($report['transactionCount'] === 0)
                        <div class="report-chart-empty">No monthly trend is available until transactions are recorded.</div>
                    @else
                        @php
                            $months = $report['monthly']->values();
                            $maxPos = max(0, (float) $months->max('net'));
                            $maxNeg = abs(min(0, (float) $months->min('net')));
                            $posShare = ($maxPos + $maxNeg) > 0 ? $maxPos / ($maxPos + $maxNeg) : 1;
                            $posShare = $maxNeg == 0 ? 1 : ($maxPos == 0 ? 0 : max(0.2, min(0.8, $posShare)));
                            $totalNet = (float) $months->sum('net');
                            $best = $months->sortByDesc('net')->first();
                            $avg = $months->count() ? $totalNet / $months->count() : 0;
                            $short = function (float $v): string {
                                $a = abs($v);
                                $s = $a >= 1000000 ? number_format($a / 1000000, 1) . 'M' : ($a >= 1000 ? number_format($a / 1000, 1) . 'k' : number_format($a, 0));
                                return ($v < 0 ? '-' : '') . '₱' . $s;
                            };
                        @endphp
                        <div class="report-cf-summary">
                            <div><span>Total net</span><strong class="{{ $totalNet < 0 ? 'neg' : 'pos' }}">{{ $totalNet < 0 ? '-' : '+' }}₱{{ number_format(abs($totalNet), 2) }}</strong></div>
                            <div><span>Avg / month</span><strong class="{{ $avg < 0 ? 'neg' : 'pos' }}">{{ $avg < 0 ? '-' : '+' }}₱{{ number_format(abs($avg), 2) }}</strong></div>
                            <div><span>Best month</span><strong>{{ $best['label'] }}</strong></div>
                        </div>
                        <div class="report-cf" role="img" aria-label="Monthly net cash flow columns">
                            @foreach ($months as $m)
                                @php
                                    $up = $m['net'] >= 0;
                                    $h = $up
                                        ? ($maxPos > 0 ? max(3, round($m['net'] / $maxPos * 100)) : 0)
                                        : ($maxNeg > 0 ? max(3, round(abs($m['net']) / $maxNeg * 100)) : 0);
                                @endphp
                                <div class="report-cf-col" data-tip-title="{{ $m['label'] }}" data-tip-rows="{{ json_encode([
                                    ['l' => 'Income', 'v' => '₱'.number_format($m['income'], 2)],
                                    ['l' => 'Expenses', 'v' => '₱'.number_format($m['expenses'], 2)],
                                    ['l' => 'Net', 'v' => ($m['net'] < 0 ? '-' : '').'₱'.number_format(abs($m['net']), 2)],
                                ]) }}">
                                    <div class="report-cf-top" style="height: {{ round($posShare * 100, 2) }}%">
                                        @if ($up)
                                            <span class="report-cf-val pos">{{ $short((float) $m['net']) }}</span>
                                            <i class="report-cf-bar pos" style="height: {{ $h }}%"></i>
                                        @endif
                                    </div>
                                    <div class="report-cf-bottom" style="height: {{ round((1 - $posShare) * 100, 2) }}%">
                                        @unless ($up)
                                            <i class="report-cf-bar neg" style="height: {{ $h }}%"></i>
                                            <span class="report-cf-val neg">{{ $short((float) $m['net']) }}</span>
                                        @endunless
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="report-cf-labels">
                            @foreach ($months as $m)
                                <div class="report-cf-label">
                                    <strong>{{ $m['label'] }}</strong>
                                    <span class="pos">▲ {{ $short((float) $m['income']) }}</span>
                                    <span class="neg">▼ {{ $short((float) $m['expenses']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="report-card" aria-labelledby="top-categories-title">
                    <div class="report-card-heading">
                        <div>
                            <h2 id="top-categories-title">Top Spending Categories</h2>
                            <p class="report-card-subtitle">Ranked by actual expenses</p>
                        </div>
                    </div>
                    @if ($report['topCategories']->isEmpty())
                        <div class="report-chart-empty">Your spending categories will appear here after you record expenses.</div>
                    @else
                        <div class="report-top-list">
                            @foreach ($report['topCategories'] as $category)
                                <div class="report-top-item" data-tip-title="{{ $category['name'] }}" data-tip-color="{{ $category['color'] }}"
                                     data-tip-rows="{{ json_encode([['l' => 'Spent', 'v' => '₱'.number_format($category['spent'], 2)], ['l' => 'Share of expenses', 'v' => $category['percentage'].'%']]) }}">
                                    <span class="report-top-name">{{ $category['name'] }}</span>
                                    <span class="report-top-amount">₱{{ number_format($category['spent'], 2) }} · {{ $category['percentage'] }}%</span>
                                    <div class="report-top-track">
                                        <div class="report-top-fill" style="width: {{ min(100, $category['percentage']) }}%; background: {{ $category['color'] }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="report-card report-recent" aria-labelledby="recent-transactions-title">
                    <div class="report-card-heading report-recent-heading">
                        <div>
                            <h2 id="recent-transactions-title">Recent Transactions</h2>
                            <p class="report-card-subtitle">Latest activity in this date range</p>
                        </div>
                        <a class="report-recent-link" href="{{ route('transactions.page') }}">View all transactions <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    @if ($report['recentTransactions']->isEmpty())
                        <div class="report-empty">No transactions recorded in this date range.</div>
                    @else
                        <div class="report-table-wrap">
                            <table class="report-table">
                                <thead>
                                    <tr><th>Date</th><th>Description</th><th>Category</th><th>Type</th><th class="text-end">Amount</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['recentTransactions'] as $transaction)
                                        <tr>
                                            <td>{{ $transaction->date->format('M j, Y') }}</td>
                                            <td>{{ $transaction->description ?: 'Transaction' }}</td>
                                            <td>{{ $transaction->category?->name ?? 'Uncategorized' }}</td>
                                            <td><span class="report-type-pill {{ $transaction->type }}">{{ ucfirst($transaction->type) }}</span></td>
                                            <td class="report-amount">₱{{ number_format((float) $transaction->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="report-card report-recent report-goal" aria-labelledby="savings-goals-title">
                    <div class="report-card-heading">
                        <div>
                            <h2 id="savings-goals-title">Savings Goals</h2>
                            <p class="report-card-subtitle">Progress from the goals you set in Budgets</p>
                        </div>
                        <span class="report-stat-icon"><i class="bi bi-bullseye" aria-hidden="true"></i></span>
                    </div>
                    @forelse ($goals as $goal)
                        @php
                            $goalPercent = min(100, (int) round($goal->saved / $goal->target * 100));
                            $goalRemaining = max(0, $goal->target - $goal->saved);
                        @endphp
                        <div class="report-goal-item">
                            <div class="report-card-heading">
                                <strong>{{ $goal->name }}</strong>
                                <span>{{ $goalPercent }}%</span>
                            </div>
                            <div class="report-goal-figures">
                                <strong>₱{{ number_format($goal->saved, 2) }}</strong>
                                <span>of ₱{{ number_format($goal->target, 2) }}</span>
                            </div>
                            <div class="report-top-track report-goal-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $goalPercent }}" aria-label="{{ $goal->name }} savings goal progress">
                                <div class="report-top-fill" style="width: {{ $goalPercent }}%; background: {{ $goalPercent >= 100 ? '#2F704F' : '#C49A54' }}"></div>
                            </div>
                            <p class="report-goal-note">
                                @if ($goalPercent >= 100)
                                    Goal reached — you’ve saved ₱{{ number_format($goal->saved, 2) }}.
                                @else
                                    ₱{{ number_format($goalRemaining, 2) }} left to reach this goal.
                                    @if ($goal->deadline)
                                        Deadline: {{ $goal->deadline->format('M j, Y') }}.
                                    @endif
                                @endif
                            </p>
                        </div>
                    @empty
                        <div class="report-chart-empty">
                            No savings goals yet. <a class="report-recent-link" href="{{ route('budgets.index') }}">Create one in Budgets</a>.
                        </div>
                    @endforelse
                </section>

                <section class="report-card report-focus" aria-label="Report date range">
                    <div class="d-flex align-items-center gap-2">
                        <span class="report-stat-icon"><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                        <div>
                            <strong style="font-size: 10px">Showing records from {{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}</strong>
                            <div class="report-stat-note">All amounts and insights are calculated from your own saved transactions.</div>
                        </div>
                    </div>
                </section>
            </main>

            @php
                $assistantFirstName = preg_split('/\s+/', trim(auth()->user()->name))[0] ?: 'there';
                $assistantGreeting = match (true) {
                    now()->hour < 12 => 'Good morning',
                    now()->hour < 18 => 'Good afternoon',
                    default => 'Good evening',
                };
            @endphp

            <aside class="report-ai-panel" id="reportAiPanel">
                <div class="report-ai-heading">
                    <div class="report-ai-title">
                        <span class="report-ai-mark"><i class="bi bi-stars" aria-hidden="true"></i></span>
                        <div>
                            <h2>SpendWise AI</h2>
                            <p>Ask me anything — finance or otherwise</p>
                        </div>
                    </div>
                    <div class="report-ai-actions">
                        <button class="report-ai-new" id="reportAiNew" type="button">New chat</button>
                        <button class="report-ai-collapse" id="reportAiCollapse" type="button"
                                aria-expanded="true" aria-controls="reportAiBody" aria-label="Collapse assistant">
                            <i class="bi bi-chevron-up" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="report-ai-body" id="reportAiBody">
                    <div class="report-ai-welcome">
                        <strong>{{ $assistantGreeting }}, {{ $assistantFirstName }}! 👋</strong>
                        I can help with everyday questions, learning, coding, or your finances. When you ask about your finances, I’ll use your saved records for the selected date range.
                    </div>
                    <div class="report-ai-section-label">Try asking me:</div>
                    <div class="report-ai-suggestions">
                        @foreach ([
                            'Where does most of my money go?',
                            'Show my expenses for the last six months.',
                            'Compare my income and expenses.',
                            'Show my daily spending trend.',
                            'What happens if I reduce my shopping expenses by 15%?',
                            'How can I reduce my expenses?',
                            'Am I staying within my budgets?',
                            'Compare my spending this month and last month.',
                            'Help me create a savings plan.',
                            'Explain compound interest.',
                            'Can you help me with a programming question?',
                        ] as $suggestion)
                            <button class="report-ai-suggestion" type="button">{{ $suggestion }}</button>
                        @endforeach
                    </div>
                    <div class="report-chat-log" id="reportChatLog" role="log" aria-live="polite">
                        @forelse ($assistantHistory as $message)
                            @if (in_array($message['role'] ?? '', ['user', 'assistant'], true) && is_string($message['content'] ?? null))
                                <div class="report-chat-bubble {{ $message['role'] }}" @if (is_array($message['insight'] ?? null)) data-insight="{{ json_encode($message['insight']) }}" @endif>{{ $message['content'] }}</div>
                            @endif
                        @empty
                            <div class="report-chat-bubble assistant">{{ $assistantGreeting }}, {{ $assistantFirstName }}! 👋 I’m here to help. Ask me anything, or ask about your finances for a thoughtful look at your recorded data.</div>
                        @endforelse
                    </div>
                    <form class="report-chat-form" id="reportChatForm">
                        <label class="visually-hidden" for="reportChatInput">Ask SpendWise AI anything</label>
                        <input id="reportChatInput" type="text" maxlength="1000" required
                               placeholder="Ask me anything…">
                        <button type="submit" aria-label="Send message"><i class="bi bi-send-fill" aria-hidden="true"></i></button>
                    </form>
                    @unless ($assistantConfigured)
                        <p class="report-ai-config-warning" role="status">SpendWise AI needs to be configured by the administrator before it can respond. Your conversation is not sent anywhere until then.</p>
                    @endunless
                    <p class="report-ai-note">Your conversation is kept in your signed-in session. Financial questions use only aggregate totals from your records; transaction descriptions and account identity are not sent. Budget records are not currently available.</p>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('reportChatForm');
    const input = document.getElementById('reportChatInput');
    const log = document.getElementById('reportChatLog');
    const collapseButton = document.getElementById('reportAiCollapse');
    const newChatButton = document.getElementById('reportAiNew');
    const panel = document.getElementById('reportAiPanel');

    function appendInline(parent, text) {
        // Supports **bold**, __bold__, *italic*, _italic_ and `code`; everything else stays plain text.
        const pattern = /(\*\*([^*\n]+?)\*\*|__([^_\n]+?)__|`([^`\n]+?)`|(?<![\w*])\*([^*\s][^*\n]*?)\*(?![\w*])|(?<![\w])_([^_\s][^_\n]*?)_(?![\w]))/g;
        let last = 0;
        let match;
        while ((match = pattern.exec(text)) !== null) {
            if (match.index > last) parent.appendChild(document.createTextNode(text.slice(last, match.index)));
            let node;
            if (match[2] !== undefined || match[3] !== undefined) {
                node = document.createElement('strong');
                node.textContent = match[2] ?? match[3];
            } else if (match[4] !== undefined) {
                node = document.createElement('code');
                node.textContent = match[4];
            } else {
                node = document.createElement('em');
                node.textContent = match[5] ?? match[6];
            }
            parent.appendChild(node);
            last = pattern.lastIndex;
        }
        if (last < text.length) parent.appendChild(document.createTextNode(text.slice(last)));
    }

    // Builds DOM nodes (never innerHTML), so model output cannot inject markup.
    function renderMarkdown(target, text) {
        const fragment = document.createDocumentFragment();
        let paragraph = null;
        let list = null;

        const closeBlocks = function () { paragraph = null; list = null; };

        text.replace(/\r\n?/g, '\n').split('\n').forEach(function (rawLine) {
            const line = rawLine.trimEnd();
            if (line.trim() === '') { closeBlocks(); return; }

            const bullet = line.match(/^\s*[-*•]\s+(.*)$/);
            const numbered = line.match(/^\s*\d+[.)]\s+(.*)$/);
            const heading = line.match(/^\s*#{1,6}\s+(.*)$/);

            if (bullet || numbered) {
                const tag = bullet ? 'UL' : 'OL';
                if (!list || list.tagName !== tag) {
                    list = document.createElement(tag.toLowerCase());
                    fragment.appendChild(list);
                }
                paragraph = null;
                const item = document.createElement('li');
                appendInline(item, (bullet || numbered)[1]);
                list.appendChild(item);
            } else if (heading) {
                closeBlocks();
                const block = document.createElement('p');
                const strong = document.createElement('strong');
                appendInline(strong, heading[1]);
                block.appendChild(strong);
                fragment.appendChild(block);
            } else {
                list = null;
                if (!paragraph) {
                    paragraph = document.createElement('p');
                    fragment.appendChild(paragraph);
                } else {
                    paragraph.appendChild(document.createElement('br'));
                }
                appendInline(paragraph, line);
            }
        });

        target.classList.add('md');
        target.replaceChildren(fragment);
    }

    log.querySelectorAll('.report-chat-bubble.assistant').forEach(function (bubble) {
        renderMarkdown(bubble, bubble.textContent);
        if (bubble.dataset.insight && window.SpendWiseAI && window.SpendWiseAI.renderInsight) {
            try { window.SpendWiseAI.renderInsight(bubble, JSON.parse(bubble.dataset.insight)); } catch (e) { /* skip unreadable saved visuals */ }
        }
    });

    function addMessage(text, kind) {
        const message = document.createElement('div');
        message.className = 'report-chat-bubble ' + kind;
        message.textContent = text;
        log.appendChild(message);
        log.scrollTop = log.scrollHeight;
        return message;
    }

    async function ask(question) {
        const value = question.trim();
        if (!value || form.dataset.loading === 'true') return;

        addMessage(value, 'user');
        const loading = addMessage('Thinking…', 'report-chat-loading');
        form.dataset.loading = 'true';
        form.querySelector('button').disabled = true;
        input.disabled = true;

        let bubble = null;
        let pending = '';
        let shown = '';
        let streamEnded = false;
        let drainResolve = null;
        let lastFrame = 0;
        let rafId = null;

        // Reveal queued text at a steady pace; the speed rises with the backlog so it never lags far behind.
        const pump = function (now) {
            rafId = null;
            const elapsed = Math.min(now - (lastFrame || now), 100);
            lastFrame = now;
            if (pending.length > 0 && bubble) {
                const charsPerSecond = Math.max(40, pending.length * 2.5);
                const count = Math.max(1, Math.round(charsPerSecond * elapsed / 1000));
                shown += pending.slice(0, count);
                pending = pending.slice(count);
                renderMarkdown(bubble, shown);
                log.scrollTop = log.scrollHeight;
            }
            if (pending.length > 0 || !streamEnded) {
                rafId = requestAnimationFrame(pump);
            } else if (drainResolve) {
                drainResolve();
            }
        };
        const startPump = function () {
            if (rafId === null) {
                lastFrame = 0;
                rafId = requestAnimationFrame(pump);
            }
        };

        try {
            const response = await fetch(@json(route('reports.assistant.stream')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/x-ndjson',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    page: 'reports',
                    question: value,
                    start_date: @json($startDate),
                    end_date: @json($endDate)
                })
            });

            if (!response.ok || !response.body) {
                let result = {};
                try { result = await response.json(); } catch {}
                throw new Error(result.message || 'The assistant could not answer right now. Please try again.');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let finished = false;
            let finalAnswer = '';
            let finalVisuals = [];
            let finalInsight = null;

            const handle = function (line) {
                if (!line.trim()) return;
                let event;
                try { event = JSON.parse(line); } catch { return; }

                if (event.type === 'token') {
                    if (!bubble) {
                        loading.remove();
                        bubble = addMessage('', 'assistant');
                    }
                    pending += event.text;
                    startPump();
                } else if (event.type === 'done') {
                    finished = true;
                    finalAnswer = event.answer;
                    finalVisuals = event.visuals || [];
                    finalInsight = event.insight || null;
                } else if (event.type === 'error') {
                    throw new Error(event.message);
                }
            };

            while (true) {
                const { done, value: chunk } = await reader.read();
                if (done) break;
                buffer += decoder.decode(chunk, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop();
                lines.forEach(handle);
            }
            handle(buffer);

            if (!finished) {
                throw new Error('The assistant response was interrupted. Please try again.');
            }

            streamEnded = true;
            if (bubble && pending.length > 0) {
                await new Promise(function (resolve) { drainResolve = resolve; startPump(); });
            }
            if (bubble) {
                renderMarkdown(bubble, finalAnswer);
                if (window.SpendWiseAI && finalInsight && window.SpendWiseAI.renderInsight) {
                    window.SpendWiseAI.renderInsight(bubble, finalInsight);
                    log.scrollTop = log.scrollHeight;
                } else if (window.SpendWiseAI && window.SpendWiseAI.renderVisuals) {
                    window.SpendWiseAI.renderVisuals(bubble, finalVisuals);
                    log.scrollTop = log.scrollHeight;
                }
            }
        } catch (error) {
            streamEnded = true;
            pending = '';
            if (rafId !== null) cancelAnimationFrame(rafId);
            loading.remove();
            if (bubble) bubble.remove();
            addMessage(error.message || 'Unable to reach the assistant. Please try again.', 'error');
        } finally {
            form.dataset.loading = 'false';
            form.querySelector('button').disabled = false;
            input.disabled = false;
            input.focus();
        }
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const question = input.value;
        input.value = '';
        ask(question);
    });

    document.querySelectorAll('.report-ai-suggestion').forEach(function (button) {
        button.addEventListener('click', function () {
            const list = button.closest('.report-ai-suggestions');
            if (list) {
                list.hidden = true;
                if (list.previousElementSibling) list.previousElementSibling.hidden = true;
            }
            ask(button.textContent);
        });
    });

    newChatButton.addEventListener('click', async function () {
        if (form.dataset.loading === 'true') return;

        newChatButton.disabled = true;
        try {
            const response = await fetch(@json(route('reports.assistant.reset')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            if (!response.ok) {
                throw new Error('Unable to start a new conversation. Please try again.');
            }

            log.replaceChildren();
            document.querySelectorAll('.report-ai-suggestions').forEach(function (list) {
                list.hidden = false;
                if (list.previousElementSibling) list.previousElementSibling.hidden = false;
            });
            addMessage('Hi! What would you like to talk about?', 'assistant');
        } catch (error) {
            addMessage(error.message || 'Unable to start a new conversation. Please try again.', 'error');
        } finally {
            newChatButton.disabled = false;
        }
    });

    collapseButton.addEventListener('click', function () {
        const collapsed = panel.classList.toggle('collapsed');
        collapseButton.setAttribute('aria-expanded', String(!collapsed));
        collapseButton.setAttribute('aria-label', collapsed ? 'Expand assistant' : 'Collapse assistant');
    });
});
</script>
@endpush
