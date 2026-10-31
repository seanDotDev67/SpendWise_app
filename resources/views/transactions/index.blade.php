@extends('layouts.app')
@section('content')
@php
    $allTransactions = $transactions ?? collect();

    $incomeTotal = $allTransactions->where('type', 'income')->sum('amount');
    $expenseTotal = $allTransactions->where('type', 'expense')->sum('amount');
    $netTotal = $incomeTotal - $expenseTotal;
    $transactionCount = $allTransactions->count();
@endphp

<div class="sw-page">

    {{-- Page heading --}}
    <div class="sw-page-heading">
        <div>
            <h1>Transactions</h1>
            <p>Track your money, understand your habits, and stay in control.</p>
        </div>

        <button class="sw-primary-btn" type="button"
                data-bs-toggle="modal" data-bs-target="#addTransactionModal">
            <i class="bi bi-plus-lg"></i>
            Add transaction
        </button>
    </div>

    {{-- Summary cards --}}
    <div class="sw-summary-grid">
        <div class="sw-summary-card">
            <div class="sw-summary-top">
                <span class="sw-summary-label">Total income</span>
                <span class="sw-summary-icon income-icon">
                    <i class="bi bi-arrow-down-left"></i>
                </span>
            </div>
            <div class="sw-summary-amount">
                ₱{{ number_format($incomeTotal, 2) }}
            </div>
            <div class="sw-summary-foot">
                <span class="sw-status-dot green-dot"></span>
                Money received
            </div>
        </div>

        <div class="sw-summary-card">
            <div class="sw-summary-top">
                <span class="sw-summary-label">Total expenses</span>
                <span class="sw-summary-icon expense-icon">
                    <i class="bi bi-arrow-up-right"></i>
                </span>
            </div>
            <div class="sw-summary-amount">
                ₱{{ number_format($expenseTotal, 2) }}
            </div>
            <div class="sw-summary-foot">
                <span class="sw-status-dot red-dot"></span>
                Money spent
            </div>
        </div>

        <div class="sw-summary-card">
            <div class="sw-summary-top">
                <span class="sw-summary-label">Net cash flow</span>
                <span class="sw-summary-icon balance-icon">
                    <i class="bi bi-wallet2"></i>
                </span>
            </div>
            <div class="sw-summary-amount {{ $netTotal < 0 ? 'negative-amount' : '' }}">
                {{ $netTotal < 0 ? '−' : '' }}₱{{ number_format(abs($netTotal), 2) }}
            </div>
            <div class="sw-summary-foot">
                <span class="sw-status-dot {{ $netTotal < 0 ? 'red-dot' : 'green-dot' }}"></span>
                Income minus expenses
            </div>
        </div>
    </div>

    {{-- Account overview --}}
    <div class="sw-overview-grid">

        <section class="sw-panel">
            <div class="sw-panel-heading">
                <div>
                    <h2>Your account</h2>
                    <p>Your personal finance overview</p>
                </div>
                <span class="sw-live-badge">
                    <span></span> Active
                </span>
            </div>

            {{-- ATM-style card --}}
            <div class="sw-atm-card">
                <div class="sw-card-pattern"></div>
                <div class="sw-card-glow"></div>

                <div class="sw-card-top">
                    <div class="sw-card-brand">
                        <div class="sw-brand-mark">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div>
                            <div class="sw-brand-name">SpendWise</div>
                            <div class="sw-brand-subtitle">PERSONAL FINANCE</div>
                        </div>
                    </div>

                    <div class="sw-card-type">DEBIT</div>
                </div>

                <div class="sw-card-chip-row">
                    <div class="sw-chip" aria-label="Decorative card chip">
                        <span></span><span></span><span></span>
                        <span></span><span></span><span></span>
                        <span></span><span></span><span></span>
                    </div>
                    <i class="bi bi-wifi sw-contactless"
                       aria-label="Contactless"></i>
                </div>

                <div class="sw-card-number" aria-label="Decorative card number">
                    <span>••••</span>
                    <span>••••</span>
                    <span>••••</span>
                    <span>••••</span>
                </div>

                <div class="sw-card-balance-label">AVAILABLE BALANCE</div>

                <div class="sw-card-balance-row">
                    <div class="sw-card-balance"
                         id="cardBalance"
                         data-balance="{{ number_format($netTotal, 2, '.', '') }}">
                        ₱{{ number_format($netTotal, 2) }}
                    </div>

                    <button type="button" class="sw-eye-btn"
                            id="toggleBalance"
                            aria-label="Hide balance"
                            title="Toggle balance visibility">
                        <i class="bi bi-eye" id="balanceEye"></i>
                    </button>
                </div>

                <div class="sw-card-bottom">
                    <div>
                        <span class="sw-card-caption">CARDHOLDER</span>
                        <div class="sw-card-holder">
                            {{ strtoupper(auth()->user()->name ?? 'YOUR NAME') }}
                        </div>
                    </div>

                    <div class="sw-card-network" aria-hidden="true">
                        <span></span><span></span>
                    </div>
                </div>
            </div>

            <div class="sw-account-footer">
                <div class="sw-account-footer-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <strong>Your finances, organized</strong>
                    <p>Keep your income and spending in one place.</p>
                </div>
            </div>
        </section>

        {{-- Statistics --}}
        <section class="sw-panel">
            <div class="sw-panel-heading">
                <div>
                    <h2>Account overview</h2>
                    <p>A quick look at your activity</p>
                </div>
                <div class="sw-overview-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>

            <div class="sw-overview-stat">
                <div class="sw-overview-stat-icon income-icon">
                    <i class="bi bi-plus-lg"></i>
                </div>
                <div class="sw-overview-stat-content">
                    <span>Total money received</span>
                    <strong class="sw-positive">
                        +₱{{ number_format($incomeTotal, 2) }}
                    </strong>
                </div>
            </div>

            <div class="sw-overview-stat">
                <div class="sw-overview-stat-icon expense-icon">
                    <i class="bi bi-dash-lg"></i>
                </div>
                <div class="sw-overview-stat-content">
                    <span>Total money spent</span>
                    <strong class="sw-negative">
                        −₱{{ number_format($expenseTotal, 2) }}
                    </strong>
                </div>
            </div>

            <div class="sw-overview-stat">
                <div class="sw-overview-stat-icon balance-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="sw-overview-stat-content">
                    <span>Recorded transactions</span>
                    <strong>{{ number_format($transactionCount) }}</strong>
                </div>
            </div>

            <div class="sw-insight">
                <div class="sw-insight-icon">
                    <i class="bi bi-lightbulb"></i>
                </div>
                <div>
                    <strong>Stay mindful of your spending</strong>
                    <p>Review your transactions regularly to understand where your money goes.</p>
                </div>
            </div>
        </section>
    </div>

    {{-- Transaction history --}}
    <section class="sw-panel sw-history-panel">
        <div class="sw-history-heading">
            <div>
                <h2>Transaction history</h2>
                <p>Review and manage your recorded activity.</p>
            </div>
            <span class="sw-count-badge">
                {{ $transactionCount }} records
            </span>
        </div>

        <div class="sw-filter-bar">
            <div class="sw-search-box">
                <i class="bi bi-search"></i>
                <input type="search" id="transactionSearch"
                       placeholder="Search transactions..."
                       aria-label="Search transactions">
            </div>

            <select id="typeFilter" class="sw-filter-select"
                    aria-label="Filter transaction type">
                <option value="all">All types</option>
                <option value="income">Income</option>
                <option value="expense">Expenses</option>
            </select>

            <select id="sortFilter" class="sw-filter-select"
                    aria-label="Sort transactions">
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="highest">Highest amount</option>
                <option value="lowest">Lowest amount</option>
            </select>
        </div>

        <div class="sw-transaction-list" id="transactionList">
            @forelse($allTransactions as $transaction)
                @php
                    $type = strtolower($transaction->type ?? 'expense');
                    $isIncome = $type === 'income';

                    $categoryName = $transaction->category->name
                        ?? $transaction->category_name
                        ?? 'Uncategorized';

                    $description = $transaction->description
                        ?? $transaction->title
                        ?? $categoryName;

                    $dateValue = $transaction->date
                        ?? $transaction->transaction_date
                        ?? $transaction->created_at;
                @endphp

                <article class="sw-transaction-row"
                         data-type="{{ $type }}"
                         data-amount="{{ (float) $transaction->amount }}"
                         data-date="{{ $dateValue ? \Carbon\Carbon::parse($dateValue)->timestamp : 0 }}"
                         data-search="{{ strtolower($description . ' ' . $categoryName . ' ' . $type) }}">

                    <div class="sw-transaction-icon {{ $isIncome ? 'income-icon' : 'expense-icon' }}">
                        <i class="bi {{ $isIncome ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i>
                    </div>

                    <div class="sw-transaction-details">
                        <div class="sw-transaction-title">{{ $description }}</div>
                        <div class="sw-transaction-meta">
                            <span>{{ $categoryName }}</span>
                            <span>•</span>
                            <span>
                                {{ $dateValue
                                    ? \Carbon\Carbon::parse($dateValue)->format('M d, Y')
                                    : 'Date unavailable' }}
                            </span>
                        </div>
                    </div>

                    <span class="sw-type-pill {{ $isIncome ? 'income-pill' : 'expense-pill' }}">
                        {{ $isIncome ? 'Income' : 'Expense' }}
                    </span>

                    <div class="sw-transaction-amount {{ $isIncome ? 'sw-positive' : 'sw-negative' }}">
                        {{ $isIncome ? '+' : '−' }}₱{{ number_format((float) $transaction->amount, 2) }}
                    </div>

                    <div class="sw-transaction-actions">
                        <button type="button" class="sw-icon-action"
                                title="View transaction"
                                aria-label="View transaction details"
                                data-bs-toggle="modal"
                                data-bs-target="#transactionDetails{{ $transaction->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </article>

                {{-- Transaction details --}}
                <div class="modal fade"
                     id="transactionDetails{{ $transaction->id }}"
                     tabindex="-1"
                     aria-labelledby="transactionDetailsLabel{{ $transaction->id }}"
                     aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content sw-modal-content">
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title"
                                        id="transactionDetailsLabel{{ $transaction->id }}">
                                        Transaction details
                                    </h5>
                                    <p class="sw-modal-subtitle">Review this record</p>
                                </div>
                                <button type="button" class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="sw-detail-amount {{ $isIncome ? 'sw-positive' : 'sw-negative' }}">
                                    {{ $isIncome ? '+' : '−' }}₱{{ number_format((float) $transaction->amount, 2) }}
                                </div>

                                <div class="sw-detail-list">
                                    <div>
                                        <span>Description</span>
                                        <strong>{{ $description }}</strong>
                                    </div>
                                    <div>
                                        <span>Category</span>
                                        <strong>{{ $categoryName }}</strong>
                                    </div>
                                    <div>
                                        <span>Type</span>
                                        <strong>{{ ucfirst($type) }}</strong>
                                    </div>
                                    <div>
                                        <span>Date</span>
                                        <strong>
                                            {{ $dateValue
                                                ? \Carbon\Carbon::parse($dateValue)->format('F d, Y')
                                                : 'Not provided' }}
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="sw-secondary-btn"
                                        data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="sw-empty-state" id="emptyState">
                    <div class="sw-empty-icon">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <h3>No transactions yet</h3>
                    <p>Your financial activity will appear here once you record your first transaction.</p>
                    <button class="sw-primary-btn" type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#addTransactionModal">
                        <i class="bi bi-plus-lg"></i>
                        Add your first transaction
                    </button>
                </div>
            @endforelse

            <div class="sw-no-results" id="noResults" hidden>
                <i class="bi bi-search"></i>
                <strong>No matching transactions</strong>
                <p>Try another search term or change your filters.</p>
            </div>
        </div>
    </section>
</div>

@include('transactions.partials.add-modal')

<style>
    :root {
        --sw-green: #176b50;
        --sw-green-dark: #104d3c;
        --sw-green-soft: #e8f5ee;
        --sw-red: #d65353;
        --sw-red-soft: #fff0ef;
        --sw-ink: #182522;
        --sw-muted: #84918c;
        --sw-border: #e9eeeb;
        --sw-surface: #fff;
    }

    /* Page uses the background inherited from layouts.app.
       Sizing follows the Categories page (--u scale). */
    .sw-page {
        --u: max(min(1vw, 1.547vh), 5px);
        --cat-green: #3b4a3c;
        --cat-ink: #47564a;
        color: var(--sw-ink);
        padding: calc(var(--u) * 1.7) 0 calc(var(--u) * 3.5) 0;
        background: transparent;
        min-height: 100%;
        font-family: 'Inter', sans-serif;
    }

    .sw-page-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: calc(var(--u) * 1.6);
    }

    .sw-status-dot,
    .sw-live-badge span {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }

    .green-dot,
    .sw-live-badge span {
        background: #28a879;
    }

    /* Title: same serif + size as Categories */
    .sw-page-heading h1 {
        margin: 0;
        font-family: 'Cormorant Garamond', serif;
        font-weight: 600;
        font-size: calc(var(--u) * 2.65);
        line-height: 1.05;
        letter-spacing: 0;
        color: var(--cat-green);
    }

    .sw-page-heading p {
        margin: calc(var(--u) * .4) 0 0;
        color: var(--cat-ink);
        font-size: calc(var(--u) * 1.01);
    }

    .sw-panel-heading p,
    .sw-history-heading p {
        margin: 6px 0 0;
        color: var(--sw-muted);
        font-size: 12px;
    }

    .sw-primary-btn,
    .sw-secondary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 15px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: .2s ease;
    }

    /* Primary button: same look as "Add Category" */
    .sw-primary-btn {
        height: calc(var(--u) * 2.65);
        padding: 0 calc(var(--u) * 1.56);
        gap: calc(var(--u) * 1.1);
        color: #fff;
        background: #a0af8d;
        border: none;
        border-radius: calc(var(--u) * .7);
        font-size: calc(var(--u) * 1.09);
        font-weight: 500;
        white-space: nowrap;
        box-shadow: 0 calc(var(--u) * .27) calc(var(--u) * .47) rgba(59, 74, 60, .28);
    }

    .sw-primary-btn i {
        font-size: calc(var(--u) * 1.4);
    }

    .sw-primary-btn:hover {
        color: #fff;
        background: #344D3D;
        transform: translateY(-.15rem);
    }

    .sw-primary-btn:active {
        transform: none;
        box-shadow: none;
    }

    .sw-secondary-btn {
        color: #4d5b55;
        background: #fff;
        border: 1px solid var(--sw-border);
    }

    .sw-secondary-btn:hover {
        background: #f5f8f6;
    }

    .sw-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 15px;
        margin-bottom: 18px;
    }

    .sw-summary-card,
    .sw-panel {
        min-width: 0;
        background: var(--sw-surface);
        border: 1px solid var(--sw-border);
        border-radius: 14px;
        box-shadow: 0 3px 12px #172b2110;
    }

    .sw-summary-card {
        padding: 17px 19px 15px;
    }

    .sw-summary-top,
    .sw-panel-heading,
    .sw-history-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .sw-summary-label {
        color: #697770;
        font-size: 12px;
    }

    .sw-summary-icon,
    .sw-overview-stat-icon,
    .sw-transaction-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 10px;
    }

    .sw-summary-icon {
        width: 34px;
        height: 34px;
        font-size: 16px;
    }

    .income-icon {
        color: #159568;
        background: #e8f7ef;
    }

    .expense-icon {
        color: #d65353;
        background: #fff0ef;
    }

    .balance-icon {
        color: #5873ca;
        background: #edf0ff;
    }

    .sw-summary-amount {
        margin: 13px 0 10px;
        font-size: clamp(20px, 2.1vw, 26px);
        font-weight: 750;
        letter-spacing: -.8px;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .sw-summary-foot {
        display: flex;
        align-items: center;
        gap: 7px;
        color: var(--sw-muted);
        font-size: 10px;
    }

    .red-dot {
        background: var(--sw-red);
    }

    .sw-overview-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr);
        gap: 18px;
        align-items: stretch;
        margin-bottom: 18px;
    }

    .sw-panel {
        padding: 20px;
    }

    .sw-panel-heading {
        margin-bottom: 17px;
    }

    /* Card headings: serif like Categories / Reports */
    .sw-panel-heading h2,
    .sw-history-heading h2 {
        margin: 0;
        font-family: 'Cormorant Garamond', serif;
        font-size: calc(var(--u) * 1.5);
        font-weight: 600;
        letter-spacing: 0;
        color: var(--cat-green);
    }

    .sw-panel-heading p,
    .sw-history-heading p {
        font-size: 11px;
    }

    .sw-live-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 9px;
        color: #16815b;
        background: #eaf7f0;
        border-radius: 30px;
        font-size: 10px;
        font-weight: 700;
    }

    /* Realistic ATM/debit-card-inspired design */
    .sw-atm-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        min-height: 260px;
        padding: 22px 24px;
        border: 1px solid #ffffff20;
        border-radius: 18px;
        color: #fff;
        background: linear-gradient(125deg, #102f27 0%, #145641 48%, #197456 100%);
        box-shadow: 0 12px 28px #103e2d30;
    }

    .sw-card-pattern {
        position: absolute;
        z-index: -2;
        inset: 0;
        opacity: .65;
        background:
            radial-gradient(ellipse at 100% 0%, #ffffff18 0, transparent 42%),
            repeating-linear-gradient(
                135deg,
                transparent 0,
                transparent 34px,
                #ffffff08 35px,
                #ffffff08 36px
            );
        pointer-events: none;
    }

    .sw-card-glow {
        position: absolute;
        z-index: -1;
        top: -100px;
        right: -70px;
        width: 230px;
        height: 230px;
        border: 1px solid #ffffff13;
        border-radius: 50%;
        box-shadow: 0 0 0 24px #ffffff04, 0 0 0 48px #ffffff03;
        pointer-events: none;
    }

    .sw-card-top,
    .sw-card-brand,
    .sw-card-chip-row,
    .sw-card-balance-row,
    .sw-card-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .sw-card-brand {
        justify-content: flex-start;
        gap: 10px;
    }

    .sw-brand-mark {
        display: grid;
        place-items: center;
        width: 39px;
        height: 39px;
        border: 1px solid #ffffff38;
        border-radius: 11px;
        background: #ffffff15;
        font-size: 19px;
    }

    .sw-brand-name {
        font-size: 16px;
        font-weight: 800;
        letter-spacing: -.4px;
    }

    .sw-brand-subtitle {
        margin-top: 3px;
        color: #c0dfd0;
        font-size: 8px;
        font-weight: 650;
        letter-spacing: 1.5px;
    }

    .sw-card-type {
        padding: 6px 10px;
        border: 1px solid #ffffff35;
        border-radius: 5px;
        color: #e2f0e9;
        font-size: 9px;
        font-weight: 750;
        letter-spacing: 1.4px;
    }

    .sw-card-chip-row {
        justify-content: flex-start;
        margin-top: 23px;
    }

    .sw-chip {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: repeat(3, 1fr);
        width: 43px;
        height: 34px;
        overflow: hidden;
        border: 1px solid #d5bd79;
        border-radius: 6px;
        background: linear-gradient(135deg, #f5e6b2, #b99a50 55%, #e9d38e);
    }

    .sw-chip span {
        border-right: 1px solid #8e783f;
        border-bottom: 1px solid #8e783f;
    }

    .sw-contactless {
        margin-left: 1px;
        color: #e4eee8;
        font-size: 23px;
        transform: rotate(90deg);
    }

    .sw-card-number {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 18px;
        color: #f3f7f4;
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 2px;
    }

    .sw-card-balance-label {
        margin-top: 17px;
        color: #c0dfd0;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 1.5px;
    }

    .sw-card-balance-row {
        justify-content: flex-start;
        margin-top: 5px;
    }

    .sw-card-balance {
        font-size: clamp(24px, 2.5vw, 31px);
        font-weight: 750;
        letter-spacing: -.8px;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .sw-eye-btn {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 30px;
        height: 30px;
        border: 1px solid #ffffff35;
        border-radius: 8px;
        color: #fff;
        background: #ffffff12;
        cursor: pointer;
    }

    .sw-eye-btn:hover {
        background: #ffffff25;
    }

    .sw-card-bottom {
        margin-top: 18px;
    }

    .sw-card-caption {
        display: block;
        color: #c0dfd0;
        font-size: 8px;
        font-weight: 650;
        letter-spacing: 1.3px;
    }

    .sw-card-holder {
        margin-top: 5px;
        font-size: 10px;
        font-weight: 750;
        letter-spacing: .8px;
        overflow-wrap: anywhere;
    }

    .sw-card-network {
        display: flex;
        align-items: center;
    }

    .sw-card-network span {
        width: 25px;
        height: 25px;
        border-radius: 50%;
        background: #e5a75fbd;
    }

    .sw-card-network span + span {
        margin-left: -10px;
        background: #f3d2a2a8;
    }

    .sw-account-footer {
        display: flex;
        align-items: center;
        gap: 11px;
        margin-top: 15px;
        padding: 12px 13px;
        border: 1px solid var(--sw-border);
        border-radius: 10px;
        background: #fbfcfb;
    }

    .sw-account-footer-icon {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        flex-shrink: 0;
        border-radius: 9px;
        color: var(--sw-green);
        background: var(--sw-green-soft);
        font-size: 16px;
    }

    .sw-account-footer strong,
    .sw-insight strong {
        display: block;
        font-size: 11px;
    }

    .sw-account-footer p {
        margin: 3px 0 0;
        color: var(--sw-muted);
        font-size: 10px;
        line-height: 1.5;
    }

    .sw-overview-icon {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        color: var(--sw-green);
        background: var(--sw-green-soft);
    }

    .sw-overview-stat {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f3f1;
    }

    .sw-overview-stat-icon {
        width: 37px;
        height: 37px;
        font-size: 15px;
    }

    .sw-overview-stat-content {
        display: flex;
        flex: 1;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        min-width: 0;
    }

    .sw-overview-stat-content span {
        color: #697770;
        font-size: 11px;
    }

    .sw-overview-stat-content strong {
        font-size: 12px;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .sw-positive {
        color: #16815b !important;
    }

    .sw-negative,
    .negative-amount {
        color: var(--sw-red) !important;
    }

    .sw-insight {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-top: 15px;
        padding: 12px;
        border: 1px solid #e1eee6;
        border-radius: 10px;
        background: #f3faf5;
    }

    .sw-insight-icon {
        color: #bc842c;
        font-size: 16px;
    }

    .sw-insight p {
        margin: 4px 0 0;
        color: #6d7c73;
        font-size: 10px;
        line-height: 1.6;
    }

    /* History and filters */
    .sw-history-panel {
        padding: 21px 22px;
    }

    .sw-history-heading {
        margin-bottom: 18px;
    }

    .sw-count-badge {
        padding: 6px 10px;
        border: 1px solid var(--sw-border);
        border-radius: 7px;
        color: #66736c;
        background: #fafcfb;
        font-size: 10px;
        font-weight: 650;
        white-space: nowrap;
    }

    .sw-filter-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 12px;
    }

    .sw-search-box {
        display: flex;
        flex: 1;
        align-items: center;
        gap: 9px;
        min-width: 100px;
        height: 39px;
        padding: 0 11px;
        border: 1px solid var(--sw-border);
        border-radius: 8px;
        color: #8a9690;
        background: #fff;
    }

    .sw-search-box:focus-within {
        border-color: #83b8a0;
        box-shadow: 0 0 0 3px #176b5010;
    }

    .sw-search-box input {
        width: 100%;
        min-width: 0;
        height: 100%;
        border: 0;
        outline: 0;
        color: var(--sw-ink);
        background: transparent;
        font-size: 11px;
    }

    .sw-filter-select {
        min-width: 125px;
        max-width: 160px;
        padding: 0 10px;
        border: 1px solid var(--sw-border);
        border-radius: 8px;
        color: #59675f;
        background: #fff;
        font-size: 11px;
        outline: none;
    }

    .sw-transaction-row {
        display: flex;
        align-items: center;
        gap: 13px;
        min-height: 70px;
        padding: 12px 3px;
        border-bottom: 1px solid #edf1ee;
        transition: background .15s ease;
    }

    .sw-transaction-row:hover {
        background: #fbfdfb;
    }

    .sw-transaction-row[hidden],
    .sw-no-results[hidden] {
        display: none !important;
    }

    .sw-transaction-icon {
        width: 40px;
        height: 40px;
        font-size: 17px;
    }

    .sw-transaction-details {
        flex: 1;
        min-width: 0;
    }

    .sw-transaction-title {
        font-size: 12px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .sw-transaction-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin-top: 5px;
        color: var(--sw-muted);
        font-size: 10px;
    }

    .sw-type-pill {
        padding: 5px 8px;
        border-radius: 6px;
        font-size: 9px;
        font-weight: 700;
    }

    .income-pill {
        color: #16815b;
        background: #eaf7f0;
    }

    .expense-pill {
        color: #c64f4f;
        background: #fff0ef;
    }

    .sw-transaction-amount {
        min-width: 110px;
        text-align: right;
        font-size: 12px;
        font-weight: 750;
        white-space: nowrap;
    }

    .sw-icon-action {
        display: grid;
        place-items: center;
        width: 31px;
        height: 31px;
        border: 1px solid var(--sw-border);
        border-radius: 8px;
        color: #64736a;
        background: #fff;
        cursor: pointer;
    }

    .sw-icon-action:hover {
        color: var(--sw-green);
        border-color: #a9cdbc;
        background: #f3faf5;
    }

    .sw-empty-state,
    .sw-no-results {
        padding: 42px 15px;
        text-align: center;
    }

    .sw-empty-icon {
        display: grid;
        place-items: center;
        width: 54px;
        height: 54px;
        margin: 0 auto 14px;
        border-radius: 15px;
        color: var(--sw-green);
        background: var(--sw-green-soft);
        font-size: 24px;
    }

    .sw-empty-state h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 750;
    }

    .sw-empty-state p,
    .sw-no-results p {
        max-width: 340px;
        margin: 8px auto 17px;
        color: var(--sw-muted);
        font-size: 11px;
        line-height: 1.7;
    }

    .sw-no-results {
        color: #66736c;
    }

    .sw-no-results > i {
        display: block;
        margin-bottom: 10px;
        font-size: 24px;
    }

    .sw-no-results strong {
        font-size: 12px;
    }

    .sw-detail-amount {
        margin-bottom: 22px;
        font-size: 30px;
        font-weight: 800;
        text-align: center;
    }

    .sw-detail-list > div {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        padding: 13px 0;
        border-bottom: 1px solid #edf1ee;
        font-size: 12px;
    }

    .sw-detail-list > div:last-child {
        border-bottom: 0;
    }

    .sw-detail-list span {
        color: var(--sw-muted);
    }

    .sw-detail-list strong {
        text-align: right;
        overflow-wrap: anywhere;
    }

    @media (min-width: 1500px) {
        .sw-summary-card {
            padding: 20px 22px;
        }
    }

    @media (max-width: 1000px) {
        .sw-page {
            padding: 22px 0 30px;
        }

        .sw-overview-grid {
            gap: 13px;
        }

        .sw-panel {
            padding: 16px;
        }

        .sw-atm-card {
            padding: 18px;
        }

        .sw-transaction-amount {
            min-width: 90px;
        }
    }

    @media (max-width: 720px) {
        .sw-summary-grid,
        .sw-overview-grid {
            grid-template-columns: 1fr;
        }

        .sw-summary-grid {
            gap: 10px;
        }

        .sw-summary-card {
            padding: 14px 16px;
        }

        .sw-summary-amount {
            margin: 9px 0;
            font-size: 23px;
        }

        .sw-page-heading {
            align-items: flex-start;
        }

        .sw-page-heading h1 {
            font-size: 30px;
        }

        .sw-page-heading p {
            font-size: 13px;
        }

        .sw-page-heading .sw-primary-btn {
            flex-shrink: 0;
            height: 38px;
            padding: 0 16px;
            font-size: 13px;
        }

        .sw-filter-bar {
            flex-wrap: wrap;
        }

        .sw-search-box {
            flex-basis: 100%;
            height: 42px;
        }

        .sw-filter-select {
            flex: 1;
            max-width: none;
            min-height: 38px;
        }

        .sw-history-panel {
            padding: 16px 13px;
        }

        .sw-transaction-row {
            flex-wrap: wrap;
            gap: 10px;
            padding: 14px 2px;
        }

        .sw-transaction-details {
            flex-basis: calc(100% - 55px);
        }

        .sw-type-pill {
            margin-left: 50px;
        }

        .sw-transaction-amount {
            flex: 1;
            min-width: 0;
            text-align: right;
        }

        .sw-transaction-actions {
            display: none;
        }

        .sw-card-balance {
            font-size: 27px;
        }
    }

    @media (max-width: 420px) {
        .sw-page {
            padding: 17px 0 25px;
        }

        .sw-page-heading {
            flex-direction: column;
            align-items: stretch;
        }

        .sw-page-heading .sw-primary-btn {
            align-self: flex-start;
        }

        .sw-atm-card {
            padding: 18px;
        }

        .sw-card-number {
            gap: 8px;
            font-size: 13px;
            letter-spacing: 1.5px;
        }

        .sw-history-heading {
            align-items: flex-start;
        }

        .sw-count-badge {
            font-size: 9px;
        }
    }

    /* ===== Bigger content (same layout, readable size like Categories) ===== */
    .sw-summary-grid,
    .sw-overview-grid,
    .sw-history-panel {
        zoom: 1.3;
    }

    @media (max-width: 1000px) {
        .sw-summary-grid,
        .sw-overview-grid,
        .sw-history-panel {
            zoom: 1.12;
        }
    }

    @media (max-width: 720px) {
        .sw-summary-grid,
        .sw-overview-grid,
        .sw-history-panel {
            zoom: 1;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Keep Bootstrap modals above the backdrop by avoiding the animated page stacking context.
    document.querySelectorAll('.modal').forEach(modal => document.body.appendChild(modal));

    // Toggle balance visibility.
    const balanceElement = document.getElementById('cardBalance');
    const toggleBalance = document.getElementById('toggleBalance');
    const balanceEye = document.getElementById('balanceEye');
    let balanceHidden = false;

    if (balanceElement && toggleBalance && balanceEye) {
        const actualBalance = Number(balanceElement.dataset.balance || 0);

        const formattedBalance = new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2
        }).format(actualBalance);

        toggleBalance.addEventListener('click', function () {
            balanceHidden = !balanceHidden;

            balanceElement.textContent = balanceHidden
                ? '₱ ••••••'
                : formattedBalance;

            balanceEye.className = balanceHidden
                ? 'bi bi-eye-slash'
                : 'bi bi-eye';

            toggleBalance.setAttribute(
                'aria-label',
                balanceHidden ? 'Show balance' : 'Hide balance'
            );
        });
    }

    // Search, filter, and sort transactions.
    const searchInput = document.getElementById('transactionSearch');
    const typeFilter = document.getElementById('typeFilter');
    const sortFilter = document.getElementById('sortFilter');
    const transactionList = document.getElementById('transactionList');
    const noResults = document.getElementById('noResults');

    if (transactionList) {
        function updateTransactions() {
            const query = (searchInput?.value || '').trim().toLowerCase();
            const selectedType = typeFilter?.value || 'all';
            const selectedSort = sortFilter?.value || 'newest';

            const rows = Array.from(
                transactionList.querySelectorAll('.sw-transaction-row')
            );

            rows.forEach(function (row) {
                const matchesSearch = (row.dataset.search || '').includes(query);
                const matchesType = selectedType === 'all'
                    || row.dataset.type === selectedType;

                row.hidden = !(matchesSearch && matchesType);
            });

            const visibleRows = rows.filter(row => !row.hidden);

            visibleRows.sort(function (a, b) {
                const amountA = Number(a.dataset.amount || 0);
                const amountB = Number(b.dataset.amount || 0);
                const dateA = Number(a.dataset.date || 0);
                const dateB = Number(b.dataset.date || 0);

                if (selectedSort === 'oldest') return dateA - dateB;
                if (selectedSort === 'highest') return amountB - amountA;
                if (selectedSort === 'lowest') return amountA - amountB;

                return dateB - dateA;
            });

            // Move only transaction rows; leave their modals in place.
            visibleRows.forEach(row => transactionList.appendChild(row));

            if (noResults) {
                noResults.hidden = rows.length === 0 || visibleRows.length > 0;
            }
        }

        searchInput?.addEventListener('input', updateTransactions);
        typeFilter?.addEventListener('change', updateTransactions);
        sortFilter?.addEventListener('change', updateTransactions);

        const initialQuery = new URLSearchParams(window.location.search).get('q');
        if (initialQuery && searchInput) searchInput.value = initialQuery;

        updateTransactions();
    }

});
</script>
@endsection