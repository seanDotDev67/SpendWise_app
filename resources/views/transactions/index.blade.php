@extends('layouts.app')

@section('title', 'Transactions')

@push('styles')
<style>
    /* Transactions-only styles */
    .sw-filter .form-control, .sw-filter .form-select {
        border: 1px solid #DDD5BF; border-radius: 8px; background: #FAFAF8;
        font-size: 13px; padding: 8px 12px; color: var(--sw-dark);
    }
    .sw-filter .form-control:focus, .sw-filter .form-select:focus {
        border-color: var(--sw-sidebar); box-shadow: 0 0 0 3px rgba(127, 148, 104, .25);
    }
    .sw-table { --bs-table-bg: transparent; margin-bottom: 0; }
    .sw-table thead th {
        background: var(--sw-card-beige); color: var(--sw-dark);
        font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
        border-bottom: 0; padding: 12px 16px; white-space: nowrap;
    }
    .sw-table tbody td { padding: 12px 16px; border-color: rgba(47, 74, 58, .08); font-size: 14px; }
    .sw-table tbody tr { transition: background-color .15s ease; }
    .sw-table tbody tr:hover { background: rgba(127, 148, 104, .08); }
    .sw-badge { font-size: 10px; font-weight: 600; padding: 4px 10px; border-radius: 999px; }
    .sw-badge-income { background: #B8F0C0; color: #1f6b33; }
    .sw-badge-expense { background: var(--sw-pink); color: #C2485A; }
    .sw-action-btn {
        width: 32px; height: 32px; border: 0; border-radius: 8px; background: transparent;
        color: var(--sw-dark); display: inline-flex; align-items: center; justify-content: center;
        text-decoration: none; transition: background-color .2s ease;
    }
    .sw-action-btn:hover { background: rgba(47, 74, 58, .1); color: var(--sw-dark); }
    .sw-action-btn.danger:hover { background: var(--sw-pink); color: #C2485A; }
</style>
@endpush

@section('content')
    {{-- Page header --}}
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mt-4 mb-3">
        <div>
            <h1 class="serif fw-semibold mb-1" style="font-size:clamp(2rem, 3vw, 2.65rem)">Transactions</h1>
            <p class="text-muted small mb-0">
                @if ($transactions->total())
                    Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions
                @else
                    Track your income and expenses
                @endif
            </p>
        </div>
        @if (Route::has('transactions.create'))
            <a href="{{ route('transactions.create') }}" class="btn btn-sw-dark d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Add Transaction
            </a>
        @endif
    </div>

    {{-- Summary (respects active filters) --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-4">
            <div class="sw-stat beige">
                <div class="sw-stat-icon" style="background:var(--sw-sidebar)"><i class="bi bi-arrow-down"></i></div>
                <div class="fw-medium mt-2">Income</div>
                <div class="sw-stat-value">₱ {{ number_format($totals['income'], 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="sw-stat pink">
                <div class="sw-stat-icon" style="background:var(--sw-pink-dark)"><i class="bi bi-arrow-up"></i></div>
                <div class="fw-medium mt-2">Expenses</div>
                <div class="sw-stat-value">₱ {{ number_format($totals['expenses'], 2) }}</div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="sw-stat beige">
                <div class="sw-stat-icon" style="background:var(--sw-dark)"><i class="bi bi-wallet2"></i></div>
                <div class="fw-medium mt-2">Net</div>
                <div class="sw-stat-value {{ $totals['net'] < 0 ? 'text-danger' : '' }}">₱ {{ number_format($totals['net'], 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="sw-card p-3 mb-3">
        <form method="GET" action="{{ route('transactions.page') }}" class="sw-filter">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="sw-search d-flex align-items-center gap-3 w-100" style="max-width:none">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search description..." aria-label="Search transactions">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select name="type" class="form-select" aria-label="Filter by type">
                        <option value="">All types</option>
                        <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="category_id" class="form-select" aria-label="Filter by category">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="month" name="month" value="{{ request('month') }}" class="form-control" aria-label="Filter by month">
                </div>
                <div class="col-6 col-md-1 d-grid">
                    <button type="submit" class="btn btn-sw-dark py-2">Apply</button>
                </div>
                <div class="col-12">
                    @if (request()->hasAny(['q', 'type', 'category_id', 'month']))
                        <a href="{{ route('transactions.page') }}" class="small text-decoration-none">
                            <i class="bi bi-x-circle"></i> Clear filters
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="sw-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table sw-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $transaction->description ?: ($transaction->category?->name ?? 'Transaction') }}</div>
                                @if ($transaction->note)
                                    <div class="text-muted" style="font-size:10px">{{ \Illuminate\Support\Str::limit($transaction->note, 60) }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($transaction->category)
                                    <span class="dot" style="background: {{ $transaction->category->color ?? '#B9B5A8' }}"></span>
                                    <span class="small">{{ $transaction->category->name }}</span>
                                @else
                                    <span class="text-muted small">Uncategorized</span>
                                @endif
                            </td>
                            <td class="small text-muted text-nowrap">{{ $transaction->date?->format('M j, Y') }}</td>
                            <td>
                                <span class="sw-badge {{ $transaction->type === 'income' ? 'sw-badge-income' : 'sw-badge-expense' }}">
                                    {{ ucfirst($transaction->type) }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold text-nowrap {{ $transaction->type === 'income' ? 'text-success' : '' }}">
                                {{ $transaction->type === 'income' ? '+' : '−' }}₱{{ number_format((float) $transaction->amount, 2) }}
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('transactions.edit', $transaction) }}" class="sw-action-btn" aria-label="Edit transaction">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this transaction?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sw-action-btn danger" aria-label="Delete transaction">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-receipt-cutoff fs-1 text-muted d-block mb-2"></i>
                                <p class="fw-medium mb-1">No transactions found</p>
                                <p class="text-muted small mb-3">
                                    {{ request()->hasAny(['q', 'type', 'category_id', 'month'])
                                        ? 'Try adjusting or clearing your filters.'
                                        : "When you record income or expenses, they'll appear here." }}
                                </p>
                                <a href="{{ route('transactions.create') }}" class="btn btn-sw-dark d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-plus-lg"></i> Add Transaction
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $transactions->links() }}
    </div>
@endsection