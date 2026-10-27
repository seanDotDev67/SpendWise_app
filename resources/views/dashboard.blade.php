@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
        .sw-transaction-description { min-width: 0; }

        /* Donut */
        .sw-donut { position: relative; width: 140px; height: 140px; border-radius: 50%; flex-shrink: 0; }
        .sw-donut-hole {
            position: absolute; inset: 26px; border-radius: 50%; background: var(--sw-card);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            box-shadow: inset 0 1px 4px rgba(0, 0, 0, .1);
        }
        .dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; border: 1px solid rgba(0, 0, 0, .05); }

        /* Progress bars */
        .sw-track { height: 5px; border-radius: 99px; background: var(--sw-track); }
        .sw-fill { height: 100%; border-radius: 99px; }

        /* Calendar */
        .sw-cal { display: grid; grid-template-columns: repeat(8, 1fr); row-gap: 8px; text-align: center; font-size: 9px; }
        .sw-cal .head { color: #E0707E; }
        .sw-cal .wk { background: #F6DADD; color: #D98E96; border-radius: 4px; padding: 0 4px; margin: 0 auto; }
        .sw-cal .day { width: 20px; height: 20px; margin: 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 4px; }
        .sw-cal .day.weekend { color: #E0707E; }
        .sw-cal .day.today { background: var(--sw-dark); color: #fff; }

        /* Buttons */
        .btn-sw-dark { background: var(--sw-dark); color: #fff; border-radius: 6px; padding: 12px; box-shadow: 0 2px 4px rgba(0, 0, 0, .25); }
        .btn-sw-dark:hover { background: #263d30; color: #fff; }
        .btn-sw-light { background: #EFEBDF; color: var(--sw-dark); border-radius: 6px; padding: 12px 24px; box-shadow: 0 2px 4px rgba(0, 0, 0, .15); }
        .btn-sw-light:hover { background: #e6e1d2; color: var(--sw-dark); }

        .sw-right { border-left: 2px solid #DDD5BF; }
        @media (max-width: 1199.98px) { .sw-right { border-left: 0; } }
        .badge-insight { background: #B8F0C0; color: #1f6b33; font-size: 8px; font-weight: 400; }

        .sw-page-title { font-size: clamp(1.75rem, 3vw, 2.5rem); }
        .sw-stat-value { font-size: clamp(1.05rem, 1.6vw, 1.45rem); letter-spacing: -.035em; overflow-wrap: anywhere; }
        .sw-donut { width: clamp(96px, 9.5vw, 124px); height: clamp(96px, 9.5vw, 124px); }
        .sw-donut-hole { inset: 19%; text-align: center; padding: 6px; }
        .sw-spending-breakdown { flex-wrap: wrap; }
        .sw-spending-legend { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 16px; }
        .sw-spending-legend li { min-width: 0; overflow-wrap: anywhere; }
        .sw-calendar-card { max-width: 420px; margin-inline: auto; }
        .sw-cal { font-size: 10px; }
        .sw-cal .day { width: 22px; height: 22px; }
        .sw-progress-item { min-width: 0; }
        .sw-progress-value { white-space: nowrap; }
        .sw-fill { transform: scaleX(0); transform-origin: left center; animation: sw-progress-in .9s cubic-bezier(.2, .7, .25, 1) forwards; }
        .sw-entrance { opacity: 0; animation: sw-enter .55s cubic-bezier(.2, .7, .25, 1) forwards; }
        .sw-entrance:nth-child(2) { animation-delay: .07s; }
        .sw-entrance:nth-child(3) { animation-delay: .14s; }
        .sw-entrance:nth-child(4) { animation-delay: .21s; }
        .sw-entrance:nth-child(5) { animation-delay: .28s; }
        .sw-donut { animation: sw-chart-in .8s .12s ease-out both; }
        .sw-stat:hover, .sw-card:hover { box-shadow: 0 10px 26px rgba(47, 74, 58, .1); }
        .btn-sw-dark:focus-visible, .btn-sw-light:focus-visible {
            outline: 3px solid rgba(47, 74, 58, .45);
            outline-offset: 3px;
        }
        .sw-empty-state { display: flex; min-height: 100%; flex-direction: column; justify-content: center; padding: 24px; }
        .sw-cashflow-track { height: 10px; border-radius: 999px; background: rgba(47, 74, 58, .12); overflow: hidden; }
        .sw-cashflow-fill { height: 100%; border-radius: inherit; background: var(--sw-sidebar); transform: scaleX(0); transform-origin: left center; animation: sw-cashflow-in .8s .15s ease-out forwards; }
        .sw-cashflow-fill.expenses { background: var(--sw-pink-dark); }
        .sw-transaction-row + .sw-transaction-row { border-top: 1px solid rgba(47, 74, 58, .1); }
        @keyframes sw-enter { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes sw-progress-in { to { transform: scaleX(var(--sw-progress, 1)); } }
        @keyframes sw-chart-in { from { opacity: 0; transform: scale(.88) rotate(-12deg); } to { opacity: 1; transform: scale(1) rotate(0); } }
        @keyframes sw-cashflow-in { to { transform: scaleX(1); } }

        @media (max-width: 991.98px) {
            .sw-right { padding: 0; }
            .sw-calendar-card { max-width: none; }
        }
        @media (max-width: 767.98px) {
            .sw-page-title { font-size: clamp(2rem, 8vw, 2.65rem); }
            .sw-spending-breakdown { gap: 18px !important; }
            .sw-calendar-card { margin-bottom: 20px !important; }
            .sw-empty-state { min-height: 150px; }
        }
        @media (max-width: 420px) {
            .sw-stat { padding: 12px; }
            .sw-stat-value { font-size: clamp(1.05rem, 5vw, 1.3rem); }
            .sw-progress-row { align-items: flex-start !important; gap: 8px !important; }
            .sw-progress-value { font-size: 10px !important; }
            .sw-spending-legend { column-gap: 8px; font-size: 10px !important; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; transition-duration: .01ms !important; }
            .sw-entrance { opacity: 1; }
            .sw-fill { transform: scaleX(var(--sw-progress, 1)); }
        }
</style>
@endpush

@php
    $greeting = $now->hour < 12 ? 'Good morning' : ($now->hour < 18 ? 'Good afternoon' : 'Good evening');

    // Calendar
    $first       = $now->copy()->startOfMonth();
    $daysInMonth = $now->daysInMonth;
    $offset      = $first->dayOfWeekIso - 1;
    $weeks       = (int) ceil(($offset + $daysInMonth) / 7);
    $calendarStart = $first->copy()->subDays($offset);

@endphp

@section('content')
<div id="dashboard-content" class="row g-3 mt-2">

                    {{-- ============ LEFT / CENTER ============ --}}
                    <div class="col-xl-9">
                        <h2 class="sw-page-title serif fw-semibold mb-1">{{ $greeting }}, {{ $firstName }}!</h2>
                        <p class="mb-3">Here's your financial overview for {{ $now->format('F Y') }}.</p>

                        {{-- Stat cards --}}
                        <div class="row g-3">
                            <div class="col-6 col-lg-3">
                                <div class="sw-stat beige sw-entrance">
                                    <div class="sw-stat-icon" style="background:var(--sw-sidebar)"><i class="bi bi-wallet2"></i></div>
                                    <div class="fw-medium mt-2">All-time balance</div>
                                    <div class="sw-stat-value">₱ {{ number_format($balance, 2) }}</div>
                                    <small style="font-size:10px">Income less expenses to date</small>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="sw-stat pink selected sw-entrance">
                                    <div class="sw-stat-icon" style="background:var(--sw-pink-dark)"><i class="bi bi-arrow-down"></i></div>
                                    <div class="fw-medium mt-2">Income this month</div>
                                    <div class="sw-stat-value">₱ {{ number_format($income, 2) }}</div>
                                    <small style="font-size:10px">{{ $now->format('F Y') }}</small>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="sw-stat pink sw-entrance">
                                    <div class="sw-stat-icon" style="background:var(--sw-pink-dark)"><i class="bi bi-arrow-up"></i></div>
                                    <div class="fw-medium mt-2">Expenses this month</div>
                                    <div class="sw-stat-value">₱ {{ number_format($expenses, 2) }}</div>
                                    <small style="font-size:10px">{{ $now->format('F Y') }}</small>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="sw-stat beige sw-entrance">
                                    <div class="sw-stat-icon" style="background:var(--sw-sidebar)"><i class="bi bi-bullseye"></i></div>
                                    <div class="fw-medium small mt-2">Income spent this month</div>
                                    <div class="sw-stat-value">{{ $incomeSpentPercentage }}%</div>
                                    <small style="font-size:10px">{{ $income > 0 ? 'Of recorded income' : 'No income recorded yet' }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- Monthly spending overview and category breakdown --}}
                        <div class="row g-3 mt-1">
                            <div class="col-lg-6">
                                <div class="sw-card h-100 sw-entrance">
                                    <h3 class="serif fw-bold fs-5 mb-0">Spending Overview</h3>
                                    <small class="text-muted" style="font-size:10px">This Month · {{ $now->format('F Y') }}</small>

                                    <div class="sw-spending-breakdown d-flex align-items-center gap-4 mt-3">
                                        <div class="sw-donut" style="background: {{ $donut }}">
                                            <div class="sw-donut-hole">
                                                <span class="fs-6 fw-semibold">₱{{ number_format($totalSpent, 2) }}</span>
                                                <span style="font-size:9px">This month</span>
                                            </div>
                                        </div>
                                        <ul class="sw-spending-legend list-unstyled mb-0" style="font-size:11px">
                                            @forelse ($spendingCategories as $category)
                                                <li class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="dot" style="background: {{ $category['color'] }}"></span>
                                                    <span>{{ $category['name'] }}</span>
                                                </li>
                                            @empty
                                                <li class="text-muted">No expenses recorded this month.</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="sw-card h-100 sw-entrance">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h3 class="serif fw-bold fs-5">Spending by category</h3>
                                        @if (Route::has('categories.index'))
                                            <a href="{{ route('categories.index') }}" class="text-dark text-decoration-none" style="font-size:9px">Categories</a>
                                        @endif
                                    </div>

                                    <div class="mt-2">
                                        @forelse ($spendingCategories as $category)
                                            <div class="sw-progress-item d-flex align-items-center gap-3 mb-2">
                                                <span class="dot" style="background: {{ $category['color'] }}; width:16px; height:16px"></span>
                                                <div class="flex-grow-1">
                                                    <div class="sw-progress-row d-flex justify-content-between gap-2" style="font-size:10px">
                                                        <span class="fw-semibold">{{ $category['name'] }}</span>
                                                        <span class="sw-progress-value">₱ {{ number_format($category['spent'], 2) }} <span class="text-muted">· {{ $category['percentage'] }}%</span></span>
                                                    </div>
                                                    <div class="sw-track mt-1">
                                                        <div class="sw-fill" style="--sw-progress: {{ $category['percentage'] / 100 }}; width: 100%; background: {{ $category['color'] }}"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="small text-muted mb-0">Your expense categories will appear here after you record spending.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Bottom row --}}
                        <div class="row g-3 mt-1">
                            <div class="col-lg-5">
                                <div class="sw-card-beige sw-empty-state h-100 sw-entrance">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <span class="sw-stat-icon" style="background:var(--sw-sidebar)"><i class="bi bi-cash-stack"></i></span>
                                        <div>
                                            <h3 class="serif fw-bold fs-5 mb-0">Monthly cash flow</h3>
                                            <small class="text-muted">{{ $now->format('F Y') }}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between small mb-2">
                                        <span>Income</span><strong>₱ {{ number_format($income, 2) }}</strong>
                                    </div>
                                    <div class="sw-cashflow-track mb-3" role="img" aria-label="Monthly income">
                                        <div class="sw-cashflow-fill" style="width:100%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between small mb-2">
                                        <span>Expenses</span><strong>₱ {{ number_format($expenses, 2) }}</strong>
                                    </div>
                                    <div class="sw-cashflow-track mb-3" role="img" aria-label="Monthly expenses relative to income">
                                        @php $cashflowExpensePct = min(100, max(0, $income > 0 ? $expenses / $income * 100 : 0)); @endphp
                                        <div class="sw-cashflow-fill expenses" style="width:{{ $cashflowExpensePct }}%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between small pt-2 border-top">
                                        <span>Net this month</span><strong>₱ {{ number_format($monthlyNet, 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="sw-card-beige p-3 sw-entrance">
                                    <div class="d-flex align-items-start gap-3">
                                        <i class="bi bi-stars fs-3"></i>
                                        <div class="flex-grow-1 lh-sm">
                                            <div class="fs-5">SpendWise AI</div>
                                            <small class="text-muted" style="font-size:9px">Your personal financial companion</small>
                                        </div>
                                        <span class="badge badge-insight">Based on your data</span>
                                    </div>
                                    <hr style="border-color:#DDD5BF">
                                    <p class="small mb-0">
                                        This month, you've recorded ₱{{ number_format($income, 2) }} in income and
                                        ₱{{ number_format($expenses, 2) }} in expenses. Your net cash flow is
                                        ₱{{ number_format($monthlyNet, 2) }}.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ RIGHT COLUMN ============ --}}
                    <aside class="col-xl-3 sw-right ps-xl-4">

                        {{-- Calendar --}}
                        <div class="sw-card sw-calendar-card p-3 mb-3 sw-entrance">
                            <div class="text-center text-white rounded py-1" style="background:var(--sw-dark); font-size:9px">{{ $now->format('F Y') }}</div>
                            <div class="sw-cal mt-2">
                                <span class="fw-medium">{{ $now->format('m') }}</span>
                                @foreach (['Mo','Tu','We','Th','Fr','Sa','Su'] as $d)
                                    <span class="head">{{ $d }}</span>
                                @endforeach

                                @for ($w = 0; $w < $weeks; $w++)
                                    <span class="wk">{{ $calendarStart->copy()->addWeeks($w)->isoWeek() }}</span>
                                    @for ($d = 0; $d < 7; $d++)
                                        @php $day = $w * 7 + $d - $offset + 1; @endphp
                                        @if ($day >= 1 && $day <= $daysInMonth)
                                            <span class="day {{ $day === $now->day ? 'today' : ($d >= 5 ? 'weekend' : '') }}"
                                                @if ($day === $now->day) aria-current="date" data-date="{{ $now->toDateString() }}" @endif>{{ $day }}</span>
                                        @else
                                            <span></span>
                                        @endif
                                    @endfor
                                @endfor
                            </div>
                        </div>

                        {{-- Quick actions --}}
                        <div class="sw-card mb-3 sw-entrance">
                            <h3 class="serif fw-semibold fs-5">Quick Actions</h3>
                            <div class="d-grid gap-2 mt-2">
                                <a href="{{ Route::has('transactions.create') ? route('transactions.create') : '#' }}"
                                   class="btn btn-sw-dark d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-plus-lg"></i> Add Transaction
                                </a>
                                <a href="{{ Route::has('budgets.index') ? route('budgets.index') : '#' }}"
                                   class="btn btn-sw-light d-flex align-items-center gap-3 text-start">
                                    <i class="bi bi-bullseye fs-5"></i> Set Budget
                                </a>
                                <a href="{{ Route::has('categories.index') ? route('categories.index') : '#' }}"
                                   class="btn btn-sw-light text-center">
                                    Categories
                                </a>
                            </div>
                        </div>

                        {{-- Recent transactions --}}
                        <div class="sw-card sw-entrance">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="serif fw-semibold fs-5 mb-0">Recent transactions</h3>
                                @if (Route::has('transactions.index'))
                                    <a href="{{ route('transactions.page') }}" class="text-dark text-decoration-none small">View all</a>
                                @endif
                            </div>
                            <div class="mt-2">
                                @forelse ($recentTransactions as $transaction)
                                    <div class="sw-transaction-row d-flex justify-content-between align-items-center gap-2 py-2">
                                        <div class="sw-transaction-description">
                                            <div class="small fw-medium text-truncate">{{ $transaction->description ?: ($transaction->category?->name ?? 'Transaction') }}</div>
                                            <div class="text-muted" style="font-size:10px">{{ $transaction->date?->format('M j, Y') }}</div>
                                        </div>
                                        <span class="small fw-semibold text-nowrap {{ $transaction->type === 'income' ? 'text-success' : '' }}">
                                            {{ $transaction->type === 'income' ? '+' : '−' }}₱{{ number_format((float) $transaction->amount, 2) }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="small text-muted mt-3 mb-0">Your transactions will appear here when you add them.</p>
                                @endforelse
                            </div>
                        </div>
                    </aside>
</div>
@endsection