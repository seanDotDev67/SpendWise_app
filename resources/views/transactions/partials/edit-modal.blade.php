<div class="modal fade" id="editTransactionModal"
     tabindex="-1" aria-labelledby="editTransactionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content sw-modal-content">
            <form id="editTransactionForm" action="" method="POST"
                  data-action-template="{{ route('transactions.update', ['transaction' => '__ID__']) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="_edit_id" id="editTransactionId" value="{{ old('_edit_id') }}">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="editTransactionLabel">Edit transaction</h5>
                        <p class="sw-modal-subtitle">Fix a wrong category, amount or detail.</p>
                    </div>
                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->any() && old('_edit_id'))
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
                        <label for="editTransactionType">Transaction type</label>
                        <select name="type" id="editTransactionType" class="form-select" required>
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>

                    <div class="sw-form-group">
                        <label for="editTransactionAmount">Amount (₱)</label>
                        <div class="sw-amount-input">
                            <span>₱</span>
                            <input type="number" name="amount" id="editTransactionAmount"
                                   class="form-control" min="0.01" step="0.01" required>
                        </div>
                    </div>

                    <div class="sw-form-group">
                        <label for="editTransactionCategory">Category</label>
                        <select name="category_id" id="editTransactionCategory" class="form-select" required>
                            @foreach(($categories ?? collect()) as $category)
                                <option value="{{ $category->id }}" data-type="{{ $category->type }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sw-form-group">
                        <label for="editTransactionDescription">Description</label>
                        <input type="text" name="description" id="editTransactionDescription"
                               class="form-control" maxlength="255" required>
                    </div>

                    <div class="sw-form-group">
                        <label for="editTransactionDate">Transaction date</label>
                        <input type="date" name="date" id="editTransactionDate" class="form-control"
                               max="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="sw-secondary-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="sw-primary-btn">
                        <i class="bi bi-check2"></i>
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('editTransactionModal');
    if (!modalEl) return;
    document.body.appendChild(modalEl);

    const form = document.getElementById('editTransactionForm');
    const typeSel = document.getElementById('editTransactionType');
    const catSel = document.getElementById('editTransactionCategory');
    const idInput = document.getElementById('editTransactionId');
    const baseOptions = Array.from(catSel.options).map(function (o) {
        return { value: o.value, text: o.textContent, type: o.dataset.type };
    });

    // Only categories matching the chosen type are offered; the current one is kept even if archived.
    function fillCategories(selected, extra) {
        const list = baseOptions.filter(function (o) { return o.type === typeSel.value; });
        if (extra && !baseOptions.some(function (o) { return o.value === extra.value; }) && extra.type === typeSel.value) {
            list.push(extra);
        }
        catSel.innerHTML = '';
        list.forEach(function (o) {
            const opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            catSel.appendChild(opt);
        });
        if (selected && list.some(function (o) { return o.value === String(selected); })) {
            catSel.value = String(selected);
        }
    }

    let extraOption = null;
    typeSel.addEventListener('change', function () { fillCategories(null, extraOption); });

    function openFor(d) {
        idInput.value = d.id;
        form.action = form.dataset.actionTemplate.replace('__ID__', d.id);
        typeSel.value = d.type;
        extraOption = { value: String(d.categoryId), text: d.categoryName, type: d.type };
        fillCategories(d.categoryId, extraOption);
        document.getElementById('editTransactionAmount').value = d.amount;
        document.getElementById('editTransactionDescription').value = d.description || '';
        document.getElementById('editTransactionDate').value = d.date;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.querySelectorAll('.sw-edit-transaction').forEach(function (btn) {
        btn.addEventListener('click', function () { openFor(btn.dataset); });
    });

    @if ($errors->any() && old('_edit_id'))
        (function () {
            const id = @json(old('_edit_id'));
            form.action = form.dataset.actionTemplate.replace('__ID__', id);
            typeSel.value = @json(old('type', 'expense'));
            extraOption = null;
            fillCategories(@json(old('category_id')), null);
            document.getElementById('editTransactionAmount').value = @json(old('amount'));
            document.getElementById('editTransactionDescription').value = @json(old('description'));
            document.getElementById('editTransactionDate').value = @json(old('date'));
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        })();
    @endif
});
</script>
@endpush
