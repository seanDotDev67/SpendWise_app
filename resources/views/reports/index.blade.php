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
    .report-trend-line { fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
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
        white-space: pre-line;
    }
    .report-ai-welcome strong { display: block; margin-bottom: 3px; }
    .report-ai-section-label { margin: 12px 0 6px; font-size: 9px; font-weight: 700; }
    .report-ai-suggestions { display: grid; gap: 5px; }
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
                                <div class="report-month-bars" title="{{ $month['label'] }}: income ₱{{ number_format($month['income'], 2) }}, expenses ₱{{ number_format($month['expenses'], 2) }}">
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
                                 style="background: {{ $report['categoryGradient'] }}">
                                <div class="report-donut-hole">
                                    <strong>₱{{ number_format($report['expenses'], 2) }}</strong>
                                    <span>Total expenses</span>
                                </div>
                            </div>
                            <ul class="report-category-legend">
                                @foreach ($report['categories']->take(5) as $category)
                                    <li>
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
                        <div class="report-line-legend">
                            <span class="report-legend-item"><i class="report-legend-dot income-color"></i> Income</span>
                            <span class="report-legend-item"><i class="report-legend-dot expense-color"></i> Expenses</span>
                            <span class="report-legend-item"><i class="report-legend-dot net-color"></i> Net</span>
                        </div>
                    </div>
                    @if ($report['transactionCount'] === 0)
                        <div class="report-chart-empty">No monthly trend is available until transactions are recorded.</div>
                    @else
                        <svg class="report-trend-svg" viewBox="0 0 100 100" preserveAspectRatio="none"
                             role="img" aria-label="Monthly income, expenses, and net cash flow trend">
                            <line class="report-grid-line" x1="0" y1="10" x2="100" y2="10"></line>
                            <line class="report-grid-line" x1="0" y1="50" x2="100" y2="50"></line>
                            <line class="report-grid-line" x1="0" y1="90" x2="100" y2="90"></line>
                            <polyline class="report-trend-line income" points="{{ $report['chartPoints']['income'] }}"></polyline>
                            <polyline class="report-trend-line expenses" points="{{ $report['chartPoints']['expenses'] }}"></polyline>
                            <polyline class="report-trend-line net" points="{{ $report['chartPoints']['net'] }}"></polyline>
                        </svg>
                        <div class="report-trend-dates">
                            @foreach ($report['monthly'] as $month)
                                <span>{{ $month['label'] }}</span>
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
                                <div class="report-top-item">
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
                            <p>Your personal finance assistant</p>
                        </div>
                    </div>
                    <button class="report-ai-collapse" id="reportAiCollapse" type="button"
                            aria-expanded="true" aria-controls="reportAiBody" aria-label="Collapse assistant">
                        <i class="bi bi-chevron-up" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="report-ai-body" id="reportAiBody">
                    <div class="report-ai-welcome">
                        <strong>{{ $assistantGreeting }}, {{ $assistantFirstName }}! 👋</strong>
                        I’m SpendWise AI. Ask about your recorded finances for the selected date range.
                    </div>
                    <div class="report-ai-section-label">Try asking me:</div>
                    <div class="report-ai-suggestions">
                        @foreach ([
                            'How much did I spend this month?',
                            'Which category has the highest spending?',
                            'Am I staying within my budget?',
                            'Show me my spending trends.',
                            'How can I save more money?',
                        ] as $suggestion)
                            <button class="report-ai-suggestion" type="button">{{ $suggestion }}</button>
                        @endforeach
                    </div>
                    <div class="report-chat-log" id="reportChatLog" role="log" aria-live="polite">
                        <div class="report-chat-bubble">{{ $assistantGreeting }}, {{ $assistantFirstName }}! 👋 I’m glad you’re here. What would you like to explore about your spending today? I’ll use your saved records and let you know if I don’t have enough information.</div>
                    </div>
                    <form class="report-chat-form" id="reportChatForm">
                        <label class="visually-hidden" for="reportChatInput">Ask about your finances</label>
                        <input id="reportChatInput" type="text" maxlength="500" required
                               placeholder="Ask me anything about your finances…">
                        <button type="submit" aria-label="Send message"><i class="bi bi-send-fill" aria-hidden="true"></i></button>
                    </form>
                    <p class="report-ai-note">Exact totals come from your saved records. When configured, Gemini receives your question and aggregate totals, category names and totals, and monthly summaries; transaction descriptions and account identity are not sent. Budget checks aren’t available until budget records are supported.</p>
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
    const panel = document.getElementById('reportAiPanel');

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
        const loading = addMessage('Checking your saved report data…', 'report-chat-loading');
        form.dataset.loading = 'true';
        form.querySelector('button').disabled = true;
        input.disabled = true;

        try {
            const response = await fetch(@json(route('reports.assistant')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    question: value,
                    start_date: @json($startDate),
                    end_date: @json($endDate)
                })
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'The assistant could not answer right now. Please try again.');
            }

            loading.remove();
            addMessage(result.answer, 'assistant');
        } catch (error) {
            loading.remove();
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
            ask(button.textContent);
        });
    });

    collapseButton.addEventListener('click', function () {
        const collapsed = panel.classList.toggle('collapsed');
        collapseButton.setAttribute('aria-expanded', String(!collapsed));
        collapseButton.setAttribute('aria-label', collapsed ? 'Expand assistant' : 'Collapse assistant');
    });
});
</script>
@endpush
