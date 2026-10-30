<div class="modal fade" id="addTransactionModal"
     tabindex="-1" aria-labelledby="addTransactionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content sw-modal-content">
            <form action="{{ route('transactions.store') }}" method="POST">
                @csrf
                @if ($returnToDashboard ?? false)
                    <input type="hidden" name="return_to" value="dashboard">
                @endif

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="addTransactionLabel">Add transaction</h5>
                        <p class="sw-modal-subtitle">Record money coming in or going out.</p>
                    </div>
                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Please check your input.</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="sw-form-group">
                        <label for="transactionType">Transaction type</label>
                        <select name="type" id="transactionType" class="form-select" required>
                            <option value="expense" {{ old('type', 'expense') === 'expense' ? 'selected' : '' }}>Expense</option>
                            <option value="income" {{ old('type') === 'income' ? 'selected' : '' }}>Income</option>
                        </select>
                    </div>

                    <div class="sw-form-group">
                        <label for="transactionAmount">Amount (₱)</label>
                        <div class="sw-amount-input">
                            <span>₱</span>
                            <input type="number" name="amount" id="transactionAmount"
                                   class="form-control" min="0.01" step="0.01"
                                   value="{{ old('amount') }}" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="sw-form-group">
                        <label for="transactionCategory">Category</label>
                        <select name="category_id" id="transactionCategory" class="form-select" required>
                            <option value="">Select a category</option>
                            @foreach(($categories ?? collect()) as $category)
                                <option value="{{ $category->id }}"
                                    {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sw-form-group">
                        <label for="transactionDescription">Description</label>
                        <input type="text" name="description" id="transactionDescription"
                               class="form-control" value="{{ old('description') }}"
                               placeholder="e.g. Lunch, allowance, groceries"
                               maxlength="255" required>
                    </div>

                    <div class="sw-form-group">
                        <label for="transactionDate">Transaction date</label>
                        <input type="date" name="date" id="transactionDate" class="form-control"
                               value="{{ old('date', now()->format('Y-m-d')) }}"
                               max="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="sw-secondary-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="sw-primary-btn">
                        <i class="bi bi-check2"></i>
                        Save transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    .sw-modal-content {
        overflow: hidden;
        border: 1px solid #e9eeeb;
        border-radius: 15px;
        background: #fff;
        box-shadow: 0 18px 55px #142c211f;
    }

    .sw-modal-content .modal-header {
        padding: 20px 22px;
        border-bottom: 1px solid #e9eeeb;
    }

    .sw-modal-content .modal-title { font-size: 17px; font-weight: 750; }
    .sw-modal-subtitle { margin: 4px 0 0; color: #84918c; font-size: 11px; }
    .sw-modal-content .modal-body { padding: 21px 22px; }
    .sw-modal-content .modal-footer { gap: 8px; padding: 14px 22px; border-top: 1px solid #e9eeeb; }
    .sw-form-group { margin-bottom: 16px; }
    .sw-form-group:last-child { margin-bottom: 0; }
    .sw-form-group label { display: block; margin-bottom: 7px; color: #45544c; font-size: 11px; font-weight: 700; }

    .sw-form-group .form-control,
    .sw-form-group .form-select {
        min-height: 42px;
        border-color: #e1e8e3;
        border-radius: 8px;
        font-size: 12px;
        box-shadow: none;
    }

    .sw-form-group .form-control:focus,
    .sw-form-group .form-select:focus {
        border-color: #83b8a0;
        box-shadow: 0 0 0 3px #176b5010;
    }

    .sw-amount-input {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-left: 12px;
        border: 1px solid #e1e8e3;
        border-radius: 8px;
    }

    .sw-amount-input:focus-within { border-color: #83b8a0; box-shadow: 0 0 0 3px #176b5010; }
    .sw-amount-input > span { color: #176b50; font-size: 14px; font-weight: 750; }
    .sw-amount-input .form-control { border: 0; }

    .sw-modal-content .sw-primary-btn,
    .sw-modal-content .sw-secondary-btn {
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

    .sw-modal-content .sw-primary-btn {
        color: #fff;
        background: #176b50;
        border: 1px solid #176b50;
        box-shadow: 0 4px 10px #176b5018;
    }

    .sw-modal-content .sw-primary-btn:hover { color: #fff; background: #104d3c; border-color: #104d3c; transform: translateY(-1px); }
    .sw-modal-content .sw-secondary-btn { color: #4d5b55; background: #fff; border: 1px solid #e9eeeb; }
    .sw-modal-content .sw-secondary-btn:hover { background: #f5f8f6; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('addTransactionModal');
    if (!addModal) return;

    document.body.appendChild(addModal);

    @if ($errors->any())
        if (window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(addModal).show();
        }
    @endif
});
</script>
@endpush
